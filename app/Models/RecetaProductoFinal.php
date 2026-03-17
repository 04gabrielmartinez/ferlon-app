<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class RecetaProductoFinal
{
    /** @return array<int, array<string, mixed>> */
    public static function listar(): array
    {
        $sql = 'SELECT rb.id,
                       rb.producto_articulo_id,
                       rb.presentacion_id,
                       rb.empaque_id,
                       rb.rendimiento,
                       rb.unidad_rendimiento,
                       a.codigo AS producto_codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), \'\'), a.nombre) AS producto_descripcion,
                       p.descripcion AS presentacion_descripcion,
                       e.descripcion AS empaque_descripcion
                FROM recetas_producto_final rb
                INNER JOIN articulos a ON a.id = rb.producto_articulo_id
                LEFT JOIN presentaciones p ON p.id = rb.presentacion_id
                LEFT JOIN empaques e ON e.id = rb.empaque_id
                ORDER BY rb.id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT rb.id,
                                                rb.producto_articulo_id,
                                                rb.presentacion_id,
                                                rb.empaque_id,
                                                rb.precio_venta,
                                                rb.rendimiento,
                                                rb.unidad_rendimiento,
                                                p.descripcion AS presentacion_descripcion,
                                                e.descripcion AS empaque_descripcion
                                         FROM recetas_producto_final rb
                                         LEFT JOIN presentaciones p ON p.id = rb.presentacion_id
                                         LEFT JOIN empaques e ON e.id = rb.empaque_id
                                         WHERE rb.id = :id
                                         LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $det = Db::conexion()->prepare('SELECT d.id,
                                               d.insumo_articulo_id,
                                               d.cantidad,
                                               d.unidad,
                                               a.codigo AS insumo_codigo,
                                               COALESCE(NULLIF(TRIM(a.descripcion), \'\'), a.nombre) AS insumo_descripcion,
                                               a.unidad_base_id AS insumo_unidad_base
                                        FROM recetas_producto_final_detalles d
                                        INNER JOIN articulos a ON a.id = d.insumo_articulo_id
                                        WHERE d.receta_producto_final_id = :receta_producto_final_id
                                        ORDER BY d.id ASC');
        $det->execute(['receta_producto_final_id' => $id]);
        $row['detalles'] = $det->fetchAll() ?: [];

        return $row;
    }

    public static function buscarPorProductoArticuloId(int $productoArticuloId, ?int $presentacionId = null, ?int $empaqueId = null): ?array
    {
        if ($presentacionId !== null && $empaqueId !== null && $presentacionId > 0 && $empaqueId > 0) {
            $stmt = Db::conexion()->prepare('SELECT id
                                             FROM recetas_producto_final
                                             WHERE producto_articulo_id = :producto_articulo_id
                                               AND presentacion_id = :presentacion_id
                                               AND empaque_id = :empaque_id
                                             LIMIT 1');
            $stmt->execute([
                'producto_articulo_id' => $productoArticuloId,
                'presentacion_id' => $presentacionId,
                'empaque_id' => $empaqueId,
            ]);
        } else {
            $stmt = Db::conexion()->prepare('SELECT id
                                             FROM recetas_producto_final
                                             WHERE producto_articulo_id = :producto_articulo_id
                                             LIMIT 1');
            $stmt->execute(['producto_articulo_id' => $productoArticuloId]);
        }
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return self::buscarPorId((int) ($row['id'] ?? 0));
    }

    public static function buscarProductoRecetaProductoFinalPorId(int $productoId): ?array
    {
        $stmt = Db::conexion()->prepare("SELECT a.id, a.codigo, COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS descripcion, a.marca_id
                                         FROM articulos a
                                         WHERE a.id = :id
                                           AND a.tiene_receta = 1
                                           AND a.estado = 'activo'
                                         LIMIT 1");
        $stmt->execute(['id' => $productoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarProductosRecetaProductoFinal(): array
    {
        $sql = "SELECT a.id,
                       a.codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS descripcion,
                       ap.presentacion_id,
                       p.descripcion AS presentacion_descripcion,
                       ae.empaque_id,
                       e.descripcion AS empaque_descripcion,
                       a.marca_id,
                       rb.id AS receta_producto_final_id
                FROM articulos a
                INNER JOIN articulos_presentaciones ap ON ap.articulo_id = a.id
                INNER JOIN presentaciones p ON p.id = ap.presentacion_id
                INNER JOIN articulos_empaques ae ON ae.articulo_id = a.id
                INNER JOIN empaques e ON e.id = ae.empaque_id
                LEFT JOIN recetas_producto_final rb ON rb.producto_articulo_id = a.id
                                                   AND rb.presentacion_id = ap.presentacion_id
                                                   AND rb.empaque_id = ae.empaque_id
                WHERE a.tiene_receta = 1
                  AND a.estado = 'activo'
                ORDER BY a.descripcion ASC, p.descripcion ASC, e.descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarProductosBaseRecetaProductoFinal(): array
    {
        $sql = "SELECT a.id,
                       a.codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS descripcion,
                       (SELECT COUNT(*) FROM articulos_presentaciones ap WHERE ap.articulo_id = a.id) AS total_presentaciones,
                       (SELECT COUNT(*) FROM articulos_empaques ae WHERE ae.articulo_id = a.id) AS total_empaques,
                       (SELECT COUNT(*) FROM recetas_producto_final rb WHERE rb.producto_articulo_id = a.id) AS recetas_configuradas
                FROM articulos a
                WHERE a.tiene_receta = 1
                  AND a.estado = 'activo'
                ORDER BY descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarPresentacionesProducto(int $productoId): array
    {
        $stmt = Db::conexion()->prepare('SELECT p.id, p.descripcion
                                         FROM articulos_presentaciones ap
                                         INNER JOIN presentaciones p ON p.id = ap.presentacion_id
                                         WHERE ap.articulo_id = :articulo_id
                                         ORDER BY p.descripcion ASC');
        $stmt->execute(['articulo_id' => $productoId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarEmpaquesProducto(int $productoId): array
    {
        $stmt = Db::conexion()->prepare('SELECT e.id, e.descripcion
                                         FROM articulos_empaques ae
                                         INNER JOIN empaques e ON e.id = ae.empaque_id
                                         WHERE ae.articulo_id = :articulo_id
                                         ORDER BY e.descripcion ASC');
        $stmt->execute(['articulo_id' => $productoId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarVariantesProductoReceta(int $productoId): array
    {
        $stmt = Db::conexion()->prepare("SELECT ap.presentacion_id,
                                                p.descripcion AS presentacion_descripcion,
                                                ae.empaque_id,
                                                e.descripcion AS empaque_descripcion,
                                                rb.id AS receta_producto_final_id
                                         FROM articulos_presentaciones ap
                                         INNER JOIN presentaciones p ON p.id = ap.presentacion_id
                                         INNER JOIN articulos_empaques ae ON ae.articulo_id = ap.articulo_id
                                         INNER JOIN empaques e ON e.id = ae.empaque_id
                                         LEFT JOIN recetas_producto_final rb
                                                ON rb.producto_articulo_id = ap.articulo_id
                                               AND rb.presentacion_id = ap.presentacion_id
                                               AND rb.empaque_id = ae.empaque_id
                                         WHERE ap.articulo_id = :articulo_id
                                         ORDER BY p.descripcion ASC, e.descripcion ASC");
        $stmt->execute(['articulo_id' => $productoId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarInsumosReceta(): array
    {
        $sql = "SELECT a.id, a.codigo, COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS descripcion, a.marca_id, m.descripcion AS marca_descripcion, a.unidad_base_id,
                       COALESCE(a.stock_actual, 0) AS stock_actual, COALESCE(a.stock_actual_kg, 0) AS stock_actual_kg
                FROM articulos a
                LEFT JOIN marcas m ON m.id = a.marca_id
                WHERE (a.insumo_receta = 1 OR a.es_fabricable = 1)
                  AND a.estado = 'activo'
                ORDER BY a.descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarRecetasCreadasPorProducto(int $productoId): array
    {
        $stmt = Db::conexion()->prepare("SELECT rb.id AS receta_id,
                                                rb.producto_articulo_id,
                                                p.descripcion AS presentacion_descripcion,
                                                e.descripcion AS empaque_descripcion,
                                                d.id AS detalle_id,
                                                d.insumo_articulo_id,
                                                d.cantidad,
                                                d.unidad,
                                                ai.codigo AS insumo_codigo,
                                                COALESCE(NULLIF(TRIM(ai.descripcion), ''), ai.nombre) AS insumo_descripcion
                                         FROM recetas_producto_final rb
                                         LEFT JOIN presentaciones p ON p.id = rb.presentacion_id
                                         LEFT JOIN empaques e ON e.id = rb.empaque_id
                                         LEFT JOIN recetas_producto_final_detalles d ON d.receta_producto_final_id = rb.id
                                         LEFT JOIN articulos ai ON ai.id = d.insumo_articulo_id
                                         WHERE rb.producto_articulo_id = :producto_articulo_id
                                         ORDER BY p.descripcion ASC, e.descripcion ASC, d.id ASC");
        $stmt->execute(['producto_articulo_id' => $productoId]);
        $rows = $stmt->fetchAll() ?: [];
        if ($rows === []) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $rid = (int) ($row['receta_id'] ?? 0);
            if ($rid <= 0) {
                continue;
            }
            if (!isset($grouped[$rid])) {
                $grouped[$rid] = [
                    'receta_id' => $rid,
                    'producto_articulo_id' => (int) ($row['producto_articulo_id'] ?? 0),
                    'presentacion_descripcion' => (string) ($row['presentacion_descripcion'] ?? ''),
                    'empaque_descripcion' => (string) ($row['empaque_descripcion'] ?? ''),
                    'detalles' => [],
                ];
            }

            $detalleId = (int) ($row['detalle_id'] ?? 0);
            if ($detalleId > 0) {
                $grouped[$rid]['detalles'][] = [
                    'detalle_id' => $detalleId,
                    'insumo_articulo_id' => (int) ($row['insumo_articulo_id'] ?? 0),
                    'insumo_codigo' => (string) ($row['insumo_codigo'] ?? ''),
                    'insumo_descripcion' => (string) ($row['insumo_descripcion'] ?? ''),
                    'cantidad' => (string) ($row['cantidad'] ?? '0'),
                    'unidad' => (string) ($row['unidad'] ?? 'u'),
                ];
            }
        }

        return array_values($grouped);
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $id = $id !== null && $id > 0 ? $id : null;
        $productoId = (int) ($data['producto_articulo_id'] ?? 0);
        $presentacionId = (int) ($data['presentacion_id'] ?? 0);
        $empaqueId = (int) ($data['empaque_id'] ?? 0);

        if ($productoId <= 0) {
            throw new RuntimeException('Debes seleccionar un producto de receta producto final.');
        }
        if ($presentacionId <= 0) {
            throw new RuntimeException('Debes seleccionar una presentacion de la variante.');
        }
        if ($empaqueId <= 0) {
            throw new RuntimeException('Debes seleccionar un empaque de la variante.');
        }

        $stmtProd = Db::conexion()->prepare('SELECT id, marca_id FROM articulos WHERE id = :id AND tiene_receta = 1 LIMIT 1');
        $stmtProd->execute(['id' => $productoId]);
        $producto = $stmtProd->fetch();
        if (!$producto) {
            throw new RuntimeException('El producto debe estar marcado como Receta producto final.');
        }
        $marcaId = (int) ($producto['marca_id'] ?? 0);
        $marcaIdValue = $marcaId > 0 ? $marcaId : null;
        self::validarVarianteProducto($productoId, $presentacionId, $empaqueId);

        self::validarProductoUnico($productoId, $presentacionId, $empaqueId, $id);

        $rendimiento = 1.0;
        $unidadRendimiento = 'u';
        $precioVenta = self::decimalPositivo($data['precio_venta'] ?? null, 'El precio de venta es obligatorio.');
        if ($id !== null) {
            $actual = self::buscarPorId($id);
            if (!$actual) {
                throw new RuntimeException('Receta producto final no encontrada.');
            }
        }

        $insumoIds = $data['detalle_insumo_id'] ?? [];
        $cantidades = $data['detalle_cantidad'] ?? [];
        $unidades = $data['detalle_unidad'] ?? [];
        if (!is_array($insumoIds) || !is_array($cantidades) || !is_array($unidades)) {
            throw new RuntimeException('Detalle de insumos invalido.');
        }
        if (count($insumoIds) === 0) {
            throw new RuntimeException('Debes agregar al menos un insumo receta.');
        }

        $detalles = [];
        $insumoUnicos = [];
        foreach ($insumoIds as $idx => $rawInsumoId) {
            $insumoId = (int) $rawInsumoId;
            if ($insumoId <= 0) {
                continue;
            }
            if (in_array($insumoId, $insumoUnicos, true)) {
                throw new RuntimeException('No puedes repetir el mismo insumo en la receta.');
            }
            $insumoUnicos[] = $insumoId;

            $cantidad = self::decimalPositivo($cantidades[$idx] ?? null, 'La cantidad de cada insumo debe ser mayor que cero.');
            $unidad = trim(strtolower((string) ($unidades[$idx] ?? 'u')));
            if (!in_array($unidad, ['g', 'kg', 'lb', 'oz', 'u'], true)) {
                throw new RuntimeException('Unidad invalida en detalle de insumos.');
            }

            $detalles[] = [
                'insumo_articulo_id' => $insumoId,
                'cantidad' => $cantidad,
                'unidad' => $unidad,
            ];
        }

        if ($detalles === []) {
            throw new RuntimeException('Debes agregar al menos un insumo receta.');
        }

        $idsParam = implode(',', array_fill(0, count($insumoUnicos), '?'));
        $stmtInsumo = Db::conexion()->prepare("SELECT COUNT(*) AS total FROM articulos WHERE (insumo_receta = 1 OR es_fabricable = 1) AND id IN ($idsParam)");
        $stmtInsumo->execute($insumoUnicos);
        $totalValidos = (int) (($stmtInsumo->fetch()['total'] ?? 0));
        if ($totalValidos !== count($insumoUnicos)) {
            throw new RuntimeException('Todos los insumos deben tener encendido "Insumo receta" o "Receta base".');
        }

        $stmtUnidades = Db::conexion()->prepare("SELECT id, LOWER(TRIM(unidad_base_id)) AS unidad_base_id FROM articulos WHERE id IN ($idsParam)");
        $stmtUnidades->execute($insumoUnicos);
        $unidadesBase = [];
        foreach (($stmtUnidades->fetchAll() ?: []) as $urow) {
            $unidadesBase[(int) ($urow['id'] ?? 0)] = (string) ($urow['unidad_base_id'] ?? 'u');
        }
        foreach ($detalles as $d) {
            $insumoId = (int) ($d['insumo_articulo_id'] ?? 0);
            $unidadReceta = strtolower((string) ($d['unidad'] ?? 'u'));
            $unidadBase = strtolower((string) ($unidadesBase[$insumoId] ?? 'u'));
            if (!self::unidadCompatible($unidadBase, $unidadReceta)) {
                throw new RuntimeException('La unidad del insumo no coincide con su unidad base.');
            }
        }

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            if ($id !== null) {
                $up = $pdo->prepare('UPDATE recetas_producto_final
                                     SET marca_id = :marca_id,
                                         familia_id = NULL,
                                         producto_articulo_id = :producto_articulo_id,
                                         presentacion_id = :presentacion_id,
                                         empaque_id = :empaque_id,
                                         precio_venta = :precio_venta,
                                         rendimiento = :rendimiento,
                                         unidad_rendimiento = :unidad_rendimiento,
                                         updated_by = :updated_by
                                     WHERE id = :id');
                $up->execute([
                    'id' => $id,
                    'marca_id' => $marcaIdValue,
                    'producto_articulo_id' => $productoId,
                    'presentacion_id' => $presentacionId,
                    'empaque_id' => $empaqueId,
                    'precio_venta' => $precioVenta,
                    'rendimiento' => $rendimiento,
                    'unidad_rendimiento' => $unidadRendimiento,
                    'updated_by' => $userId > 0 ? $userId : null,
                ]);

                $pdo->prepare('DELETE FROM recetas_producto_final_detalles WHERE receta_producto_final_id = :receta_producto_final_id')
                    ->execute(['receta_producto_final_id' => $id]);
            } else {
                $ins = $pdo->prepare('INSERT INTO recetas_producto_final (marca_id, familia_id, producto_articulo_id, presentacion_id, empaque_id, precio_venta, rendimiento, unidad_rendimiento, created_by, updated_by)
                                      VALUES (:marca_id, NULL, :producto_articulo_id, :presentacion_id, :empaque_id, :precio_venta, :rendimiento, :unidad_rendimiento, :created_by, :updated_by)');
                $ins->execute([
                    'marca_id' => $marcaIdValue,
                    'producto_articulo_id' => $productoId,
                    'presentacion_id' => $presentacionId,
                    'empaque_id' => $empaqueId,
                    'precio_venta' => $precioVenta,
                    'rendimiento' => $rendimiento,
                    'unidad_rendimiento' => $unidadRendimiento,
                    'created_by' => $userId > 0 ? $userId : null,
                    'updated_by' => $userId > 0 ? $userId : null,
                ]);
                $id = (int) $pdo->lastInsertId();
            }

            $insDet = $pdo->prepare('INSERT INTO recetas_producto_final_detalles (receta_producto_final_id, insumo_articulo_id, cantidad, unidad)
                                     VALUES (:receta_producto_final_id, :insumo_articulo_id, :cantidad, :unidad)');
            foreach ($detalles as $d) {
                $insDet->execute([
                    'receta_producto_final_id' => $id,
                    'insumo_articulo_id' => $d['insumo_articulo_id'],
                    'cantidad' => $d['cantidad'],
                    'unidad' => $d['unidad'],
                ]);
            }

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function validarProductoUnico(int $productoId, int $presentacionId, int $empaqueId, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id
                                         FROM recetas_producto_final
                                         WHERE producto_articulo_id = :producto_articulo_id
                                           AND presentacion_id = :presentacion_id
                                           AND empaque_id = :empaque_id
                                         LIMIT 1');
        $stmt->execute([
            'producto_articulo_id' => $productoId,
            'presentacion_id' => $presentacionId,
            'empaque_id' => $empaqueId,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }
        throw new RuntimeException('La variante seleccionada ya tiene una receta producto final configurada.');
    }

    private static function validarVarianteProducto(int $productoId, int $presentacionId, int $empaqueId): void
    {
        $stmtP = Db::conexion()->prepare('SELECT COUNT(*) AS total
                                          FROM articulos_presentaciones
                                          WHERE articulo_id = :articulo_id
                                            AND presentacion_id = :presentacion_id');
        $stmtP->execute([
            'articulo_id' => $productoId,
            'presentacion_id' => $presentacionId,
        ]);
        if ((int) (($stmtP->fetch()['total'] ?? 0)) <= 0) {
            throw new RuntimeException('La presentacion no pertenece al producto seleccionado.');
        }

        $stmtE = Db::conexion()->prepare('SELECT COUNT(*) AS total
                                          FROM articulos_empaques
                                          WHERE articulo_id = :articulo_id
                                            AND empaque_id = :empaque_id');
        $stmtE->execute([
            'articulo_id' => $productoId,
            'empaque_id' => $empaqueId,
        ]);
        if ((int) (($stmtE->fetch()['total'] ?? 0)) <= 0) {
            throw new RuntimeException('El empaque no pertenece al producto seleccionado.');
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
}
