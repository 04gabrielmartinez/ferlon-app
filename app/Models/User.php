<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Settings;

final class User
{
    private static ?bool $twoFactorColumnExists = null;
    private static ?bool $twoFactorSecretColumnExists = null;
    private static ?bool $twoFactorPromptColumnExists = null;

    public static function buscarPorLogin(string $login): ?array
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        $twoFactorField = self::twoFactorSelectField('users');
        $twoFactorPromptField = self::twoFactorPromptSelectField('users');
        $sql = 'SELECT id, nombre, email, username, empleado_id, nivel_acceso_id, role, password_hash, ' . $twoFactorField . ', ' . $twoFactorPromptField . ', is_active
                FROM users
                WHERE email = :login_email OR username = :login_username';
        $params = [
            'login_email' => $login,
            'login_username' => $login,
        ];

        if (str_contains($login, '@')) {
            [$localPart, $domainPart] = explode('@', $login, 2);
            $dominio = strtolower((string) Settings::get('dominio', ''));
            if ($dominio !== '' && strtolower($domainPart) === $dominio && $localPart !== '') {
                $sql .= ' OR username = :local_part';
                $params['local_part'] = $localPart;
            }
        }

        $sql .= ' LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($params);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $twoFactorField = self::twoFactorSelectField('u');
        $twoFactorSecretField = self::twoFactorSecretSelectField('u');
        $twoFactorPromptField = self::twoFactorPromptSelectField('u');
        $sql = 'SELECT u.id, u.nombre, u.email, u.username, u.empleado_id, u.nivel_acceso_id, u.role, ' . $twoFactorField . ', ' . $twoFactorSecretField . ', ' . $twoFactorPromptField . ', u.is_active, u.created_at, e.foto_path
                FROM users u
                LEFT JOIN empleados e ON e.id = u.empleado_id
                WHERE u.id = :id
                LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public static function buscarConPasswordPorId(int $id): ?array
    {
        $twoFactorField = self::twoFactorSelectField('users');
        $twoFactorSecretField = self::twoFactorSecretSelectField('users');
        $sql = 'SELECT id, password_hash, ' . $twoFactorField . ', ' . $twoFactorSecretField . ' FROM users WHERE id = :id LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public static function actualizarPassword(int $id, string $nuevoHash): void
    {
        $sql = 'UPDATE users SET password_hash = :password_hash WHERE id = :id';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'password_hash' => $nuevoHash,
        ]);
    }

    public static function setTwoFactor(int $id, bool $enabled): void
    {
        if (!self::hasTwoFactorColumn()) {
            return;
        }

        $sql = 'UPDATE users SET two_factor_enabled = :enabled WHERE id = :id';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'enabled' => $enabled ? 1 : 0,
        ]);
    }

    public static function guardarTwoFactorSecret(int $id, string $secret): void
    {
        if (!self::hasTwoFactorColumn() || !self::hasTwoFactorSecretColumn()) {
            return;
        }

        $sql = 'UPDATE users SET two_factor_enabled = 1, two_factor_secret = :secret WHERE id = :id';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'secret' => $secret,
        ]);
    }

    public static function disableTwoFactor(int $id): void
    {
        if (!self::hasTwoFactorColumn()) {
            return;
        }

        if (self::hasTwoFactorSecretColumn()) {
            $sql = 'UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = :id';
        } else {
            $sql = 'UPDATE users SET two_factor_enabled = 0 WHERE id = :id';
        }

        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public static function setTwoFactorPromptDisabled(int $id, bool $disabled): bool
    {
        if (!self::hasTwoFactorPromptColumn()) {
            return false;
        }

        $sql = 'UPDATE users SET two_factor_prompt_disabled = :disabled WHERE id = :id';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'disabled' => $disabled ? 1 : 0,
        ]);

        return true;
    }

    private static function twoFactorSelectField(string $tableAlias): string
    {
        if (self::hasTwoFactorColumn()) {
            return $tableAlias . '.two_factor_enabled';
        }

        return '0 AS two_factor_enabled';
    }

    private static function hasTwoFactorColumn(): bool
    {
        if (self::$twoFactorColumnExists !== null) {
            return self::$twoFactorColumnExists;
        }

        $stmt = Db::conexion()->query("SHOW COLUMNS FROM users LIKE 'two_factor_enabled'");
        self::$twoFactorColumnExists = (bool) $stmt->fetch();

        return self::$twoFactorColumnExists;
    }

    public static function hasTwoFactorSecretColumn(): bool
    {
        if (self::$twoFactorSecretColumnExists !== null) {
            return self::$twoFactorSecretColumnExists;
        }

        $stmt = Db::conexion()->query("SHOW COLUMNS FROM users LIKE 'two_factor_secret'");
        self::$twoFactorSecretColumnExists = (bool) $stmt->fetch();

        return self::$twoFactorSecretColumnExists;
    }

    public static function hasTwoFactorPromptColumn(): bool
    {
        if (self::$twoFactorPromptColumnExists !== null) {
            return self::$twoFactorPromptColumnExists;
        }

        $stmt = Db::conexion()->query("SHOW COLUMNS FROM users LIKE 'two_factor_prompt_disabled'");
        self::$twoFactorPromptColumnExists = (bool) $stmt->fetch();

        return self::$twoFactorPromptColumnExists;
    }

    private static function twoFactorSecretSelectField(string $tableAlias): string
    {
        if (self::hasTwoFactorSecretColumn()) {
            return $tableAlias . '.two_factor_secret';
        }

        return 'NULL AS two_factor_secret';
    }

    private static function twoFactorPromptSelectField(string $tableAlias): string
    {
        if (self::hasTwoFactorPromptColumn()) {
            return $tableAlias . '.two_factor_prompt_disabled';
        }

        return '0 AS two_factor_prompt_disabled';
    }
}
