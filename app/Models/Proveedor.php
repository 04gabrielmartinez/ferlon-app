<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Proveedor
{
    private static ?bool $hasTipoBancoColumn = null;

    public static function listar(): array
    {
        $sql = 'SELECT p.*, uc.nombre AS creado_por_nombre, uu.nombre AS actualizado_por_nombre
                FROM proveedores p
                LEFT JOIN users uc ON uc.id = p.created_by
                LEFT JOIN users uu ON uu.id = p.updated_by
                ORDER BY p.id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM proveedores WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $razonSocial = trim((string) ($data['razon_social'] ?? ''));
        if ($razonSocial === '') {
            throw new RuntimeException('La razon social es obligatoria.');
        }

        $correo = trim((string) ($data['correo'] ?? ''));
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Correo general invalido.');
        }

        $contactoEmail = trim((string) ($data['contacto_email'] ?? ''));
        if ($contactoEmail !== '' && !filter_var($contactoEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Correo del contacto invalido.');
        }

        $payload = [
            'razon_social' => $razonSocial,
            'nombre_comercial' => trim((string) ($data['nombre_comercial'] ?? '')),
            'rnc' => trim((string) ($data['rnc'] ?? '')),
            'cedula' => trim((string) ($data['cedula'] ?? '')),
            'tipo_proveedor' => trim((string) ($data['tipo_proveedor'] ?? '')),
            'estado' => trim((string) ($data['estado'] ?? 'activo')),
            'correo' => $correo,
            'telefono' => trim((string) ($data['telefono'] ?? '')),
            'telefono_secundario' => trim((string) ($data['telefono_secundario'] ?? '')),
            'whatsapp' => trim((string) ($data['whatsapp'] ?? '')),
            'contacto_nombre' => trim((string) ($data['contacto_nombre'] ?? '')),
            'contacto_telefono' => trim((string) ($data['contacto_telefono'] ?? '')),
            'contacto_email' => $contactoEmail,
            'contacto_cargo' => trim((string) ($data['contacto_cargo'] ?? '')),
            'direccion' => trim((string) ($data['direccion'] ?? '')),
            'ciudad' => trim((string) ($data['ciudad'] ?? '')),
            'provincia' => trim((string) ($data['provincia'] ?? '')),
            'pais' => trim((string) ($data['pais'] ?? '')),
            'codigo_postal' => trim((string) ($data['codigo_postal'] ?? '')),
            'condicion_pago' => trim((string) ($data['condicion_pago'] ?? '')),
            'dias_credito' => max(0, (int) ($data['dias_credito'] ?? 0)),
            'limite_credito' => (float) ($data['limite_credito'] ?? 0),
            'balance_actual' => (float) ($data['balance_actual'] ?? 0),
            'moneda' => trim((string) ($data['moneda'] ?? 'DOP')),
            'banco' => trim((string) ($data['banco'] ?? '')),
            'tipo_cuenta' => trim((string) ($data['tipo_cuenta'] ?? '')),
            'numero_cuenta' => trim((string) ($data['numero_cuenta'] ?? '')),
            'titular_cuenta' => trim((string) ($data['titular_cuenta'] ?? '')),
            'rnc_titular' => trim((string) ($data['rnc_titular'] ?? '')),
            'cuenta_contable' => trim((string) ($data['cuenta_contable'] ?? '')),
            'tipo_gasto' => trim((string) ($data['tipo_gasto'] ?? '')),
            'retencion_itbis' => (float) ($data['retencion_itbis'] ?? 0),
            'retencion_isr' => (float) ($data['retencion_isr'] ?? 0),
            'aplica_impuestos' => isset($data['aplica_impuestos']) ? 1 : 0,
            'categoria_proveedor' => trim((string) ($data['categoria_proveedor'] ?? '')),
            'rubro' => trim((string) ($data['rubro'] ?? '')),
            'tiempo_entrega' => trim((string) ($data['tiempo_entrega'] ?? '')),
            'calificacion' => trim((string) ($data['calificacion'] ?? '')),
            'observaciones' => trim((string) ($data['observaciones'] ?? '')),
            'contrato' => trim((string) ($data['contrato'] ?? '')),
            'documento_rnc' => trim((string) ($data['documento_rnc'] ?? '')),
            'documento_identidad' => trim((string) ($data['documento_identidad'] ?? '')),
            'otros_documentos' => trim((string) ($data['otros_documentos'] ?? '')),
            'recibir_ordenes_correo' => isset($data['recibir_ordenes_correo']) ? 1 : 0,
            'recibir_pagos_correo' => isset($data['recibir_pagos_correo']) ? 1 : 0,
            'correos_notificacion' => trim((string) ($data['correos_notificacion'] ?? '')),
            'inactivo' => isset($data['inactivo']) ? 1 : 0,
            'fecha_inactivo' => trim((string) ($data['fecha_inactivo'] ?? '')) !== '' ? trim((string) $data['fecha_inactivo']) : null,
            'motivo_inactivo' => trim((string) ($data['motivo_inactivo'] ?? '')),
        ];

        if (self::hasTipoBancoColumn()) {
            $payload['tipo_banco'] = trim((string) ($data['tipo_banco'] ?? ''));
        }

        if (($payload['inactivo'] ?? 0) === 1 && $payload['fecha_inactivo'] === null) {
            $payload['fecha_inactivo'] = date('Y-m-d');
        }

        $id = $id !== null && $id > 0 ? $id : null;
        if ($id !== null) {
            $set = [];
            foreach (array_keys($payload) as $k) {
                $set[] = $k . ' = :' . $k;
            }
            $sql = 'UPDATE proveedores SET ' . implode(', ', $set) . ', updated_by = :updated_by WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute($payload + [
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);
            return $id;
        }

        $fields = array_keys($payload);
        $sql = 'INSERT INTO proveedores (' . implode(', ', $fields) . ', created_by, updated_by) VALUES (:' .
            implode(', :', $fields) . ', :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($payload + [
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function listarParaModal(): array
    {
        $sql = 'SELECT id, razon_social, rnc, telefono, estado, condicion_pago
                FROM proveedores
                ORDER BY id DESC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function listarActivos(): array
    {
        $sql = "SELECT id, razon_social
                FROM proveedores
                WHERE estado = 'activo' AND (inactivo = 0 OR inactivo IS NULL)
                ORDER BY razon_social ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    private static function hasTipoBancoColumn(): bool
    {
        if (self::$hasTipoBancoColumn !== null) {
            return self::$hasTipoBancoColumn;
        }

        $stmt = Db::conexion()->prepare(
            'SELECT COUNT(*) AS total
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([
            'table_name' => 'proveedores',
            'column_name' => 'tipo_banco',
        ]);
        $row = $stmt->fetch();
        self::$hasTipoBancoColumn = ((int) ($row['total'] ?? 0) > 0);

        return self::$hasTipoBancoColumn;
    }
}
