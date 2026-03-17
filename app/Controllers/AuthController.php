<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\EdicionLock;

final class AuthController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEGUNDOS = 300;

    public function mostrarLogin(): void
    {
        if (($_GET['reiniciar'] ?? '') === '1') {
            Auth::cancelarLogin2fa();
            $this->redirect('/login');
        }

        $this->render('sistema/auth/login', [
            'titulo' => 'Iniciar sesión',
            'csrf' => Csrf::token(),
            'error' => $_SESSION['flash_error'] ?? null,
            'info' => $_SESSION['flash_info'] ?? null,
            'estado2fa' => Auth::datosLogin2fa(),
            'twoFactorEnabled' => Auth::twoFactorEnabled(),
        ]);

        unset($_SESSION['flash_error']);
        unset($_SESSION['flash_info']);
    }

    public function login(): void
    {
        $ip = $this->obtenerIp();

        if (!$this->csrfValido()) {
            AuditLog::write('auth.login.csrf_invalid', [
                'tipo_accion' => 'login',
                'apartado' => '/login',
                'descripcion' => 'Intento de login con token CSRF invalido',
            ]);
            $_SESSION['flash_error'] = 'Solicitud inválida. Recarga la página e inténtalo nuevamente.';
            $this->redirect('/login');
        }

        if ($this->estaBloqueado($ip)) {
            AuditLog::write('auth.login.blocked', [
                'tipo_accion' => 'login_bloqueado',
                'apartado' => '/login',
                'descripcion' => 'IP bloqueada por demasiados intentos',
                'ip_detectada' => $ip,
            ]);
            $segundos = max(1, (int) (($_SESSION['login_intentos'][$ip]['bloqueado_hasta'] ?? time()) - time()));
            $_SESSION['flash_error'] = 'Demasiados intentos. Espera ' . $segundos . ' segundos.';
            $this->redirect('/login');
        }

        $paso = $_POST['paso'] ?? 'credenciales';

        if ($paso === 'codigo') {
            $this->verificarCodigo2fa();
            return;
        }

        $login = trim((string) ($_POST['email'] ?? ''));
        $contrasena = $_POST['password'] ?? '';

        if ($login === '' || $contrasena === '') {
            AuditLog::write('auth.login.missing_credentials', [
                'tipo_accion' => 'login',
                'apartado' => '/login',
                'descripcion' => 'Faltan credenciales requeridas',
                'login' => $login,
            ]);
            $_SESSION['flash_error'] = 'Usuario/correo y contraseña son obligatorios.';
            $this->redirect('/login');
        }

        $usuario = Auth::validarCredenciales($login, (string) $contrasena);
        if (!$usuario) {
            $this->registrarIntentoFallido($ip);
            AuditLog::write('auth.login.failed', [
                'tipo_accion' => 'login_fallido',
                'apartado' => '/login',
                'descripcion' => 'Credenciales invalidas o usuario inactivo',
                'login' => $login,
            ]);
            $_SESSION['flash_error'] = 'Credenciales inválidas o usuario inactivo.';
            $this->redirect('/login');
        }

        $this->limpiarIntentos($ip);

        if (!Auth::twoFactorEnabledForUser($usuario)) {
            Auth::completarLogin($usuario);
            if (!(bool) ($usuario['two_factor_prompt_disabled'] ?? false)) {
                $_SESSION['open_two_factor_prompt'] = '1';
            }
            AuditLog::write('auth.login.success', [
                'tipo_accion' => 'login_exitoso',
                'apartado' => '/dashboard',
                'descripcion' => 'Inicio de sesion exitoso',
                'user_id' => (int) ($usuario['id'] ?? 0),
                'email' => (string) ($usuario['email'] ?? $login),
            'two_factor' => false,
            ]);
            $this->redirect('/dashboard');
        }

        Auth::iniciarLogin2fa($usuario, $login);
        AuditLog::write('auth.login.2fa_started', [
            'tipo_accion' => 'login_2fa_inicio',
            'apartado' => '/login',
            'descripcion' => 'Se inicio proceso de doble factor por autenticador',
            'user_id' => (int) ($usuario['id'] ?? 0),
            'email' => (string) ($usuario['email'] ?? $login),
        ]);
        $_SESSION['flash_info'] = 'Ingresa el codigo de tu app autenticadora para completar el acceso.';

        $this->redirect('/login');
    }

    public function logout(): void
    {
        $usuario = Auth::user();
        $usuarioId = (int) ($usuario['id'] ?? 0);
        AuditLog::write('auth.logout', [
            'tipo_accion' => 'logout',
            'apartado' => '/logout',
            'descripcion' => 'Cierre de sesion',
            'user_id' => $usuarioId,
            'email' => (string) ($usuario['email'] ?? ''),
        ]);
        EdicionLock::releaseAllByUser($usuarioId);
        Auth::cancelarLogin2fa();
        Auth::logout();
        $this->redirect('/login');
    }

    private function verificarCodigo2fa(): void
    {
        $codigo = trim((string) ($_POST['codigo_2fa'] ?? ''));
        $codigo = preg_replace('/\\D/', '', $codigo) ?? '';

        if ($codigo === '') {
            AuditLog::write('auth.2fa.empty_code', [
                'tipo_accion' => '2fa',
                'apartado' => '/login',
                'descripcion' => 'Codigo 2FA vacio',
            ]);
            $_SESSION['flash_error'] = 'Ingresa el código de verificación.';
            $this->redirect('/login');
        }

        if (!Auth::verificarCodigo2fa($codigo)) {
            AuditLog::write('auth.2fa.failed', [
                'tipo_accion' => '2fa_fallido',
                'apartado' => '/login',
                'descripcion' => 'Codigo 2FA invalido o expirado',
            ]);
            $_SESSION['flash_error'] = 'Código inválido, expirado o superaste los intentos permitidos.';
            $this->redirect('/login');
        }

        $usuario = Auth::user();
        AuditLog::write('auth.login.success', [
            'tipo_accion' => 'login_exitoso',
            'apartado' => '/dashboard',
            'descripcion' => 'Inicio de sesion exitoso con 2FA',
            'user_id' => (int) ($usuario['id'] ?? 0),
            'email' => (string) ($usuario['email'] ?? ''),
            'two_factor' => true,
        ]);
        $this->redirect('/dashboard');
    }

    private function csrfValido(): bool
    {
        $token = $_POST['_csrf'] ?? null;
        return Csrf::validar(is_string($token) ? $token : null);
    }

    private function obtenerIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function estaBloqueado(string $ip): bool
    {
        $registro = $_SESSION['login_intentos'][$ip] ?? null;

        if (!$registro) {
            return false;
        }

        $bloqueadoHasta = (int) ($registro['bloqueado_hasta'] ?? 0);

        if ($bloqueadoHasta <= time()) {
            $this->limpiarIntentos($ip);
            return false;
        }

        return true;
    }

    private function registrarIntentoFallido(string $ip): void
    {
        if (!isset($_SESSION['login_intentos'][$ip])) {
            $_SESSION['login_intentos'][$ip] = [
                'conteo' => 0,
                'bloqueado_hasta' => 0,
            ];
        }

        $_SESSION['login_intentos'][$ip]['conteo']++;

        if ($_SESSION['login_intentos'][$ip]['conteo'] >= self::MAX_INTENTOS) {
            $_SESSION['login_intentos'][$ip]['bloqueado_hasta'] = time() + self::BLOQUEO_SEGUNDOS;
        }
    }

    private function limpiarIntentos(string $ip): void
    {
        unset($_SESSION['login_intentos'][$ip]);
    }
}
