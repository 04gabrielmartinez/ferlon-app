<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\TwoFactor;
use App\Models\User;

final class MiCuentaController extends Controller
{
    public function cambiarPassword(): void
    {
        $usuario = Auth::user();
        if (!$usuario) {
            $this->redirect('/login');
        }

        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $this->toast('danger', 'Seguridad', 'Token CSRF invalido.');
            $this->redirectOrigen();
        }

        $actual = (string) ($_POST['password_actual'] ?? '');
        $nuevo = (string) ($_POST['password_nuevo'] ?? '');
        $confirmacion = (string) ($_POST['password_confirmacion'] ?? '');

        if ($actual === '' || $nuevo === '' || $confirmacion === '') {
            $this->toast('warning', 'Mi cuenta', 'Completa todos los campos de contraseña.');
            $this->redirectOrigen();
        }

        if ($nuevo !== $confirmacion) {
            $this->toast('warning', 'Mi cuenta', 'Las contraseñas nuevas no coinciden.');
            $this->redirectOrigen();
        }

        if (!$this->passwordValido($nuevo)) {
            $this->toast('warning', 'Mi cuenta', 'La nueva contraseña no cumple la política requerida.');
            $this->redirectOrigen();
        }

        $userSecure = User::buscarConPasswordPorId((int) ($usuario['id'] ?? 0));
        if (!$userSecure || !password_verify($actual, (string) ($userSecure['password_hash'] ?? ''))) {
            $this->toast('danger', 'Mi cuenta', 'La contraseña actual es incorrecta.');
            AuditLog::write('cuenta.password.fail', [
                'tipo_accion' => 'mi_cuenta_password_fallido',
                'apartado' => '/mi-cuenta/password',
                'descripcion' => 'Intento fallido de cambio de contraseña',
                'user_id' => (int) ($usuario['id'] ?? 0),
            ]);
            $this->redirectOrigen();
        }

        User::actualizarPassword((int) $usuario['id'], password_hash($nuevo, PASSWORD_DEFAULT));
        AuditLog::write('cuenta.password.ok', [
            'tipo_accion' => 'mi_cuenta_password',
            'apartado' => '/mi-cuenta/password',
            'descripcion' => 'Contrasena cambiada por el usuario',
            'user_id' => (int) ($usuario['id'] ?? 0),
        ]);

        $this->toast('success', 'Mi cuenta', 'Contraseña actualizada correctamente.');
        $this->redirectOrigen();
    }

    public function configurar2fa(): void
    {
        $usuario = Auth::user();
        if (!$usuario) {
            $this->redirect('/login');
        }

        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $this->toast('danger', 'Seguridad', 'Token CSRF invalido.');
            $this->redirectOrigen();
        }

        if (!User::hasTwoFactorSecretColumn()) {
            $this->toast('warning', 'Mi cuenta', 'Falta columna users.two_factor_secret. Actualiza la base de datos.');
            $this->redirectOrigen();
        }

        $action = (string) ($_POST['action'] ?? '');
        $userId = (int) ($usuario['id'] ?? 0);

        if ($action === 'disable') {
            User::disableTwoFactor($userId);
            Auth::clearPendingTwoFactorSetup($userId);
            AuditLog::write('cuenta.2fa.disabled', [
                'tipo_accion' => 'mi_cuenta_2fa',
                'apartado' => '/mi-cuenta/2fa',
                'descripcion' => 'Usuario desactivo 2FA',
                'user_id' => $userId,
                'two_factor_enabled' => false,
            ]);
            $this->toast('success', 'Mi cuenta', '2FA desactivado.');
            $this->redirectOrigen();
        }

        if ($action !== 'enable') {
            $this->toast('warning', 'Mi cuenta', 'Accion de 2FA no valida.');
            $this->redirectOrigen();
        }

        $codigo = trim((string) ($_POST['codigo_2fa'] ?? ''));
        $secret = Auth::getPendingTwoFactorSecret($userId);
        if ($secret === '') {
            $pending = Auth::getOrCreatePendingTwoFactorSetup($usuario);
            $secret = trim((string) ($pending['secret'] ?? ''));
        }

        if ($secret === '' || !TwoFactor::verifyCode($secret, $codigo)) {
            $_SESSION['open_two_factor_modal'] = '1';
            $this->toast('danger', 'Mi cuenta', 'Codigo 2FA invalido. Verifica y vuelve a intentar.');
            AuditLog::write('cuenta.2fa.enable_fail', [
                'tipo_accion' => 'mi_cuenta_2fa_fallido',
                'apartado' => '/mi-cuenta/2fa',
                'descripcion' => 'Intento fallido al activar 2FA',
                'user_id' => $userId,
            ]);
            $this->redirectOrigen();
        }

        User::guardarTwoFactorSecret($userId, $secret);
        Auth::clearPendingTwoFactorSetup($userId);

        AuditLog::write('cuenta.2fa.enabled', [
            'tipo_accion' => 'mi_cuenta_2fa',
            'apartado' => '/mi-cuenta/2fa',
            'descripcion' => 'Usuario activo 2FA con autenticador',
            'user_id' => $userId,
            'two_factor_enabled' => true,
        ]);

        $this->toast('success', 'Mi cuenta', '2FA activado correctamente.');
        $this->redirectOrigen();
    }

    public function configurarPrompt2fa(): void
    {
        $usuario = Auth::user();
        if (!$usuario) {
            $this->redirect('/login');
        }

        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $this->toast('danger', 'Seguridad', 'Token CSRF invalido.');
            $this->redirectOrigen();
        }

        $disabled = isset($_POST['prompt_disabled']) && (string) $_POST['prompt_disabled'] === '1';
        $userId = (int) ($usuario['id'] ?? 0);
        $ok = User::setTwoFactorPromptDisabled($userId, $disabled);

        if (!$ok) {
            $this->toast('warning', 'Mi cuenta', 'Falta columna users.two_factor_prompt_disabled para guardar esta preferencia.');
            $this->redirectOrigen();
        }

        AuditLog::write('cuenta.2fa.prompt', [
            'tipo_accion' => 'mi_cuenta_2fa_prompt',
            'apartado' => '/mi-cuenta/2fa/prompt',
            'descripcion' => $disabled ? 'Usuario desactivo recordatorio de 2FA en login' : 'Usuario activo recordatorio de 2FA en login',
            'user_id' => $userId,
            'prompt_disabled' => $disabled,
        ]);

        if ($disabled) {
            $_SESSION['open_two_factor_prompt'] = '0';
        }

        $this->toast('success', 'Mi cuenta', $disabled ? 'No se mostrara el recordatorio de 2FA al iniciar sesion.' : 'Recordatorio de 2FA activado.');
        $this->redirectOrigen();
    }

    private function passwordValido(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    private function redirectOrigen(): void
    {
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $path = parse_url($ref, PHP_URL_PATH);
        $query = parse_url($ref, PHP_URL_QUERY);

        if (!is_string($path) || $path === '' || $path[0] !== '/') {
            $this->redirect('/dashboard');
        }

        $destino = $path . (is_string($query) && $query !== '' ? ('?' . $query) : '');
        $this->redirect($destino);
    }

    private function toast(string $type, string $title, string $message): void
    {
        $_SESSION['flash_toast'] = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ];
    }
}
