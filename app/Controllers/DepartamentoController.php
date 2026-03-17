<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Departamento;
use Throwable;

final class DepartamentoController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $departamento = $id > 0 ? Departamento::buscarPorId($id) : null;

        $this->render('sistema/departamentos/index', [
            'titulo' => 'Departamentos',
            'csrf' => Csrf::token(),
            'departamento' => $departamento ?: [],
            'departamentosModal' => Departamento::listarParaModal(),
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
            $this->redirect('/mantenimientos/organizacion/departamentos');
        }

        $accion = (string) ($_POST['accion'] ?? 'save');

        try {
            if ($accion === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new \RuntimeException('Departamento invalido para eliminar.');
                }

                Departamento::eliminar($id);
                AuditLog::write('departamentos.deleted', [
                    'tipo_accion' => 'departamento_eliminar',
                    'apartado' => '/mantenimientos/organizacion/departamentos',
                    'descripcion' => 'Departamento eliminado',
                    'departamento_id' => $id,
                ]);

                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'Departamentos',
                    'message' => 'Departamento eliminado correctamente.',
                ];
                $this->redirect('/mantenimientos/organizacion/departamentos');
            }

            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $usuario = Auth::user();
            $savedId = Departamento::guardar($id, $_POST, (int) ($usuario['id'] ?? 0));

            AuditLog::write($id ? 'departamentos.updated' : 'departamentos.created', [
                'tipo_accion' => $id ? 'departamento_editar' : 'departamento_crear',
                'apartado' => '/mantenimientos/organizacion/departamentos',
                'descripcion' => $id ? 'Departamento actualizado' : 'Departamento creado',
                'departamento_id' => $savedId,
                'codigo' => (string) ($_POST['codigo'] ?? ''),
                'nombre' => (string) ($_POST['nombre'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Departamentos',
                'message' => 'Departamento guardado correctamente.',
            ];
            $this->redirect('/mantenimientos/organizacion/departamentos');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Departamentos',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo completar la operacion.',
            ];
            $this->redirect('/mantenimientos/organizacion/departamentos' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
