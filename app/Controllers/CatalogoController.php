<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Catalogo;
use Throwable;

final class CatalogoController extends Controller
{
    public function index(): void
    {
        $tab = (string) ($_GET['tab'] ?? 'presentaciones');
        if (!in_array($tab, ['presentaciones', 'empaques', 'tipos', 'categorias', 'subcategorias', 'unidades'], true)) {
            $tab = 'presentaciones';
        }

        $presentacionId = isset($_GET['presentacion_id']) ? (int) $_GET['presentacion_id'] : 0;
        $empaqueId = isset($_GET['empaque_id']) ? (int) $_GET['empaque_id'] : 0;
        $tipoId = isset($_GET['tipo_id']) ? (int) $_GET['tipo_id'] : 0;
        $categoriaId = isset($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : 0;
        $subcategoriaId = isset($_GET['subcategoria_id']) ? (int) $_GET['subcategoria_id'] : 0;

        $this->render('mantenimientos/organizacion/catalogo/index', [
            'titulo' => 'Catalogo',
            'csrf' => Csrf::token(),
            'tab' => $tab,
            'presentacion' => $presentacionId > 0 ? (Catalogo::buscarPresentacion($presentacionId) ?: []) : [],
            'empaque' => $empaqueId > 0 ? (Catalogo::buscarEmpaque($empaqueId) ?: []) : [],
            'tipoArticulo' => $tipoId > 0 ? (Catalogo::buscarTipoArticulo($tipoId) ?: []) : [],
            'categoriaArticulo' => $categoriaId > 0 ? (Catalogo::buscarCategoriaArticulo($categoriaId) ?: []) : [],
            'subcategoriaArticulo' => $subcategoriaId > 0 ? (Catalogo::buscarSubcategoriaArticulo($subcategoriaId) ?: []) : [],
            'presentaciones' => Catalogo::listarPresentaciones(),
            'empaques' => Catalogo::listarEmpaques(),
            'tiposArticulo' => Catalogo::listarTiposArticulo(),
            'categoriasArticulo' => Catalogo::listarCategoriasArticulo(),
            'subcategoriasArticulo' => Catalogo::listarSubcategoriasArticulo(),
            'categoriasActivas' => Catalogo::listarCategoriasArticuloActivas(),
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
            $this->redirect('/mantenimientos/organizacion/catalogo');
        }

        $accion = (string) ($_POST['accion'] ?? '');
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        try {
            if ($accion === 'guardar_presentacion') {
                $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
                $savedId = Catalogo::guardarPresentacion($id, $_POST, $userId);
                AuditLog::write($id ? 'catalogo.presentacion.updated' : 'catalogo.presentacion.created', [
                    'tipo_accion' => $id ? 'presentacion_editar' : 'presentacion_crear',
                    'apartado' => '/mantenimientos/organizacion/catalogo',
                    'descripcion' => $id ? 'Presentacion actualizada' : 'Presentacion creada',
                    'presentacion_id' => $savedId,
                ]);
                $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Catalogo', 'message' => 'Presentacion guardada correctamente.'];
                $this->redirect('/mantenimientos/organizacion/catalogo?tab=presentaciones');
            }

            if ($accion === 'guardar_empaque') {
                $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
                $savedId = Catalogo::guardarEmpaque($id, $_POST, $userId);
                AuditLog::write($id ? 'catalogo.empaque.updated' : 'catalogo.empaque.created', [
                    'tipo_accion' => $id ? 'empaque_editar' : 'empaque_crear',
                    'apartado' => '/mantenimientos/organizacion/catalogo',
                    'descripcion' => $id ? 'Empaque actualizado' : 'Empaque creado',
                    'empaque_id' => $savedId,
                ]);
                $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Catalogo', 'message' => 'Empaque guardado correctamente.'];
                $this->redirect('/mantenimientos/organizacion/catalogo?tab=empaques');
            }

            if ($accion === 'guardar_tipo') {
                $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
                $savedId = Catalogo::guardarTipoArticulo($id, $_POST, $userId);
                AuditLog::write($id ? 'catalogo.tipo.updated' : 'catalogo.tipo.created', [
                    'tipo_accion' => $id ? 'tipo_articulo_editar' : 'tipo_articulo_crear',
                    'apartado' => '/mantenimientos/organizacion/catalogo',
                    'descripcion' => $id ? 'Tipo de articulo actualizado' : 'Tipo de articulo creado',
                    'tipo_articulo_id' => $savedId,
                ]);
                $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Catalogo', 'message' => 'Tipo de articulo guardado correctamente.'];
                $this->redirect('/mantenimientos/organizacion/catalogo?tab=tipos');
            }

            if ($accion === 'guardar_categoria') {
                $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
                $savedId = Catalogo::guardarCategoriaArticulo($id, $_POST, $userId);
                AuditLog::write($id ? 'catalogo.categoria.updated' : 'catalogo.categoria.created', [
                    'tipo_accion' => $id ? 'categoria_articulo_editar' : 'categoria_articulo_crear',
                    'apartado' => '/mantenimientos/organizacion/catalogo',
                    'descripcion' => $id ? 'Categoria de articulo actualizada' : 'Categoria de articulo creada',
                    'categoria_articulo_id' => $savedId,
                ]);
                $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Catalogo', 'message' => 'Categoria guardada correctamente.'];
                $this->redirect('/mantenimientos/organizacion/catalogo?tab=categorias');
            }

            if ($accion === 'guardar_subcategoria') {
                $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
                $savedId = Catalogo::guardarSubcategoriaArticulo($id, $_POST, $userId);
                AuditLog::write($id ? 'catalogo.subcategoria.updated' : 'catalogo.subcategoria.created', [
                    'tipo_accion' => $id ? 'subcategoria_articulo_editar' : 'subcategoria_articulo_crear',
                    'apartado' => '/mantenimientos/organizacion/catalogo',
                    'descripcion' => $id ? 'Subcategoria de articulo actualizada' : 'Subcategoria de articulo creada',
                    'subcategoria_articulo_id' => $savedId,
                ]);
                $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Catalogo', 'message' => 'Subcategoria guardada correctamente.'];
                $this->redirect('/mantenimientos/organizacion/catalogo?tab=subcategorias');
            }

            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Catalogo',
                'message' => 'Accion no valida.',
            ];
            $this->redirect('/mantenimientos/organizacion/catalogo');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Catalogo',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el registro.',
            ];
            $tab = 'presentaciones';
            if ($accion === 'guardar_empaque') {
                $tab = 'empaques';
            } elseif ($accion === 'guardar_tipo') {
                $tab = 'tipos';
            } elseif ($accion === 'guardar_categoria') {
                $tab = 'categorias';
            } elseif ($accion === 'guardar_subcategoria') {
                $tab = 'subcategorias';
            }
            $this->redirect('/mantenimientos/organizacion/catalogo?tab=' . $tab);
        }
    }
}
