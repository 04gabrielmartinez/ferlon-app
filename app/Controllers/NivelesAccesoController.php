<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Settings;
use App\Models\Acceso;
use Throwable;

final class NivelesAccesoController extends Controller
{
    public function index(): void
    {
        Acceso::sincronizarPermisosBase();

        $tab = (string) ($_GET['tab'] ?? 'cuentas');
        if (!in_array($tab, ['cuentas', 'niveles', 'permisos'], true)) {
            $tab = 'cuentas';
        }

        $niveles = Acceso::niveles();
        $permisos = Acceso::permisos();
        $nivelSeleccionado = isset($_GET['nivel_id']) ? (int) $_GET['nivel_id'] : (isset($niveles[0]['id']) ? (int) $niveles[0]['id'] : 0);
        $permisosNivel = $nivelSeleccionado > 0 ? Acceso::permisosPorNivel($nivelSeleccionado) : [];
        $nivelEdicionId = isset($_GET['nivel_edit_id']) ? (int) $_GET['nivel_edit_id'] : 0;
        $nivelEdicion = $nivelEdicionId > 0 ? Acceso::obtenerNivelPorId($nivelEdicionId) : null;
        $empleadosNivel = $nivelEdicionId > 0 ? Acceso::empleadosPorNivel($nivelEdicionId) : [];
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $cuentaSeleccionada = $userId > 0 ? Acceso::obtenerCuentaPorUsuarioId($userId) : null;

        $this->render('sistema/niveles-acceso/index', [
            'titulo' => 'Niveles de acceso',
            'csrf' => Csrf::token(),
            'tab' => $tab,
            'dominio' => (string) Settings::get('dominio', ''),
            'niveles' => $niveles,
            'permisos' => $permisos,
            'permisosNivel' => $permisosNivel,
            'nivelSeleccionado' => $nivelSeleccionado,
            'nivelEdicionId' => $nivelEdicionId,
            'nivelEdicion' => $nivelEdicion ?: [],
            'empleadosNivel' => $empleadosNivel,
            'cuentas' => Acceso::cuentasAcceso(),
            'cuentasPicker' => Acceso::cuentasParaPicker(),
            'cuentaSeleccionada' => $cuentaSeleccionada ?: [],
        ]);
    }

