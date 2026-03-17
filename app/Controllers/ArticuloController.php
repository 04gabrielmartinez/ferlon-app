<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Catalogo;
use App\Models\Familia;
use App\Models\Marca;
use App\Models\Proveedor;
use Throwable;

final class ArticuloController extends Controller
{
    public function index(): void
    {
        $articuloId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        $this->render('mantenimientos/organizacion/articulos/index', [
            'titulo' => 'Articulos',
            'csrf' => Csrf::token(),
            'articulo' => $articuloId > 0 ? (Catalogo::buscarArticulo($articuloId) ?: []) : [],
            'tiposArticulo' => Catalogo::listarTiposArticulo(),
            'categoriasArticulo' => Catalogo::listarCategoriasArticulo(),
            'subcategoriasArticulo' => Catalogo::listarSubcategoriasArticulo(),
            'presentaciones' => Catalogo::listarPresentaciones(),
            'empaques' => Catalogo::listarEmpaques(),
            'marcasActivas' => Marca::listarActivas(),
            'familiasActivas' => Familia::listarActivas(),
            'proveedoresActivos' => Proveedor::listarActivos(),
            'articulos' => Catalogo::listarArticulos(),
            'variantesRecetaProductoFinal' => Catalogo::listarVariantesRecetaProductoFinalStock(),
            'impuestosOpciones' => Catalogo::IMPUESTOS_OPCIONES(),
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
            $this->redirect('/mantenimientos/organizacion/articulos');
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        try {
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $_POST['foto_path'] = trim((string) ($_POST['foto_actual'] ?? ''));
            $nuevaFoto = $this->procesarFotoArticulo();
            if ($nuevaFoto !== null) {
                $_POST['foto_path'] = $nuevaFoto;
            }

            $savedId = Catalogo::guardarArticulo($id, $_POST, $userId);
            AuditLog::write($id ? 'articulo.updated' : 'articulo.created', [
                'tipo_accion' => $id ? 'articulo_editar' : 'articulo_crear',
                'apartado' => '/mantenimientos/organizacion/articulos',
                'descripcion' => $id ? 'Articulo actualizado' : 'Articulo creado',
                'articulo_id' => $savedId,
                'codigo' => (string) ($_POST['codigo'] ?? ''),
                'nombre' => (string) ($_POST['descripcion'] ?? $_POST['codigo'] ?? ''),
            ]);

            $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Articulos', 'message' => 'Articulo guardado correctamente.'];
            $this->redirect('/mantenimientos/organizacion/articulos');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Articulos',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el registro.',
            ];
            $this->redirect('/mantenimientos/organizacion/articulos' . (!empty($_POST['id']) ? ('?id=' . (int) $_POST['id']) : ''));
        }
    }

    private function procesarFotoArticulo(): ?string
    {
        if (!isset($_FILES['foto']) || !is_array($_FILES['foto'])) {
            return null;
        }

        $archivo = $_FILES['foto'];
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE || $error !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            return null;
        }

        $size = (int) ($archivo['size'] ?? 0);
        if ($size <= 0 || $size > 1024 * 1024) {
            return null;
        }

        $mime = mime_content_type($tmp) ?: '';
        $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($map[$mime])) {
            return null;
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/articulos';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $nombre = 'art_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $map[$mime];
        $destino = $dir . '/' . $nombre;
        if (!move_uploaded_file($tmp, $destino)) {
            return null;
        }

        return '/uploads/articulos/' . $nombre;
    }
}
