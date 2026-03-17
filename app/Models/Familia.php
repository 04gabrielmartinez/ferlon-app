<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Familia
{
    public static function listarActivas(): array
    {
        $sql = 'SELECT id, marca_id, descripcion, estado
                FROM familias
                WHERE estado = "activo"
                ORDER BY descripcion ASC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM familias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function listarParaModal(): array
    {
        $sql = 'SELECT f.id, f.marca_id, m.descripcion AS marca_descripcion, f.descripcion, f.estado
                FROM familias f
                INNER JOIN marcas m ON m.id = f.marca_id
                ORDER BY f.id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function listarPorMarca(int $marcaId): array
    {
        $stmt = Db::conexion()->prepare('SELECT id, descripcion, estado FROM familias WHERE marca_id = :marca_id ORDER BY descripcion ASC');
        $stmt->execute(['marca_id' => $marcaId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function guardar(?int $id, array $data, int $userId): int
    {
        $marcaId = (int) ($data['marca_id'] ?? 0);
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        $estado = strtolower(trim((string) ($data['estado'] ?? 'activo')));

        if ($marcaId <= 0) {
            throw new RuntimeException('Debes seleccionar una marca.');
        }
        if ($descripcion === '') {
            throw new RuntimeException('La descripcion de la familia es obligatoria.');
        }
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $marca = Marca::buscarPorId($marcaId);
        if (!$marca) {
            throw new RuntimeException('La marca seleccionada no existe.');
        }
        if (strtolower((string) ($marca['estado'] ?? 'inactivo')) !== 'activo' && $estado === 'activo') {
            throw new RuntimeException('No puedes activar una familia con su marca inactiva.');
        }

        $id = $id !== null && $id > 0 ? $id : null;
        self::validarDescripcionUnica($marcaId, $descripcion, $id);

        if ($id !== null) {
            $sql = 'UPDATE familias
                    SET marca_id = :marca_id,
                        descripcion = :descripcion,
                        estado = :estado,
                        updated_by = :updated_by
                    WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute([
                'marca_id' => $marcaId,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);

            return $id;
        }

        $sql = 'INSERT INTO familias (marca_id, descripcion, estado, created_by, updated_by)
                VALUES (:marca_id, :descripcion, :estado, :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'marca_id' => $marcaId,
            'descripcion' => $descripcion,
            'estado' => $estado,
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function desactivarPorMarca(int $marcaId): void
    {
        $stmt = Db::conexion()->prepare("UPDATE familias SET estado = 'inactivo' WHERE marca_id = :marca_id");
        $stmt->execute(['marca_id' => $marcaId]);
    }

    public static function activarPorMarca(int $marcaId): void
    {
        $stmt = Db::conexion()->prepare("UPDATE familias SET estado = 'activo' WHERE marca_id = :marca_id");
        $stmt->execute(['marca_id' => $marcaId]);
    }

    public static function activarPorIds(int $marcaId, array $familiaIds): void
    {
        $familiaIds = array_values(array_filter(array_map(static fn ($v): int => (int) $v, $familiaIds), static fn (int $v): bool => $v > 0));
        if ($familiaIds === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($familiaIds), '?'));
        $sql = "UPDATE familias SET estado = 'activo' WHERE marca_id = ? AND id IN ($placeholders)";
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(array_merge([$marcaId], $familiaIds));
    }

    private static function validarDescripcionUnica(int $marcaId, string $descripcion, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id FROM familias WHERE marca_id = :marca_id AND descripcion = :descripcion LIMIT 1');
        $stmt->execute([
            'marca_id' => $marcaId,
            'descripcion' => $descripcion,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }

        throw new RuntimeException('Ya existe una familia con esa descripcion para la marca seleccionada.');
    }
}
