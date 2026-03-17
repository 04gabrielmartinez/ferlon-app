<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class SmtpClient
{
    /** @var resource|null */
    private $socket = null;

    public function sendTest(array $config, string $to): void
    {
        $host = trim((string) ($config['host'] ?? ''));
        $port = (int) ($config['port'] ?? 0);
        $user = trim((string) ($config['user'] ?? ''));
        $password = (string) ($config['password'] ?? '');
        $encryption = strtolower(trim((string) ($config['encryption'] ?? 'none')));
        $fromEmail = trim((string) ($config['from_email'] ?? ''));
        $fromName = trim((string) ($config['from_name'] ?? 'Sistema'));
        $replyTo = trim((string) ($config['reply_to'] ?? ''));

        if ($host === '' || $port < 1 || $port > 65535 || $fromEmail === '') {
            throw new RuntimeException('Configuracion SMTP incompleta.');
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ],
        ]);

        $transportHost = $encryption === 'ssl' ? ('ssl://' . $host) : $host;
        $this->socket = @stream_socket_client($transportHost . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($this->socket)) {
            $last = error_get_last();
            $extra = is_array($last) ? (' ' . (string) ($last['message'] ?? '')) : '';
            throw new RuntimeException('No se pudo conectar al servidor SMTP: ' . trim($errstr . $extra));
        }

        stream_set_timeout($this->socket, 15);

        $this->expectCode([220], $this->readResponse());
        $this->command('EHLO ferlon.local', [250]);

        if ($encryption === 'tls') {
            $this->command('STARTTLS', [220]);
            $crypto = @stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto !== true) {
                throw new RuntimeException('No se pudo iniciar cifrado TLS.');
            }
            $this->command('EHLO ferlon.local', [250]);
        }

        if ($user !== '') {
            $this->command('AUTH LOGIN', [334]);
            $this->command(base64_encode($user), [334]);
            $this->command(base64_encode($password), [235]);
        }

        $this->command('MAIL FROM:<' . $fromEmail . '>', [250]);
        $this->command('RCPT TO:<' . $to . '>', [250, 251]);
        $this->command('DATA', [354]);

        $subject = 'Prueba SMTP - FERLON';
        $body = 'Correo de prueba SMTP enviado correctamente desde FERLON.';
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->formatAddress($fromEmail, $fromName),
            'To: <' . $to . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        fwrite($this->socket, $message . "\r\n");
        $this->expectCode([250], $this->readResponse());

        $this->command('QUIT', [221]);
        fclose($this->socket);
        $this->socket = null;
    }

    /**
     * @return array{code:int,text:string}
     */
    private function readResponse(): array
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Conexion SMTP no disponible.');
        }

        $text = '';
        $code = 0;

        while (($line = fgets($this->socket, 1024)) !== false) {
            $text .= $line;
            if (preg_match('/^(\d{3})([\s-])/', $line, $match)) {
                $code = (int) $match[1];
                if ($match[2] === ' ') {
                    break;
                }
            }
        }

        if ($code === 0) {
            throw new RuntimeException('Respuesta SMTP invalida.');
        }

        return [
            'code' => $code,
            'text' => trim($text),
        ];
    }

    /**
     * @param int[] $expected
     * @param array{code:int,text:string} $response
     */
    private function expectCode(array $expected, array $response): void
    {
        if (!in_array($response['code'], $expected, true)) {
            throw new RuntimeException('SMTP error [' . $response['code'] . ']: ' . $response['text']);
        }
    }

    /**
     * @param int[] $expected
     */
    private function command(string $command, array $expected): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Conexion SMTP no disponible.');
        }

        fwrite($this->socket, $command . "\r\n");
        $this->expectCode($expected, $this->readResponse());
    }

    private function formatAddress(string $email, string $name): string
    {
        $safeName = trim($name);
        if ($safeName === '') {
            return '<' . $email . '>';
        }

        return '"' . str_replace('"', '\"', $safeName) . '" <' . $email . '>';
    }
}
