<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Models\Secuencia;
use DateTime;
use RuntimeException;

final class Cotizacion
{
    /** @return array<int, array<string, mixed>> */
    public static function listarRegistros(): array
    {
        $sql = "SELECT c.id,
                       c.codigo_cotizacion,
                       c.fecha,
                       c.cliente_nombre,
                       c.empleado_nombre,
                       c.total,
                       c.estado
                FROM cotizaciones c
                ORDER BY c.id DESC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM cotizaciones WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $det = Db::conexion()->prepare("SELECT d.id,
                                               d.articulo_id,
                                               d.presentacion_id,
                                               d.empaque_id,
                                               d.cantidad,
                                               d.precio,
                                               d.descuento_pct,
                                               d.impuesto_pct,
                                               d.total,
                                               d.articulo_codigo,
                                               d.articulo_descripcion,
                                               d.presentacion_descripcion,
                                               d.empaque_descripcion,
                                               TRIM(COALESCE(a.impuestos, '')) AS impuestos
                                        FROM cotizacion_detalles d
                                        LEFT JOIN articulos a ON a.id = d.articulo_id
                                        WHERE d.cotizacion_id = :id
                                        ORDER BY d.id ASC");
        $det->execute(['id' => $id]);
        $row['detalles'] = $det->fetchAll() ?: [];

        return $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarVariantesConPrecio(): array
    {
        $sql = "SELECT r.id AS receta_id,
                       a.id AS articulo_id,
                       a.codigo AS articulo_codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS articulo_descripcion,
                       r.presentacion_id,
                       p.descripcion AS presentacion_descripcion,
                       r.empaque_id,
                       e.descripcion AS empaque_descripcion,
                       r.precio_venta,
                       TRIM(COALESCE(a.impuestos, '')) AS impuestos,
                       COALESCE(v.stock_actual, 0) AS stock_actual
                FROM recetas_producto_final r
                INNER JOIN articulos a ON a.id = r.producto_articulo_id
                LEFT JOIN presentaciones p ON p.id = r.presentacion_id
                LEFT JOIN empaques e ON e.id = r.empaque_id
                LEFT JOIN articulos_variantes_stock v
                       ON v.articulo_id = r.producto_articulo_id
                      AND v.presentacion_id = r.presentacion_id
                      AND v.empaque_id = r.empaque_id
                WHERE a.tiene_receta = 1
                  AND a.estado = 'activo'
                  AND COALESCE(r.precio_venta, 0) > 0
                ORDER BY a.descripcion ASC, p.descripcion ASC, e.descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function precioVentaPorVariante(int $articuloId, int $presentacionId, int $empaqueId): ?float
    {
        if ($articuloId <= 0) {
            return null;
        }
        $sql = 'SELECT precio_venta
                FROM recetas_producto_final
                WHERE producto_articulo_id = :articulo_id
                  AND presentacion_id = :presentacion_id
                  AND empaque_id = :empaque_id
                LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'articulo_id' => $articuloId,
            'presentacion_id' => $presentacionId,
            'empaque_id' => $empaqueId,
        ]);
        $precio = $stmt->fetchColumn();
        if ($precio === false || $precio === null) {
            return null;
        }
        return (float) $precio;
    }

    /**
     * @param array<int, array<string, mixed>> $detalles
     * @return array{subtotal: float, desc_lineas: float, desc_general: float, impuesto: float, total: float}
     */
    private static function calcularTotalesConImpuesto(array $detalles, float $descGeneralPct, bool $aplicaItbis): array
    {
        $subtotal = 0.0;
        $descLineas = 0.0;
        $lineNetos = [];

        foreach ($detalles as $idx => $d) {
            $cant = (float) ($d['cantidad'] ?? 0);
            $precio = (float) ($d['precio'] ?? 0);
            $bruto = $cant * $precio;
            $descPct = (float) ($d['descuento_pct'] ?? 0);
            $descMonto = $bruto * ($descPct / 100);
            $subtotal += $bruto;
            $descLineas += $descMonto;
            $lineNetos[$idx] = max(0.0, $bruto - $descMonto);
        }

        $subtotalNeto = max(0.0, $subtotal - $descLineas);
        $descGeneral = $subtotalNeto * ($descGeneralPct / 100);

        $impuesto = 0.0;
        $totalNeto = max(0.0, $subtotalNeto - $descGeneral);
        $totalBase = array_sum($lineNetos);

        if (!$aplicaItbis) {
            return [
                'subtotal' => $subtotal,
                'desc_lineas' => $descLineas,
                'desc_general' => $descGeneral,
                'impuesto' => 0.0,
                'total' => $totalNeto,
            ];
        }

        foreach ($detalles as $idx => $d) {
            $lineNeto = $lineNetos[$idx] ?? 0.0;
            if ($lineNeto <= 0) {
                continue;
            }
            $proporcion = $totalBase > 0 ? ($lineNeto / $totalBase) : 0;
            $lineaBase = max(0.0, $lineNeto - ($descGeneral * $proporcion));
            $impuestoPct = self::calcImpuestoPct((string) ($d['impuestos'] ?? ''));
            $impuesto += $lineaBase * ($impuestoPct / 100);
        }

        return [
            'subtotal' => $subtotal,
            'desc_lineas' => $descLineas,
            'desc_general' => $descGeneral,
            'impuesto' => $impuesto,
            'total' => $totalNeto + $impuesto,
        ];
    }

    /**
     * @param int[] $articuloIds
     * @return array<int, string>
     */
    private static function impuestosPorArticulos(array $articuloIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $articuloIds), static fn (int $v): bool => $v > 0));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Db::conexion()->prepare("SELECT id, TRIM(COALESCE(impuestos, '')) AS impuestos FROM articulos WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll() ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int) ($row['id'] ?? 0)] = (string) ($row['impuestos'] ?? '');
        }
        return $map;
    }

    private static function calcImpuestoPct(string $rawImpuestos): float
    {
        $txt = strtoupper(trim($rawImpuestos));
        if ($txt === '') {
            return 0.0;
        }
        return str_contains($txt, 'ITBIS') ? 18.0 : 0.0;
    }

    /**
     * @param array<string, mixed> $record
     * @param array<int, array<string, mixed>> $detalles
     * @return array{ id: int, codigo_cotizacion: string }
     */
    public static function guardarCotizacion(array $record, array $detalles): array
    {
        if ($detalles === []) {
            throw new RuntimeException('Debes agregar al menos un articulo a la cotizacion.');
        }

        $clienteId = (int) ($record['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            throw new RuntimeException('Selecciona un cliente para la cotizacion.');
        }

        $subtotalBruto = 0.0;
        $descuentoLineas = 0.0;
        foreach ($detalles as &$d) {
            $cant = self::enteroPositivo($d['cantidad'] ?? null, 'La cantidad debe ser mayor que cero.');
            $precio = self::decimalPositivo($d['precio'] ?? null, 'El precio debe ser mayor que cero.');
            $descuentoPct = self::decimalOrCero($d['descuento_pct'] ?? 0);
            if ($descuentoPct < 0 || $descuentoPct > 100) {
                throw new RuntimeException('El descuento debe estar entre 0 y 100.');
            }
            $d['cantidad'] = $cant;
            $d['precio'] = $precio;
            $lineaBruto = $cant * $precio;
            $descuentoMonto = $lineaBruto * ($descuentoPct / 100);
            $d['descuento_pct'] = $descuentoPct;
            $d['descuento_monto'] = $descuentoMonto;
            $d['total'] = $lineaBruto - $descuentoMonto;
            $subtotalBruto += $lineaBruto;
            $descuentoLineas += $descuentoMonto;
        }
        unset($d);

        $subtotalNeto = $subtotalBruto - $descuentoLineas;
        $descuentoGeneralPct = self::decimalOrCero($record['descuento_general_pct'] ?? 0);
        if ($descuentoGeneralPct < 0 || $descuentoGeneralPct > 100) {
            throw new RuntimeException('El descuento general debe estar entre 0 y 100.');
        }
        $descuentoGeneralMonto = $subtotalNeto * ($descuentoGeneralPct / 100);
        $baseImponible = $subtotalNeto - $descuentoGeneralMonto;
        $articuloIds = array_map(static fn (array $d): int => (int) ($d['articulo_id'] ?? 0), $detalles);
        $impuestosMap = self::impuestosPorArticulos($articuloIds);
        foreach ($detalles as $idx => $d) {
            $aid = (int) ($d['articulo_id'] ?? 0);
            $detalles[$idx]['impuestos'] = $impuestosMap[$aid] ?? '';
        }
        $aplicaItbis = ((int) ($record['cliente_aplica_itbis'] ?? 1) === 1)
            && ((int) ($record['cliente_exento_itbis'] ?? 0) === 0);
        $totales = self::calcularTotalesConImpuesto($detalles, $descuentoGeneralPct, $aplicaItbis);
        $impuestoPct = 0.0;
        $impuestoMonto = $totales['impuesto'];
        $total = $totales['total'];

        $fechas = self::resolverFechas((string) ($record['fecha'] ?? date('Y-m-d')), (int) ($record['validez_dias'] ?? 0), (string) ($record['fecha_vencimiento'] ?? ''));

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $codigo = (string) ($record['codigo_cotizacion'] ?? '');
            if (($record['secuencia_clave'] ?? '') !== '' || $codigo === '') {
                Secuencia::ensureExists('ct', 'Cotizacion', 'CT', 5, 1);
                $codigo = Secuencia::getNextNumberInTransaction($pdo, 'ct');
            }

            $stmt = $pdo->prepare('INSERT INTO cotizaciones
                (codigo_cotizacion, fecha, fecha_vencimiento, validez_dias, estado, moneda, cliente_id, cliente_nombre, cliente_telefono, cliente_rnc, localidad_id, localidad_nombre, empleado_id, empleado_nombre, comentario, condiciones, subtotal, descuento_lineas, descuento_general_pct, descuento_general_monto, impuesto_pct, impuesto, total, created_by, updated_by)
                VALUES (:codigo, :fecha, :fecha_vencimiento, :validez_dias, :estado, :moneda, :cliente_id, :cliente_nombre, :cliente_telefono, :cliente_rnc, :localidad_id, :localidad_nombre, :empleado_id, :empleado_nombre, :comentario, :condiciones, :subtotal, :descuento_lineas, :descuento_general_pct, :descuento_general_monto, :impuesto_pct, :impuesto, :total, :created_by, :updated_by)');
            $stmt->execute([
                'codigo' => $codigo,
                'fecha' => $fechas['fecha'],
                'fecha_vencimiento' => $fechas['fecha_vencimiento'],
                'validez_dias' => $fechas['validez_dias'],
                'estado' => (string) ($record['estado'] ?? 'borrador'),
                'moneda' => (string) ($record['moneda'] ?? 'DOP'),
                'cliente_id' => $clienteId,
                'cliente_nombre' => (string) ($record['cliente_nombre'] ?? ''),
                'cliente_telefono' => (string) ($record['cliente_telefono'] ?? ''),
                'cliente_rnc' => (string) ($record['cliente_rnc'] ?? ''),
                'localidad_id' => (int) ($record['localidad_id'] ?? 0) ?: null,
                'localidad_nombre' => (string) ($record['localidad_nombre'] ?? ''),
                'empleado_id' => (int) ($record['empleado_id'] ?? 0) ?: null,
                'empleado_nombre' => (string) ($record['empleado_nombre'] ?? ''),
                'comentario' => (string) ($record['comentario'] ?? ''),
                'condiciones' => (string) ($record['condiciones'] ?? ''),
                'subtotal' => $subtotalBruto,
                'descuento_lineas' => $descuentoLineas,
                'descuento_general_pct' => $descuentoGeneralPct,
                'descuento_general_monto' => $descuentoGeneralMonto,
                'impuesto_pct' => $impuestoPct,
                'impuesto' => $impuestoMonto,
                'total' => $total,
                'created_by' => (int) ($record['created_by'] ?? 0) ?: null,
                'updated_by' => (int) ($record['updated_by'] ?? 0) ?: null,
            ]);
            $cotizacionId = (int) $pdo->lastInsertId();

            $insDet = $pdo->prepare('INSERT INTO cotizacion_detalles
                (cotizacion_id, articulo_id, presentacion_id, empaque_id, cantidad, precio, descuento_pct, descuento_monto, impuesto_pct, total, articulo_codigo, articulo_descripcion, presentacion_descripcion, empaque_descripcion)
                VALUES (:cotizacion_id, :articulo_id, :presentacion_id, :empaque_id, :cantidad, :precio, :descuento_pct, :descuento_monto, :impuesto_pct, :total, :articulo_codigo, :articulo_descripcion, :presentacion_descripcion, :empaque_descripcion)');
            foreach ($detalles as $d) {
                $insDet->execute([
                    'cotizacion_id' => $cotizacionId,
                    'articulo_id' => (int) ($d['articulo_id'] ?? 0),
                    'presentacion_id' => (int) ($d['presentacion_id'] ?? 0),
                    'empaque_id' => (int) ($d['empaque_id'] ?? 0),
                    'cantidad' => (int) ($d['cantidad'] ?? 0),
                    'precio' => (float) ($d['precio'] ?? 0),
                    'descuento_pct' => (float) ($d['descuento_pct'] ?? 0),
                    'descuento_monto' => (float) ($d['descuento_monto'] ?? 0),
                    'impuesto_pct' => (float) ($d['impuesto_pct'] ?? 0),
                    'total' => (float) ($d['total'] ?? 0),
                    'articulo_codigo' => (string) ($d['articulo_codigo'] ?? ''),
                    'articulo_descripcion' => (string) ($d['articulo_descripcion'] ?? ''),
                    'presentacion_descripcion' => (string) ($d['presentacion_descripcion'] ?? ''),
                    'empaque_descripcion' => (string) ($d['empaque_descripcion'] ?? ''),
                ]);
            }

            $pdo->commit();

            return ['id' => $cotizacionId, 'codigo_cotizacion' => $codigo];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $record
     * @param array<int, array<string, mixed>> $detalles
     * @return array{ id: int, codigo_cotizacion: string }
     */
    public static function actualizarCotizacion(array $record, array $detalles): array
    {
        $id = (int) ($record['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Cotizacion no valida.');
        }
        if ($detalles === []) {
            throw new RuntimeException('Debes agregar al menos un articulo a la cotizacion.');
        }
        $clienteId = (int) ($record['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            throw new RuntimeException('Selecciona un cliente para la cotizacion.');
        }

        $subtotalBruto = 0.0;
        $descuentoLineas = 0.0;
        foreach ($detalles as &$d) {
            $cant = self::enteroPositivo($d['cantidad'] ?? null, 'La cantidad debe ser mayor que cero.');
            $precio = self::decimalPositivo($d['precio'] ?? null, 'El precio debe ser mayor que cero.');
            $descuentoPct = self::decimalOrCero($d['descuento_pct'] ?? 0);
            if ($descuentoPct < 0 || $descuentoPct > 100) {
                throw new RuntimeException('El descuento debe estar entre 0 y 100.');
            }
            $d['cantidad'] = $cant;
            $d['precio'] = $precio;
            $lineaBruto = $cant * $precio;
            $descuentoMonto = $lineaBruto * ($descuentoPct / 100);
            $d['descuento_pct'] = $descuentoPct;
            $d['descuento_monto'] = $descuentoMonto;
            $d['total'] = $lineaBruto - $descuentoMonto;
            $subtotalBruto += $lineaBruto;
            $descuentoLineas += $descuentoMonto;
        }
        unset($d);

        $subtotalNeto = $subtotalBruto - $descuentoLineas;
        $descuentoGeneralPct = self::decimalOrCero($record['descuento_general_pct'] ?? 0);
        if ($descuentoGeneralPct < 0 || $descuentoGeneralPct > 100) {
            throw new RuntimeException('El descuento general debe estar entre 0 y 100.');
        }
        $descuentoGeneralMonto = $subtotalNeto * ($descuentoGeneralPct / 100);
        $baseImponible = $subtotalNeto - $descuentoGeneralMonto;
        $articuloIds = array_map(static fn (array $d): int => (int) ($d['articulo_id'] ?? 0), $detalles);
        $impuestosMap = self::impuestosPorArticulos($articuloIds);
        foreach ($detalles as $idx => $d) {
            $aid = (int) ($d['articulo_id'] ?? 0);
            $detalles[$idx]['impuestos'] = $impuestosMap[$aid] ?? '';
        }
        $aplicaItbis = ((int) ($record['cliente_aplica_itbis'] ?? 1) === 1)
            && ((int) ($record['cliente_exento_itbis'] ?? 0) === 0);
        $totales = self::calcularTotalesConImpuesto($detalles, $descuentoGeneralPct, $aplicaItbis);
        $impuestoPct = 0.0;
        $impuestoMonto = $totales['impuesto'];
        $total = $totales['total'];

        $fechas = self::resolverFechas((string) ($record['fecha'] ?? date('Y-m-d')), (int) ($record['validez_dias'] ?? 0), (string) ($record['fecha_vencimiento'] ?? ''));

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT codigo_cotizacion FROM cotizaciones WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new RuntimeException('Cotizacion no encontrada.');
            }
            $codigo = (string) ($row['codigo_cotizacion'] ?? '');

            $upd = $pdo->prepare('UPDATE cotizaciones
                SET fecha = :fecha,
                    fecha_vencimiento = :fecha_vencimiento,
                    validez_dias = :validez_dias,
                    estado = :estado,
                    moneda = :moneda,
                    cliente_id = :cliente_id,
                    cliente_nombre = :cliente_nombre,
                    cliente_telefono = :cliente_telefono,
                    cliente_rnc = :cliente_rnc,
                    localidad_id = :localidad_id,
                    localidad_nombre = :localidad_nombre,
                    empleado_id = :empleado_id,
                    empleado_nombre = :empleado_nombre,
                    comentario = :comentario,
                    condiciones = :condiciones,
                    subtotal = :subtotal,
                    descuento_lineas = :descuento_lineas,
                    descuento_general_pct = :descuento_general_pct,
                    descuento_general_monto = :descuento_general_monto,
                    impuesto_pct = :impuesto_pct,
                    impuesto = :impuesto,
                    total = :total,
                    updated_by = :updated_by
                WHERE id = :id');
            $upd->execute([
                'id' => $id,
                'fecha' => $fechas['fecha'],
                'fecha_vencimiento' => $fechas['fecha_vencimiento'],
                'validez_dias' => $fechas['validez_dias'],
                'estado' => (string) ($record['estado'] ?? 'borrador'),
                'moneda' => (string) ($record['moneda'] ?? 'DOP'),
                'cliente_id' => $clienteId,
                'cliente_nombre' => (string) ($record['cliente_nombre'] ?? ''),
                'cliente_telefono' => (string) ($record['cliente_telefono'] ?? ''),
                'cliente_rnc' => (string) ($record['cliente_rnc'] ?? ''),
                'localidad_id' => (int) ($record['localidad_id'] ?? 0) ?: null,
                'localidad_nombre' => (string) ($record['localidad_nombre'] ?? ''),
                'empleado_id' => (int) ($record['empleado_id'] ?? 0) ?: null,
                'empleado_nombre' => (string) ($record['empleado_nombre'] ?? ''),
                'comentario' => (string) ($record['comentario'] ?? ''),
                'condiciones' => (string) ($record['condiciones'] ?? ''),
                'subtotal' => $subtotalBruto,
                'descuento_lineas' => $descuentoLineas,
                'descuento_general_pct' => $descuentoGeneralPct,
                'descuento_general_monto' => $descuentoGeneralMonto,
                'impuesto_pct' => $impuestoPct,
                'impuesto' => $impuestoMonto,
                'total' => $total,
                'updated_by' => (int) ($record['updated_by'] ?? 0) ?: null,
            ]);

            $pdo->prepare('DELETE FROM cotizacion_detalles WHERE cotizacion_id = :id')->execute(['id' => $id]);
            $insDet = $pdo->prepare('INSERT INTO cotizacion_detalles
                (cotizacion_id, articulo_id, presentacion_id, empaque_id, cantidad, precio, descuento_pct, descuento_monto, impuesto_pct, total, articulo_codigo, articulo_descripcion, presentacion_descripcion, empaque_descripcion)
                VALUES (:cotizacion_id, :articulo_id, :presentacion_id, :empaque_id, :cantidad, :precio, :descuento_pct, :descuento_monto, :impuesto_pct, :total, :articulo_codigo, :articulo_descripcion, :presentacion_descripcion, :empaque_descripcion)');
            foreach ($detalles as $d) {
                $insDet->execute([
                    'cotizacion_id' => $id,
                    'articulo_id' => (int) ($d['articulo_id'] ?? 0),
                    'presentacion_id' => (int) ($d['presentacion_id'] ?? 0),
                    'empaque_id' => (int) ($d['empaque_id'] ?? 0),
                    'cantidad' => (int) ($d['cantidad'] ?? 0),
                    'precio' => (float) ($d['precio'] ?? 0),
                    'descuento_pct' => (float) ($d['descuento_pct'] ?? 0),
                    'descuento_monto' => (float) ($d['descuento_monto'] ?? 0),
                    'impuesto_pct' => (float) ($d['impuesto_pct'] ?? 0),
                    'total' => (float) ($d['total'] ?? 0),
                    'articulo_codigo' => (string) ($d['articulo_codigo'] ?? ''),
                    'articulo_descripcion' => (string) ($d['articulo_descripcion'] ?? ''),
                    'presentacion_descripcion' => (string) ($d['presentacion_descripcion'] ?? ''),
                    'empaque_descripcion' => (string) ($d['empaque_descripcion'] ?? ''),
                ]);
            }

            $pdo->commit();
            return ['id' => $id, 'codigo_cotizacion' => $codigo];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function eliminar(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM cotizacion_detalles WHERE cotizacion_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM cotizaciones WHERE id = :id')->execute(['id' => $id]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function resolverFechas(string $fecha, int $validezDias, string $fechaVencimiento): array
    {
        $fecha = trim($fecha) !== '' ? $fecha : date('Y-m-d');
        $validezDias = max(0, $validezDias);
        $fechaVencimiento = trim($fechaVencimiento);
        if ($fechaVencimiento === '' && $validezDias > 0) {
            try {
                $dt = new DateTime($fecha);
                $dt->modify('+' . $validezDias . ' days');
                $fechaVencimiento = $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                $fechaVencimiento = '';
            }
        }
        return [
            'fecha' => $fecha,
            'validez_dias' => $validezDias,
            'fecha_vencimiento' => $fechaVencimiento !== '' ? $fechaVencimiento : null,
        ];
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

    private static function decimalOrCero(mixed $value): float
    {
        $txt = trim((string) $value);
        if ($txt === '') {
            return 0.0;
        }
        return is_numeric($txt) ? (float) $txt : 0.0;
    }
}
