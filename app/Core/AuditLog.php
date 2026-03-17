<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class AuditLog
{
    public static function write(string $evento, array $datos = []): void
    {
        $userId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        $rutaNormalizada = parse_url($uri, PHP_URL_PATH) ?: '/';
        $tipoAccion = self::sanitizeShort((string) ($datos['tipo_accion'] ?? $evento), 80);
        $apartado = self::sanitizeShort((string) ($datos['apartado'] ?? $rutaNormalizada), 160);
        $descripcion = isset($datos['descripcion']) ? self::sanitizeShort((string) $datos['descripcion'], 255) : null;

        unset($datos['tipo_accion'], $datos['apartado'], $datos['descripcion']);

        $payload = [
            'timestamp' => date('c'),
            'event' => $evento,
            'user_id' => $userId,
            'ip' => $ip,
            'method' => $metodo,
            'uri' => $uri,
            'ua' => substr($ua, 0, 250),
            'data' => $datos,
        ];

        try {
            $sql = 'INSERT INTO activity_logs (
                        user_id, tipo_accion, apartado, evento, descripcion, metodo, ruta, ip, user_agent, datos_json
                    ) VALUES (
                        :user_id, :tipo_accion, :apartado, :evento, :descripcion, :metodo, :ruta, :ip, :user_agent, :datos_json
                    )';

            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'tipo_accion' => $tipoAccion,
                'apartado' => $apartado,
                'evento' => self::sanitizeShort($evento, 160),
                'descripcion' => $descripcion,
                'metodo' => self::sanitizeShort($metodo, 10),
                'ruta' => self::sanitizeShort($rutaNormalizada, 255),
                'ip' => self::sanitizeShort($ip, 45),
                'user_agent' => self::sanitizeShort($ua, 255),
                'datos_json' => json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            return;
        } catch (Throwable) {
            // Fallback a archivo para no perder eventos si DB falla.
        }

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }
        $path = dirname(__DIR__, 2) . '/storage/logs/activity.log';
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function sanitizeShort(string $value, int $max): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (mb_strlen($trimmed) <= $max) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, $max);
    }
}
