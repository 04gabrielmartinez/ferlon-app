<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Produccion
{
    /** @return array<int, array<string, mixed>> */
    public static function listarRegistros(): array
    {
        $sql = "SELECT p.id,
                       p.codigo_produccion,
                       p.fecha,
                       p.cantidad,
                       p.unidad,
                       a.codigo AS producto_codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS producto_descripcion,
                       COALESCE(NULLIF(TRIM(e.nombre), ''), u.nombre, u.username, '') AS empleado_nombre
                FROM producciones p
                INNER JOIN articulos a ON a.id = p.producto_articulo_id
                LEFT JOIN users u ON u.id = p.created_by
                LEFT JOIN empleados e ON e.id = u.empleado_id
                ORDER BY p.id DESC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare("SELECT p.*,
                                                a.codigo AS producto_codigo,
                                                COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS producto_descripcion,
                                                a.unidad_base_id AS producto_unidad_base,
                                                COALESCE(a.maneja_inventario,1) AS producto_maneja_inventario,
                                                COALESCE(NULLIF(TRIM(e.nombre), ''), u.nombre, u.username, '') AS empleado_nombre
                                         FROM producciones p
                                         INNER JOIN articulos a ON a.id = p.producto_articulo_id
                                         LEFT JOIN users u ON u.id = p.created_by
                                         LEFT JOIN empleados e ON e.id = u.empleado_id
                                         WHERE p.id = :id
                                         LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $det = Db::conexion()->prepare("SELECT d.id,
                                               d.insumo_articulo_id,
                                               d.cantidad,
                                               d.unidad,
                                               d.cantidad_kg,
                                               a.codigo AS insumo_codigo,
                                               COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS insumo_descripcion,
                                               a.unidad_base_id AS insumo_unidad_base
                                        FROM produccion_detalles d
                                        INNER JOIN articulos a ON a.id = d.insumo_articulo_id
                                        WHERE d.produccion_id = :id
                                        ORDER BY d.id ASC");
        $det->execute(['id' => $id]);
        $row['detalles'] = $det->fetchAll() ?: [];

        return $row;
    }

    public static function obtenerRecetaParaProduccion(int $productoId): ?array
    {
        if ($productoId <= 0) {
            return null;
        }
        $receta = RecetaBase::buscarPorProductoArticuloId($productoId);
        if (!$receta) {
            return null;
        }
        $stmtProd = Db::conexion()->prepare("SELECT id, codigo, COALESCE(NULLIF(TRIM(descripcion), ''), nombre) AS descripcion, unidad_base_id, COALESCE(maneja_inventario,1) AS maneja_inventario
                                             FROM articulos
                                             WHERE id = :id
                                             LIMIT 1");
        $stmtProd->execute(['id' => $productoId]);
        $producto = $stmtProd->fetch();
        if (!$producto) {
            return null;
        }
        $receta['producto'] = $producto;
        return $receta;
    }

    /**
     * @param array<string, mixed> $record
     * @return array{ id: int, codigo_produccion: string }
     */
    public static function guardarProduccion(array $record): array
    {
        $productoId = (int) ($record['producto_articulo_id'] ?? 0);
        if ($productoId <= 0) {
            throw new RuntimeException('Selecciona un producto de receta base.');
        }
        $cantidadKg = self::decimalPositivo($record['cantidad'] ?? null, 'La cantidad a producir debe ser mayor que cero.');
        $fecha = (string) ($record['fecha'] ?? date('Y-m-d'));

        $receta = self::obtenerRecetaParaProduccion($productoId);
        if (!$receta) {
            throw new RuntimeException('El producto no tiene receta base configurada.');
        }
        $recetaId = (int) ($receta['id'] ?? 0);
        if ($recetaId <= 0) {
            throw new RuntimeException('Receta base no valida.');
        }
        $rendimiento = (float) ($receta['rendimiento'] ?? 0);
        if ($rendimiento <= 0) {
            throw new RuntimeException('La receta base no tiene rendimiento valido.');
        }
        $unidadRend = strtolower((string) ($receta['unidad_rendimiento'] ?? 'u'));
        if ($unidadRend === 'u') {
            throw new RuntimeException('La produccion debe registrarse en KG. La receta base esta en unidades.');
        }

        $producto = (array) ($receta['producto'] ?? []);
        $unidadProducto = strtolower((string) ($producto['unidad_base_id'] ?? 'u'));
        $productoManejaInv = (int) ($producto['maneja_inventario'] ?? 1) === 1;
        if (!self::unidadCompatible($unidadProducto, $unidadRend)) {
            throw new RuntimeException('La unidad del producto no es compatible con la receta base.');
        }
        if ($unidadProducto === 'u') {
            throw new RuntimeException('El producto a producir debe tener unidad base en peso (kg/g/lb/oz).');
        }

        $detalles = is_array($receta['detalles'] ?? null) ? $receta['detalles'] : [];
        if ($detalles === []) {
            throw new RuntimeException('La receta base no tiene insumos configurados.');
        }

        $cantidadEnUnidadReceta = self::convertirDesdeKg($cantidadKg, $unidadRend);
        if ($cantidadEnUnidadReceta === null) {
            throw new RuntimeException('No se pudo convertir la cantidad a la unidad de la receta base.');
        }
        $factor = $cantidadEnUnidadReceta / $rendimiento;
        if ($factor <= 0) {
            throw new RuntimeException('No se pudo calcular el factor de produccion.');
        }

        $insumoIds = [];
        foreach ($detalles as $d) {
            $insumoIds[] = (int) ($d['insumo_articulo_id'] ?? 0);
        }
        $insumoIds = array_values(array_filter($insumoIds, static fn ($v): bool => $v > 0));
        if ($insumoIds === []) {
            throw new RuntimeException('La receta base no tiene insumos validos.');
        }
        $idsParam = implode(',', array_fill(0, count($insumoIds), '?'));
        $stmtInsumos = Db::conexion()->prepare("SELECT id, unidad_base_id, COALESCE(maneja_inventario,1) AS maneja_inventario,
                                                       COALESCE(stock_actual,0) AS stock_actual,
                                                       COALESCE(stock_actual_kg,0) AS stock_actual_kg
                                                FROM articulos
                                                WHERE id IN ($idsParam)");
        $stmtInsumos->execute($insumoIds);
        $insumos = [];
        foreach (($stmtInsumos->fetchAll() ?: []) as $row) {
            $insumos[(int) ($row['id'] ?? 0)] = $row;
        }

        $consumos = [];
        foreach ($detalles as $d) {
            $insumoId = (int) ($d['insumo_articulo_id'] ?? 0);
            if ($insumoId <= 0) {
                continue;
            }
            $unidad = strtolower((string) ($d['unidad'] ?? 'u'));
            $cantidadBase = (float) ($d['cantidad'] ?? 0);
            $requerido = $cantidadBase * $factor;
            if ($requerido <= 0) {
                throw new RuntimeException('La cantidad requerida de un insumo es invalida.');
            }
            $insumoInfo = $insumos[$insumoId] ?? null;
            if (!$insumoInfo) {
                throw new RuntimeException('Insumo no encontrado para la receta base.');
            }
            $unidadBase = strtolower((string) ($insumoInfo['unidad_base_id'] ?? 'u'));
            if (!self::unidadCompatible($unidadBase, $unidad)) {
                $codigo = trim((string) ($d['insumo_codigo'] ?? ''));
                $desc = trim((string) ($d['insumo_descripcion'] ?? ''));
                $label = $codigo !== '' ? $codigo : ($desc !== '' ? $desc : 'insumo');
                throw new RuntimeException('Unidad incompatible en insumo ' . $label . '.');
            }
            $manejaInv = (int) ($insumoInfo['maneja_inventario'] ?? 1) === 1;

            $requeridoKg = null;
            if ($unidadBase !== 'u') {
                $requeridoKg = self::convertirAKg($requerido, $unidad);
                if ($requeridoKg === null) {
                    throw new RuntimeException('No se pudo convertir a KG un insumo de la receta base.');
                }
                if ($manejaInv) {
                    $stockKg = (float) ($insumoInfo['stock_actual_kg'] ?? 0);
                    if ($stockKg + 0.00001 < $requeridoKg) {
                        throw new RuntimeException('Stock insuficiente para el insumo ' . ($d['insumo_codigo'] ?? '') . '.');
                    }
                }
            } else {
                if ($manejaInv) {
                    $stockU = (float) ($insumoInfo['stock_actual'] ?? 0);
                    if ($stockU + 0.00001 < $requerido) {
                        throw new RuntimeException('Stock insuficiente para el insumo ' . ($d['insumo_codigo'] ?? '') . '.');
                    }
                }
            }

            $consumos[] = [
                'insumo_articulo_id' => $insumoId,
                'cantidad' => $requerido,
                'unidad' => $unidad,
                'cantidad_kg' => $requeridoKg,
                'unidad_base' => $unidadBase,
                'maneja_inventario' => $manejaInv,
            ];
        }

        $deltaProductoKg = $cantidadKg;

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $codigo = (string) ($record['codigo_produccion'] ?? '');
            if (($record['secuencia_clave'] ?? '') !== '' || $codigo === '') {
                Secuencia::ensureExists('pr', 'Produccion', 'PR', 5, 1);
                $codigo = Secuencia::getNextNumberInTransaction($pdo, 'pr');
            }

            $stmt = $pdo->prepare('INSERT INTO producciones
                (codigo_produccion, fecha, producto_articulo_id, receta_base_id, cantidad, unidad, comentario, created_by, updated_by)
                VALUES (:codigo, :fecha, :producto_id, :receta_id, :cantidad, :unidad, :comentario, :created_by, :updated_by)');
            $stmt->execute([
                'codigo' => $codigo,
                'fecha' => $fecha,
                'producto_id' => $productoId,
                'receta_id' => $recetaId,
                'cantidad' => $cantidadKg,
                'unidad' => 'kg',
                'comentario' => (string) ($record['comentario'] ?? ''),
                'created_by' => (int) ($record['created_by'] ?? 0) ?: null,
                'updated_by' => (int) ($record['updated_by'] ?? 0) ?: null,
            ]);
            $produccionId = (int) $pdo->lastInsertId();

            $insDet = $pdo->prepare('INSERT INTO produccion_detalles
                (produccion_id, insumo_articulo_id, cantidad, unidad, cantidad_kg)
                VALUES (:produccion_id, :insumo_id, :cantidad, :unidad, :cantidad_kg)');
            foreach ($consumos as $c) {
                $insDet->execute([
                    'produccion_id' => $produccionId,
                    'insumo_id' => (int) $c['insumo_articulo_id'],
                    'cantidad' => (string) $c['cantidad'],
                    'unidad' => (string) $c['unidad'],
                    'cantidad_kg' => $c['cantidad_kg'] !== null ? (string) $c['cantidad_kg'] : null,
                ]);

                if (!(bool) $c['maneja_inventario']) {
                    continue;
                }
                if ((string) $c['unidad_base'] === 'u') {
                    $up = $pdo->prepare('UPDATE articulos
                        SET stock_actual = GREATEST(0, COALESCE(stock_actual, 0) - :delta)
                        WHERE id = :id');
                    $up->execute([
                        'delta' => (float) $c['cantidad'],
                        'id' => (int) $c['insumo_articulo_id'],
                    ]);
                } else {
                    $up = $pdo->prepare('UPDATE articulos
                        SET stock_actual_kg = GREATEST(0, COALESCE(stock_actual_kg, 0) - :delta)
                        WHERE id = :id');
                    $up->execute([
                        'delta' => (float) ($c['cantidad_kg'] ?? 0),
                        'id' => (int) $c['insumo_articulo_id'],
                    ]);
                }
            }

            if ($productoManejaInv) {
                $upProd = $pdo->prepare('UPDATE articulos
                    SET stock_actual_kg = COALESCE(stock_actual_kg, 0) + :delta
                    WHERE id = :id');
                $upProd->execute([
                    'delta' => (float) $deltaProductoKg,
                    'id' => $productoId,
                ]);
            }

            $pdo->commit();

            return ['id' => $produccionId, 'codigo_produccion' => $codigo];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $record
     * @return array{ id: int, codigo_produccion: string }
     */
    public static function actualizarProduccion(array $record): array
    {
        $produccionId = (int) ($record['id'] ?? 0);
        if ($produccionId <= 0) {
            throw new RuntimeException('Produccion no valida.');
        }
        $actual = self::buscarPorId($produccionId);
        if (!$actual) {
            throw new RuntimeException('Produccion no encontrada.');
        }

        $productoId = (int) ($record['producto_articulo_id'] ?? ($actual['producto_articulo_id'] ?? 0));
        if ($productoId <= 0) {
            throw new RuntimeException('Selecciona un producto de receta base.');
        }
        $cantidadKg = self::decimalPositivo($record['cantidad'] ?? null, 'La cantidad a producir debe ser mayor que cero.');
        $fecha = (string) ($record['fecha'] ?? ($actual['fecha'] ?? date('Y-m-d')));

        $receta = self::obtenerRecetaParaProduccion($productoId);
        if (!$receta) {
            throw new RuntimeException('El producto no tiene receta base configurada.');
        }
        $recetaId = (int) ($receta['id'] ?? 0);
        if ($recetaId <= 0) {
            throw new RuntimeException('Receta base no valida.');
        }
        $rendimiento = (float) ($receta['rendimiento'] ?? 0);
        if ($rendimiento <= 0) {
            throw new RuntimeException('La receta base no tiene rendimiento valido.');
        }
        $unidadRend = strtolower((string) ($receta['unidad_rendimiento'] ?? 'u'));
        if ($unidadRend === 'u') {
            throw new RuntimeException('La produccion debe registrarse en KG. La receta base esta en unidades.');
        }

        $producto = (array) ($receta['producto'] ?? []);
        $unidadProducto = strtolower((string) ($producto['unidad_base_id'] ?? 'u'));
        $productoManejaInv = (int) ($producto['maneja_inventario'] ?? 1) === 1;
        if (!self::unidadCompatible($unidadProducto, $unidadRend)) {
            throw new RuntimeException('La unidad del producto no es compatible con la receta base.');
        }
        if ($unidadProducto === 'u') {
            throw new RuntimeException('El producto a producir debe tener unidad base en peso (kg/g/lb/oz).');
        }

        $detalles = is_array($receta['detalles'] ?? null) ? $receta['detalles'] : [];
        if ($detalles === []) {
            throw new RuntimeException('La receta base no tiene insumos configurados.');
        }

        $cantidadEnUnidadReceta = self::convertirDesdeKg($cantidadKg, $unidadRend);
        if ($cantidadEnUnidadReceta === null) {
            throw new RuntimeException('No se pudo convertir la cantidad a la unidad de la receta base.');
        }
        $factor = $cantidadEnUnidadReceta / $rendimiento;
        if ($factor <= 0) {
            throw new RuntimeException('No se pudo calcular el factor de produccion.');
        }

        $insumoIds = [];
        foreach ($detalles as $d) {
            $insumoIds[] = (int) ($d['insumo_articulo_id'] ?? 0);
        }
        $insumoIds = array_values(array_filter($insumoIds, static fn ($v): bool => $v > 0));
        if ($insumoIds === []) {
            throw new RuntimeException('La receta base no tiene insumos validos.');
        }
        $idsParam = implode(',', array_fill(0, count($insumoIds), '?'));
        $stmtInsumos = Db::conexion()->prepare("SELECT id, codigo, unidad_base_id, COALESCE(maneja_inventario,1) AS maneja_inventario,
                                                       COALESCE(stock_actual,0) AS stock_actual,
                                                       COALESCE(stock_actual_kg,0) AS stock_actual_kg
                                                FROM articulos
                                                WHERE id IN ($idsParam)");
        $stmtInsumos->execute($insumoIds);
        $insumos = [];
        foreach (($stmtInsumos->fetchAll() ?: []) as $row) {
            $insumos[(int) ($row['id'] ?? 0)] = $row;
        }

        $consumos = [];
        foreach ($detalles as $d) {
            $insumoId = (int) ($d['insumo_articulo_id'] ?? 0);
            if ($insumoId <= 0) {
                continue;
            }
            $unidad = strtolower((string) ($d['unidad'] ?? 'u'));
            $cantidadBase = (float) ($d['cantidad'] ?? 0);
            $requerido = $cantidadBase * $factor;
            if ($requerido <= 0) {
                throw new RuntimeException('La cantidad requerida de un insumo es invalida.');
            }
            $insumoInfo = $insumos[$insumoId] ?? null;
            if (!$insumoInfo) {
                throw new RuntimeException('Insumo no encontrado para la receta base.');
            }
            $unidadBase = strtolower((string) ($insumoInfo['unidad_base_id'] ?? 'u'));
            if (!self::unidadCompatible($unidadBase, $unidad)) {
                $codigo = trim((string) ($d['insumo_codigo'] ?? ''));
                $desc = trim((string) ($d['insumo_descripcion'] ?? ''));
                $label = $codigo !== '' ? $codigo : ($desc !== '' ? $desc : 'insumo');
                throw new RuntimeException('Unidad incompatible en insumo ' . $label . '.');
            }
            $manejaInv = (int) ($insumoInfo['maneja_inventario'] ?? 1) === 1;

            $requeridoKg = null;
            if ($unidadBase !== 'u') {
                $requeridoKg = self::convertirAKg($requerido, $unidad);
                if ($requeridoKg === null) {
                    throw new RuntimeException('No se pudo convertir a KG un insumo de la receta base.');
                }
            }

            $consumos[] = [
                'insumo_articulo_id' => $insumoId,
                'cantidad' => $requerido,
                'unidad' => $unidad,
                'cantidad_kg' => $requeridoKg,
                'unidad_base' => $unidadBase,
                'maneja_inventario' => $manejaInv,
            ];
        }

        $oldDetalles = is_array($actual['detalles'] ?? null) ? $actual['detalles'] : [];
        $oldMap = [];
        foreach ($oldDetalles as $d) {
            $insumoId = (int) ($d['insumo_articulo_id'] ?? 0);
            if ($insumoId <= 0) {
                continue;
            }
            $unidadBase = strtolower((string) ($d['insumo_unidad_base'] ?? 'u'));
            $unidad = strtolower((string) ($d['unidad'] ?? 'u'));
            if (!isset($oldMap[$insumoId])) {
                $oldMap[$insumoId] = ['u' => 0.0, 'kg' => 0.0, 'unidad_base' => $unidadBase];
            }
            if ($unidadBase === 'u') {
                $oldMap[$insumoId]['u'] += (float) ($d['cantidad'] ?? 0);
            } else {
                $qtyKg = $d['cantidad_kg'] ?? null;
                if ($qtyKg === null || $qtyKg === '') {
                    $qtyKg = self::convertirAKg((float) ($d['cantidad'] ?? 0), $unidad);
                }
                $oldMap[$insumoId]['kg'] += (float) ($qtyKg ?? 0);
            }
        }

        $newMap = [];
        foreach ($consumos as $c) {
            $insumoId = (int) ($c['insumo_articulo_id'] ?? 0);
            if ($insumoId <= 0) {
                continue;
            }
            if (!isset($newMap[$insumoId])) {
                $newMap[$insumoId] = ['u' => 0.0, 'kg' => 0.0, 'unidad_base' => (string) ($c['unidad_base'] ?? 'u')];
            }
            if ((string) ($c['unidad_base'] ?? '') === 'u') {
                $newMap[$insumoId]['u'] += (float) ($c['cantidad'] ?? 0);
            } else {
                $newMap[$insumoId]['kg'] += (float) ($c['cantidad_kg'] ?? 0);
            }
        }

        $allInsumoIds = array_unique(array_merge(array_keys($oldMap), array_keys($newMap)));
        $insumosStock = [];
        if ($allInsumoIds !== []) {
            $idsParamAll = implode(',', array_fill(0, count($allInsumoIds), '?'));
            $stmtStock = Db::conexion()->prepare("SELECT id, codigo, unidad_base_id, COALESCE(maneja_inventario,1) AS maneja_inventario,
                                                         COALESCE(stock_actual,0) AS stock_actual,
                                                         COALESCE(stock_actual_kg,0) AS stock_actual_kg
                                                  FROM articulos
                                                  WHERE id IN ($idsParamAll)");
            $stmtStock->execute($allInsumoIds);
            foreach (($stmtStock->fetchAll() ?: []) as $row) {
                $insumosStock[(int) ($row['id'] ?? 0)] = $row;
            }
        }

        foreach ($allInsumoIds as $insumoId) {
            $info = $insumosStock[$insumoId] ?? null;
            if (!$info) {
                continue;
            }
            if ((int) ($info['maneja_inventario'] ?? 1) !== 1) {
                continue;
            }
            $unidadBase = strtolower((string) ($info['unidad_base_id'] ?? 'u'));
            $oldQty = $unidadBase === 'u' ? (float) ($oldMap[$insumoId]['u'] ?? 0) : (float) ($oldMap[$insumoId]['kg'] ?? 0);
            $newQty = $unidadBase === 'u' ? (float) ($newMap[$insumoId]['u'] ?? 0) : (float) ($newMap[$insumoId]['kg'] ?? 0);
            $delta = $newQty - $oldQty;
            $stock = $unidadBase === 'u' ? (float) ($info['stock_actual'] ?? 0) : (float) ($info['stock_actual_kg'] ?? 0);
            if ($stock - $delta < -0.00001) {
                $codigo = (string) ($info['codigo'] ?? '');
                $label = $codigo !== '' ? $codigo : ('ID ' . $insumoId);
                throw new RuntimeException('Stock insuficiente en insumo ' . $label . ' para editar la produccion.');
            }
        }

        $oldProductoId = (int) ($actual['producto_articulo_id'] ?? 0);
        $oldCantidadKg = (float) ($actual['cantidad'] ?? 0);

        $prodIds = array_unique(array_filter([$oldProductoId, $productoId]));
        $productosInfo = [];
        if ($prodIds !== []) {
            $idsParamProd = implode(',', array_fill(0, count($prodIds), '?'));
            $stmtProd = Db::conexion()->prepare("SELECT id, codigo, COALESCE(stock_actual_kg,0) AS stock_actual_kg,
                                                        COALESCE(maneja_inventario,1) AS maneja_inventario
                                                 FROM articulos
                                                 WHERE id IN ($idsParamProd)");
            $stmtProd->execute($prodIds);
            foreach (($stmtProd->fetchAll() ?: []) as $row) {
                $productosInfo[(int) ($row['id'] ?? 0)] = $row;
            }
        }

        if ($oldProductoId === $productoId) {
            $info = $productosInfo[$productoId] ?? null;
            if ($info && (int) ($info['maneja_inventario'] ?? 1) === 1) {
                $deltaProd = $cantidadKg - $oldCantidadKg;
                $stock = (float) ($info['stock_actual_kg'] ?? 0);
                if ($stock + $deltaProd < -0.00001) {
                    throw new RuntimeException('Stock insuficiente en el producto para editar la produccion.');
                }
            }
        } else {
            $infoOld = $productosInfo[$oldProductoId] ?? null;
            if ($infoOld && (int) ($infoOld['maneja_inventario'] ?? 1) === 1) {
                $stockOld = (float) ($infoOld['stock_actual_kg'] ?? 0);
                if ($stockOld - $oldCantidadKg < -0.00001) {
                    throw new RuntimeException('Stock insuficiente en el producto anterior para editar la produccion.');
                }
            }
        }

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $stmtUpd = $pdo->prepare('UPDATE producciones
                SET fecha = :fecha,
                    producto_articulo_id = :producto_id,
                    receta_base_id = :receta_id,
                    cantidad = :cantidad,
                    unidad = :unidad,
                    comentario = :comentario,
                    updated_by = :updated_by
                WHERE id = :id');
            $stmtUpd->execute([
                'fecha' => $fecha,
                'producto_id' => $productoId,
                'receta_id' => $recetaId,
                'cantidad' => $cantidadKg,
                'unidad' => 'kg',
                'comentario' => (string) ($record['comentario'] ?? ''),
                'updated_by' => (int) ($record['updated_by'] ?? 0) ?: null,
                'id' => $produccionId,
            ]);

            $pdo->prepare('DELETE FROM produccion_detalles WHERE produccion_id = :id')
                ->execute(['id' => $produccionId]);

            $insDet = $pdo->prepare('INSERT INTO produccion_detalles
                (produccion_id, insumo_articulo_id, cantidad, unidad, cantidad_kg)
                VALUES (:produccion_id, :insumo_id, :cantidad, :unidad, :cantidad_kg)');
            foreach ($consumos as $c) {
                $insDet->execute([
                    'produccion_id' => $produccionId,
                    'insumo_id' => (int) $c['insumo_articulo_id'],
                    'cantidad' => (string) $c['cantidad'],
                    'unidad' => (string) $c['unidad'],
                    'cantidad_kg' => $c['cantidad_kg'] !== null ? (string) $c['cantidad_kg'] : null,
                ]);
            }

            foreach ($allInsumoIds as $insumoId) {
                $info = $insumosStock[$insumoId] ?? null;
                if (!$info || (int) ($info['maneja_inventario'] ?? 1) !== 1) {
                    continue;
                }
                $unidadBase = strtolower((string) ($info['unidad_base_id'] ?? 'u'));
                $oldQty = $unidadBase === 'u' ? (float) ($oldMap[$insumoId]['u'] ?? 0) : (float) ($oldMap[$insumoId]['kg'] ?? 0);
                $newQty = $unidadBase === 'u' ? (float) ($newMap[$insumoId]['u'] ?? 0) : (float) ($newMap[$insumoId]['kg'] ?? 0);
                $delta = $newQty - $oldQty;
                if (abs($delta) < 0.0000001) {
                    continue;
                }
                if ($unidadBase === 'u') {
                    $pdo->prepare('UPDATE articulos SET stock_actual = COALESCE(stock_actual,0) - :delta WHERE id = :id')
                        ->execute(['delta' => $delta, 'id' => $insumoId]);
                } else {
                    $pdo->prepare('UPDATE articulos SET stock_actual_kg = COALESCE(stock_actual_kg,0) - :delta WHERE id = :id')
                        ->execute(['delta' => $delta, 'id' => $insumoId]);
                }
            }

            $infoOld = $productosInfo[$oldProductoId] ?? null;
            $infoNew = $productosInfo[$productoId] ?? null;
            $manejaInvOld = $infoOld ? (int) ($infoOld['maneja_inventario'] ?? 1) === 1 : false;
            $manejaInvNew = $infoNew ? (int) ($infoNew['maneja_inventario'] ?? 1) === 1 : false;

            if ($oldProductoId === $productoId) {
                if ($manejaInvNew) {
                    $deltaProd = $cantidadKg - $oldCantidadKg;
                    if (abs($deltaProd) > 0.0000001) {
                        $pdo->prepare('UPDATE articulos SET stock_actual_kg = COALESCE(stock_actual_kg,0) + :delta WHERE id = :id')
                            ->execute(['delta' => $deltaProd, 'id' => $productoId]);
                    }
                }
            } else {
                if ($manejaInvOld && $oldProductoId > 0) {
                    $pdo->prepare('UPDATE articulos SET stock_actual_kg = COALESCE(stock_actual_kg,0) - :delta WHERE id = :id')
                        ->execute(['delta' => $oldCantidadKg, 'id' => $oldProductoId]);
                }
                if ($manejaInvNew) {
                    $pdo->prepare('UPDATE articulos SET stock_actual_kg = COALESCE(stock_actual_kg,0) + :delta WHERE id = :id')
                        ->execute(['delta' => $cantidadKg, 'id' => $productoId]);
                }
            }

            $pdo->commit();

            return [
                'id' => $produccionId,
                'codigo_produccion' => (string) ($actual['codigo_produccion'] ?? ''),
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function decimalPositivo(mixed $value, string $error): float
    {
        $txt = trim((string) $value);
        if ($txt === '' || !is_numeric($txt) || (float) $txt <= 0) {
            throw new RuntimeException($error);
        }
        return (float) $txt;
    }

    private static function unidadCompatible(string $unidadBase, string $unidadReceta): bool
    {
        $unidadBase = strtolower(trim($unidadBase));
        $unidadReceta = strtolower(trim($unidadReceta));
        $mass = ['g', 'kg', 'lb', 'oz'];
        if (in_array($unidadBase, $mass, true)) {
            return in_array($unidadReceta, $mass, true);
        }
        if ($unidadBase === 'u') {
            return $unidadReceta === 'u';
        }
        return $unidadBase === $unidadReceta;
    }

    private static function convertirAKg(float $cantidad, string $unidad): ?float
    {
        $unidad = strtolower(trim($unidad));
        $factor = match ($unidad) {
            'kg' => 1.0,
            'g' => 0.001,
            'lb' => 0.45359237,
            'oz' => 0.028349523125,
            default => null,
        };
        if ($factor === null) {
            return null;
        }
        return $cantidad * $factor;
    }

    private static function convertirDesdeKg(float $kg, string $unidad): ?float
    {
        $unidad = strtolower(trim($unidad));
        $factor = match ($unidad) {
            'kg' => 1.0,
            'g' => 1000.0,
            'lb' => 2.2046226218,
            'oz' => 35.27396195,
            default => null,
        };
        if ($factor === null) {
            return null;
        }
        return $kg * $factor;
    }
}
