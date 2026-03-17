<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Settings;
use App\Core\SimplePdf;
use App\Models\RecetaBase;
use Throwable;

final class RecetaBaseController extends Controller
{
    public function index(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $productoId = isset($_GET['producto_id']) ? (int) $_GET['producto_id'] : 0;
        $receta = [];
        if ($id > 0) {
            $receta = RecetaBase::buscarPorId($id) ?: [];
        } elseif ($productoId > 0) {
            $receta = RecetaBase::buscarPorProductoArticuloId($productoId) ?: ['producto_articulo_id' => $productoId];
        }
        $productoSeleccionado = ((int) ($receta['producto_articulo_id'] ?? 0) > 0)
            ? (RecetaBase::buscarProductoRecetaBasePorId((int) $receta['producto_articulo_id']) ?: null)
            : null;

        $this->render('mantenimientos/organizacion/recetas-base/index', [
            'titulo' => 'Receta Base',
            'csrf' => Csrf::token(),
            'receta' => $receta,
            'productoSeleccionado' => $productoSeleccionado,
            'productosRecetaBase' => RecetaBase::listarProductosRecetaBase(),
            'insumosReceta' => RecetaBase::listarInsumosReceta(),
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
            $this->redirect('/mantenimientos/organizacion/recetas-base');
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        try {
            $id = isset($_POST['id']) && (int) $_POST['id'] > 0 ? (int) $_POST['id'] : null;
            $savedId = RecetaBase::guardar($id, $_POST, $userId);

            AuditLog::write($id ? 'receta_base.updated' : 'receta_base.created', [
                'tipo_accion' => $id ? 'receta_base_editar' : 'receta_base_crear',
                'apartado' => '/mantenimientos/organizacion/recetas-base',
                'descripcion' => $id ? 'Receta base actualizada' : 'Receta base creada',
                'receta_base_id' => $savedId,
                'producto_articulo_id' => (int) ($_POST['producto_articulo_id'] ?? 0),
            ]);

            $_SESSION['flash_toast'] = ['type' => 'success', 'title' => 'Receta Base', 'message' => 'Receta base guardada correctamente.'];
            $this->redirect('/mantenimientos/organizacion/recetas-base');
        } catch (Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Receta Base',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la receta base.',
            ];
            $this->redirect('/mantenimientos/organizacion/recetas-base');
        }
    }

    public function imprimir(): void
    {
        $productoId = isset($_GET['producto_id']) ? (int) $_GET['producto_id'] : 0;
        if ($productoId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Receta Base',
                'message' => 'Selecciona un producto para imprimir.',
            ];
            $this->redirect('/mantenimientos/organizacion/recetas-base');
        }

        $producto = RecetaBase::buscarProductoRecetaBasePorId($productoId);
        if (!$producto) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Receta Base',
                'message' => 'Producto no valido para receta base.',
            ];
            $this->redirect('/mantenimientos/organizacion/recetas-base');
        }

        $receta = RecetaBase::buscarPorProductoArticuloId($productoId);
        if (!$receta) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Receta Base',
                'message' => 'Este producto no tiene receta base creada.',
            ];
            $this->redirect('/mantenimientos/organizacion/recetas-base?producto_id=' . $productoId);
        }

        $company = Settings::many([
            'company_name',
            'company_phone',
            'company_mail',
            'company_address',
        ]);
        $pdf = new SimplePdf();
        $pdf->addPage();

        $y = 34.0;
        $this->drawPdfHeader($pdf, $producto, $receta, $company, $y);
        $detalles = is_array($receta['detalles'] ?? null) ? $receta['detalles'] : [];

        $pdf->rect(30, $y - 12, 535, 20, 'F', 0.92);
        $pdf->text(36, $y, 'Detalle de Insumos', 11, 'F2');
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
                    $this->drawPdfHeader($pdf, $producto, $receta, $company, $y, true);
                    $pdf->rect(30, $y - 12, 535, 20, 'F', 0.92);
                    $pdf->text(36, $y, 'Detalle de Insumos (continuacion)', 11, 'F2');
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

        $filename = 'receta_base_' . ($producto['codigo'] ?? ('P' . $productoId)) . '.pdf';
        $bin = $pdf->output($filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', (string) $filename) . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bin;
        exit;
    }

    /** @param array<string,mixed> $producto @param array<string,mixed> $receta @param array<string,mixed> $company */
    private function drawPdfHeader(SimplePdf $pdf, array $producto, array $receta, array $company, float &$y, bool $continuacion = false): void
    {
        $titulo = $continuacion ? 'Receta Base (continuacion)' : 'Receta Base';
        $codigo = trim((string) ($producto['codigo'] ?? 'N/D'));
        $descripcion = trim((string) ($producto['descripcion'] ?? 'N/D'));
        $fecha = date('d/m/Y H:i');
        $companyName = trim((string) ($company['company_name'] ?? 'FERLON'));
        $companyPhone = trim((string) ($company['company_phone'] ?? ''));
        $companyMail = trim((string) ($company['company_mail'] ?? ''));
        $companyAddress = trim((string) ($company['company_address'] ?? ''));
        $rendimiento = (string) ($receta['rendimiento'] ?? '1');
        $unidadRend = (string) ($receta['unidad_rendimiento'] ?? 'kg');

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
        $pdf->text(300, $y, 'Rendimiento: ' . $rendimiento . ' ' . $unidadRend, 10, 'F2');
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
