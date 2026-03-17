<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Db;
use App\Models\Banco;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Puesto;
use App\Models\Subdepartamento;
use PDOException;

final class EmpleadoController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $empleado = $id > 0 ? Empleado::buscarPorId($id) : null;

        $this->render('mantenimientos/terceros/empleados/index', [
            'titulo' => 'Empleados',
            'csrf' => Csrf::token(),
            'empleado' => $empleado ?: [],
            'departamentos' => Departamento::listarActivos(),
            'subdepartamentos' => Subdepartamento::listarActivos(),
            'puestos' => Puesto::listarActivos(),
            'bancos' => Banco::listarActivos(),
            'supervisores' => Empleado::listarParaSupervisorSelect($id > 0 ? $id : null),
        ]);
    }

    public function guardar(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            AuditLog::write('empleados.save.csrf_invalid', [
                'tipo_accion' => 'empleados_guardar',
                'apartado' => '/mantenimientos/terceros/empleados',
                'descripcion' => 'Token CSRF invalido al guardar empleado',
            ]);
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Seguridad',
                'message' => 'Token CSRF invalido.',
            ];
            $this->redirect('/mantenimientos/terceros/empleados');
        }

        $datos = $_POST;
        $datos['foto_path'] = trim((string) ($datos['foto_actual'] ?? ''));

        $nuevaFoto = $this->procesarFoto();
        if ($nuevaFoto !== null) {
            $datos['foto_path'] = $nuevaFoto;
        }

        $datos = $this->completarCamposCatalogo($datos);

        try {
            $id = Empleado::guardar($datos);
        } catch (PDOException $e) {
            $mensaje = 'No se pudo guardar el empleado.';
            if ((int) $e->getCode() === 23000) {
                $mensaje = 'Cedula o correo ya existe. Verifica datos unicos.';
            }

            AuditLog::write('empleados.save.failed', [
                'tipo_accion' => 'empleados_guardar_error',
                'apartado' => '/mantenimientos/terceros/empleados',
                'descripcion' => 'Error al guardar empleado',
                'empleado_id' => isset($datos['id']) ? (int) $datos['id'] : null,
                'cedula' => (string) ($datos['cedula'] ?? ''),
                'email_personal' => (string) ($datos['email_personal'] ?? ''),
                'email_empresa' => (string) ($datos['email_empresa'] ?? ''),
                'db_code' => (int) $e->getCode(),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Empleados',
                'message' => $mensaje,
            ];
            $this->redirect('/mantenimientos/terceros/empleados' . (!empty($datos['id']) ? '?id=' . (int) $datos['id'] : ''));
        }

        if (($datos['estado'] ?? 'activo') === 'inactivo') {
            $stmt = Db::conexion()->prepare('DELETE FROM users WHERE empleado_id = :empleado_id');
            $stmt->execute(['empleado_id' => $id]);
            AuditLog::write('empleados.inactivo.desvincula_acceso', [
                'tipo_accion' => 'empleado_inactivar_acceso',
                'apartado' => '/mantenimientos/terceros/empleados',
                'descripcion' => 'Empleado inactivo: usuario de acceso eliminado',
                'empleado_id' => $id,
            ]);
        }

        AuditLog::write(!empty($datos['id']) ? 'empleados.updated' : 'empleados.created', [
            'tipo_accion' => !empty($datos['id']) ? 'empleados_editar' : 'empleados_crear',
            'apartado' => '/mantenimientos/terceros/empleados',
            'descripcion' => !empty($datos['id']) ? 'Empleado actualizado' : 'Empleado creado',
            'empleado_id' => $id,
            'cedula' => (string) ($datos['cedula'] ?? ''),
            'nombre' => (string) ($datos['nombre'] ?? ''),
            'apellido' => (string) ($datos['apellido'] ?? ''),
        ]);

        $_SESSION['flash_toast'] = [
            'type' => 'success',
            'title' => 'Empleados',
            'message' => 'Empleado guardado correctamente.',
        ];
        $this->redirect('/mantenimientos/terceros/empleados');
    }

    private function procesarFoto(): ?string
    {
        if (!isset($_FILES['foto_empleado']) || !is_array($_FILES['foto_empleado'])) {
            return null;
        }

        $archivo = $_FILES['foto_empleado'];
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return null;
        }

        $maxBytes = 1024 * 1024;
        $size = (int) ($archivo['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            return null;
        }

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            return null;
        }

        $mime = mime_content_type($tmp) ?: '';
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!isset($map[$mime])) {
            return null;
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/empleados';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $nombre = 'emp_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $map[$mime];
        $destino = $dir . '/' . $nombre;

        if (!move_uploaded_file($tmp, $destino)) {
            return null;
        }

        return '/uploads/empleados/' . $nombre;
    }

    private function completarCamposCatalogo(array $datos): array
    {
        $departamentoId = (int) ($datos['departamento_id'] ?? 0);
        if ($departamentoId > 0) {
            $stmt = Db::conexion()->prepare('SELECT nombre FROM departamentos WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $departamentoId]);
            $row = $stmt->fetch();
            if (is_array($row) && !empty($row['nombre'])) {
                $datos['departamento'] = (string) $row['nombre'];
            }
        }

        $subdepartamentoId = (int) ($datos['subdepartamento_id'] ?? 0);
        if ($subdepartamentoId > 0) {
            $stmt = Db::conexion()->prepare('SELECT nombre FROM subdepartamentos WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $subdepartamentoId]);
            $row = $stmt->fetch();
            if (is_array($row) && !empty($row['nombre'])) {
                $datos['subdepartamento'] = (string) $row['nombre'];
            }
        }

        $puestoId = (int) ($datos['puesto_id'] ?? 0);
        if ($puestoId > 0) {
            $stmt = Db::conexion()->prepare('SELECT nombre FROM puestos WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $puestoId]);
            $row = $stmt->fetch();
            if (is_array($row) && !empty($row['nombre'])) {
                $datos['cargo'] = (string) $row['nombre'];
            }
        }

        $direccion = trim((string) ($datos['direccion_completa'] ?? ''));
        if ($direccion !== '') {
            $datos['ubicacion'] = $direccion;
        }

        return $datos;
    }
}
