<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf_token'];
    }

    public static function validar(?string $token): bool
    {
        $tokenSesion = $_SESSION['_csrf_token'] ?? '';
        return is_string($token) && is_string($tokenSesion) && hash_equals($tokenSesion, $token);
    }
}
