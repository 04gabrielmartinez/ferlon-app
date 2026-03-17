<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use Throwable;

final class OrdenCompra
{
    /** @return array<int, array<string, mixed>> */
    public static function listarArticulosComprables(): array
    {
        $sql = "SELECT a.id,
                       a.codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS descripcion,
                       LOWER(TRIM(COALESCE(a.unidad_base_id, 'u'))) AS unidad_base_id,
                       COALESCE(a.stock_actual, 0) AS stock_actual,
                       COALESCE(a.stock_actual_kg, 0) AS stock_actual_kg,
                       COALESCE(a.costo_ultimo, 0) AS costo_ultimo,
                       TRIM(COALESCE(a.impuestos, '')) AS impuestos
                FROM articulos a
                WHERE a.estado = 'activo'
                  AND COALESCE(a.es_comprable, 0) = 1
                ORDER BY descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function generarCodigoVista(): string
    {
        try {
            Secuencia::ensureExists('oc', 'Orden Compra', 'OC', 5, 1);
            $stmt = Db::conexion()->prepare('SELECT prefijo, longitud, valor_actual, incremento
                                             FROM secuencias
                                             WHERE clave = :clave
                                             LIMIT 1');
            $stmt->execute(['clave' => 'oc']);
            $row = $stmt->fetch();
            if ($row) {
                $prefijo = (string) ($row['prefijo'] ?? 'OC');
                $longitud = max(2, (int) ($row['longitud'] ?? 5));
                $valorActual = (int) ($row['valor_actual'] ?? 0);
                $incremento = max(1, (int) ($row['incremento'] ?? 1));
                $next = $valorActual + $incremento;
                return $prefijo . str_pad((string) $next, $longitud, '0', STR_PAD_LEFT);
            }
        } catch (Throwable) {
        }
        return 'OC00001';
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarRegistros(): array
    {
        $sql = 'SELECT id,
                       codigo_compra,
                       fecha,
                       proveedor_label,
                       total_compra,
                       estado
                FROM ordenes_compra
                ORDER BY id DESC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT *
                                         FROM ordenes_compra
                                         WHERE id = :id
                                         LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $stmtDet = Db::conexion()->prepare('SELECT *
                                            FROM ordenes_compra_detalles
                                            WHERE orden_compra_id = :id
                                            ORDER BY id ASC');
        $stmtDet->execute(['id' => $id]);
        $detalles = $stmtDet->fetchAll() ?: [];
        $row['detalles'] = $detalles;

        return $row;
    }

    /**
     * @param array<int, array<string, mixed>> $detalles
     * @return array{ id: int, codigo_compra: string }
     */
    public static function guardarOC(array $record, array $detalles): array
    {
        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $id = (int) ($record['id'] ?? 0);
            $codigoCompra = (string) ($record['codigo_compra'] ?? '');
            if ($id > 0) {
                $sql = 'UPDATE ordenes_compra
                        SET codigo_compra = :codigo_compra,
                            fecha = :fecha,
                            empleado_id = :empleado_id,
                            empleado_nombre = :empleado_nombre,
                            proveedor_id = :proveedor_id,
                            proveedor_label = :proveedor_label,
                            proveedor_rnc = :proveedor_rnc,
                            condicion_pago = :condicion_pago,
                            comentario = :comentario,
                            subtotal = :subtotal,
                            total_descuento = :total_descuento,
                            descuento_general_pct = :descuento_general_pct,
                            impuesto = :impuesto,
                            total_compra = :total_compra,
                            moneda = :moneda,
                            estado = :estado,
                            updated_by = :updated_by
                        WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id' => $id,
                    'codigo_compra' => $codigoCompra,
                    'fecha' => $record['fecha'] ?? date('Y-m-d'),
                    'empleado_id' => $record['empleado_id'] ?? null,
                    'empleado_nombre' => $record['empleado_nombre'] ?? '',
                    'proveedor_id' => $record['proveedor_id'] ?? 0,
                    'proveedor_label' => $record['proveedor_label'] ?? '',
                    'proveedor_rnc' => $record['proveedor_rnc'] ?? '',
                    'condicion_pago' => $record['condicion_pago'] ?? '',
                    'comentario' => $record['comentario'] ?? '',
                    'subtotal' => $record['subtotal'] ?? '0',
                    'total_descuento' => $record['total_descuento'] ?? '0',
                    'descuento_general_pct' => $record['descuento_general_pct'] ?? '0',
                    'impuesto' => $record['impuesto'] ?? '0',
                    'total_compra' => $record['total_compra'] ?? '0',
                    'moneda' => $record['moneda'] ?? 'DOP',
                    'estado' => $record['estado'] ?? 'abierta',
                    'updated_by' => $record['updated_by'] ?? null,
                ]);

                $del = $pdo->prepare('DELETE FROM ordenes_compra_detalles WHERE orden_compra_id = :id');
                $del->execute(['id' => $id]);
            } else {
                if (($record['secuencia_clave'] ?? '') !== '' || $codigoCompra === '') {
                    Secuencia::ensureExists('oc', 'Orden Compra', 'OC', 5, 1);
                    $codigoCompra = Secuencia::getNextNumberInTransaction($pdo, 'oc');
                }
                $sql = 'INSERT INTO ordenes_compra
                        (codigo_compra, fecha, empleado_id, empleado_nombre, proveedor_id, proveedor_label, proveedor_rnc,
                         condicion_pago, comentario, subtotal, total_descuento, descuento_general_pct, impuesto, total_compra,
                         moneda, estado, created_by, updated_by)
                        VALUES (:codigo_compra, :fecha, :empleado_id, :empleado_nombre, :proveedor_id, :proveedor_label, :proveedor_rnc,
                                :condicion_pago, :comentario, :subtotal, :total_descuento, :descuento_general_pct, :impuesto, :total_compra,
                                :moneda, :estado, :created_by, :updated_by)';
                $stmt = $pdo->prepare($sql);
                $createdBy = (int) ($record['created_by'] ?? 0);
                $updatedBy = (int) ($record['updated_by'] ?? 0);
                $stmt->execute([
                    'codigo_compra' => $codigoCompra,
                    'fecha' => $record['fecha'] ?? date('Y-m-d'),
                    'empleado_id' => $record['empleado_id'] ?? null,
                    'empleado_nombre' => $record['empleado_nombre'] ?? '',
                    'proveedor_id' => $record['proveedor_id'] ?? 0,
                    'proveedor_label' => $record['proveedor_label'] ?? '',
                    'proveedor_rnc' => $record['proveedor_rnc'] ?? '',
                    'condicion_pago' => $record['condicion_pago'] ?? '',
                    'comentario' => $record['comentario'] ?? '',
                    'subtotal' => $record['subtotal'] ?? '0',
                    'total_descuento' => $record['total_descuento'] ?? '0',
                    'descuento_general_pct' => $record['descuento_general_pct'] ?? '0',
                    'impuesto' => $record['impuesto'] ?? '0',
                    'total_compra' => $record['total_compra'] ?? '0',
                    'moneda' => $record['moneda'] ?? 'DOP',
                    'estado' => $record['estado'] ?? 'abierta',
                    'created_by' => $createdBy > 0 ? $createdBy : null,
                    'updated_by' => $updatedBy > 0 ? $updatedBy : null,
                ]);
                $id = (int) $pdo->lastInsertId();
            }

            if (!empty($detalles)) {
                $toNull = static function ($value) {
                    if ($value === null) {
                        return null;
                    }
                    if (is_string($value)) {
                        $value = trim($value);
                        if ($value === '') {
                            return null;
                        }
                    }
                    return $value;
                };
                $ins = $pdo->prepare('INSERT INTO ordenes_compra_detalles
                    (orden_compra_id, articulo_id, codigo, descripcion, cantidad, unidad, cant_por_unidad, peso_por_unidad, peso_unidad,
                     costo, desc_pct, impuesto_pct, total)
                    VALUES (:orden_compra_id, :articulo_id, :codigo, :descripcion, :cantidad, :unidad, :cant_por_unidad, :peso_por_unidad, :peso_unidad,
                            :costo, :desc_pct, :impuesto_pct, :total)');
                foreach ($detalles as $d) {
                    $articuloId = (int) ($d['articulo_id'] ?? 0);
                    $ins->execute([
                        'orden_compra_id' => $id,
                        'articulo_id' => $articuloId > 0 ? $articuloId : null,
                        'codigo' => $d['codigo'] ?? '',
                        'descripcion' => $d['descripcion'] ?? '',
                        'cantidad' => $d['cantidad'] ?? '0',
                        'unidad' => $d['unidad'] ?? 'u',
                        'cant_por_unidad' => $toNull($d['cant_por_unidad'] ?? null),
                        'peso_por_unidad' => $toNull($d['peso_por_unidad'] ?? null),
                        'peso_unidad' => $d['peso_unidad'] ?? 'g',
                        'costo' => $d['costo'] ?? '0',
                        'desc_pct' => $d['desc_pct'] ?? '0',
                        'impuesto_pct' => $d['impuesto_pct'] ?? '0',
                        'total' => $d['total'] ?? '0',
                    ]);
                }
            }

            $pdo->commit();
            return [
                'id' => $id,
                'codigo_compra' => $codigoCompra,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function resetAll(): void
    {
        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM ordenes_compra_detalles');
            $pdo->exec('DELETE FROM ordenes_compra');
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
