<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Departamento
{
    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM departamentos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function listarParaModal(): array
    {
        $sql = 'SELECT id, nombre, estado
                FROM departamentos
                ORDER BY id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function listarActivos(): array
    {
        $sql = "SELECT id, nombre
                FROM departamentos
                WHERE estado = 'activo'
                ORDER BY nombre ASC";

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        $estado = strtolower(trim((string) ($data['estado'] ?? 'activo')));

        if ($nombre === '') {
            throw new RuntimeException('Nombre de departamento es obligatorio.');
        }
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $id = $id !== null && $id > 0 ? $id : null;
        self::validarNombreUnico($nombre, $id);

        $payload = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'estado' => $estado,
        ];

        if ($id !== null) {
            $sql = 'UPDATE departamentos
                    SET nombre = :nombre,
                        descripcion = :descripcion,
                        estado = :estado,
                        updated_by = :updated_by
                    WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute($payload + [
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);

            return $id;
        }

        $sql = 'INSERT INTO departamentos (nombre, descripcion, estado, created_by, updated_by)
                VALUES (:nombre, :descripcion, :estado, :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($payload + [
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function eliminar(int $id): void
    {
        $stmt = Db::conexion()->prepare('SELECT COUNT(*) AS total FROM subdepartamentos WHERE departamento_id = :id');
        $stmt->execute(['id' => $id]);
        $total = (int) ($stmt->fetch()['total'] ?? 0);
        if ($total > 0) {
            throw new RuntimeException('No se puede eliminar: el departamento tiene subdepartamentos asociados.');
        }

        $stmt = Db::conexion()->prepare('DELETE FROM departamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function validarNombreUnico(string $nombre, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id FROM departamentos WHERE nombre = :nombre LIMIT 1');
        $stmt->execute(['nombre' => $nombre]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }

        throw new RuntimeException('Nombre de departamento ya existe.');
    }
}