    public function guardar(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Seguridad',
                'message' => 'Token CSRF invalido.',
            ];
            $this->redirect('/sistema/niveles-acceso');
        }

        $accion = (string) ($_POST['accion'] ?? '');

        try {
            if ($accion === 'config_dominio') {
                $dominio = strtolower(trim((string) ($_POST['dominio'] ?? '')));
                $dominio = preg_replace('/^@+/', '', $dominio) ?? $dominio;
                Settings::set('dominio', $dominio);
                AuditLog::write('acceso.config.dominio', [
                    'tipo_accion' => 'configurar_dominio',
                    'apartado' => '/sistema/niveles-acceso',
                    'descripcion' => 'Dominio de usuarios actualizado',
                    'dominio' => $dominio,
                ]);
                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'Configuracion',
                    'message' => 'Dominio guardado.',
                ];
                $this->redirect('/sistema/niveles-acceso?tab=cuentas');
            }

            if ($accion === 'cuenta_empleado') {
                $userId = isset($_POST['user_id']) && (int) $_POST['user_id'] > 0 ? (int) $_POST['user_id'] : null;
                $empleadoId = (int) ($_POST['empleado_id'] ?? 0);
                $nivelId = (int) ($_POST['nivel_acceso_id'] ?? 0);
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = trim((string) ($_POST['password'] ?? ''));
                $password2 = trim((string) ($_POST['password_confirm'] ?? ''));

                if ($password === '' || $password2 === '') {
                    throw new \RuntimeException('Debes completar ambas contraseñas.');
                }

                if ($password !== $password2) {
                    throw new \RuntimeException('Las contraseñas no coinciden.');
                }

                if (!$this->passwordValido($password)) {
                    throw new \RuntimeException('La contraseña no cumple la política requerida.');
                }

                $savedUserId = Acceso::crearOActualizarCuentaEmpleado($userId, $empleadoId, $nivelId, $username, $password);
                AuditLog::write('acceso.cuenta_empleado.guardada', [
                    'tipo_accion' => 'cuenta_empleado_guardar',
                    'apartado' => '/sistema/niveles-acceso',
                    'descripcion' => 'Cuenta de acceso de empleado guardada',
                    'user_id_target' => $savedUserId,
                    'empleado_id' => $empleadoId,
                    'nivel_id' => $nivelId,
                    'username' => $username,
                ]);
                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'Acceso empleado',
                    'message' => 'Cuenta guardada correctamente.',
                ];
                $this->redirect('/sistema/niveles-acceso?tab=cuentas');
            }

            if ($accion === 'guardar_nivel') {
                $nivelId = (int) ($_POST['nivel_id'] ?? 0);
                $nombre = trim((string) ($_POST['nombre_nivel'] ?? ''));
                $descripcion = trim((string) ($_POST['descripcion_nivel'] ?? ''));
                $activo = (string) ($_POST['activo_nivel'] ?? '1') === '1';

                if ($nombre === '') {
                    throw new \RuntimeException('El nombre del nivel es obligatorio.');
                }

                if ($nivelId > 0) {
                    Acceso::actualizarNivel($nivelId, $nombre, $descripcion, $activo);
                    AuditLog::write('acceso.nivel.actualizado', [
                        'tipo_accion' => 'nivel_actualizar',
                        'apartado' => '/sistema/niveles-acceso',
                        'descripcion' => 'Nivel de acceso actualizado',
                        'nivel_id' => $nivelId,
                        'nombre' => $nombre,
                    ]);
                    $_SESSION['flash_toast'] = [
                        'type' => 'success',
                        'title' => 'Niveles',
                        'message' => 'Nivel actualizado correctamente.',
                    ];
                    $this->redirect('/sistema/niveles-acceso?tab=niveles');
                }

                $nuevoNivelId = Acceso::crearNivel($nombre, $descripcion, $activo);
                AuditLog::write('acceso.nivel.creado', [
                    'tipo_accion' => 'nivel_crear',
                    'apartado' => '/sistema/niveles-acceso',
                    'descripcion' => 'Nivel de acceso creado',
                    'nivel_id' => $nuevoNivelId,
                    'nombre' => $nombre,
                ]);
                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'Niveles',
                    'message' => 'Nivel creado correctamente.',
                ];
                $this->redirect('/sistema/niveles-acceso?tab=niveles');
            }

            if ($accion === 'eliminar_nivel') {
                $nivelId = (int) ($_POST['nivel_id'] ?? 0);
                $nivel = $nivelId > 0 ? Acceso::obtenerNivelPorId($nivelId) : null;
                Acceso::eliminarNivel($nivelId);
                AuditLog::write('acceso.nivel.eliminado', [
                    'tipo_accion' => 'nivel_eliminar',
                    'apartado' => '/sistema/niveles-acceso',
                    'descripcion' => 'Nivel de acceso eliminado',
                    'nivel_id' => $nivelId,
                    'nombre' => (string) ($nivel['nombre'] ?? ''),
                ]);
                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'Niveles',
                    'message' => 'Nivel eliminado correctamente.',
                ];
                $this->redirect('/sistema/niveles-acceso?tab=niveles');
            }

            if ($accion === 'asignar_permisos') {
                $nivelId = (int) ($_POST['nivel_id'] ?? 0);
                $permisos = $_POST['permisos'] ?? [];
                if (!is_array($permisos)) {
                    $permisos = [];
                }
                Acceso::asignarPermisosNivel($nivelId, $permisos);
                AuditLog::write('acceso.nivel.permisos', [
                    'tipo_accion' => 'nivel_permisos_asignar',
                    'apartado' => '/sistema/niveles-acceso',
                    'descripcion' => 'Permisos de nivel actualizados',
                    'nivel_id' => $nivelId,
                    'total_permisos' => count($permisos),
                ]);
                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'Permisos',
                    'message' => 'Permisos actualizados.',
                ];
                $this->redirect('/sistema/niveles-acceso?tab=permisos&nivel_id=' . $nivelId);
            }

            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Niveles de acceso',
                'message' => 'Accion no reconocida.',
            ];
            $this->redirect('/sistema/niveles-acceso');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Niveles de acceso',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo completar la accion.',
            ];
            $this->redirect('/sistema/niveles-acceso');
        }
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
}
