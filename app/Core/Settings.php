<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

final class Settings
{
    private const DEFAULTS = [
        'items_por_pagina' => 15,
        'dominio' => 'cellphone.do',
        'session_timeout' => 60,
        'expired_order' => 0,
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'two_factor_prompt_disabled' => 0,
    ];

    private const NUMERIC_KEYS = [
        'items_por_pagina',
        'session_timeout',
        'expired_order',
        'smtp_port',
        'two_factor_prompt_disabled',
    ];

    public static function get(string $clave, mixed $porDefecto = null): mixed
    {
        $envKey = 'SETTING_' . strtoupper($clave);
        $envValue = getenv($envKey);

        if ($envValue !== false && $envValue !== '') {
            if (self::isNumericSetting($clave) && is_numeric($envValue)) {
                return (int) $envValue;
            }

            return $envValue;
        }

        try {
            $sql = 'SELECT valor FROM settings WHERE clave = :clave LIMIT 1';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute(['clave' => $clave]);
            $valorDb = $stmt->fetchColumn();

            if ($valorDb !== false && $valorDb !== null && $valorDb !== '') {
                if (self::isNumericSetting($clave) && is_numeric((string) $valorDb)) {
                    return (int) $valorDb;
                }

                return (string) $valorDb;
            }
        } catch (Throwable) {
            // Si falla DB, usar fallback.
        }

        if (array_key_exists($clave, self::DEFAULTS)) {
            return self::DEFAULTS[$clave];
        }

        return $porDefecto;
    }

    public static function set(string $clave, string $valor): void
    {
        $sql = 'INSERT INTO settings (clave, valor) VALUES (:clave, :valor)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'clave' => $clave,
            'valor' => $valor,
        ]);
    }

    /**
     * @param string[] $claves
     * @return array<string, mixed>
     */
    public static function many(array $claves): array
    {
        $resultado = [];

        foreach ($claves as $clave) {
            $resultado[$clave] = self::get($clave, self::DEFAULTS[$clave] ?? null);
        }

        return $resultado;
    }

    public static function encryptValue(string $plain): string
    {
        $plain = trim($plain);
        if ($plain === '') {
            return '';
        }

        $key = self::encryptionKey();
        if ($key === null) {
            return 'plain:' . base64_encode($plain);
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if (!is_string($cipher)) {
            throw new RuntimeException('No se pudo cifrar el valor.');
        }

        return 'enc:' . base64_encode($iv . $tag . $cipher);
    }

    public static function decryptValue(string $cipherText): string
    {
        $cipherText = trim($cipherText);
        if ($cipherText === '') {
            return '';
        }

        if (str_starts_with($cipherText, 'plain:')) {
            $raw = base64_decode(substr($cipherText, 6), true);
            return $raw === false ? '' : $raw;
        }

        if (!str_starts_with($cipherText, 'enc:')) {
            return $cipherText;
        }

        $payload = base64_decode(substr($cipherText, 4), true);
        if ($payload === false || strlen($payload) < 28) {
            return '';
        }

        $key = self::encryptionKey();
        if ($key === null) {
            return '';
        }

        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $cipher = substr($payload, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return is_string($plain) ? $plain : '';
    }

    private static function encryptionKey(): ?string
    {
        $rawKey = trim((string) (getenv('APP_KEY') ?: ''));
        if ($rawKey === '') {
            return null;
        }

        if (str_starts_with($rawKey, 'base64:')) {
            $decoded = base64_decode(substr($rawKey, 7), true);
            if ($decoded === false || $decoded === '') {
                return null;
            }
            return hash('sha256', $decoded, true);
        }

        return hash('sha256', $rawKey, true);
    }

    private static function isNumericSetting(string $clave): bool
    {
        return in_array($clave, self::NUMERIC_KEYS, true);
    }
}
