<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Db
{
    private static ?PDO $conexion = null;

    public static function conexion(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $host = getenv('DB_HOST') ?: self::hostPorDefecto();
        $puerto = getenv('DB_PORT') ?: '3306';
        $baseDatos = getenv('DB_DATABASE') ?: 'ferlon';
        $usuario = getenv('DB_USERNAME') ?: 'ferlon';
        $clave = getenv('DB_PASSWORD') ?: '';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $puerto, $baseDatos);

        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            self::$conexion = new PDO($dsn, $usuario, $clave, $opciones);
            return self::$conexion;
        } catch (PDOException $e) {
            error_log('[DB] ' . $e->getMessage());
            http_response_code(500);
            exit('Error de conexion con la base de datos. Revisa .env y que MySQL este activo.');
        }
    }

    private static function hostPorDefecto(): string
    {
        return is_file('/.dockerenv') ? 'db' : '127.0.0.1';
    }
}
