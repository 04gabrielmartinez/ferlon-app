<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Banco;
use App\Models\Proveedor;
use Throwable;

final class ProveedorController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $proveedor = $id > 0 ? Proveedor::buscarPorId($id) : null;

        $this->render('mantenimientos/terceros/proveedores/index', [
            'titulo' => 'Proveedores',
            'csrf' => Csrf::token(),
            'proveedor' => $proveedor ?: [],
            'proveedoresModal' => Proveedor::listarParaModal(),
            'bancos' => Banco::listarActivos(),
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
            $this->redirect('/mantenimientos/terceros/proveedores');
        }

        try {
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $usuario = Auth::user();
            $savedId = Proveedor::guardar($id, $_POST, (int) ($usuario['id'] ?? 0));

            AuditLog::write($id ? 'proveedores.updated' : 'proveedores.created', [
                'tipo_accion' => $id ? 'proveedor_editar' : 'proveedor_crear',
                'apartado' => '/mantenimientos/terceros/proveedores',
                'descripcion' => $id ? 'Proveedor actualizado' : 'Proveedor creado',
                'proveedor_id' => $savedId,
                'razon_social' => (string) ($_POST['razon_social'] ?? ''),
                'rnc' => (string) ($_POST['rnc'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Proveedores',
                'message' => 'Proveedor guardado correctamente.',
            ];
            $this->redirect('/mantenimientos/terceros/proveedores');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Proveedores',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el proveedor.',
            ];
            $this->redirect('/mantenimientos/terceros/proveedores' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
