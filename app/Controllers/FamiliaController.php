<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Familia;
use App\Models\Marca;
use Throwable;

final class FamiliaController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $familia = $id > 0 ? Familia::buscarPorId($id) : null;

        $this->render('mantenimientos/organizacion/familias/index', [
            'titulo' => 'Familia',
            'csrf' => Csrf::token(),
            'familia' => $familia ?: [],
            'familiasModal' => Familia::listarParaModal(),
            'marcasActivas' => Marca::listarActivas(),
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
            $this->redirect('/mantenimientos/organizacion/familias');
        }

        try {
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $user = Auth::user();
            $savedId = Familia::guardar($id, $_POST, (int) ($user['id'] ?? 0));

            AuditLog::write($id ? 'familias.updated' : 'familias.created', [
                'tipo_accion' => $id ? 'familia_editar' : 'familia_crear',
                'apartado' => '/mantenimientos/organizacion/familias',
                'descripcion' => $id ? 'Familia actualizada' : 'Familia creada',
                'familia_id' => $savedId,
                'marca_id' => (int) ($_POST['marca_id'] ?? 0),
                'familia_descripcion' => (string) ($_POST['descripcion'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Familias',
                'message' => 'Familia guardada correctamente.',
            ];
            $this->redirect('/mantenimientos/organizacion/familias');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Familias',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la familia.',
            ];
            $this->redirect('/mantenimientos/organizacion/familias' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
