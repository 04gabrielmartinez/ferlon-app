<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Departamento;
use App\Models\Subdepartamento;
use Throwable;

final class SubdepartamentoController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $subdepartamento = $id > 0 ? Subdepartamento::buscarPorId($id) : null;

        $this->render('sistema/subdepartamentos/index', [
            'titulo' => 'Subdepartamentos',
            'csrf' => Csrf::token(),
            'subdepartamento' => $subdepartamento ?: [],
            'departamentos' => Departamento::listarActivos(),
            'subdepartamentosModal' => Subdepartamento::listarParaModal(),
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
            $this->redirect('/mantenimientos/organizacion/subdepartamentos');
        }

        $accion = (string) ($_POST['accion'] ?? 'save');

        try {
            if ($accion === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new \RuntimeException('Subdepartamento invalido para eliminar.');
                }

                Subdepartamento::eliminar($id);
                AuditLog::write('subdepartamentos.deleted', [
                    'tipo_accion' => 'subdepartamento_eliminar',
                    'apartado' => '/mantenimientos/organizacion/subdepartamentos',
                    'descripcion' => 'Subdepartamento eliminado',
                    'subdepartamento_id' => $id,
                ]);

                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'Subdepartamentos',
                    'message' => 'Subdepartamento eliminado correctamente.',
                ];
                $this->redirect('/mantenimientos/organizacion/subdepartamentos');
            }

            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $usuario = Auth::user();
            $savedId = Subdepartamento::guardar($id, $_POST, (int) ($usuario['id'] ?? 0));

            AuditLog::write($id ? 'subdepartamentos.updated' : 'subdepartamentos.created', [
                'tipo_accion' => $id ? 'subdepartamento_editar' : 'subdepartamento_crear',
                'apartado' => '/mantenimientos/organizacion/subdepartamentos',
                'descripcion' => $id ? 'Subdepartamento actualizado' : 'Subdepartamento creado',
                'subdepartamento_id' => $savedId,
                'departamento_id' => (int) ($_POST['departamento_id'] ?? 0),
                'codigo' => (string) ($_POST['codigo'] ?? ''),
                'nombre' => (string) ($_POST['nombre'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Subdepartamentos',
                'message' => 'Subdepartamento guardado correctamente.',
            ];
            $this->redirect('/mantenimientos/organizacion/subdepartamentos');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Subdepartamentos',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo completar la operacion.',
            ];
            $this->redirect('/mantenimientos/organizacion/subdepartamentos' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
