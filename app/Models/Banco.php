<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Banco
{
    private static ?array $columnCache = null;

    public static function listarActivos(): array
    {
        try {
            $nameExpr = self::hasColumn('nombre_banco') ? 'nombre_banco' : 'nombre';
            $estadoExpr = self::hasColumn('estado') ? "LOWER(COALESCE(estado, 'activo')) = 'activo'" : 'activo = 1';

            $sql = 'SELECT id, ' . $nameExpr . ' AS nombre, ' . $nameExpr . ' AS nombre_banco
                    FROM bancos
                    WHERE ' . $estadoExpr . '
                    ORDER BY ' . $nameExpr . ' ASC';
            return Db::conexion()->query($sql)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function listarParaModal(): array
    {
        $nameExpr = self::hasColumn('nombre_banco') ? 'nombre_banco' : 'nombre';
        $codigoExpr = self::hasColumn('codigo_banco') ? 'codigo_banco' : "''";
        $telefonoExpr = self::hasColumn('telefono') ? 'telefono' : "''";
        $paisExpr = self::hasColumn('pais') ? 'pais' : "''";
        $estadoExpr = self::hasColumn('estado') ? 'estado' : "IF(activo = 1, 'activo', 'inactivo')";

        $sql = 'SELECT id,
                       ' . $nameExpr . ' AS nombre_banco,
                       ' . $codigoExpr . ' AS codigo_banco,
                       ' . $telefonoExpr . ' AS telefono,
                       ' . $paisExpr . ' AS pais,
                       ' . $estadoExpr . ' AS estado
                FROM bancos
                ORDER BY id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM bancos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function guardar(?int $id, array $data, int $userId = 0): int
    {
        $nombreBanco = trim((string) ($data['nombre_banco'] ?? ''));
        $codigoBanco = strtoupper(trim((string) ($data['codigo_banco'] ?? '')));
        $estado = strtolower(trim((string) ($data['estado'] ?? 'activo')));

        if ($nombreBanco === '') {
            throw new RuntimeException('Nombre banco es obligatorio.');
        }
        if ($codigoBanco === '') {
            throw new RuntimeException('Codigo banco es obligatorio.');
        }
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $correo = trim((string) ($data['correo_contacto'] ?? ''));
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Correo contacto invalido.');
        }

        $sitioWeb = trim((string) ($data['sitio_web'] ?? ''));
        if ($sitioWeb !== '' && !filter_var($sitioWeb, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Sitio web invalido.');
        }

        $id = $id !== null && $id > 0 ? $id : null;
        self::validarCodigoUnico($codigoBanco, $id);

        $payload = [];
        $payload[self::hasColumn('nombre_banco') ? 'nombre_banco' : 'nombre'] = $nombreBanco;
        if (self::hasColumn('codigo_banco')) {
            $payload['codigo_banco'] = $codigoBanco;
        }
        if (self::hasColumn('estado')) {
            $payload['estado'] = $estado;
        }
        if (self::hasColumn('activo')) {
            $payload['activo'] = $estado === 'activo' ? 1 : 0;
        }

        foreach (['rnc', 'telefono', 'correo_contacto', 'sitio_web', 'direccion', 'pais'] as $field) {
            if (self::hasColumn($field)) {
                $payload[$field] = trim((string) ($data[$field] ?? ''));
            }
        }

        if (self::hasColumn('updated_by')) {
            $payload['updated_by'] = $userId > 0 ? $userId : null;
        }

        if ($id !== null) {
            $set = [];
            foreach (array_keys($payload) as $k) {
                $set[] = $k . ' = :' . $k;
            }
            $sql = 'UPDATE bancos SET ' . implode(', ', $set) . ' WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute($payload + ['id' => $id]);
            return $id;
        }

        if (self::hasColumn('created_by')) {
            $payload['created_by'] = $userId > 0 ? $userId : null;
        }

        $fields = array_keys($payload);
        $sql = 'INSERT INTO bancos (' . implode(', ', $fields) . ') VALUES (:' . implode(', :', $fields) . ')';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($payload);

        return (int) Db::conexion()->lastInsertId();
    }

    private static function validarCodigoUnico(string $codigo, ?int $exceptId): void
    {
        if (!self::hasColumn('codigo_banco')) {
            return;
        }

        $stmt = Db::conexion()->prepare('SELECT id FROM bancos WHERE codigo_banco = :codigo LIMIT 1');
        $stmt->execute(['codigo' => $codigo]);
        $row = $stmt->fetch();

        if (!$row) {
            return;
        }

        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }

        throw new RuntimeException('Codigo banco ya existe.');
    }

    private static function hasColumn(string $column): bool
    {
        if (self::$columnCache === null) {
            $rows = Db::conexion()->query('SHOW COLUMNS FROM bancos')->fetchAll() ?: [];
            self::$columnCache = [];
            foreach ($rows as $row) {
                if (isset($row['Field'])) {
                    self::$columnCache[(string) $row['Field']] = true;
                }
            }
        }

        return isset(self::$columnCache[$column]);
    }
}
