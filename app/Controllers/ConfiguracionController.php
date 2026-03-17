<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Settings;
use App\Core\SmtpClient;
use App\Models\EdicionLock;
use App\Models\Secuencia;
use Throwable;

final class ConfiguracionController extends Controller
{
    private const EMAIL_LIST_FIELDS = [
        'correos_pedidos',
        'correos_minimo_inventario',
    ];

    public function index(): void
    {
        $tab = (string) ($_GET['tab'] ?? 'general');
        if (!in_array($tab, ['general', 'smtp', 'secuencias', 'temporales'], true)) {
            $tab = 'general';
        }
        if ($tab === 'secuencias') {
            Secuencia::ensureExists('oc', 'Orden Compra', 'OC', 5, 1);
            Secuencia::ensureExists('ct', 'Cotizacion', 'CT', 5, 1);
        }

        $keys = [
            'logo_path',
            'favicon_path',
            'session_timeout',
            'company_name',
            'company_phone',
            'company_mail',
            'company_address',
            'expired_order',
            'smtp_host',
            'smtp_port',
            'smtp_user',
            'smtp_password',
            'dominio',
            'correo_contacto',
            'correos_pedidos',
            'correos_minimo_inventario',
            'smtp_encryption',
            'smtp_from_name',
            'smtp_from_email',
            'smtp_reply_to',
            'smtp_test_to',
        ];

        $settings = Settings::many($keys);
        $settings['smtp_password_masked'] = trim((string) ($settings['smtp_password'] ?? '')) !== '' ? '********' : '';

        $secuenciaEditId = isset($_GET['secuencia_id']) ? (int) $_GET['secuencia_id'] : 0;
        $secuenciaEdit = $secuenciaEditId > 0 ? Secuencia::buscarPorId($secuenciaEditId) : null;

        $this->render('sistema/configuracion/index', [
            'titulo' => 'Configuracion',
            'csrf' => Csrf::token(),
            'tab' => $tab,
            'settings' => $settings,
            'secuencias' => Secuencia::listar(),
            'secuenciaEdit' => $secuenciaEdit ?: [],
            'aplicaAOptions' => Secuencia::opcionesAplicaA(),
            'locks' => $tab === 'temporales' ? EdicionLock::listarActivos() : [],
        ]);
    }

    public function registrosTemporales(): void
    {
        $this->render('sistema/registros-temporales/index', [
            'titulo' => 'Registros temporales',
            'csrf' => Csrf::token(),
            'locks' => EdicionLock::listarActivos(),
        ]);
    }

    public function guardar(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $this->toast('danger', 'Seguridad', 'Token CSRF invalido.');
            $this->redirect('/sistema/configuracion');
        }

        $accion = (string) ($_POST['accion'] ?? '');
        $isTestRequest = isset($_POST['probar_smtp']) && (string) $_POST['probar_smtp'] === '1';

        try {
            if ($accion === 'save_general') {
                $this->guardarGeneral();
                return;
            }

            if ($accion === 'save_secuencia') {
                $this->guardarSecuencia();
                return;
            }

            if ($accion === 'test_smtp' || $isTestRequest) {
                $this->guardarSmtp(true);
                return;
            }

            if ($accion === 'save_smtp') {
                $this->guardarSmtp(false);
                return;
            }

            if ($accion === 'clean_temporales') {
                EdicionLock::cleanupExpired();
                $this->toast('success', 'Temporales', 'Bloqueos expirados limpiados.');
                $this->redirect('/sistema/registros-temporales');
            }

            if ($accion === 'clean_temporales_all') {
                EdicionLock::limpiarTodo();
                $this->toast('success', 'Temporales', 'Todos los bloqueos fueron eliminados.');
                $this->redirect('/sistema/registros-temporales');
            }

            $this->toast('warning', 'Configuracion', 'Accion no valida.');
            $this->redirect('/sistema/configuracion');
        } catch (Throwable $e) {
            $this->toast('warning', 'Configuracion', $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo completar la operacion.');
            $tab = 'smtp';
            if ($accion === 'save_general') {
                $tab = 'general';
            } elseif ($accion === 'save_secuencia') {
                $tab = 'secuencias';
            } elseif ($accion === 'clean_temporales' || $accion === 'clean_temporales_all') {
                $tab = 'temporales';
            }
            $this->redirect('/sistema/configuracion?tab=' . $tab);
        }
    }

