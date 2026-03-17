<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Banco;
use Throwable;

final class BancoController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $banco = $id > 0 ? Banco::buscarPorId($id) : null;

        $this->render('mantenimientos/terceros/bancos/index', [
            'titulo' => 'Bancos',
            'csrf' => Csrf::token(),
            'banco' => $banco ?: [],
            'bancosModal' => Banco::listarParaModal(),
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
            $this->redirect('/mantenimientos/terceros/bancos');
        }

        try {
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $usuario = Auth::user();
            $savedId = Banco::guardar($id, $_POST, (int) ($usuario['id'] ?? 0));

            AuditLog::write($id ? 'bancos.updated' : 'bancos.created', [
                'tipo_accion' => $id ? 'banco_editar' : 'banco_crear',
                'apartado' => '/mantenimientos/terceros/bancos',
                'descripcion' => $id ? 'Banco actualizado' : 'Banco creado',
                'banco_id' => $savedId,
                'nombre_banco' => (string) ($_POST['nombre_banco'] ?? ''),
                'codigo_banco' => (string) ($_POST['codigo_banco'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Bancos',
                'message' => 'Banco guardado correctamente.',
            ];
            $this->redirect('/mantenimientos/terceros/bancos');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Bancos',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el banco.',
            ];
            $this->redirect('/mantenimientos/terceros/bancos' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
