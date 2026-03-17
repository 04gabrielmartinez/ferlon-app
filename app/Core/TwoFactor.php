<?php

declare(strict_types=1);

namespace App\Core;

final class TwoFactor
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 32): string
    {
        $alphabet = self::BASE32_ALPHABET;
        $max = strlen($alphabet) - 1;
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, $max)];
        }

        return $secret;
    }

    public static function buildOtpAuthUri(string $issuer, string $account, string $secret): string
    {
        $label = rawurlencode($issuer . ':' . $account);
        $issuerEncoded = rawurlencode($issuer);
        $secretEncoded = rawurlencode($secret);

        return "otpauth://totp/{$label}?secret={$secretEncoded}&issuer={$issuerEncoded}&algorithm=SHA1&digits=6&period=30";
    }

    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::totpAt($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private static function totpAt(string $secret, int $timeSlice): string
    {
        $secretKey = self::base32Decode($secret);
        if ($secretKey === '') {
            return '';
        }

        $binaryTime = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $binaryTime, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $part = substr($hash, $offset, 4);
        $unpacked = unpack('N', $part);
        $value = ($unpacked[1] ?? 0) & 0x7FFFFFFF;
        $mod = 10 ** 6;

        return str_pad((string) ($value % $mod), 6, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $input): string
    {
        $clean = strtoupper(trim($input));
        $clean = str_replace('=', '', $clean);
        if ($clean === '') {
            return '';
        }

        $binaryString = '';
        $alphabet = self::BASE32_ALPHABET;
        $length = strlen($clean);

        for ($i = 0; $i < $length; $i++) {
            $char = $clean[$i];
            $position = strpos($alphabet, $char);
            if ($position === false) {
                return '';
            }
            $binaryString .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        $binLength = strlen($binaryString);
        for ($i = 0; $i + 8 <= $binLength; $i += 8) {
            $decoded .= chr(bindec(substr($binaryString, $i, 8)));
        }

        return $decoded;
    }
}