    private function guardarGeneral(): void
    {
        $sessionTimeout = (int) ($_POST['session_timeout'] ?? 0);
        $expiredOrder = (int) ($_POST['expired_order'] ?? 0);
        $companyMail = trim((string) ($_POST['company_mail'] ?? ''));

        if ($sessionTimeout < 5 || $sessionTimeout > 1440) {
            throw new \RuntimeException('Session timeout debe estar entre 5 y 1440 minutos.');
        }
        if ($expiredOrder < 0 || $expiredOrder > 365) {
            throw new \RuntimeException('Expired order debe estar entre 0 y 365 dias.');
        }
        if ($companyMail !== '' && !filter_var($companyMail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Company mail no tiene formato valido.');
        }

        $logoPath = $this->procesarBranding('logo', ['image/png', 'image/jpeg', 'image/svg+xml']);
        $faviconPath = $this->procesarBranding('favicon', ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon']);

        if ($logoPath !== null) {
            Settings::set('logo_path', $logoPath);
        }
        if ($faviconPath !== null) {
            Settings::set('favicon_path', $faviconPath);
        }

        $toSave = [
            'session_timeout' => (string) $sessionTimeout,
            'company_name' => trim((string) ($_POST['company_name'] ?? '')),
            'company_phone' => trim((string) ($_POST['company_phone'] ?? '')),
            'company_mail' => $companyMail,
            'company_address' => trim((string) ($_POST['company_address'] ?? '')),
            'expired_order' => (string) $expiredOrder,
        ];

        foreach ($toSave as $k => $v) {
            Settings::set($k, $v);
        }

        AuditLog::write('config.general.saved', [
            'tipo_accion' => 'configuracion_general',
            'apartado' => '/sistema/configuracion',
            'descripcion' => 'Configuracion general actualizada',
        ]);

        $this->toast('success', 'Configuracion', 'Configuracion general guardada.');
        $this->redirect('/sistema/configuracion?tab=general');
    }

    private function guardarSmtp(bool $testOnly): void
    {
        $smtp = $this->sanitizeSmtpInput($_POST);

        if ($smtp['smtp_host'] === '') {
            throw new \RuntimeException('Servidor SMTP es obligatorio.');
        }
        if ($smtp['smtp_port'] < 1 || $smtp['smtp_port'] > 65535) {
            throw new \RuntimeException('Puerto SMTP debe estar entre 1 y 65535.');
        }
        if (!in_array($smtp['smtp_encryption'], ['none', 'ssl', 'tls'], true)) {
            throw new \RuntimeException('Tipo de cifrado SMTP no valido.');
        }

        foreach (['correo_contacto', 'smtp_from_email', 'smtp_reply_to', 'smtp_test_to'] as $emailField) {
            $value = (string) $smtp[$emailField];
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Campo ' . $emailField . ' no es un correo valido.');
            }
        }

        foreach (self::EMAIL_LIST_FIELDS as $listField) {
            $smtp[$listField] = $this->normalizeEmailList((string) $smtp[$listField], $listField);
        }

        $storedEncryptedPass = (string) Settings::get('smtp_password', '');
        $plainPasswordInput = (string) ($_POST['smtp_password'] ?? '');
        $plainPassword = trim($plainPasswordInput) !== '' ? $plainPasswordInput : Settings::decryptValue($storedEncryptedPass);
        $encryptedPassword = trim($plainPasswordInput) !== '' ? Settings::encryptValue($plainPasswordInput) : $storedEncryptedPass;

        $persist = [
            'smtp_host' => $smtp['smtp_host'],
            'smtp_port' => (string) $smtp['smtp_port'],
            'smtp_user' => $smtp['smtp_user'],
            'dominio' => $smtp['dominio'],
            'correo_contacto' => $smtp['correo_contacto'],
            'correos_pedidos' => $smtp['correos_pedidos'],
            'correos_minimo_inventario' => $smtp['correos_minimo_inventario'],
            'smtp_encryption' => $smtp['smtp_encryption'],
            'smtp_from_name' => $smtp['smtp_from_name'],
            'smtp_from_email' => $smtp['smtp_from_email'],
            'smtp_reply_to' => $smtp['smtp_reply_to'],
            'smtp_test_to' => $smtp['smtp_test_to'],
            'smtp_password' => $encryptedPassword,
        ];

        foreach ($persist as $k => $v) {
            Settings::set($k, $v);
        }

        if ($testOnly) {
            if ($smtp['smtp_test_to'] === '') {
                throw new \RuntimeException('Indica un correo en SMTP test to.');
            }

            try {
                $client = new SmtpClient();
                $client->sendTest([
                    'host' => $smtp['smtp_host'],
                    'port' => $smtp['smtp_port'],
                    'user' => $smtp['smtp_user'],
                    'password' => $plainPassword,
                    'encryption' => $smtp['smtp_encryption'],
                    'from_email' => $smtp['smtp_from_email'] !== '' ? $smtp['smtp_from_email'] : $smtp['smtp_user'],
                    'from_name' => $smtp['smtp_from_name'],
                    'reply_to' => $smtp['smtp_reply_to'],
                ], $smtp['smtp_test_to']);

                AuditLog::write('config.smtp.test.ok', [
                    'tipo_accion' => 'configuracion_smtp_test',
                    'apartado' => '/sistema/configuracion',
                    'descripcion' => 'Prueba SMTP exitosa',
                    'to' => $smtp['smtp_test_to'],
                ]);

                $this->toast('success', 'SMTP Test', 'Correo de prueba enviado correctamente a ' . $smtp['smtp_test_to'] . '.');
            } catch (Throwable $e) {
                AuditLog::write('config.smtp.test.fail', [
                    'tipo_accion' => 'configuracion_smtp_test_fallido',
                    'apartado' => '/sistema/configuracion',
                    'descripcion' => 'Prueba SMTP fallida',
                    'to' => $smtp['smtp_test_to'],
                    'error' => $e->getMessage(),
                ]);

                $this->toast('danger', 'SMTP Test', 'No se pudo enviar el correo de prueba. Motivo: ' . $e->getMessage());
            }

            $this->redirect('/sistema/configuracion?tab=smtp');
        }

        AuditLog::write('config.smtp.saved', [
            'tipo_accion' => 'configuracion_smtp',
            'apartado' => '/sistema/configuracion',
            'descripcion' => 'Configuracion SMTP actualizada',
        ]);

        $this->toast('success', 'Configuracion', 'Configuracion SMTP guardada.');
        $this->redirect('/sistema/configuracion?tab=smtp');
    }

    private function guardarSecuencia(): void
    {
        $id = isset($_POST['secuencia_id']) && (int) $_POST['secuencia_id'] > 0 ? (int) $_POST['secuencia_id'] : null;
        $clave = (string) ($_POST['clave'] ?? '');
        $aplicaA = (string) ($_POST['aplica_a'] ?? '');
        $prefijo = (string) ($_POST['prefijo'] ?? '');
        $longitud = (int) ($_POST['longitud'] ?? 0);
        $valorActual = (int) ($_POST['valor_actual'] ?? 0);
        $incremento = (int) ($_POST['incremento'] ?? 0);
        $activo = (string) ($_POST['activo'] ?? '1') === '1';

        $savedId = Secuencia::guardar($id, $clave, $aplicaA, $prefijo, $longitud, $valorActual, $incremento, $activo);

        AuditLog::write($id ? 'config.secuencia.updated' : 'config.secuencia.created', [
            'tipo_accion' => $id ? 'configuracion_secuencia_editar' : 'configuracion_secuencia_crear',
            'apartado' => '/sistema/configuracion',
            'descripcion' => $id ? 'Secuencia actualizada' : 'Secuencia creada',
            'secuencia_id' => $savedId,
            'clave' => strtolower(trim($clave)),
        ]);

        $this->toast('success', 'Secuencias', 'Secuencia guardada correctamente.');
        $this->redirect('/sistema/configuracion?tab=secuencias&secuencia_id=' . $savedId);
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizeSmtpInput(array $input): array
    {
        return [
            'smtp_host' => trim((string) ($input['smtp_host'] ?? '')),
            'smtp_port' => (int) ($input['smtp_port'] ?? 0),
            'smtp_user' => trim((string) ($input['smtp_user'] ?? '')),
            'dominio' => strtolower(trim((string) ($input['dominio'] ?? ''))),
            'correo_contacto' => trim((string) ($input['correo_contacto'] ?? '')),
            'correos_pedidos' => (string) ($input['correos_pedidos'] ?? ''),
            'correos_minimo_inventario' => (string) ($input['correos_minimo_inventario'] ?? ''),
            'smtp_encryption' => strtolower(trim((string) ($input['smtp_encryption'] ?? 'none'))),
            'smtp_from_name' => trim((string) ($input['smtp_from_name'] ?? '')),
            'smtp_from_email' => trim((string) ($input['smtp_from_email'] ?? '')),
            'smtp_reply_to' => trim((string) ($input['smtp_reply_to'] ?? '')),
            'smtp_test_to' => trim((string) ($input['smtp_test_to'] ?? '')),
        ];
    }

    private function normalizeEmailList(string $raw, string $fieldName): string
    {
        $parts = array_map('trim', explode(',', $raw));
        $clean = [];

        foreach ($parts as $email) {
            if ($email === '') {
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Lista de correos invalida en ' . $fieldName . '.');
            }
            $clean[] = strtolower($email);
        }

        return implode(', ', array_values(array_unique($clean)));
    }

    /**
     * @param string[] $allowedMimes
     */
    private function procesarBranding(string $field, array $allowedMimes): ?string
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return null;
        }

        $file = $_FILES[$field];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Error al subir archivo de ' . $field . '.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            throw new \RuntimeException('Archivo de ' . $field . ' invalido.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 1024 * 1024) {
            throw new \RuntimeException('El archivo ' . $field . ' supera 1MB o es invalido.');
        }

        $mime = mime_content_type($tmp) ?: '';
        if (!in_array($mime, $allowedMimes, true)) {
            throw new \RuntimeException('Formato no permitido para ' . $field . '.');
        }

        $extMap = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/svg+xml' => 'svg',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
        ];
        $ext = $extMap[$mime] ?? null;
        if ($ext === null) {
            throw new \RuntimeException('Extension no soportada para ' . $field . '.');
        }

        if ($field === 'favicon' && !in_array($ext, ['ico', 'png'], true)) {
            throw new \RuntimeException('Favicon solo permite .ico o .png.');
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/branding';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        foreach (glob($dir . '/' . $field . '.*') ?: [] as $existing) {
            @unlink($existing);
        }

        $filename = $field . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException('No se pudo guardar archivo de ' . $field . '.');
        }

        return '/uploads/branding/' . $filename . '?v=' . time();
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
