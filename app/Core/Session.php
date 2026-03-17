<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private const ROTACION_SEGUNDOS = 900;

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) === '443');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_secure', $esHttps ? '1' : '0');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $esHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::rotarIdSiCorresponde();
    }

    public static function regenerarId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['_rotado_en'] = time();
        }
    }

    private static function rotarIdSiCorresponde(): void
    {
        $ultimo = $_SESSION['_rotado_en'] ?? 0;
        if ((time() - (int) $ultimo) > self::ROTACION_SEGUNDOS) {
            self::regenerarId();
        }
    }

    public static function destruir(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }
}
