<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Cliente
{
    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM clientes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }


    public static function listarParaSelect(): array
    {
        $sql = 'SELECT id, razon_social, nombre_comercial, rnc
                FROM clientes
                WHERE estado = :estado
                ORDER BY razon_social ASC';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['estado' => 'activo']);

        return $stmt->fetchAll() ?: [];
    }

    public static function listarParaModal(): array
    {
        $sql = 'SELECT id, razon_social, rnc, telefono_cliente, estado, descuento_default, aplica_itbis, exento_itbis
                FROM clientes
                WHERE estado = :estado
                ORDER BY id DESC';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['estado' => 'activo']);

        return $stmt->fetchAll() ?: [];
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $razonSocial = trim((string) ($data['razon_social'] ?? ''));
        if ($razonSocial === '') {
            throw new RuntimeException('La razon social es obligatoria.');
        }

        foreach (['correo_electronico', 'correo_facturacion'] as $mailField) {
            $value = trim((string) ($data[$mailField] ?? ''));
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Campo ' . $mailField . ' invalido.');
            }
        }

        $payload = [
            'razon_social' => $razonSocial,
            'nombre_comercial' => trim((string) ($data['nombre_comercial'] ?? '')),
            'rnc' => trim((string) ($data['rnc'] ?? '')),
            'cedula' => trim((string) ($data['cedula'] ?? '')),
            'tipo_cliente' => trim((string) ($data['tipo_cliente'] ?? 'empresa')),
            'tipo_ncf_preferido' => trim((string) ($data['tipo_ncf_preferido'] ?? '')),
            'estado' => trim((string) ($data['estado'] ?? 'activo')),
            'nombre_contacto' => trim((string) ($data['nombre_contacto'] ?? '')),
            'cargo_contacto' => trim((string) ($data['cargo_contacto'] ?? '')),
            'telefono_cliente' => trim((string) ($data['telefono_cliente'] ?? '')),
            'telefono_secundario' => trim((string) ($data['telefono_secundario'] ?? '')),
            'whatsapp' => trim((string) ($data['whatsapp'] ?? '')),
            'correo_electronico' => trim((string) ($data['correo_electronico'] ?? '')),
            'correo_facturacion' => trim((string) ($data['correo_facturacion'] ?? '')),
            'direccion' => trim((string) ($data['direccion'] ?? '')),
            'ciudad' => trim((string) ($data['ciudad'] ?? '')),
            'provincia' => trim((string) ($data['provincia'] ?? '')),
            'pais' => trim((string) ($data['pais'] ?? '')),
            'codigo_postal' => trim((string) ($data['codigo_postal'] ?? '')),
            'condicion_pago' => trim((string) ($data['condicion_pago'] ?? 'contado')),
            'dias_credito' => max(0, (int) ($data['dias_credito'] ?? 0)),
            'limite_credito' => (float) ($data['limite_credito'] ?? 0),
            'balance_actual' => (float) ($data['balance_actual'] ?? 0),
            'moneda' => trim((string) ($data['moneda'] ?? 'DOP')),
            'descuento_default' => (float) ($data['descuento_default'] ?? 0),
            'vendedor_asignado' => trim((string) ($data['vendedor_asignado'] ?? '')),
            'canal_venta' => trim((string) ($data['canal_venta'] ?? '')),
            'tipo_comprobante_preferido' => trim((string) ($data['tipo_comprobante_preferido'] ?? '')),
            'secuencia_asignada' => trim((string) ($data['secuencia_asignada'] ?? '')),
            'aplica_itbis' => isset($data['aplica_itbis']) ? 1 : 0,
            'exento_itbis' => isset($data['exento_itbis']) ? 1 : 0,
            'retencion_aplica' => isset($data['retencion_aplica']) ? 1 : 0,
            'agente_retencion' => isset($data['agente_retencion']) ? 1 : 0,
            'banco_cliente' => trim((string) ($data['banco_cliente'] ?? '')),
            'numero_cuenta_cliente' => trim((string) ($data['numero_cuenta_cliente'] ?? '')),
            'tipo_cuenta_cliente' => trim((string) ($data['tipo_cuenta_cliente'] ?? '')),
            'tipo_negocio' => trim((string) ($data['tipo_negocio'] ?? '')),
            'sector' => trim((string) ($data['sector'] ?? '')),
            'categoria_cliente' => trim((string) ($data['categoria_cliente'] ?? '')),
            'prioridad' => trim((string) ($data['prioridad'] ?? '')),
            'calificacion' => trim((string) ($data['calificacion'] ?? '')),
            'recibir_factura_correo' => isset($data['recibir_factura_correo']) ? 1 : 0,
            'recibir_estado_cuenta' => isset($data['recibir_estado_cuenta']) ? 1 : 0,
            'correos_notificacion_adicional' => trim((string) ($data['correos_notificacion_adicional'] ?? '')),
        ];

        $id = $id !== null && $id > 0 ? $id : null;

        if ($id !== null) {
            $set = [];
            foreach (array_keys($payload) as $k) {
                $set[] = $k . ' = :' . $k;
            }
            $sql = 'UPDATE clientes SET ' . implode(', ', $set) . ', updated_by = :updated_by WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute($payload + [
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);

            return $id;
        }

        $fields = array_keys($payload);
        $sql = 'INSERT INTO clientes (' . implode(', ', $fields) . ', created_by, updated_by) VALUES (:' .
            implode(', :', $fields) . ', :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($payload + [
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }
}
