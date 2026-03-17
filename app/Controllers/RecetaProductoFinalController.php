<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Settings;
use App\Core\SimplePdf;
use App\Models\RecetaProductoFinal;
use Throwable;

final class RecetaProductoFinalController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $productoId = isset($_GET['producto_id']) ? (int) $_GET['producto_id'] : 0;
        $presentacionId = isset($_GET['presentacion_id']) ? (int) $_GET['presentacion_id'] : 0;
        $empaqueId = isset($_GET['empaque_id']) ? (int) $_GET['empaque_id'] : 0;
        $receta = [];
        $productosRecetaProductoFinal = RecetaProductoFinal::listarProductosRecetaProductoFinal();
        $productosBaseRecetaProductoFinal = RecetaProductoFinal::listarProductosBaseRecetaProductoFinal();
        if ($id > 0) {
            $receta = RecetaProductoFinal::buscarPorId($id) ?: [];
        } elseif ($productoId > 0 && $presentacionId > 0 && $empaqueId > 0) {
            $receta = RecetaProductoFinal::buscarPorProductoArticuloId($productoId, $presentacionId, $empaqueId)
                ?: [
                    'producto_articulo_id' => $productoId,
                    'presentacion_id' => $presentacionId,
                    'empaque_id' => $empaqueId,
                ];
        } elseif ($productoId > 0) {
            $receta = [
                'producto_articulo_id' => $productoId,
                'presentacion_id' => $presentacionId > 0 ? $presentacionId : 0,
                'empaque_id' => $empaqueId > 0 ? $empaqueId : 0,
            ];
        }
        $productoSeleccionado = ((int) ($receta['producto_articulo_id'] ?? 0) > 0)
            ? (RecetaProductoFinal::buscarProductoRecetaProductoFinalPorId((int) $receta['producto_articulo_id']) ?: null)
            : null;
        $varianteSeleccionada = null;
        foreach ($productosRecetaProductoFinal as $v) {
            if (
                (int) ($v['id'] ?? 0) === (int) ($receta['producto_articulo_id'] ?? 0)
                && (int) ($v['presentacion_id'] ?? 0) === (int) ($receta['presentacion_id'] ?? 0)
                && (int) ($v['empaque_id'] ?? 0) === (int) ($receta['empaque_id'] ?? 0)
            ) {
                $varianteSeleccionada = $v;
                break;
            }
        }
        $productoContextoId = (int) ($receta['producto_articulo_id'] ?? $productoId);
        $presentacionesProducto = $productoContextoId > 0 ? RecetaProductoFinal::listarPresentacionesProducto($productoContextoId) : [];
        $empaquesProducto = $productoContextoId > 0 ? RecetaProductoFinal::listarEmpaquesProducto($productoContextoId) : [];
        $variantesProductoReceta = $productoContextoId > 0 ? RecetaProductoFinal::listarVariantesProductoReceta($productoContextoId) : [];

        $this->render('mantenimientos/organizacion/recetas-producto-final/index', [
            'titulo' => 'Receta Producto Final',
            'csrf' => Csrf::token(),
            'receta' => $receta,
            'productoSeleccionado' => $productoSeleccionado,
            'varianteSeleccionada' => $varianteSeleccionada,
            'productosRecetaProductoFinal' => $productosRecetaProductoFinal,
            'productosBaseRecetaProductoFinal' => $productosBaseRecetaProductoFinal,
            'presentacionesProducto' => $presentacionesProducto,
            'empaquesProducto' => $empaquesProducto,
            'variantesProductoReceta' => $variantesProductoReceta,
            'insumosReceta' => RecetaProductoFinal::listarInsumosReceta(),
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
            $this->redirect('/mantenimientos/organizacion/recetas-producto-final');
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        try {
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $savedId = RecetaProductoFinal::guardar($id, $_POST, $userId);

            AuditLog::write($id ? 'receta_producto_final.updated' : 'receta_producto_final.created', [
                'tipo_accion' => $id ? 'receta_producto_final_editar' : 'receta_producto_final_crear',
                'apartado' => '/mantenimientos/organizacion/recetas-producto-final',
                'descripcion' => $id ? 'Receta producto final actualizada' : 'Receta producto final creada',
                'receta_producto_final_id' => $savedId,
                'producto_articulo_id' => (int) ($_POST['producto_articulo_id'] ?? 0),
            ]);

            $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Receta Producto Final', 'message' => 'Receta producto final guardada correctamente.'];
            $this->redirect('/mantenimientos/organizacion/recetas-producto-final');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Receta Producto Final',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la receta producto final.',
            ];
            $this->redirect('/mantenimientos/organizacion/recetas-producto-final');
        }
    }

    public function imprimir(): void
    {
        $productoId = isset($_GET['producto_id']) ? (int) $_GET['producto_id'] : 0;
        if ($productoId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Receta Producto Final',
                'message' => 'Selecciona un producto para imprimir.',
            ];
            $this->redirect('/mantenimientos/organizacion/recetas-producto-final');
        }

        $producto = RecetaProductoFinal::buscarProductoRecetaProductoFinalPorId($productoId);
        if (!$producto) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Receta Producto Final',
                'message' => 'Producto no valido para receta producto final.',
            ];
            $this->redirect('/mantenimientos/organizacion/recetas-producto-final');
        }

        $recetas = RecetaProductoFinal::listarRecetasCreadasPorProducto($productoId);
        $company = Settings::many([
            'company_name',
            'company_phone',
            'company_mail',
            'company_address',
        ]);
        $pdf = new SimplePdf();
        $pdf->addPage();

        $y = 34.0;
        $this->drawPdfHeader($pdf, $producto, $recetas, $company, $y);

        foreach ($recetas as $idx => $r) {
            $detalles = is_array($r['detalles'] ?? null) ? $r['detalles'] : [];

            if ($y > 740) {
                $pdf->addPage();
                $y = 34.0;
                $this->drawPdfHeader($pdf, $producto, $recetas, $company, $y, true);
            }

            $variant = trim(((string) ($r['presentacion_descripcion'] ?? '')) . ' / ' . ((string) ($r['empaque_descripcion'] ?? '')));
            $pdf->rect(30, $y - 12, 535, 20, 'F', 0.92);
            $pdf->text(36, $y, 'Variante: ' . ($variant !== '' ? $variant : 'N/D'), 11, 'F2');
            $y += 22;

            $pdf->rect(30, $y - 11, 535, 16, 'F', 0.97);
            $pdf->text(34, $y, 'Codigo', 9, 'F2');
            $pdf->text(130, $y, 'Insumo', 9, 'F2');
            $pdf->text(450, $y, 'Cantidad', 9, 'F2');
            $pdf->text(515, $y, 'Unidad', 9, 'F2');
            $y += 16;

            if ($detalles === []) {
                $pdf->text(34, $y, 'Sin insumos en esta receta.', 9);
                $y += 16;
            } else {
                foreach ($detalles as $d) {
                    if ($y > 790) {
                        $pdf->addPage();
                        $y = 34.0;
                        $this->drawPdfHeader($pdf, $producto, $recetas, $company, $y, true);
                        $pdf->rect(30, $y - 12, 535, 20, 'F', 0.92);
                        $pdf->text(36, $y, 'Variante: ' . ($variant !== '' ? $variant : 'N/D') . ' (continuacion)', 11, 'F2');
                        $y += 22;
                        $pdf->rect(30, $y - 11, 535, 16, 'F', 0.97);
                        $pdf->text(34, $y, 'Codigo', 9, 'F2');
                        $pdf->text(130, $y, 'Insumo', 9, 'F2');
                        $pdf->text(450, $y, 'Cantidad', 9, 'F2');
                        $pdf->text(515, $y, 'Unidad', 9, 'F2');
                        $y += 16;
                    }

                    $codigo = $this->truncateForPdf((string) ($d['insumo_codigo'] ?? ''), 14);
                    $insumo = $this->truncateForPdf((string) ($d['insumo_descripcion'] ?? ''), 62);
                    $cantidad = (string) ($d['cantidad'] ?? '0');
                    $unidad = (string) ($d['unidad'] ?? 'u');

                    $pdf->text(34, $y, $codigo, 9);
                    $pdf->text(130, $y, $insumo, 9);
                    $pdf->text(460, $y, $cantidad, 9);
                    $pdf->text(520, $y, $unidad, 9);
                    $pdf->line(30, $y + 4, 565, $y + 4, 0.3);
                    $y += 14;
                }
            }

            if ($idx < count($recetas) - 1) {
                $y += 8;
            }
        }

        $filename = 'recetas_producto_final_' . ($producto['codigo'] ?? ('P' . $productoId)) . '.pdf';
        $bin = $pdf->output($filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', (string) $filename) . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bin;
        exit;
    }

    /** @param array<string,mixed> $producto @param array<int,array<string,mixed>> $recetas @param array<string,mixed> $company */
    private function drawPdfHeader(SimplePdf $pdf, array $producto, array $recetas, array $company, float &$y, bool $continuacion = false): void
    {
        $titulo = $continuacion ? 'Recetas de Producto Final (continuacion)' : 'Recetas de Producto Final';
        $codigo = trim((string) ($producto['codigo'] ?? 'N/D'));
        $descripcion = trim((string) ($producto['descripcion'] ?? 'N/D'));
        $fecha = date('d/m/Y H:i');
        $companyName = trim((string) ($company['company_name'] ?? 'FERLON'));
        $companyPhone = trim((string) ($company['company_phone'] ?? ''));
        $companyMail = trim((string) ($company['company_mail'] ?? ''));
        $companyAddress = trim((string) ($company['company_address'] ?? ''));

        $pdf->rect(30, $y - 14, 535, 44, 'F', 0.96);
        $pdf->line(30, $y + 31, 565, $y + 31, 1.0);
        $pdf->text(34, $y + 2, $companyName !== '' ? $companyName : 'FERLON', 13, 'F2');
        $line2 = trim($companyPhone . ($companyPhone !== '' && $companyMail !== '' ? ' | ' : '') . $companyMail);
        if ($line2 !== '') {
            $pdf->text(34, $y + 14, $this->truncateForPdf($line2, 84), 9);
        }
        if ($companyAddress !== '') {
            $pdf->text(34, $y + 26, $this->truncateForPdf($companyAddress, 84), 9);
        }

        $pdf->text(395, $y + 4, $titulo, 11, 'F2');
        $pdf->text(395, $y + 17, 'Fecha: ' . $fecha, 9);
        $y += 48;
        $y += 14;
        $pdf->rect(30, $y - 11, 535, 18, 'F', 0.98);
        $pdf->text(34, $y, 'Codigo producto: ' . $codigo, 10, 'F2');
        $pdf->text(300, $y, 'Recetas creadas: ' . count($recetas), 10, 'F2');
        $y += 14;
        $pdf->text(34, $y, 'Producto: ' . $this->truncateForPdf($descripcion, 88), 10);
        $y += 18;
    }

    private function truncateForPdf(string $txt, int $max): string
    {
        $txt = trim($txt);
        if ($txt === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($txt) <= $max) {
                return $txt;
            }
            return mb_substr($txt, 0, max(1, $max - 1)) . '...';
        }
        if (strlen($txt) <= $max) {
            return $txt;
        }
        return substr($txt, 0, max(1, $max - 1)) . '...';
    }
}
