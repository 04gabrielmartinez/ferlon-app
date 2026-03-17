<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Departamento;
use App\Models\Puesto;
use App\Models\Subdepartamento;
use Throwable;

final class PuestosController extends Controller
{
    public function index(): void
    {
        $tab = (string) ($_GET['tab'] ?? 'departamentos');
        if (!in_array($tab, ['departamentos', 'subdepartamentos', 'puestos'], true)) {
            $tab = 'departamentos';
        }

        $departamentoId = isset($_GET['departamento_id']) ? (int) $_GET['departamento_id'] : 0;
        $subdepartamentoId = isset($_GET['subdepartamento_id']) ? (int) $_GET['subdepartamento_id'] : 0;
        $puestoId = isset($_GET['puesto_id']) ? (int) $_GET['puesto_id'] : 0;

        $this->render('sistema/puestos/index', [
            'titulo' => 'Puestos',
            'csrf' => Csrf::token(),
            'tab' => $tab,
            'departamento' => $departamentoId > 0 ? (Departamento::buscarPorId($departamentoId) ?: []) : [],
            'subdepartamento' => $subdepartamentoId > 0 ? (Subdepartamento::buscarPorId($subdepartamentoId) ?: []) : [],
            'puesto' => $puestoId > 0 ? (Puesto::buscarPorId($puestoId) ?: []) : [],
            'departamentosModal' => Departamento::listarParaModal(),
            'subdepartamentosModal' => Subdepartamento::listarParaModal(),
            'puestosModal' => Puesto::listarParaModal(),
            'departamentosActivos' => Departamento::listarActivos(),
            'subdepartamentosActivos' => Subdepartamento::listarActivos(),
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
            $this->redirect('/sistema/puestos');
        }

        $entity = (string) ($_POST['entity'] ?? '');
        $accion = (string) ($_POST['accion'] ?? 'save');
        $tabMap = [
            'departamento' => 'departamentos',
            'subdepartamento' => 'subdepartamentos',
            'puesto' => 'puestos',
        ];
        $tab = $tabMap[$entity] ?? 'departamentos';

        try {
            $usuario = Auth::user();
            $userId = (int) ($usuario['id'] ?? 0);
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;

            if ($entity === 'departamento') {
                if ($accion === 'delete') {
                    if ($id === null) {
                        throw new \RuntimeException('Departamento invalido para eliminar.');
                    }
                    Departamento::eliminar($id);
                    AuditLog::write('departamentos.deleted', [
                        'tipo_accion' => 'departamento_eliminar',
                        'apartado' => '/sistema/puestos',
                        'descripcion' => 'Departamento eliminado',
                        'departamento_id' => $id,
                    ]);
                    $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Puestos', 'message' => 'Departamento eliminado correctamente.'];
                    $this->redirect('/sistema/puestos?tab=departamentos');
                }

                $savedId = Departamento::guardar($id, $_POST, $userId);
                AuditLog::write($id ? 'departamentos.updated' : 'departamentos.created', [
                    'tipo_accion' => $id ? 'departamento_editar' : 'departamento_crear',
                    'apartado' => '/sistema/puestos',
                    'descripcion' => $id ? 'Departamento actualizado' : 'Departamento creado',
                    'departamento_id' => $savedId,
                    'nombre' => (string) ($_POST['nombre'] ?? ''),
                ]);
                $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Puestos', 'message' => 'Departamento guardado correctamente.'];
                $this->redirect('/sistema/puestos?tab=departamentos');
            }

            if ($entity === 'subdepartamento') {
                if ($accion === 'delete') {
                    if ($id === null) {
                        throw new \RuntimeException('Subdepartamento invalido para eliminar.');
                    }
                    Subdepartamento::eliminar($id);
                    AuditLog::write('subdepartamentos.deleted', [
                        'tipo_accion' => 'subdepartamento_eliminar',
                        'apartado' => '/sistema/puestos',
                        'descripcion' => 'Subdepartamento eliminado',
                        'subdepartamento_id' => $id,
                    ]);
                    $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Puestos', 'message' => 'Subdepartamento eliminado correctamente.'];
                    $this->redirect('/sistema/puestos?tab=subdepartamentos');
                }

                $savedId = Subdepartamento::guardar($id, $_POST, $userId);
                AuditLog::write($id ? 'subdepartamentos.updated' : 'subdepartamentos.created', [
                    'tipo_accion' => $id ? 'subdepartamento_editar' : 'subdepartamento_crear',
                    'apartado' => '/sistema/puestos',
                    'descripcion' => $id ? 'Subdepartamento actualizado' : 'Subdepartamento creado',
                    'subdepartamento_id' => $savedId,
                    'departamento_id' => (int) ($_POST['departamento_id'] ?? 0),
                    'nombre' => (string) ($_POST['nombre'] ?? ''),
                ]);
                $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Puestos', 'message' => 'Subdepartamento guardado correctamente.'];
                $this->redirect('/sistema/puestos?tab=subdepartamentos');
            }

            if ($entity === 'puesto') {
                if ($accion === 'delete') {
                    if ($id === null) {
                        throw new \RuntimeException('Puesto invalido para eliminar.');
                    }
                    Puesto::eliminar($id);
                    AuditLog::write('puestos.deleted', [
                        'tipo_accion' => 'puesto_eliminar',
                        'apartado' => '/sistema/puestos',
                        'descripcion' => 'Puesto eliminado',
                        'puesto_id' => $id,
                    ]);
                    $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Puestos', 'message' => 'Puesto eliminado correctamente.'];
                    $this->redirect('/sistema/puestos?tab=puestos');
                }

                $savedId = Puesto::guardar($id, $_POST, $userId);
                AuditLog::write($id ? 'puestos.updated' : 'puestos.created', [
                    'tipo_accion' => $id ? 'puesto_editar' : 'puesto_crear',
                    'apartado' => '/sistema/puestos',
                    'descripcion' => $id ? 'Puesto actualizado' : 'Puesto creado',
                    'puesto_id' => $savedId,
                    'nombre' => (string) ($_POST['nombre'] ?? ''),
                ]);
                $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Puestos', 'message' => 'Puesto guardado correctamente.'];
                $this->redirect('/sistema/puestos?tab=puestos');
            }

            throw new \RuntimeException('Entidad de organizacion invalida.');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Puestos',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo completar la operacion.',
            ];
            $this->redirect('/sistema/puestos?tab=' . rawurlencode($tab));
        }
    }
}
