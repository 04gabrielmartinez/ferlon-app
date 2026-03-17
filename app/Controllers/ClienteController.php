<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Cliente;
use Throwable;

final class ClienteController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $cliente = $id > 0 ? Cliente::buscarPorId($id) : null;

        $this->render('mantenimientos/terceros/clientes/index', [
            'titulo' => 'Clientes',
            'csrf' => Csrf::token(),
            'cliente' => $cliente ?: [],
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
            $this->redirect('/mantenimientos/terceros/clientes');
        }

        try {
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $usuario = Auth::user();
            $savedId = Cliente::guardar($id, $_POST, (int) ($usuario['id'] ?? 0));

            AuditLog::write($id ? 'clientes.updated' : 'clientes.created', [
                'tipo_accion' => $id ? 'cliente_editar' : 'cliente_crear',
                'apartado' => '/mantenimientos/terceros/clientes',
                'descripcion' => $id ? 'Cliente actualizado' : 'Cliente creado',
                'cliente_id' => $savedId,
                'razon_social' => (string) ($_POST['razon_social'] ?? ''),
                'rnc' => (string) ($_POST['rnc'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Clientes',
                'message' => 'Cliente guardado correctamente.',
            ];
            $this->redirect('/mantenimientos/terceros/clientes');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Clientes',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el cliente.',
            ];
            $this->redirect('/mantenimientos/terceros/clientes' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
