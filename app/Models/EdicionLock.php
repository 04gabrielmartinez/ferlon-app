<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use DateTimeImmutable;
use RuntimeException;

final class EdicionLock
{
    public static function cleanupExpired(): int
    {
        $stmt = Db::conexion()->prepare('DELETE FROM edicion_locks WHERE expira_en <= NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * @return array{locked: bool, owner: bool, lock: array<string, mixed>|null}
     */
    public static function acquire(string $recursoTipo, int $recursoId, int $usuarioId, int $ttlSeconds = 600): array
    {
        $recursoTipo = trim(strtolower($recursoTipo));
        if ($recursoTipo === '' || $recursoId <= 0) {
            throw new RuntimeException('Recurso invalido para bloqueo.');
        }

        self::cleanupExpired();

        $lock = self::buscar($recursoTipo, $recursoId);
        if (is_array($lock) && self::lockActivo($lock)) {
            $owner = (int) ($lock['usuario_id'] ?? 0) === $usuarioId && $usuarioId > 0;
            return [
                'locked' => true,
                'owner' => $owner,
                'lock' => $lock,
            ];
        }

        if ($usuarioId <= 0) {
            return [
                'locked' => false,
                'owner' => false,
                'lock' => null,
            ];
        }

        $expiresAt = (new DateTimeImmutable())->modify('+' . $ttlSeconds . ' seconds')->format('Y-m-d H:i:s');
        $pdo = Db::conexion();
        $stmt = $pdo->prepare('INSERT INTO edicion_locks (recurso_tipo, recurso_id, usuario_id, expira_en)
                               VALUES (:recurso_tipo, :recurso_id, :usuario_id, :expira_en)');
        try {
            $stmt->execute([
                'recurso_tipo' => $recursoTipo,
                'recurso_id' => $recursoId,
                'usuario_id' => $usuarioId,
                'expira_en' => $expiresAt,
            ]);
        } catch (\Throwable $e) {
            // Si existe un lock concurrente, lo leeremos abajo.
        }

        $lock = self::buscar($recursoTipo, $recursoId);
        if (is_array($lock) && self::lockActivo($lock)) {
            $owner = (int) ($lock['usuario_id'] ?? 0) === $usuarioId;
            return [
                'locked' => true,
                'owner' => $owner,
                'lock' => $lock,
            ];
        }

        return [
            'locked' => false,
            'owner' => false,
            'lock' => null,
        ];
    }

    public static function isOwnerActive(string $recursoTipo, int $recursoId, int $usuarioId): bool
    {
        if ($usuarioId <= 0) {
            return false;
        }
        $lock = self::buscar($recursoTipo, $recursoId);
        if (!is_array($lock) || !self::lockActivo($lock)) {
            return false;
        }

        return (int) ($lock['usuario_id'] ?? 0) === $usuarioId;
    }

    public static function release(string $recursoTipo, int $recursoId, int $usuarioId): void
    {
        if ($usuarioId <= 0) {
            return;
        }
        $stmt = Db::conexion()->prepare('DELETE FROM edicion_locks
                                         WHERE recurso_tipo = :recurso_tipo
                                           AND recurso_id = :recurso_id
                                           AND usuario_id = :usuario_id');
        $stmt->execute([
            'recurso_tipo' => trim(strtolower($recursoTipo)),
            'recurso_id' => $recursoId,
            'usuario_id' => $usuarioId,
        ]);
    }

    public static function releaseAllByUser(int $usuarioId): int
    {
        if ($usuarioId <= 0) {
            return 0;
        }
        $stmt = Db::conexion()->prepare('DELETE FROM edicion_locks WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);
        return $stmt->rowCount();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listarActivos(): array
    {
        self::cleanupExpired();
        $sql = 'SELECT l.id, l.recurso_tipo, l.recurso_id, l.usuario_id, l.creado_en, l.expira_en,
                       u.nombre, u.username, u.email
                FROM edicion_locks l
                LEFT JOIN users u ON u.id = l.usuario_id
                ORDER BY l.expira_en ASC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function limpiarTodo(): int
    {
        $stmt = Db::conexion()->prepare('DELETE FROM edicion_locks');
        $stmt->execute();
        return $stmt->rowCount();
    }

    private static function buscar(string $recursoTipo, int $recursoId): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT l.id, l.recurso_tipo, l.recurso_id, l.usuario_id, l.creado_en, l.expira_en,
                                                u.nombre, u.username, u.email
                                         FROM edicion_locks l
                                         LEFT JOIN users u ON u.id = l.usuario_id
                                         WHERE l.recurso_tipo = :recurso_tipo
                                           AND l.recurso_id = :recurso_id
                                         LIMIT 1');
        $stmt->execute([
            'recurso_tipo' => trim(strtolower($recursoTipo)),
            'recurso_id' => $recursoId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function lockActivo(array $lock): bool
    {
        $expira = (string) ($lock['expira_en'] ?? '');
        if ($expira === '') {
            return false;
        }
        $expiraTs = strtotime($expira);
        if ($expiraTs === false) {
            return false;
        }
        return $expiraTs > time();
    }
}
