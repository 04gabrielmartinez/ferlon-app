<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;
use Throwable;

final class EntradaCompra
{
    /** @return array<int, array<string, mixed>> */
    public static function listarRegistros(): array
    {
        $sql = 'SELECT e.id,
                       e.codigo_entrada,
                       e.fecha,
                       e.proveedor_label,
                       e.total_compra,
                       e.estado,
                       oc.codigo_compra AS oc_codigo
                FROM entradas_compra e
                LEFT JOIN ordenes_compra oc ON oc.id = e.orden_compra_id
                ORDER BY e.id DESC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT e.*,
                                                oc.codigo_compra AS oc_codigo
                                         FROM entradas_compra e
                                         LEFT JOIN ordenes_compra oc ON oc.id = e.orden_compra_id
                                         WHERE e.id = :id
                                         LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $stmtDet = Db::conexion()->prepare('SELECT *
                                            FROM entradas_compra_detalles
                                            WHERE entrada_compra_id = :id
                                            ORDER BY id ASC');
        $stmtDet->execute(['id' => $id]);
        $row['detalles'] = $stmtDet->fetchAll() ?: [];

        return $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function prepararDetallesDesdeOc(int $ocId): array
    {
        $oc = OrdenCompra::buscarPorId($ocId);
        if (!$oc) {
            return [];
        }
        $detalles = is_array($oc['detalles'] ?? null) ? $oc['detalles'] : [];
        if ($detalles === []) {
            return [];
        }
        $recibidos = self::cantidadesRecibidasPorDetalle($ocId);
        $resultado = [];
        foreach ($detalles as $d) {
            $detalleId = (int) ($d['id'] ?? 0);
            $cantidadOc = (float) ($d['cantidad'] ?? 0);
            $cantidadRec = (float) ($recibidos[$detalleId] ?? 0);
            $restante = $cantidadOc - $cantidadRec;
            if ($restante <= 0) {
                continue;
            }
            $resultado[] = [
                'oc_detalle_id' => $detalleId,
                'articulo_id' => (int) ($d['articulo_id'] ?? 0),
                'codigo' => (string) ($d['codigo'] ?? ''),
                'descripcion' => (string) ($d['descripcion'] ?? ''),
                'cantidad' => (string) $restante,
                'unidad' => (string) ($d['unidad'] ?? 'u'),
                'cant_por_unidad' => (string) ($d['cant_por_unidad'] ?? ''),
                'peso_por_unidad' => (string) ($d['peso_por_unidad'] ?? ''),
                'peso_unidad' => (string) ($d['peso_unidad'] ?? 'g'),
                'costo' => (string) ($d['costo'] ?? '0'),
                'desc_pct' => (string) ($d['desc_pct'] ?? '0'),
                'impuesto_pct' => (string) ($d['impuesto_pct'] ?? '0'),
                'total' => (string) ($d['total'] ?? '0'),
            ];
        }

        return $resultado;
    }

    /** @return array<int, float> */
    private static function cantidadesRecibidasPorDetalle(int $ocId, ?int $excludeEntradaId = null): array
    {
        $sql = 'SELECT d.orden_compra_detalle_id, SUM(d.cantidad) AS total
                FROM entradas_compra_detalles d
                INNER JOIN entradas_compra e ON e.id = d.entrada_compra_id
                WHERE e.orden_compra_id = :oc_id
                  AND d.orden_compra_detalle_id IS NOT NULL';
        $params = ['oc_id' => $ocId];
        if ($excludeEntradaId !== null && $excludeEntradaId > 0) {
            $sql .= ' AND e.id <> :exclude_id';
            $params['exclude_id'] = $excludeEntradaId;
        }
        $sql .= ' GROUP BY d.orden_compra_detalle_id';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int) ($row['orden_compra_detalle_id'] ?? 0)] = (float) ($row['total'] ?? 0);
        }
        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $detalles
     * @return array{ id: int, codigo_entrada: string }
     */
    public static function guardarEntrada(array $record, array $detalles): array
    {
        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $id = (int) ($record['id'] ?? 0);
            $codigoEntrada = (string) ($record['codigo_entrada'] ?? '');
            $ocId = (int) ($record['orden_compra_id'] ?? 0);
            $proveedorId = (int) ($record['proveedor_id'] ?? 0);
            $fecha = (string) ($record['fecha'] ?? date('Y-m-d'));
            $moneda = (string) ($record['moneda'] ?? 'DOP');

            if ($ocId > 0) {
                self::validarDetallesContraOc($ocId, $detalles, $id > 0 ? $id : null);
            }

            if ($id > 0) {
                $prevDetalles = self::listarDetallesEntrada($id);
                $sql = 'UPDATE entradas_compra
                        SET codigo_entrada = :codigo_entrada,
                            fecha = :fecha,
                            empleado_id = :empleado_id,
                            empleado_nombre = :empleado_nombre,
                            proveedor_id = :proveedor_id,
                            proveedor_label = :proveedor_label,
                            proveedor_rnc = :proveedor_rnc,
                            condicion_pago = :condicion_pago,
                            ncf = :ncf,
                            orden_no = :orden_no,
                            factura_no = :factura_no,
                            pedido_no = :pedido_no,
                            comentario = :comentario,
                            subtotal = :subtotal,
                            total_descuento = :total_descuento,
                            descuento_general_pct = :descuento_general_pct,
                            impuesto = :impuesto,
                            total_compra = :total_compra,
                            moneda = :moneda,
                            estado = :estado,
                            orden_compra_id = :orden_compra_id,
                            updated_by = :updated_by
                        WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id' => $id,
                    'codigo_entrada' => $codigoEntrada,
                    'fecha' => $record['fecha'] ?? date('Y-m-d'),
                    'empleado_id' => $record['empleado_id'] ?? null,
                    'empleado_nombre' => $record['empleado_nombre'] ?? '',
                    'proveedor_id' => $record['proveedor_id'] ?? 0,
                    'proveedor_label' => $record['proveedor_label'] ?? '',
                    'proveedor_rnc' => $record['proveedor_rnc'] ?? '',
                    'condicion_pago' => $record['condicion_pago'] ?? '',
                    'ncf' => $record['ncf'] ?? '',
                    'orden_no' => $record['orden_no'] ?? '',
                    'factura_no' => $record['factura_no'] ?? '',
                    'pedido_no' => $record['pedido_no'] ?? '',
                    'comentario' => $record['comentario'] ?? '',
                    'subtotal' => $record['subtotal'] ?? '0',
                    'total_descuento' => $record['total_descuento'] ?? '0',
                    'descuento_general_pct' => $record['descuento_general_pct'] ?? '0',
                    'impuesto' => $record['impuesto'] ?? '0',
                    'total_compra' => $record['total_compra'] ?? '0',
                    'moneda' => $record['moneda'] ?? 'DOP',
                    'estado' => $record['estado'] ?? 'abierta',
                    'orden_compra_id' => $ocId > 0 ? $ocId : null,
                    'updated_by' => $record['updated_by'] ?? null,
                ]);

