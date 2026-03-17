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

final class MarcaController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $marca = $id > 0 ? Marca::buscarPorId($id) : null;

        $this->render('mantenimientos/organizacion/marcas/index', [
            'titulo' => 'Marcas',
            'csrf' => Csrf::token(),
            'marca' => $marca ?: [],
            'marcasModal' => Marca::listarParaModal(),
            'familiasMarca' => $id > 0 ? Familia::listarPorMarca($id) : [],
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
            $this->redirect('/mantenimientos/organizacion/marcas');
        }

        try {
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $anterior = $id ? Marca::buscarPorId($id) : null;
            $estadoAnterior = strtolower((string) ($anterior['estado'] ?? 'activo'));
            $estadoNuevo = strtolower(trim((string) ($_POST['estado'] ?? 'activo')));
            $user = Auth::user();
            $savedId = Marca::guardar($id, $_POST, (int) ($user['id'] ?? 0));

            if ($estadoNuevo === 'inactivo') {
                Familia::desactivarPorMarca($savedId);
            } elseif ($id !== null && $estadoAnterior === 'inactivo' && $estadoNuevo === 'activo') {
                $modo = strtolower(trim((string) ($_POST['activar_familias_modo'] ?? '')));
                if ($modo === 'todas') {
                    Familia::activarPorMarca($savedId);
                } elseif ($modo === 'algunas') {
                    $familias = $_POST['familias_activar'] ?? [];
                    if (!is_array($familias)) {
                        $familias = [];
                    }
                    Familia::activarPorIds($savedId, $familias);
                }
            }

            AuditLog::write($id ? 'marcas.updated' : 'marcas.created', [
                'tipo_accion' => $id ? 'marca_editar' : 'marca_crear',
                'apartado' => '/mantenimientos/organizacion/marcas',
                'descripcion' => $id ? 'Marca actualizada' : 'Marca creada',
                'marca_id' => $savedId,
                'marca_descripcion' => (string) ($_POST['descripcion'] ?? ''),
                'estado' => $estadoNuevo,
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Marcas',
                'message' => 'Marca guardada correctamente.',
            ];
            $this->redirect('/mantenimientos/organizacion/marcas');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Marcas',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la marca.',
            ];
            $this->redirect('/mantenimientos/organizacion/marcas' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
