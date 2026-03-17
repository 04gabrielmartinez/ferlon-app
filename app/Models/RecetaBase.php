<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class RecetaBase
{
    /** @return array<int, array<string, mixed>> */
    public static function listar(): array
    {
        $sql = 'SELECT rb.id,
                       rb.producto_articulo_id,
                       rb.rendimiento,
                       rb.unidad_rendimiento,
                       a.codigo AS producto_codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), \'\'), a.nombre) AS producto_descripcion
                FROM recetas_base rb
                INNER JOIN articulos a ON a.id = rb.producto_articulo_id
                ORDER BY rb.id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT id, producto_articulo_id, rendimiento, unidad_rendimiento
                                         FROM recetas_base
                                         WHERE id = :id
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
                                        FROM recetas_base_detalles d
                                        INNER JOIN articulos a ON a.id = d.insumo_articulo_id
                                        WHERE d.receta_base_id = :receta_base_id
                                        ORDER BY d.id ASC');
        $det->execute(['receta_base_id' => $id]);
        $row['detalles'] = $det->fetchAll() ?: [];

        return $row;
    }

    public static function buscarPorProductoArticuloId(int $productoArticuloId): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT id
                                         FROM recetas_base
                                         WHERE producto_articulo_id = :producto_articulo_id
                                         LIMIT 1');
        $stmt->execute(['producto_articulo_id' => $productoArticuloId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return self::buscarPorId((int) ($row['id'] ?? 0));
    }

    public static function buscarProductoRecetaBasePorId(int $productoId): ?array
    {
        $stmt = Db::conexion()->prepare("SELECT a.id, a.codigo, COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS descripcion, a.marca_id
                                         FROM articulos a
                                         WHERE a.id = :id
                                           AND a.es_fabricable = 1
                                           AND a.estado = 'activo'
                                         LIMIT 1");
        $stmt->execute(['id' => $productoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarProductosRecetaBase(): array
    {
        $sql = "SELECT a.id,
                       a.codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS descripcion,
                       a.marca_id,
                       rb.id AS receta_base_id
                FROM articulos a
                LEFT JOIN recetas_base rb ON rb.producto_articulo_id = a.id
                WHERE a.es_fabricable = 1
                  AND a.estado = 'activo'
                ORDER BY a.descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarInsumosReceta(): array
    {
        $sql = "SELECT a.id, a.codigo, COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS descripcion, a.marca_id, m.descripcion AS marca_descripcion, a.unidad_base_id,
                       COALESCE(a.stock_actual, 0) AS stock_actual, COALESCE(a.stock_actual_kg, 0) AS stock_actual_kg
                FROM articulos a
                LEFT JOIN marcas m ON m.id = a.marca_id
                WHERE a.insumo_receta = 1
                  AND a.estado = 'activo'
                ORDER BY a.descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $id = $id !== null && $id > 0 ? $id : null;
        $productoId = (int) ($data['producto_articulo_id'] ?? 0);

        if ($productoId <= 0) {
            throw new RuntimeException('Debes seleccionar un producto de receta base.');
        }

        $stmtProd = Db::conexion()->prepare('SELECT id, marca_id FROM articulos WHERE id = :id AND es_fabricable = 1 LIMIT 1');
        $stmtProd->execute(['id' => $productoId]);
        $producto = $stmtProd->fetch();
        if (!$producto) {
            throw new RuntimeException('El producto debe estar marcado como Receta base.');
        }
        $marcaId = (int) ($producto['marca_id'] ?? 0);
        $marcaIdValue = $marcaId > 0 ? $marcaId : null;

        self::validarProductoUnico($productoId, $id);

        $editRendimiento = ((int) ($data['editar_rendimiento'] ?? 0) === 1);
        $rendimiento = 1.0;
        $unidadRendimiento = 'kg';
        if ($id !== null) {
            $actual = self::buscarPorId($id);
            if (!$actual) {
                throw new RuntimeException('Receta base no encontrada.');
            }
            $rendimiento = (float) ($actual['rendimiento'] ?? 1.0);
            $unidadRendimiento = (string) ($actual['unidad_rendimiento'] ?? 'kg');
        }
        if ($editRendimiento) {
            $rendimiento = self::decimalPositivo($data['rendimiento'] ?? null, 'El rendimiento debe ser mayor que cero.');
            $unidadRendimiento = trim(strtolower((string) ($data['unidad_rendimiento'] ?? 'kg')));
            if (!in_array($unidadRendimiento, ['g', 'kg', 'lb', 'oz', 'u'], true)) {
                throw new RuntimeException('Unidad de rendimiento invalida.');
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
        $stmtInsumo = Db::conexion()->prepare("SELECT COUNT(*) AS total FROM articulos WHERE insumo_receta = 1 AND id IN ($idsParam)");
        $stmtInsumo->execute($insumoUnicos);
        $totalValidos = (int) (($stmtInsumo->fetch()['total'] ?? 0));
        if ($totalValidos !== count($insumoUnicos)) {
            throw new RuntimeException('Todos los insumos deben tener encendido "Insumo receta".');
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
                $up = $pdo->prepare('UPDATE recetas_base
                                     SET marca_id = :marca_id,
                                         familia_id = NULL,
                                         producto_articulo_id = :producto_articulo_id,
                                         rendimiento = :rendimiento,
                                         unidad_rendimiento = :unidad_rendimiento,
                                         updated_by = :updated_by
                                     WHERE id = :id');
                $up->execute([
                    'id' => $id,
                    'marca_id' => $marcaIdValue,
                    'producto_articulo_id' => $productoId,
                    'rendimiento' => $rendimiento,
                    'unidad_rendimiento' => $unidadRendimiento,
                    'updated_by' => $userId > 0 ? $userId : null,
                ]);

                $pdo->prepare('DELETE FROM recetas_base_detalles WHERE receta_base_id = :receta_base_id')
                    ->execute(['receta_base_id' => $id]);
            } else {
                $ins = $pdo->prepare('INSERT INTO recetas_base (marca_id, familia_id, producto_articulo_id, rendimiento, unidad_rendimiento, created_by, updated_by)
                                      VALUES (:marca_id, NULL, :producto_articulo_id, :rendimiento, :unidad_rendimiento, :created_by, :updated_by)');
                $ins->execute([
                    'marca_id' => $marcaIdValue,
                    'producto_articulo_id' => $productoId,
                    'rendimiento' => $rendimiento,
                    'unidad_rendimiento' => $unidadRendimiento,
                    'created_by' => $userId > 0 ? $userId : null,
                    'updated_by' => $userId > 0 ? $userId : null,
                ]);
                $id = (int) $pdo->lastInsertId();
            }

            $insDet = $pdo->prepare('INSERT INTO recetas_base_detalles (receta_base_id, insumo_articulo_id, cantidad, unidad)
                                     VALUES (:receta_base_id, :insumo_articulo_id, :cantidad, :unidad)');
            foreach ($detalles as $d) {
                $insDet->execute([
                    'receta_base_id' => $id,
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

    private static function validarProductoUnico(int $productoId, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id FROM recetas_base WHERE producto_articulo_id = :producto_articulo_id LIMIT 1');
        $stmt->execute(['producto_articulo_id' => $productoId]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }
        throw new RuntimeException('El producto ya tiene una receta base configurada.');
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
