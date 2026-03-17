<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Ncf;
use Throwable;

final class NcfController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $ncf = $id > 0 ? Ncf::buscarPorId($id) : null;

        $this->render('sistema/ncf/index', [
            'titulo' => 'Mantenimiento NCF',
            'csrf' => Csrf::token(),
            'tipos' => Ncf::TIPOS,
            'ncf' => $ncf ?: [],
            'listado' => Ncf::listar(),
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
            $this->redirect('/sistema/ncf');
        }

        $accion = (string) ($_POST['accion'] ?? 'save');

        try {
            if ($accion === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new \RuntimeException('ID invalido para eliminar.');
                }
                Ncf::eliminar($id);
                AuditLog::write('ncf.deleted', [
                    'tipo_accion' => 'ncf_eliminar',
                    'apartado' => '/sistema/ncf',
                    'descripcion' => 'NCF eliminado',
                    'ncf_id' => $id,
                ]);
                $_SESSION['flash_toast'] = [
                    'type' => 'success',
                    'title' => 'NCF',
                    'message' => 'Registro eliminado.',
                ];
                $this->redirect('/sistema/ncf');
            }

            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $usuario = Auth::user();
            $savedId = Ncf::guardar($id, $_POST, (int) ($usuario['id'] ?? 0));

            AuditLog::write($id ? 'ncf.updated' : 'ncf.created', [
                'tipo_accion' => $id ? 'ncf_editar' : 'ncf_crear',
                'apartado' => '/sistema/ncf',
                'descripcion' => $id ? 'NCF actualizado' : 'NCF creado',
                'ncf_id' => $savedId,
                'tipo_ncf' => (string) ($_POST['tipo_ncf'] ?? ''),
                'prefijo' => (string) ($_POST['prefijo'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'NCF',
                'message' => 'Registro guardado correctamente.',
            ];
            $this->redirect('/sistema/ncf?id=' . $savedId);
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'NCF',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo completar la operacion.',
            ];
            $this->redirect('/sistema/ncf' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }
}
