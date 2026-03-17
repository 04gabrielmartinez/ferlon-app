<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Models\Secuencia;
use RuntimeException;

final class Fabricacion
{
    /** @return array<int, array<string, mixed>> */
    public static function listarRegistros(): array
    {
        $sql = "SELECT f.id,
                       f.codigo_fabricacion,
                       f.fecha,
                       f.cantidad,
                       f.unidad,
                       a.codigo AS producto_codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS producto_descripcion,
                       p.descripcion AS presentacion_descripcion,
                       e.descripcion AS empaque_descripcion,
                       COALESCE(NULLIF(TRIM(emp.nombre), ''), u.nombre, u.username, '') AS empleado_nombre
                FROM fabricaciones f
                INNER JOIN articulos a ON a.id = f.producto_articulo_id
                LEFT JOIN presentaciones p ON p.id = f.presentacion_id
                LEFT JOIN empaques e ON e.id = f.empaque_id
                LEFT JOIN users u ON u.id = f.created_by
                LEFT JOIN empleados emp ON emp.id = u.empleado_id
                ORDER BY f.id DESC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare("SELECT f.*,
                                                a.codigo AS producto_codigo,
                                                COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS producto_descripcion,
                                                a.unidad_base_id AS producto_unidad_base,
                                                COALESCE(a.maneja_inventario,1) AS producto_maneja_inventario,
                                                p.descripcion AS presentacion_descripcion,
                                                e.descripcion AS empaque_descripcion,
                                                COALESCE(NULLIF(TRIM(emp.nombre), ''), u.nombre, u.username, '') AS empleado_nombre
                                         FROM fabricaciones f
                                         INNER JOIN articulos a ON a.id = f.producto_articulo_id
                                         LEFT JOIN presentaciones p ON p.id = f.presentacion_id
                                         LEFT JOIN empaques e ON e.id = f.empaque_id
                                         LEFT JOIN users u ON u.id = f.created_by
                                         LEFT JOIN empleados emp ON emp.id = u.empleado_id
                                         WHERE f.id = :id
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
                                        FROM fabricacion_detalles d
                                        INNER JOIN articulos a ON a.id = d.insumo_articulo_id
                                        WHERE d.fabricacion_id = :id
                                        ORDER BY d.id ASC");
        $det->execute(['id' => $id]);
        $row['detalles'] = $det->fetchAll() ?: [];

        return $row;
    }

    public static function obtenerRecetaParaFabricacion(int $productoId, ?int $presentacionId = null, ?int $empaqueId = null): ?array
    {
        if ($productoId <= 0) {
            return null;
        }
        $receta = RecetaProductoFinal::buscarPorProductoArticuloId($productoId, $presentacionId, $empaqueId);
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
     * @return array{ id: int, codigo_fabricacion: string }
     */
    public static function guardarFabricacion(array $record): array
    {
        $productoId = (int) ($record['producto_articulo_id'] ?? 0);
        if ($productoId <= 0) {
            throw new RuntimeException('Selecciona un producto de receta producto final.');
        }
        $cantidad = self::enteroPositivo($record['cantidad'] ?? null, 'La cantidad a fabricar debe ser un numero entero mayor que cero.');
        $fecha = (string) ($record['fecha'] ?? date('Y-m-d'));
        $presentacionId = (int) ($record['presentacion_id'] ?? 0);
        $empaqueId = (int) ($record['empaque_id'] ?? 0);
        if ($presentacionId <= 0 || $empaqueId <= 0) {
            throw new RuntimeException('Selecciona presentacion y empaque para fabricar.');
        }

        $receta = self::obtenerRecetaParaFabricacion($productoId, $presentacionId > 0 ? $presentacionId : null, $empaqueId > 0 ? $empaqueId : null);
        if (!$receta) {
            throw new RuntimeException('El producto no tiene receta producto final configurada.');
        }
        $recetaId = (int) ($receta['id'] ?? 0);
        if ($recetaId <= 0) {
            throw new RuntimeException('Receta producto final no valida.');
        }
        $rendimiento = (float) ($receta['rendimiento'] ?? 0);
        if ($rendimiento <= 0) {
            throw new RuntimeException('La receta producto final no tiene rendimiento valido.');
        }
        $unidadRend = strtolower((string) ($receta['unidad_rendimiento'] ?? 'u'));
        if ($unidadRend !== 'u') {
            throw new RuntimeException('La receta producto final debe estar en unidades.');
        }

        $producto = (array) ($receta['producto'] ?? []);
        $unidadProducto = strtolower((string) ($producto['unidad_base_id'] ?? 'u'));
        $productoManejaInv = (int) ($producto['maneja_inventario'] ?? 1) === 1;
        if ($unidadProducto !== 'u') {
            throw new RuntimeException('El producto final debe tener unidad base en "u".');
        }

        $detalles = is_array($receta['detalles'] ?? null) ? $receta['detalles'] : [];
        if ($detalles === []) {
            throw new RuntimeException('La receta producto final no tiene insumos configurados.');
        }

        $factor = $cantidad / $rendimiento;
        if ($factor <= 0) {
            throw new RuntimeException('No se pudo calcular el factor de fabricacion.');
        }

        $insumoIds = [];
        foreach ($detalles as $d) {
            $insumoIds[] = (int) ($d['insumo_articulo_id'] ?? 0);
        }
        $insumoIds = array_values(array_filter($insumoIds, static fn ($v): bool => $v > 0));
        if ($insumoIds === []) {
            throw new RuntimeException('La receta producto final no tiene insumos validos.');
        }
        $idsParam = implode(',', array_fill(0, count($insumoIds), '?'));
        $stmtInsumos = Db::conexion()->prepare("SELECT id, unidad_base_id, COALESCE(maneja_inventario,1) AS maneja_inventario,
                                                       COALESCE(stock_actual,0) AS stock_actual,
                                                       COALESCE(stock_actual_kg,0) AS stock_actual_kg,
                                                       codigo
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
                throw new RuntimeException('Insumo no encontrado para la receta producto final.');
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
                    throw new RuntimeException('No se pudo convertir a KG un insumo de la receta producto final.');
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

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $codigo = (string) ($record['codigo_fabricacion'] ?? '');
            if (($record['secuencia_clave'] ?? '') !== '' || $codigo === '') {
                Secuencia::ensureExists('fb', 'Fabricacion', 'FB', 5, 1);
                $codigo = Secuencia::getNextNumberInTransaction($pdo, 'fb');
            }

            $stmt = $pdo->prepare('INSERT INTO fabricaciones
                (codigo_fabricacion, fecha, producto_articulo_id, presentacion_id, empaque_id, receta_producto_final_id, cantidad, unidad, comentario, created_by, updated_by)
                VALUES (:codigo, :fecha, :producto_id, :presentacion_id, :empaque_id, :receta_id, :cantidad, :unidad, :comentario, :created_by, :updated_by)');
            $stmt->execute([
                'codigo' => $codigo,
                'fecha' => $fecha,
                'producto_id' => $productoId,
                'presentacion_id' => $presentacionId > 0 ? $presentacionId : null,
                'empaque_id' => $empaqueId > 0 ? $empaqueId : null,
                'receta_id' => $recetaId,
                'cantidad' => $cantidad,
                'unidad' => 'u',
                'comentario' => (string) ($record['comentario'] ?? ''),
                'created_by' => (int) ($record['created_by'] ?? 0) ?: null,
                'updated_by' => (int) ($record['updated_by'] ?? 0) ?: null,
            ]);
            $fabricacionId = (int) $pdo->lastInsertId();

            $insDet = $pdo->prepare('INSERT INTO fabricacion_detalles
                (fabricacion_id, insumo_articulo_id, cantidad, unidad, cantidad_kg)
                VALUES (:fabricacion_id, :insumo_id, :cantidad, :unidad, :cantidad_kg)');
            foreach ($consumos as $c) {
                $insDet->execute([
                    'fabricacion_id' => $fabricacionId,
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
                $upVar = $pdo->prepare('INSERT INTO articulos_variantes_stock
                    (articulo_id, presentacion_id, empaque_id, stock_actual, updated_at)
                    VALUES (:articulo_id, :presentacion_id, :empaque_id, :stock_actual, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE stock_actual = stock_actual + VALUES(stock_actual), updated_at = CURRENT_TIMESTAMP');
                $upVar->execute([
                    'articulo_id' => $productoId,
                    'presentacion_id' => $presentacionId,
                    'empaque_id' => $empaqueId,
                    'stock_actual' => (float) $cantidad,
                ]);
                $pdo->prepare('UPDATE articulos
                    SET stock_actual = (SELECT COALESCE(SUM(stock_actual),0) FROM articulos_variantes_stock WHERE articulo_id = :id_calc)
                    WHERE id = :id_target')
                    ->execute(['id_calc' => $productoId, 'id_target' => $productoId]);
            }

            $pdo->commit();

            return ['id' => $fabricacionId, 'codigo_fabricacion' => $codigo];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $record
     * @return array{ id: int, codigo_fabricacion: string }
     */
    public static function actualizarFabricacion(array $record): array
    {
        $fabricacionId = (int) ($record['id'] ?? 0);
        if ($fabricacionId <= 0) {
            throw new RuntimeException('Fabricacion no valida.');
        }
        $actual = self::buscarPorId($fabricacionId);
        if (!$actual) {
            throw new RuntimeException('Fabricacion no encontrada.');
        }

        $productoId = (int) ($record['producto_articulo_id'] ?? ($actual['producto_articulo_id'] ?? 0));
        if ($productoId <= 0) {
            throw new RuntimeException('Selecciona un producto de receta producto final.');
        }
        $cantidad = self::enteroPositivo($record['cantidad'] ?? null, 'La cantidad a fabricar debe ser un numero entero mayor que cero.');
        $fecha = (string) ($record['fecha'] ?? ($actual['fecha'] ?? date('Y-m-d')));
        $presentacionId = (int) ($record['presentacion_id'] ?? ($actual['presentacion_id'] ?? 0));
        $empaqueId = (int) ($record['empaque_id'] ?? ($actual['empaque_id'] ?? 0));
        if ($presentacionId <= 0 || $empaqueId <= 0) {
            throw new RuntimeException('Selecciona presentacion y empaque para fabricar.');
        }

        $receta = self::obtenerRecetaParaFabricacion($productoId, $presentacionId > 0 ? $presentacionId : null, $empaqueId > 0 ? $empaqueId : null);
        if (!$receta) {
            throw new RuntimeException('El producto no tiene receta producto final configurada.');
        }
        $recetaId = (int) ($receta['id'] ?? 0);
        if ($recetaId <= 0) {
            throw new RuntimeException('Receta producto final no valida.');
        }
        $rendimiento = (float) ($receta['rendimiento'] ?? 0);
        if ($rendimiento <= 0) {
            throw new RuntimeException('La receta producto final no tiene rendimiento valido.');
        }
        $unidadRend = strtolower((string) ($receta['unidad_rendimiento'] ?? 'u'));
        if ($unidadRend !== 'u') {
            throw new RuntimeException('La receta producto final debe estar en unidades.');
        }

        $producto = (array) ($receta['producto'] ?? []);
        $unidadProducto = strtolower((string) ($producto['unidad_base_id'] ?? 'u'));
        if ($unidadProducto !== 'u') {
            throw new RuntimeException('El producto final debe tener unidad base en "u".');
        }

        $detalles = is_array($receta['detalles'] ?? null) ? $receta['detalles'] : [];
        if ($detalles === []) {
            throw new RuntimeException('La receta producto final no tiene insumos configurados.');
        }

        $factor = $cantidad / $rendimiento;
        if ($factor <= 0) {
            throw new RuntimeException('No se pudo calcular el factor de fabricacion.');
        }

        $insumoIds = [];
        foreach ($detalles as $d) {
            $insumoIds[] = (int) ($d['insumo_articulo_id'] ?? 0);
        }
        $insumoIds = array_values(array_filter($insumoIds, static fn ($v): bool => $v > 0));
        if ($insumoIds === []) {
            throw new RuntimeException('La receta producto final no tiene insumos validos.');
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
                throw new RuntimeException('Insumo no encontrado para la receta producto final.');
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
                    throw new RuntimeException('No se pudo convertir a KG un insumo de la receta producto final.');
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
                throw new RuntimeException('Stock insuficiente en insumo ' . $label . ' para editar la fabricacion.');
            }
        }

        $oldProductoId = (int) ($actual['producto_articulo_id'] ?? 0);
        $oldCantidad = (float) ($actual['cantidad'] ?? 0);
        $oldPresentacionId = (int) ($actual['presentacion_id'] ?? 0);
        $oldEmpaqueId = (int) ($actual['empaque_id'] ?? 0);

        $prodIds = array_unique(array_filter([$oldProductoId, $productoId]));
        $productosInfo = [];
        if ($prodIds !== []) {
            $idsParamProd = implode(',', array_fill(0, count($prodIds), '?'));
            $stmtProd = Db::conexion()->prepare("SELECT id, codigo, COALESCE(stock_actual,0) AS stock_actual,
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
                if ($oldPresentacionId === $presentacionId && $oldEmpaqueId === $empaqueId) {
                    $varStock = self::obtenerStockVariante($productoId, $presentacionId, $empaqueId);
                    $deltaProd = $cantidad - $oldCantidad;
                    if ($varStock + $deltaProd < -0.00001) {
                        throw new RuntimeException('Stock insuficiente en la variante para editar la fabricacion.');
                    }
                } else {
                    $oldVarStock = self::obtenerStockVariante($oldProductoId, $oldPresentacionId, $oldEmpaqueId);
                    if ($oldVarStock - $oldCantidad < -0.00001) {
                        throw new RuntimeException('Stock insuficiente en la variante anterior para editar la fabricacion.');
                    }
                }
            }
        } else {
            $infoOld = $productosInfo[$oldProductoId] ?? null;
            if ($infoOld && (int) ($infoOld['maneja_inventario'] ?? 1) === 1) {
                $oldVarStock = self::obtenerStockVariante($oldProductoId, $oldPresentacionId, $oldEmpaqueId);
                if ($oldVarStock - $oldCantidad < -0.00001) {
                    throw new RuntimeException('Stock insuficiente en la variante anterior para editar la fabricacion.');
                }
            }
        }

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $stmtUpd = $pdo->prepare('UPDATE fabricaciones
                SET fecha = :fecha,
                    producto_articulo_id = :producto_id,
                    presentacion_id = :presentacion_id,
                    empaque_id = :empaque_id,
                    receta_producto_final_id = :receta_id,
                    cantidad = :cantidad,
                    unidad = :unidad,
                    comentario = :comentario,
                    updated_by = :updated_by
                WHERE id = :id');
            $stmtUpd->execute([
                'fecha' => $fecha,
                'producto_id' => $productoId,
                'presentacion_id' => $presentacionId > 0 ? $presentacionId : null,
                'empaque_id' => $empaqueId > 0 ? $empaqueId : null,
                'receta_id' => $recetaId,
                'cantidad' => $cantidad,
                'unidad' => 'u',
                'comentario' => (string) ($record['comentario'] ?? ''),
                'updated_by' => (int) ($record['updated_by'] ?? 0) ?: null,
                'id' => $fabricacionId,
            ]);

            $pdo->prepare('DELETE FROM fabricacion_detalles WHERE fabricacion_id = :id')
                ->execute(['id' => $fabricacionId]);

            $insDet = $pdo->prepare('INSERT INTO fabricacion_detalles
                (fabricacion_id, insumo_articulo_id, cantidad, unidad, cantidad_kg)
                VALUES (:fabricacion_id, :insumo_id, :cantidad, :unidad, :cantidad_kg)');
            foreach ($consumos as $c) {
                $insDet->execute([
                    'fabricacion_id' => $fabricacionId,
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

            if ($oldProductoId === $productoId && $oldPresentacionId === $presentacionId && $oldEmpaqueId === $empaqueId) {
                if ($manejaInvNew) {
                    $deltaProd = $cantidad - $oldCantidad;
                    if (abs($deltaProd) > 0.0000001) {
                        self::ajustarStockVariante($pdo, $productoId, $presentacionId, $empaqueId, $deltaProd);
                    }
                }
            } else {
                if ($manejaInvOld && $oldProductoId > 0) {
                    self::ajustarStockVariante($pdo, $oldProductoId, $oldPresentacionId, $oldEmpaqueId, -$oldCantidad);
                }
                if ($manejaInvNew) {
                    self::ajustarStockVariante($pdo, $productoId, $presentacionId, $empaqueId, $cantidad);
                }
            }

            if ($manejaInvOld && $oldProductoId > 0) {
                $pdo->prepare('UPDATE articulos
                    SET stock_actual = (SELECT COALESCE(SUM(stock_actual),0) FROM articulos_variantes_stock WHERE articulo_id = :id_calc)
                    WHERE id = :id_target')
                    ->execute(['id_calc' => $oldProductoId, 'id_target' => $oldProductoId]);
            }
            if ($manejaInvNew && $productoId > 0 && $productoId !== $oldProductoId) {
                $pdo->prepare('UPDATE articulos
                    SET stock_actual = (SELECT COALESCE(SUM(stock_actual),0) FROM articulos_variantes_stock WHERE articulo_id = :id_calc)
                    WHERE id = :id_target')
                    ->execute(['id_calc' => $productoId, 'id_target' => $productoId]);
            } elseif ($manejaInvNew && $productoId > 0 && $productoId === $oldProductoId) {
                $pdo->prepare('UPDATE articulos
                    SET stock_actual = (SELECT COALESCE(SUM(stock_actual),0) FROM articulos_variantes_stock WHERE articulo_id = :id_calc)
                    WHERE id = :id_target')
                    ->execute(['id_calc' => $productoId, 'id_target' => $productoId]);
            }

            $pdo->commit();

            return [
                'id' => $fabricacionId,
                'codigo_fabricacion' => (string) ($actual['codigo_fabricacion'] ?? ''),
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

    private static function enteroPositivo(mixed $value, string $error): int
    {
        $txt = trim((string) $value);
        if ($txt === '' || !is_numeric($txt)) {
            throw new RuntimeException($error);
        }
        $num = (float) $txt;
        if ($num <= 0 || floor($num) != $num) {
            throw new RuntimeException($error);
        }
        return (int) $num;
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

    private static function obtenerStockVariante(int $articuloId, int $presentacionId, int $empaqueId): float
    {
        if ($articuloId <= 0 || $presentacionId <= 0 || $empaqueId <= 0) {
            return 0.0;
        }
        $stmt = Db::conexion()->prepare("SELECT COALESCE(stock_actual, 0) AS stock_actual
                                         FROM articulos_variantes_stock
                                         WHERE articulo_id = :articulo_id
                                           AND presentacion_id = :presentacion_id
                                           AND empaque_id = :empaque_id
                                         LIMIT 1");
        $stmt->execute([
            'articulo_id' => $articuloId,
            'presentacion_id' => $presentacionId,
            'empaque_id' => $empaqueId,
        ]);
        $row = $stmt->fetch();
        return (float) ($row['stock_actual'] ?? 0);
    }

    private static function ajustarStockVariante(\PDO $pdo, int $articuloId, int $presentacionId, int $empaqueId, float $delta): void
    {
        if ($articuloId <= 0 || $presentacionId <= 0 || $empaqueId <= 0 || abs($delta) < 0.0000001) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO articulos_variantes_stock
            (articulo_id, presentacion_id, empaque_id, stock_actual, updated_at)
            VALUES (:articulo_id, :presentacion_id, :empaque_id, :stock_actual, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE stock_actual = stock_actual + VALUES(stock_actual), updated_at = CURRENT_TIMESTAMP');
        $stmt->execute([
            'articulo_id' => $articuloId,
            'presentacion_id' => $presentacionId,
            'empaque_id' => $empaqueId,
            'stock_actual' => $delta,
        ]);
    }
}
