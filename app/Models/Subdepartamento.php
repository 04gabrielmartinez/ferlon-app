<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Subdepartamento
{
    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM subdepartamentos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function listarParaModal(): array
    {
        $sql = 'SELECT s.id, s.departamento_id, d.nombre AS departamento_nombre, s.nombre, s.estado
                FROM subdepartamentos s
                INNER JOIN departamentos d ON d.id = s.departamento_id
                ORDER BY s.id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function listarActivos(): array
    {
        $sql = "SELECT id, departamento_id, nombre
                FROM subdepartamentos
                WHERE estado = 'activo'
                ORDER BY nombre ASC";

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $departamentoId = (int) ($data['departamento_id'] ?? 0);
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        $estado = strtolower(trim((string) ($data['estado'] ?? 'activo')));

        if ($departamentoId <= 0) {
            throw new RuntimeException('Selecciona un departamento.');
        }
        if ($nombre === '') {
            throw new RuntimeException('Nombre de subdepartamento es obligatorio.');
        }
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $stmt = Db::conexion()->prepare('SELECT id FROM departamentos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $departamentoId]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Departamento seleccionado no existe.');
        }

        $id = $id !== null && $id > 0 ? $id : null;
        self::validarNombreUnico($departamentoId, $nombre, $id);

        $payload = [
            'departamento_id' => $departamentoId,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'estado' => $estado,
        ];

        if ($id !== null) {
            $sql = 'UPDATE subdepartamentos
                    SET departamento_id = :departamento_id,
                        nombre = :nombre,
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

        $sql = 'INSERT INTO subdepartamentos (departamento_id, nombre, descripcion, estado, created_by, updated_by)
                VALUES (:departamento_id, :nombre, :descripcion, :estado, :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($payload + [
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function eliminar(int $id): void
    {
        $stmt = Db::conexion()->prepare('DELETE FROM subdepartamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function validarNombreUnico(int $departamentoId, string $nombre, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id FROM subdepartamentos WHERE departamento_id = :departamento_id AND nombre = :nombre LIMIT 1');
        $stmt->execute([
            'departamento_id' => $departamentoId,
            'nombre' => $nombre,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }

        throw new RuntimeException('Nombre de subdepartamento ya existe en este departamento.');
    }
}
