<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private const VIDA_CODIGO_2FA = 300;
    private const MAX_INTENTOS_2FA = 5;
    private static ?array $permissionCache = null;

    public static function validarCredenciales(string $login, string $contrasena): ?array
    {
        $usuario = User::buscarPorLogin($login);

        if (!$usuario || !(bool) $usuario['is_active']) {
            return null;
        }

        if (!password_verify($contrasena, (string) $usuario['password_hash'])) {
            return null;
        }

        return $usuario;
    }

    public static function login(string $login, string $contrasena): bool
    {
        $usuario = self::validarCredenciales($login, $contrasena);
        if (!$usuario) {
            return false;
        }

        self::completarLogin($usuario);
        return true;
    }

    public static function completarLogin(array $usuario): void
    {
        Session::regenerarId();
        $_SESSION['usuario_id'] = (int) $usuario['id'];
        self::$permissionCache = null;
    }

    public static function logout(): void
    {
        self::$permissionCache = null;
        Session::destruir();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return User::buscarPorId((int) $_SESSION['usuario_id']);
    }

    public static function hasPermission(string $permission): bool
    {
        $permission = strtolower(trim($permission));
        if ($permission === '') {
            return false;
        }

        $permissions = self::permissionKeys();
        if ($permissions === []) {
            // Compatibilidad: mientras no existan permisos asignados al usuario, usa rol.
            return self::hasRole('admin');
        }

        return in_array($permission, $permissions, true);
    }

    public static function hasAnyPermission(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function iniciarLogin2fa(array $usuario, string $correo): void
    {
        $_SESSION['login_2fa'] = [
            'usuario_id' => (int) $usuario['id'],
            'correo' => $correo,
            'expira_en' => time() + self::VIDA_CODIGO_2FA,
            'intentos' => 0,
        ];
    }

    public static function verificarCodigo2fa(string $codigo): bool
    {
        $pendiente = $_SESSION['login_2fa'] ?? null;

        if (!is_array($pendiente)) {
            return false;
        }

        if ((int) ($pendiente['expira_en'] ?? 0) < time()) {
            self::cancelarLogin2fa();
            return false;
        }

        $intentos = (int) ($pendiente['intentos'] ?? 0);
        if ($intentos >= self::MAX_INTENTOS_2FA) {
            self::cancelarLogin2fa();
            return false;
        }

        $_SESSION['login_2fa']['intentos'] = $intentos + 1;
        $usuario = User::buscarConPasswordPorId((int) ($pendiente['usuario_id'] ?? 0));
        $secret = trim((string) ($usuario['two_factor_secret'] ?? ''));
        if ($secret === '' || !TwoFactor::verifyCode($secret, $codigo)) {
            return false;
        }

        Session::regenerarId();
        $_SESSION['usuario_id'] = (int) $pendiente['usuario_id'];
        self::cancelarLogin2fa();

        return true;
    }

    public static function datosLogin2fa(): ?array
    {
        $pendiente = $_SESSION['login_2fa'] ?? null;
        if (!is_array($pendiente)) {
            return null;
        }

        if ((int) ($pendiente['expira_en'] ?? 0) < time()) {
            self::cancelarLogin2fa();
            return null;
        }

        return [
            'correo' => (string) ($pendiente['correo'] ?? ''),
            'expira_en' => (int) ($pendiente['expira_en'] ?? 0),
        ];
    }

    public static function cancelarLogin2fa(): void
    {
        unset($_SESSION['login_2fa']);
    }

    public static function twoFactorEnabled(): bool
    {
        return filter_var(getenv('AUTH_2FA_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL);
    }

    public static function twoFactorEnabledForUser(array $usuario): bool
    {
        if (array_key_exists('two_factor_enabled', $usuario)) {
            return (bool) $usuario['two_factor_enabled'];
        }

        return self::twoFactorEnabled();
    }

    public static function hasRole(string ...$roles): bool
    {
        $usuario = self::user();
        if (!$usuario) {
            return false;
        }

        $rolUsuario = strtolower((string) ($usuario['role'] ?? ''));
        $rolesNormalizados = array_map(static fn (string $rol): string => strtolower(trim($rol)), $roles);

        return in_array($rolUsuario, $rolesNormalizados, true);
    }

    /**
     * @return array<int, string>
     */
    private static function permissionKeys(): array
    {
        if (!self::check()) {
            return [];
        }

        if (self::$permissionCache !== null) {
            return self::$permissionCache;
        }

        $userId = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($userId <= 0) {
            self::$permissionCache = [];
            return [];
        }

        try {
            $sql = 'SELECT DISTINCT LOWER(TRIM(p.clave)) AS clave
                    FROM users u
                    INNER JOIN nivel_permiso np ON np.nivel_id = u.nivel_acceso_id
                    INNER JOIN permisos p ON p.id = np.permiso_id
                    WHERE u.id = :user_id
                      AND p.clave IS NOT NULL
                      AND TRIM(p.clave) <> ""';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $rows = $stmt->fetchAll() ?: [];
            self::$permissionCache = array_values(array_filter(array_map(
                static fn (array $row): string => strtolower(trim((string) ($row['clave'] ?? ''))),
                $rows
            )));
        } catch (\Throwable) {
            self::$permissionCache = [];
        }

        return self::$permissionCache;
    }

    public static function getOrCreatePendingTwoFactorSetup(array $usuario): ?array
    {
        $userId = (int) ($usuario['id'] ?? 0);
        if ($userId <= 0 || !User::hasTwoFactorSecretColumn()) {
            return null;
        }

        if ((bool) ($usuario['two_factor_enabled'] ?? false)) {
            return null;
        }

        $existing = trim((string) ($usuario['two_factor_secret'] ?? ''));
        if ($existing !== '') {
            $secret = $existing;
        } else {
            $secret = trim((string) ($_SESSION['two_factor_setup'][$userId] ?? ''));
            if ($secret === '') {
                $secret = TwoFactor::generateSecret();
                $_SESSION['two_factor_setup'][$userId] = $secret;
            }
        }

        $issuer = trim((string) (getenv('APP_NAME') ?: 'FERLON'));
        $account = trim((string) ($usuario['email'] ?? $usuario['username'] ?? ('user-' . $userId)));
        $otpAuth = TwoFactor::buildOtpAuthUri($issuer, $account, $secret);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=190x190&data=' . rawurlencode($otpAuth);

        return [
            'secret' => $secret,
            'otp_auth' => $otpAuth,
            'qr_url' => $qrUrl,
        ];
    }

    public static function clearPendingTwoFactorSetup(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        unset($_SESSION['two_factor_setup'][$userId]);
    }

    public static function getPendingTwoFactorSecret(int $userId): string
    {
        return trim((string) ($_SESSION['two_factor_setup'][$userId] ?? ''));
    }
}
