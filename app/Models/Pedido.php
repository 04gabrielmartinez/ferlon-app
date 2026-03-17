<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Pedido
{
    /** @return array<int, array<string, mixed>> */
    public static function listarEnProceso(): array
    {
        $departamentoExpr = self::hasColumn('pedidos', 'departamento')
            ? "LOWER(COALESCE(NULLIF(TRIM(p.departamento), ''), 'almacen'))"
            : "'almacen'";
        $vistoExpr = self::hasColumn('pedidos', 'visto')
            ? 'CASE WHEN COALESCE(p.visto, 0) = 1 THEN 1 ELSE 0 END'
            : '0';

        $sql = "SELECT p.id,
                       p.codigo_pedido,
                       p.orden_no,
                       p.comentario,
                       p.fecha,
                       p.cliente_nombre,
                       p.empleado_nombre,
                       {$departamentoExpr} AS departamento,
                       {$vistoExpr} AS visto
                FROM pedidos p
                ORDER BY p.id DESC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function obtenerDatosGestion(int $pedidoId): ?array
    {
        if ($pedidoId <= 0) {
            return null;
        }

        $departamentoExpr = self::hasColumn('pedidos', 'departamento')
            ? "LOWER(COALESCE(NULLIF(TRIM(p.departamento), ''), 'almacen'))"
            : "'almacen'";
        $vistoExpr = self::hasColumn('pedidos', 'visto')
            ? 'CASE WHEN COALESCE(p.visto, 0) = 1 THEN 1 ELSE 0 END'
            : '0';

        $sql = "SELECT p.id,
                       p.codigo_pedido,
                       p.orden_no,
                       p.fecha,
                       p.cliente_nombre,
                       p.cliente_rnc,
                       p.cliente_telefono,
                       p.empleado_nombre,
                       p.comentario,
                       {$departamentoExpr} AS departamento,
                       {$vistoExpr} AS visto
                FROM pedidos p
                WHERE p.id = :id
                LIMIT 1";
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $pedidoId]);
        $pedido = $stmt->fetch();
        if (!$pedido) {
            return null;
        }

        $detallesStmt = Db::conexion()->prepare("SELECT d.id,
                                                        d.articulo_codigo AS codigo,
                                                        d.articulo_descripcion AS descripcion,
                                                        d.cantidad AS cantidad_pedida,
                                                        COALESCE(s.stock_actual, 0) AS stock
                                                 FROM pedido_detalles d
                                                 LEFT JOIN articulos_variantes_stock s
                                                        ON s.articulo_id = d.articulo_id
                                                       AND s.presentacion_id = d.presentacion_id
                                                       AND s.empaque_id = d.empaque_id
                                                 WHERE d.pedido_id = :pedido_id
                                                 ORDER BY d.id ASC");
        $detallesStmt->execute(['pedido_id' => $pedidoId]);
        $productos = [];
        foreach ($detallesStmt->fetchAll() ?: [] as $row) {
            $productos[] = [
                'detalle_id' => (int) ($row['id'] ?? 0),
                'codigo' => (string) ($row['codigo'] ?? ''),
                'descripcion' => (string) ($row['descripcion'] ?? ''),
                'cantidad_pedida' => (float) ($row['cantidad_pedida'] ?? 0),
                'stock' => (float) ($row['stock'] ?? 0),
                'cantidad_solicitar' => (float) ($row['cantidad_pedida'] ?? 0),
            ];
        }

        $departamento = strtolower(trim((string) ($pedido['departamento'] ?? 'almacen')));
        if (!in_array($departamento, ['almacen', 'facturacion'], true)) {
            $departamento = 'almacen';
        }

        return [
            'id' => (int) ($pedido['id'] ?? 0),
            'numero_pedido' => (string) ($pedido['codigo_pedido'] ?? ''),
            'orden' => (string) ($pedido['orden_no'] ?? ''),
            'cliente' => (string) ($pedido['cliente_nombre'] ?? ''),
            'cedula_rnc' => (string) ($pedido['cliente_rnc'] ?? ''),
            'telefono' => (string) ($pedido['cliente_telefono'] ?? ''),
            'empleado' => (string) ($pedido['empleado_nombre'] ?? ''),
            'fecha_pedido' => (string) ($pedido['fecha'] ?? ''),
            'estado_visto' => ((int) ($pedido['visto'] ?? 0) === 1) ? 'visto' : 'no_visto',
            'departamento' => $departamento,
            'departamento_actual' => $departamento,
            'comentario' => (string) ($pedido['comentario'] ?? ''),
            'acciones_disponibles' => self::accionesDisponibles($departamento),
            'total_items' => count($productos),
            'productos' => $productos,
            'historial' => self::listarHistorial($pedidoId),
        ];
    }

    /** @param array<int, array<string, mixed>> $cantidades */
    public static function guardarGestion(
        int $pedidoId,
        string $accion,
        string $comentario,
        array $cantidades,
        int $userId,
        string $userName
    ): array {
        if ($pedidoId <= 0) {
            throw new RuntimeException('Pedido no valido.');
        }
        $accion = trim($accion);
        if ($accion === '') {
            throw new RuntimeException('Debes seleccionar una accion.');
        }

        $actual = self::obtenerDatosGestion($pedidoId);
        if (!$actual) {
            throw new RuntimeException('Pedido no encontrado.');
        }

        $accionesDisponibles = self::accionesDisponibles((string) ($actual['departamento'] ?? 'almacen'));
        $accionCfg = null;
        foreach ($accionesDisponibles as $opt) {
            if ((string) ($opt['value'] ?? '') === $accion) {
                $accionCfg = $opt;
                break;
            }
        }
        if (!is_array($accionCfg)) {
            throw new RuntimeException('La accion seleccionada no esta disponible para este pedido.');
        }
        if (!empty($accionCfg['requires_comment']) && trim($comentario) === '') {
            throw new RuntimeException('Esta accion requiere un comentario.');
        }

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $productos = is_array($actual['productos'] ?? null) ? $actual['productos'] : [];
            $productosMap = [];
            foreach ($productos as $prod) {
                $detalleId = (int) ($prod['detalle_id'] ?? 0);
                if ($detalleId > 0) {
                    $productosMap[$detalleId] = $prod;
                }
            }

            $cantidadesValidadas = [];
            foreach ($cantidades as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $detalleId = (int) ($row['detalle_id'] ?? 0);
                $cantidad = (float) ($row['cantidad_solicitar'] ?? 0);
                if ($detalleId <= 0 || !isset($productosMap[$detalleId])) {
                    continue;
                }
                if ($cantidad < 0) {
                    throw new RuntimeException('No se permiten cantidades negativas.');
                }
                $max = (float) ($productosMap[$detalleId]['cantidad_pedida'] ?? 0);
                if ($cantidad > $max) {
                    throw new RuntimeException('La cantidad a solicitar no puede exceder la cantidad pedida.');
                }
                $cantidadesValidadas[] = [
                    'detalle_id' => $detalleId,
                    'cantidad_solicitar' => $cantidad,
                ];
            }

            $targetDepartamento = trim((string) ($accionCfg['target_departamento'] ?? ''));
            $updateSql = 'UPDATE pedidos SET updated_by = :user_id';
            $payload = [
                'id' => $pedidoId,
                'user_id' => $userId > 0 ? $userId : null,
            ];
            if ($targetDepartamento !== '' && self::hasColumn('pedidos', 'departamento')) {
                $updateSql .= ', departamento = :departamento';
                $payload['departamento'] = $targetDepartamento;
            }
            if (self::hasColumn('pedidos', 'visto')) {
                // Al ejecutar accion, el pedido vuelve a no visto para el nuevo departamento.
                $updateSql .= ', visto = 0';
            }
            $updateSql .= ' WHERE id = :id';
            $pdo->prepare($updateSql)->execute($payload);

            self::insertHistorial(
                $pdo,
                $pedidoId,
                $userId,
                $userName,
                (string) ($accionCfg['label'] ?? $accion),
                'Accion administrativa ejecutada.',
                $comentario,
                $cantidadesValidadas
            );

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $fresh = self::obtenerDatosGestion($pedidoId);
        if (!$fresh) {
            throw new RuntimeException('No se pudo recargar el pedido actualizado.');
        }

        return $fresh;
    }

    public static function marcarVisto(int $pedidoId, string $departamento, int $userId, string $userName): bool
    {
        if ($pedidoId <= 0 || !self::hasColumn('pedidos', 'visto')) {
            return false;
        }

        $pdo = Db::conexion();
        $sql = 'UPDATE pedidos SET visto = 1, updated_by = :user_id WHERE id = :id';
        $payload = [
            'id' => $pedidoId,
            'user_id' => $userId > 0 ? $userId : null,
        ];
        if (self::hasColumn('pedidos', 'departamento')) {
            $dep = strtolower(trim($departamento));
            if (!in_array($dep, ['almacen', 'facturacion'], true)) {
                $dep = 'almacen';
            }
            $sql .= " AND LOWER(COALESCE(NULLIF(TRIM(departamento), ''), 'almacen')) = :departamento";
            $payload['departamento'] = $dep;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($payload);
        $updated = $stmt->rowCount() > 0;
        if ($updated) {
            self::insertHistorial(
                $pdo,
                $pedidoId,
                $userId,
                $userName,
                'Visto',
                'Pedido marcado como visto en el departamento actual.',
                '',
                []
            );
        }
        return $updated;
    }

    /** @return array<int, array<string, mixed>> */
    private static function listarHistorial(int $pedidoId): array
    {
        if ($pedidoId <= 0 || !self::hasTable('pedido_historial')) {
            return [];
        }
        $stmt = Db::conexion()->prepare('SELECT id, usuario_nombre, accion_realizada, detalle, comentario, created_at
                                         FROM pedido_historial
                                         WHERE pedido_id = :pedido_id
                                         ORDER BY id DESC');
        $stmt->execute(['pedido_id' => $pedidoId]);
        $rows = $stmt->fetchAll() ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'usuario' => (string) ($row['usuario_nombre'] ?? ''),
                'fecha_hora' => (string) ($row['created_at'] ?? ''),
                'accion_realizada' => (string) ($row['accion_realizada'] ?? ''),
                'detalle' => (string) ($row['detalle'] ?? ''),
                'comentario' => (string) ($row['comentario'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $cantidades
     */
    private static function insertHistorial(
        \PDO $pdo,
        int $pedidoId,
        int $userId,
        string $userName,
        string $accion,
        string $detalle,
        string $comentario,
        array $cantidades
    ): void {
        if (!self::hasTable('pedido_historial')) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO pedido_historial
            (pedido_id, usuario_id, usuario_nombre, accion_realizada, detalle, comentario, datos_json)
            VALUES (:pedido_id, :usuario_id, :usuario_nombre, :accion_realizada, :detalle, :comentario, :datos_json)');
        $stmt->execute([
            'pedido_id' => $pedidoId,
            'usuario_id' => $userId > 0 ? $userId : null,
            'usuario_nombre' => $userName,
            'accion_realizada' => $accion,
            'detalle' => $detalle,
            'comentario' => $comentario,
            'datos_json' => $cantidades === [] ? null : json_encode($cantidades, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private static function accionesDisponibles(string $departamento): array
    {
        $departamento = strtolower(trim($departamento));
        if ($departamento === 'facturacion') {
            return [
                ['value' => 'facturar', 'label' => 'Facturar', 'requires_comment' => false, 'requires_qty' => false],
                ['value' => 'devolver_almacen', 'label' => 'Devolver a almacen', 'requires_comment' => true, 'requires_qty' => false, 'target_departamento' => 'almacen'],
                ['value' => 'rechazar', 'label' => 'Rechazar', 'requires_comment' => true, 'requires_qty' => false],
            ];
        }
        return [
            ['value' => 'enviar_facturacion', 'label' => 'Enviar a facturacion', 'requires_comment' => false, 'requires_qty' => false, 'target_departamento' => 'facturacion'],
            ['value' => 'aprobar', 'label' => 'Aprobar', 'requires_comment' => false, 'requires_qty' => true],
            ['value' => 'generar_solicitud', 'label' => 'Generar solicitud relacionada', 'requires_comment' => false, 'requires_qty' => true],
            ['value' => 'produccion', 'label' => 'Produccion', 'requires_comment' => false, 'requires_qty' => true],
            ['value' => 'rechazar', 'label' => 'Rechazar', 'requires_comment' => true, 'requires_qty' => false],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarRegistros(): array
    {
        $sql = "SELECT p.id,
                       p.codigo_pedido,
                       p.fecha,
                       p.cliente_nombre,
                       p.empleado_nombre,
                       p.total
                FROM pedidos p
                ORDER BY p.id DESC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM pedidos WHERE id = :id LIMIT 1');
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
                                               d.total,
                                               d.articulo_codigo,
                                               d.articulo_descripcion,
                                               d.presentacion_descripcion,
                                               d.empaque_descripcion
                                        FROM pedido_detalles d
                                        WHERE d.pedido_id = :id
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

    /**
     * @param array<string, mixed> $record
     * @param array<int, array<string, mixed>> $detalles
     * @return array{ id: int, codigo_pedido: string }
     */
    public static function guardarPedido(array $record, array $detalles): array
    {
        if ($detalles === []) {
            throw new RuntimeException('Debes agregar al menos un articulo al pedido.');
        }

        $clienteId = (int) ($record['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            throw new RuntimeException('Selecciona un cliente para el pedido.');
        }

        $subtotal = 0.0;
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
            $subtotal += $d['total'];
        }
        unset($d);

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $codigo = (string) ($record['codigo_pedido'] ?? '');
            if (($record['secuencia_clave'] ?? '') !== '' || $codigo === '') {
                Secuencia::ensureExists('pd', 'Pedido', 'PD', 5, 1);
                $codigo = Secuencia::getNextNumberInTransaction($pdo, 'pd');
            }

            $stmt = $pdo->prepare('INSERT INTO pedidos
                (codigo_pedido, fecha, cliente_id, cliente_nombre, cliente_telefono, cliente_rnc, localidad_id, localidad_nombre, empleado_id, empleado_nombre, orden_no, comentario, subtotal, total, created_by, updated_by)
                VALUES (:codigo, :fecha, :cliente_id, :cliente_nombre, :cliente_telefono, :cliente_rnc, :localidad_id, :localidad_nombre, :empleado_id, :empleado_nombre, :orden_no, :comentario, :subtotal, :total, :created_by, :updated_by)');
            $stmt->execute([
                'codigo' => $codigo,
                'fecha' => (string) ($record['fecha'] ?? date('Y-m-d')),
                'cliente_id' => $clienteId,
                'cliente_nombre' => (string) ($record['cliente_nombre'] ?? ''),
                'cliente_telefono' => (string) ($record['cliente_telefono'] ?? ''),
                'cliente_rnc' => (string) ($record['cliente_rnc'] ?? ''),
                'localidad_id' => (int) ($record['localidad_id'] ?? 0) ?: null,
                'localidad_nombre' => (string) ($record['localidad_nombre'] ?? ''),
                'empleado_id' => (int) ($record['empleado_id'] ?? 0) ?: null,
                'empleado_nombre' => (string) ($record['empleado_nombre'] ?? ''),
                'orden_no' => (string) ($record['orden_no'] ?? ''),
                'comentario' => (string) ($record['comentario'] ?? ''),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'created_by' => (int) ($record['created_by'] ?? 0) ?: null,
                'updated_by' => (int) ($record['updated_by'] ?? 0) ?: null,
            ]);
            $pedidoId = (int) $pdo->lastInsertId();

            $insDet = $pdo->prepare('INSERT INTO pedido_detalles
                (pedido_id, articulo_id, presentacion_id, empaque_id, cantidad, precio, descuento_pct, descuento_monto, total, articulo_codigo, articulo_descripcion, presentacion_descripcion, empaque_descripcion)
                VALUES (:pedido_id, :articulo_id, :presentacion_id, :empaque_id, :cantidad, :precio, :descuento_pct, :descuento_monto, :total, :articulo_codigo, :articulo_descripcion, :presentacion_descripcion, :empaque_descripcion)');
            foreach ($detalles as $d) {
                $insDet->execute([
                    'pedido_id' => $pedidoId,
                    'articulo_id' => (int) ($d['articulo_id'] ?? 0),
                    'presentacion_id' => (int) ($d['presentacion_id'] ?? 0),
                    'empaque_id' => (int) ($d['empaque_id'] ?? 0),
                    'cantidad' => (int) ($d['cantidad'] ?? 0),
                    'precio' => (float) ($d['precio'] ?? 0),
                    'descuento_pct' => (float) ($d['descuento_pct'] ?? 0),
                    'descuento_monto' => (float) ($d['descuento_monto'] ?? 0),
                    'total' => (float) ($d['total'] ?? 0),
                    'articulo_codigo' => (string) ($d['articulo_codigo'] ?? ''),
                    'articulo_descripcion' => (string) ($d['articulo_descripcion'] ?? ''),
                    'presentacion_descripcion' => (string) ($d['presentacion_descripcion'] ?? ''),
                    'empaque_descripcion' => (string) ($d['empaque_descripcion'] ?? ''),
                ]);
            }

            $pdo->commit();

            return ['id' => $pedidoId, 'codigo_pedido' => $codigo];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $record
     * @param array<int, array<string, mixed>> $detalles
     * @return array{ id: int, codigo_pedido: string }
     */
    public static function actualizarPedido(array $record, array $detalles): array
    {
        $id = (int) ($record['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Pedido no valido.');
        }
        if ($detalles === []) {
            throw new RuntimeException('Debes agregar al menos un articulo al pedido.');
        }
        $clienteId = (int) ($record['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            throw new RuntimeException('Selecciona un cliente para el pedido.');
        }

        $subtotal = 0.0;
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
            $subtotal += $d['total'];
        }
        unset($d);

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT codigo_pedido FROM pedidos WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new RuntimeException('Pedido no encontrado.');
            }
            $codigo = (string) ($row['codigo_pedido'] ?? '');

            $upd = $pdo->prepare('UPDATE pedidos
                SET fecha = :fecha,
                    cliente_id = :cliente_id,
                    cliente_nombre = :cliente_nombre,
                    cliente_telefono = :cliente_telefono,
                    cliente_rnc = :cliente_rnc,
                    localidad_id = :localidad_id,
                    localidad_nombre = :localidad_nombre,
                    empleado_id = :empleado_id,
                    empleado_nombre = :empleado_nombre,
                    orden_no = :orden_no,
                    comentario = :comentario,
                    subtotal = :subtotal,
                    total = :total,
                    updated_by = :updated_by
                WHERE id = :id');
            $upd->execute([
                'id' => $id,
                'fecha' => (string) ($record['fecha'] ?? date('Y-m-d')),
                'cliente_id' => $clienteId,
                'cliente_nombre' => (string) ($record['cliente_nombre'] ?? ''),
                'cliente_telefono' => (string) ($record['cliente_telefono'] ?? ''),
                'cliente_rnc' => (string) ($record['cliente_rnc'] ?? ''),
                'localidad_id' => (int) ($record['localidad_id'] ?? 0) ?: null,
                'localidad_nombre' => (string) ($record['localidad_nombre'] ?? ''),
                'empleado_id' => (int) ($record['empleado_id'] ?? 0) ?: null,
                'empleado_nombre' => (string) ($record['empleado_nombre'] ?? ''),
                'orden_no' => (string) ($record['orden_no'] ?? ''),
                'comentario' => (string) ($record['comentario'] ?? ''),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'updated_by' => (int) ($record['updated_by'] ?? 0) ?: null,
            ]);

            $pdo->prepare('DELETE FROM pedido_detalles WHERE pedido_id = :id')->execute(['id' => $id]);
            $insDet = $pdo->prepare('INSERT INTO pedido_detalles
                (pedido_id, articulo_id, presentacion_id, empaque_id, cantidad, precio, descuento_pct, descuento_monto, total, articulo_codigo, articulo_descripcion, presentacion_descripcion, empaque_descripcion)
                VALUES (:pedido_id, :articulo_id, :presentacion_id, :empaque_id, :cantidad, :precio, :descuento_pct, :descuento_monto, :total, :articulo_codigo, :articulo_descripcion, :presentacion_descripcion, :empaque_descripcion)');
            foreach ($detalles as $d) {
                $insDet->execute([
                    'pedido_id' => $id,
                    'articulo_id' => (int) ($d['articulo_id'] ?? 0),
                    'presentacion_id' => (int) ($d['presentacion_id'] ?? 0),
                    'empaque_id' => (int) ($d['empaque_id'] ?? 0),
                    'cantidad' => (int) ($d['cantidad'] ?? 0),
                    'precio' => (float) ($d['precio'] ?? 0),
                    'descuento_pct' => (float) ($d['descuento_pct'] ?? 0),
                    'descuento_monto' => (float) ($d['descuento_monto'] ?? 0),
                    'total' => (float) ($d['total'] ?? 0),
                    'articulo_codigo' => (string) ($d['articulo_codigo'] ?? ''),
                    'articulo_descripcion' => (string) ($d['articulo_descripcion'] ?? ''),
                    'presentacion_descripcion' => (string) ($d['presentacion_descripcion'] ?? ''),
                    'empaque_descripcion' => (string) ($d['empaque_descripcion'] ?? ''),
                ]);
            }

            $pdo->commit();
            return ['id' => $id, 'codigo_pedido' => $codigo];
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
            $pdo->prepare('DELETE FROM pedido_detalles WHERE pedido_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM pedidos WHERE id = :id')->execute(['id' => $id]);
            $pdo->commit();
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

    private static function decimalOrCero(mixed $value): float
    {
        $txt = trim((string) $value);
        if ($txt === '') {
            return 0.0;
        }
        return is_numeric($txt) ? (float) $txt : 0.0;
    }

    private static function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $sql = 'SELECT 1
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table
                  AND COLUMN_NAME = :column
                LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);
        $cache[$key] = (bool) $stmt->fetchColumn();
        return $cache[$key];
    }

    private static function hasTable(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }
        $stmt = Db::conexion()->prepare('SELECT 1
                                         FROM information_schema.TABLES
                                         WHERE TABLE_SCHEMA = DATABASE()
                                           AND TABLE_NAME = :table
                                         LIMIT 1');
        $stmt->execute(['table' => $table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
        return $cache[$table];
    }
}
