<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Localidad
{
    public static function listar(): array
    {
        $sql = 'SELECT l.id, l.cliente_id, c.razon_social AS cliente_nombre, l.nombre_localidad, l.referencia,
                       l.latitud, l.longitud, l.estado, l.created_at, l.updated_at
                FROM localidades l
                INNER JOIN clientes c ON c.id = l.cliente_id
                ORDER BY l.id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarPorCliente(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [];
        }
        $stmt = Db::conexion()->prepare('SELECT id, cliente_id, nombre_localidad, referencia, latitud, longitud, estado
                                         FROM localidades
                                         WHERE cliente_id = :cliente_id
                                         ORDER BY nombre_localidad ASC');
        $stmt->execute(['cliente_id' => $clienteId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM localidades WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $clienteId = (int) ($data['cliente_id'] ?? 0);
        $nombreLocalidad = trim((string) ($data['nombre_localidad'] ?? ''));
        $referencia = trim((string) ($data['referencia'] ?? ''));
        $latitudRaw = trim((string) ($data['latitud'] ?? ''));
        $longitudRaw = trim((string) ($data['longitud'] ?? ''));

        if ($clienteId <= 0) {
            throw new RuntimeException('Selecciona un cliente.');
        }
        if ($nombreLocalidad === '') {
            throw new RuntimeException('Nombre de la localidad es obligatorio.');
        }
        if ($latitudRaw === '' || $longitudRaw === '') {
            throw new RuntimeException('Latitud y longitud son obligatorias.');
        }

        if (!is_numeric($latitudRaw) || !is_numeric($longitudRaw)) {
            throw new RuntimeException('Latitud y longitud deben ser numericas.');
        }

        $latitud = (float) $latitudRaw;
        $longitud = (float) $longitudRaw;

        if ($latitud < -90 || $latitud > 90) {
            throw new RuntimeException('Latitud fuera de rango (-90 a 90).');
        }
        if ($longitud < -180 || $longitud > 180) {
            throw new RuntimeException('Longitud fuera de rango (-180 a 180).');
        }

        $payload = [
            'cliente_id' => $clienteId,
            'nombre_localidad' => $nombreLocalidad,
            'referencia' => $referencia,
            'latitud' => $latitud,
            'longitud' => $longitud,
            'estado' => isset($data['estado']) ? 'activo' : 'inactivo',
        ];

        $id = $id !== null && $id > 0 ? $id : null;

        if ($id !== null) {
            $set = [];
            foreach (array_keys($payload) as $k) {
                $set[] = $k . ' = :' . $k;
            }
            $sql = 'UPDATE localidades SET ' . implode(', ', $set) . ', updated_by = :updated_by WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute($payload + [
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);

            return $id;
        }

        $fields = array_keys($payload);
        $sql = 'INSERT INTO localidades (' . implode(', ', $fields) . ', created_by, updated_by) VALUES (:' .
            implode(', :', $fields) . ', :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($payload + [
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function eliminar(int $id): void
    {
        $stmt = Db::conexion()->prepare('DELETE FROM localidades WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
