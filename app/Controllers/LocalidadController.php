<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Cliente;
use App\Models\Localidad;
use Throwable;

final class LocalidadController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $localidad = $id > 0 ? Localidad::buscarPorId($id) : null;

        $this->render('mantenimientos/terceros/localidades/index', [
            'titulo' => 'Localidades',
            'csrf' => Csrf::token(),
            'localidad' => $localidad ?: [],
            'clientes' => Cliente::listarParaSelect(),
            'localidades' => Localidad::listar(),
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
            $this->redirect('/mantenimientos/terceros/localidades');
        }

        $accion = (string) ($_POST['accion'] ?? 'save');

        try {
            if ($accion === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new \RuntimeException('Localidad invalida para eliminar.');
                }

                Localidad::eliminar($id);
                AuditLog::write('localidades.deleted', [
                    'tipo_accion' => 'localidad_eliminar',
                    'apartado' => '/mantenimientos/terceros/localidades',
                    'descripcion' => 'Localidad eliminada',
                    'localidad_id' => $id,
                ]);

                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'Localidades',
                    'message' => 'Localidad eliminada correctamente.',
                ];
                $this->redirect('/mantenimientos/terceros/localidades');
            }

            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $usuario = Auth::user();
            $savedId = Localidad::guardar($id, $_POST, (int) ($usuario['id'] ?? 0));

            AuditLog::write($id ? 'localidades.updated' : 'localidades.created', [
                'tipo_accion' => $id ? 'localidad_editar' : 'localidad_crear',
                'apartado' => '/mantenimientos/terceros/localidades',
                'descripcion' => $id ? 'Localidad actualizada' : 'Localidad creada',
                'localidad_id' => $savedId,
                'cliente_id' => (int) ($_POST['cliente_id'] ?? 0),
                'nombre_localidad' => (string) ($_POST['nombre_localidad'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Localidades',
                'message' => 'Localidad guardada correctamente.',
            ];
            $this->redirect('/mantenimientos/terceros/localidades');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Localidades',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo completar la operacion.',
            ];
            $this->redirect('/mantenimientos/terceros/localidades' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
