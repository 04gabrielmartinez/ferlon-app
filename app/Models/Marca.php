<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Marca
{
    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM marcas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function listarParaModal(): array
    {
        $sql = 'SELECT id, descripcion, estado
                FROM marcas
                ORDER BY id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function listarActivas(): array
    {
        $sql = "SELECT id, descripcion
                FROM marcas
                WHERE estado = 'activo'
                ORDER BY descripcion ASC";

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        $estado = strtolower(trim((string) ($data['estado'] ?? 'activo')));

        if ($descripcion === '') {
            throw new RuntimeException('La descripcion de la marca es obligatoria.');
        }
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $id = $id !== null && $id > 0 ? $id : null;
        self::validarDescripcionUnica($descripcion, $id);

        if ($id !== null) {
            $sql = 'UPDATE marcas
                    SET descripcion = :descripcion,
                        estado = :estado,
                        updated_by = :updated_by
                    WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute([
                'descripcion' => $descripcion,
                'estado' => $estado,
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);

            return $id;
        }

        $sql = 'INSERT INTO marcas (descripcion, estado, created_by, updated_by)
                VALUES (:descripcion, :estado, :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'descripcion' => $descripcion,
            'estado' => $estado,
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    private static function validarDescripcionUnica(string $descripcion, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id FROM marcas WHERE descripcion = :descripcion LIMIT 1');
        $stmt->execute(['descripcion' => $descripcion]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }

        throw new RuntimeException('Ya existe una marca con esa descripcion.');
    }
}