                $del = $pdo->prepare('DELETE FROM entradas_compra_detalles WHERE entrada_compra_id = :id');
                $del->execute(['id' => $id]);
                $pdo->prepare('DELETE FROM articulos_costo_historial WHERE entrada_compra_id = :id')->execute(['id' => $id]);
            } else {
                if (($record['secuencia_clave'] ?? '') !== '' || $codigoEntrada === '') {
                    Secuencia::ensureExists('ec', 'Entradas Compras', 'EC', 5, 1);
                    $codigoEntrada = Secuencia::getNextNumberInTransaction($pdo, 'ec');
                }
                $sql = 'INSERT INTO entradas_compra
                        (codigo_entrada, fecha, empleado_id, empleado_nombre, proveedor_id, proveedor_label, proveedor_rnc,
                         condicion_pago, ncf, orden_no, factura_no, pedido_no, comentario, subtotal, total_descuento, descuento_general_pct, impuesto, total_compra,
                         moneda, estado, orden_compra_id, created_by, updated_by)
                        VALUES (:codigo_entrada, :fecha, :empleado_id, :empleado_nombre, :proveedor_id, :proveedor_label, :proveedor_rnc,
                                :condicion_pago, :ncf, :orden_no, :factura_no, :pedido_no, :comentario, :subtotal, :total_descuento, :descuento_general_pct, :impuesto, :total_compra,
                                :moneda, :estado, :orden_compra_id, :created_by, :updated_by)';
                $stmt = $pdo->prepare($sql);
                $createdBy = (int) ($record['created_by'] ?? 0);
                $updatedBy = (int) ($record['updated_by'] ?? 0);
                $stmt->execute([
                    'codigo_entrada' => $codigoEntrada,
                    'fecha' => $record['fecha'] ?? date('Y-m-d'),
                    'empleado_id' => $record['empleado_id'] ?? null,
                    'empleado_nombre' => $record['empleado_nombre'] ?? '',
                    'proveedor_id' => $record['proveedor_id'] ?? 0,
                    'proveedor_label' => $record['proveedor_label'] ?? '',
                    'proveedor_rnc' => $record['proveedor_rnc'] ?? '',
                    'condicion_pago' => $record['condicion_pago'] ?? '',
                    'ncf' => $record['ncf'] ?? '',
                    'orden_no' => $record['orden_no'] ?? '',
                    'factura_no' => $record['factura_no'] ?? '',
                    'pedido_no' => $record['pedido_no'] ?? '',
                    'comentario' => $record['comentario'] ?? '',
                    'subtotal' => $record['subtotal'] ?? '0',
                    'total_descuento' => $record['total_descuento'] ?? '0',
                    'descuento_general_pct' => $record['descuento_general_pct'] ?? '0',
                    'impuesto' => $record['impuesto'] ?? '0',
                    'total_compra' => $record['total_compra'] ?? '0',
                    'moneda' => $record['moneda'] ?? 'DOP',
                    'estado' => $record['estado'] ?? 'abierta',
                    'orden_compra_id' => $ocId > 0 ? $ocId : null,
                    'created_by' => $createdBy > 0 ? $createdBy : null,
                    'updated_by' => $updatedBy > 0 ? $updatedBy : null,
                ]);
                $id = (int) $pdo->lastInsertId();
                $prevDetalles = [];
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
                $ins = $pdo->prepare('INSERT INTO entradas_compra_detalles
                    (entrada_compra_id, orden_compra_detalle_id, articulo_id, codigo, descripcion, cantidad, unidad, cant_por_unidad, peso_por_unidad, peso_unidad,
                     costo, desc_pct, impuesto_pct, total)
                    VALUES (:entrada_compra_id, :orden_compra_detalle_id, :articulo_id, :codigo, :descripcion, :cantidad, :unidad, :cant_por_unidad, :peso_por_unidad, :peso_unidad,
                            :costo, :desc_pct, :impuesto_pct, :total)');
                foreach ($detalles as $d) {
                    $articuloId = (int) ($d['articulo_id'] ?? 0);
                    $ocDetalleId = (int) ($d['oc_detalle_id'] ?? 0);
                    $ins->execute([
                        'entrada_compra_id' => $id,
                        'orden_compra_detalle_id' => $ocDetalleId > 0 ? $ocDetalleId : null,
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

            if (!empty($prevDetalles)) {
                foreach ($prevDetalles as $d) {
                    self::aplicarInventario($pdo, $d, -1);
                }
            }
            if (!empty($detalles)) {
                foreach ($detalles as $d) {
                    self::aplicarInventario($pdo, $d, 1);
                    self::registrarCostoHistorial($pdo, $d, $proveedorId, $ocId, $id, $fecha, $moneda);
                }
            }

            if ($ocId > 0) {
                self::actualizarEstadoOc($ocId);
            }

            $pdo->commit();
            return [
                'id' => $id,
                'codigo_entrada' => $codigoEntrada,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<int, array<string, mixed>> */
    private static function listarDetallesEntrada(int $entradaId): array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM entradas_compra_detalles WHERE entrada_compra_id = :id');
        $stmt->execute(['id' => $entradaId]);
        return $stmt->fetchAll() ?: [];
    }

    private static function aplicarInventario(\PDO $pdo, array $detalle, int $signo): void
    {
        $articuloId = (int) ($detalle['articulo_id'] ?? 0);
        if ($articuloId <= 0) {
            return;
        }
        $stmt = $pdo->prepare('SELECT maneja_inventario FROM articulos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $articuloId]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if (array_key_exists('maneja_inventario', $row) && $row['maneja_inventario'] !== null && (int) $row['maneja_inventario'] === 0) {
            return;
        }

        $cantidad = (float) ($detalle['cantidad'] ?? 0);
        $unidad = trim((string) ($detalle['unidad'] ?? 'u'));
        $cantPorUnidad = (float) ($detalle['cant_por_unidad'] ?? 0);
        $pesoPorUnidad = (float) ($detalle['peso_por_unidad'] ?? 0);
        $pesoUnidad = trim((string) ($detalle['peso_unidad'] ?? ''));
        $costo = (float) ($detalle['costo'] ?? 0);

        if ($unidad === 'u') {
            $cantPorUnidad = $cantPorUnidad > 0 ? $cantPorUnidad : 1.0;
            $delta = $cantidad * $cantPorUnidad * $signo;
            $upd = $pdo->prepare('UPDATE articulos
                                  SET stock_actual = GREATEST(0, COALESCE(stock_actual, 0) + :delta),
                                      costo_ultimo = :costo
                                  WHERE id = :id');
            $upd->execute([
                'delta' => $delta,
                'costo' => $costo,
                'id' => $articuloId,
            ]);
            return;
        }

        $totalKg = self::calcularKg($cantidad, $cantPorUnidad, $pesoPorUnidad, $pesoUnidad, $unidad);
        if ($totalKg === null) {
            throw new RuntimeException('No se pudo convertir a KG la entrada de un articulo. Completa cantidad x unidad y peso x unidad.');
        }
        $upd = $pdo->prepare('UPDATE articulos
                              SET stock_actual_kg = GREATEST(0, COALESCE(stock_actual_kg, 0) + :delta),
                                  costo_ultimo = :costo
                              WHERE id = :id');
        $upd->execute([
            'delta' => $totalKg * $signo,
            'costo' => $costo,
            'id' => $articuloId,
        ]);
    }

    private static function registrarCostoHistorial(\PDO $pdo, array $detalle, int $proveedorId, int $ocId, int $entradaId, string $fecha, string $moneda): void
    {
        $articuloId = (int) ($detalle['articulo_id'] ?? 0);
        if ($articuloId <= 0 || $proveedorId <= 0) {
            return;
        }
        $cantidad = (float) ($detalle['cantidad'] ?? 0);
        $unidad = (string) ($detalle['unidad'] ?? 'u');
        $cantPorUnidad = (float) ($detalle['cant_por_unidad'] ?? 0);
        $pesoPorUnidad = (float) ($detalle['peso_por_unidad'] ?? 0);
        $pesoUnidad = (string) ($detalle['peso_unidad'] ?? '');
        $totalKg = self::calcularKg($cantidad, $cantPorUnidad, $pesoPorUnidad, $pesoUnidad, $unidad);
        $costo = (float) ($detalle['costo'] ?? 0);
        $totalLinea = (float) ($detalle['total'] ?? 0);

        $ins = $pdo->prepare('INSERT INTO articulos_costo_historial
            (articulo_id, proveedor_id, entrada_compra_id, orden_compra_id, fecha, cantidad, unidad, cant_por_unidad, peso_por_unidad, peso_unidad,
             total_kg, costo_unitario, moneda, total_linea)
            VALUES (:articulo_id, :proveedor_id, :entrada_compra_id, :orden_compra_id, :fecha, :cantidad, :unidad, :cant_por_unidad, :peso_por_unidad, :peso_unidad,
                    :total_kg, :costo_unitario, :moneda, :total_linea)');
        $ins->execute([
            'articulo_id' => $articuloId,
            'proveedor_id' => $proveedorId,
            'entrada_compra_id' => $entradaId,
            'orden_compra_id' => $ocId > 0 ? $ocId : null,
            'fecha' => $fecha,
            'cantidad' => $cantidad,
            'unidad' => $unidad,
            'cant_por_unidad' => $cantPorUnidad > 0 ? $cantPorUnidad : null,
            'peso_por_unidad' => $pesoPorUnidad > 0 ? $pesoPorUnidad : null,
            'peso_unidad' => $pesoUnidad !== '' ? $pesoUnidad : null,
            'total_kg' => $totalKg,
            'costo_unitario' => $costo,
            'moneda' => $moneda,
            'total_linea' => $totalLinea,
        ]);
    }

    private static function calcularKg(float $cantidad, float $cantPorUnidad, float $pesoPorUnidad, string $pesoUnidad, string $unidad): ?float
    {
        $unidad = trim(strtolower($unidad));
        $pesoUnidad = trim(strtolower($pesoUnidad));

        if ($unidad === 'u') {
            return null;
        }

        if ($pesoPorUnidad <= 0) {
            if (in_array($unidad, ['kg', 'g', 'lb', 'oz'], true)) {
                $pesoPorUnidad = 1.0;
                $cantPorUnidad = max(1.0, $cantPorUnidad);
                $pesoUnidad = $unidad;
            } else {
                return null;
            }
        }

        $cantPorUnidad = $cantPorUnidad > 0 ? $cantPorUnidad : 1.0;
        $totalPeso = $cantidad * $cantPorUnidad * $pesoPorUnidad;
        if ($totalPeso <= 0) {
            return null;
        }

        $unidadBase = $pesoUnidad !== '' ? $pesoUnidad : $unidad;
        $factor = match ($unidadBase) {
            'kg' => 1.0,
            'g' => 0.001,
            'lb' => 0.45359237,
            'oz' => 0.028349523125,
            default => null,
        };
        if ($factor === null) {
            return null;
        }

        return $totalPeso * $factor;
    }

    private static function validarDetallesContraOc(int $ocId, array $detalles, ?int $excludeEntradaId): void
    {
        $oc = OrdenCompra::buscarPorId($ocId);
        if (!$oc) {
            throw new RuntimeException('Orden de compra no valida para esta entrada.');
        }
        $ocDetalles = is_array($oc['detalles'] ?? null) ? $oc['detalles'] : [];
        $ocMap = [];
        foreach ($ocDetalles as $d) {
            $ocMap[(int) ($d['id'] ?? 0)] = (float) ($d['cantidad'] ?? 0);
        }
        $recibidos = self::cantidadesRecibidasPorDetalle($ocId, $excludeEntradaId);
        foreach ($detalles as $d) {
            $ocDetalleId = (int) ($d['oc_detalle_id'] ?? 0);
            if ($ocDetalleId <= 0) {
                // Articulo adicional fuera de OC: permitido.
                continue;
            }
            if (!array_key_exists($ocDetalleId, $ocMap)) {
                throw new RuntimeException('Hay articulos sin relacion valida con la orden de compra.');
            }
            $cantidad = (float) ($d['cantidad'] ?? 0);
            $ya = (float) ($recibidos[$ocDetalleId] ?? 0);
            $limite = (float) $ocMap[$ocDetalleId];
            if ($cantidad + $ya > $limite + 0.00001) {
                throw new RuntimeException('La cantidad recibida excede lo pendiente en la orden de compra.');
            }
        }
    }

    private static function actualizarEstadoOc(int $ocId): void
    {
        $oc = OrdenCompra::buscarPorId($ocId);
        if (!$oc) {
            return;
        }
        $ocDetalles = is_array($oc['detalles'] ?? null) ? $oc['detalles'] : [];
        if ($ocDetalles === []) {
            return;
        }
        $recibidos = self::cantidadesRecibidasPorDetalle($ocId, null);
        $restante = 0.0;
        foreach ($ocDetalles as $d) {
            $detalleId = (int) ($d['id'] ?? 0);
            $cantidad = (float) ($d['cantidad'] ?? 0);
            $ya = (float) ($recibidos[$detalleId] ?? 0);
            $restante += max(0.0, $cantidad - $ya);
        }
        $estado = $restante <= 0.00001 ? 'cerrada' : 'parcial';
        $stmt = Db::conexion()->prepare('UPDATE ordenes_compra SET estado = :estado WHERE id = :id');
        $stmt->execute([
            'estado' => $estado,
            'id' => $ocId,
        ]);
    }
}
