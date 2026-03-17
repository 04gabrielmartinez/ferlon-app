<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Settings;
use App\Core\SimplePdf;
use App\Models\Fabricacion;
use App\Models\RecetaProductoFinal;
use App\Models\Secuencia;

final class FabricacionController extends Controller
{
    public function index(): void
    {
        Secuencia::ensureExists('fb', 'Fabricacion', 'FB', 5, 1);
        $productosReceta = RecetaProductoFinal::listarProductosRecetaProductoFinal();
        $productosReceta = array_values(array_filter($productosReceta, static fn ($r): bool => (int) ($r['receta_producto_final_id'] ?? 0) > 0));

        $canCrear = Auth::hasPermission('fabricacion.crear');
        $canEditar = Auth::hasPermission('fabricacion.editar');
        $canVer = Auth::hasPermission('fabricacion.ver') || $canCrear || $canEditar;

        $this->render('procesos/almacen/fabricacion/index', [
            'titulo' => 'Fabricacion',
            'csrf' => Csrf::token(),
            'productosReceta' => $productosReceta,
            'fabricaciones' => Fabricacion::listarRegistros(),
            'canCrear' => $canCrear,
            'canEditar' => $canEditar,
            'canVer' => $canVer,
        ]);
    }

    public function guardar(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Fabricacion',
                'message' => 'Token CSRF invalido.',
            ];
            $this->redirect('/procesos/almacen/fabricacion');
        }

        try {
            $isEdit = (int) ($_POST['fabricacion_id'] ?? 0) > 0;
            if ($isEdit) {
                if (!Auth::hasPermission('fabricacion.editar')) {
                    throw new \RuntimeException('No tienes permiso para editar fabricaciones.');
                }
            } elseif (!Auth::hasPermission('fabricacion.crear')) {
                throw new \RuntimeException('No tienes permiso para crear fabricaciones.');
            }

            $usuario = Auth::user() ?? [];
            $userId = (int) ($usuario['id'] ?? 0);
            $fecha = (string) ($_POST['fecha'] ?? date('Y-m-d'));
            $comentario = (string) ($_POST['comentario'] ?? '');

            $productos = $_POST['producto_articulo_id'] ?? [];
            $cantidades = $_POST['cantidad'] ?? [];
            $presentaciones = $_POST['presentacion_id'] ?? [];
            $empaques = $_POST['empaque_id'] ?? [];
            if (!is_array($productos) || !is_array($cantidades) || count($productos) === 0) {
                throw new \RuntimeException('Debes agregar al menos un producto para fabricar.');
            }

            if ($isEdit && count($productos) > 1) {
                throw new \RuntimeException('Solo puedes editar una fabricacion a la vez.');
            }

            $creadas = 0;
            $codigos = [];
            foreach ($productos as $idx => $prodRaw) {
                $prodId = (int) $prodRaw;
                $cant = $cantidades[$idx] ?? null;
                $presentacionId = isset($presentaciones[$idx]) ? (int) $presentaciones[$idx] : 0;
                $empaqueId = isset($empaques[$idx]) ? (int) $empaques[$idx] : 0;
                if ($prodId <= 0) {
                    continue;
                }
                $record = [
                    'producto_articulo_id' => $prodId,
                    'presentacion_id' => $presentacionId,
                    'empaque_id' => $empaqueId,
                    'cantidad' => $cant,
                    'fecha' => $fecha,
                    'comentario' => $comentario,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'secuencia_clave' => 'fb',
                ];
                if ($isEdit) {
                    $record['id'] = (int) ($_POST['fabricacion_id'] ?? 0);
                    $res = Fabricacion::actualizarFabricacion($record);
                } else {
                    $res = Fabricacion::guardarFabricacion($record);
                }
                $creadas++;
                if (!empty($res['codigo_fabricacion'])) {
                    $codigos[] = $res['codigo_fabricacion'];
                }
            }

            if ($creadas === 0) {
                throw new \RuntimeException('No se pudo crear ninguna fabricacion. Verifica los datos.');
            }

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Fabricacion',
                'message' => ($isEdit ? 'Fabricacion actualizada: ' : 'Fabricaciones creadas: ') . $creadas . (empty($codigos) ? '' : (' (' . implode(', ', $codigos) . ')')),
            ];
            $this->redirect('/procesos/almacen/fabricacion');
        } catch (\Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Fabricacion',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la fabricacion.',
            ];
            $this->redirect('/procesos/almacen/fabricacion');
        }
    }

    public function receta(): void
    {
        $productoId = (int) ($_GET['producto_id'] ?? 0);
        $presentacionId = (int) ($_GET['presentacion_id'] ?? 0);
        $empaqueId = (int) ($_GET['empaque_id'] ?? 0);
        $receta = Fabricacion::obtenerRecetaParaFabricacion(
            $productoId,
            $presentacionId > 0 ? $presentacionId : null,
            $empaqueId > 0 ? $empaqueId : null
        );
        if (!$receta) {
            $this->json(['ok' => false, 'message' => 'Receta producto final no encontrada.']);
            return;
        }
        $this->json([
            'ok' => true,
            'receta' => [
                'id' => (int) ($receta['id'] ?? 0),
                'rendimiento' => (float) ($receta['rendimiento'] ?? 0),
                'unidad_rendimiento' => (string) ($receta['unidad_rendimiento'] ?? ''),
                'detalles' => $receta['detalles'] ?? [],
            ],
            'producto' => $receta['producto'] ?? [],
        ]);
    }

    public function detalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'Fabricacion no valida.']);
            return;
        }
        $fab = Fabricacion::buscarPorId($id);
        if (!$fab) {
            $this->json(['ok' => false, 'message' => 'Fabricacion no encontrada.']);
            return;
        }
        $this->json(['ok' => true, 'fabricacion' => $fab]);
    }

    public function imprimir(): void
    {
        $fabId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($fabId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Fabricacion',
                'message' => 'Selecciona una fabricacion para imprimir.',
            ];
            $this->redirect('/procesos/almacen/fabricacion');
        }

        $fab = Fabricacion::buscarPorId($fabId);
        if (!$fab) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Fabricacion',
                'message' => 'Fabricacion no valida.',
            ];
            $this->redirect('/procesos/almacen/fabricacion');
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
        $this->drawPdfHeader($pdf, $fab, $company, $y);

        $detalles = is_array($fab['detalles'] ?? null) ? $fab['detalles'] : [];
        $pdf->rect(30, $y - 12, 535, 20, 'F', 0.9);
        $pdf->text(36, $y, 'Detalle de insumos', 11, 'F2');
        $y += 22;

        $pdf->rect(30, $y - 11, 535, 16, 'F', 0.94);
        $pdf->text(34, $y, 'Codigo', 9, 'F2');
        $pdf->text(120, $y, 'Descripcion', 9, 'F2');
        $pdf->text(420, $y, 'Cant', 9, 'F2');
        $pdf->text(500, $y, 'Unidad', 9, 'F2');
        $y += 16;

        if ($detalles === []) {
            $pdf->text(34, $y, 'Sin insumos registrados.', 9);
            $y += 16;
        } else {
            $rowIdx = 0;
            foreach ($detalles as $d) {
                if ($y > 760) {
                    $pdf->addPage();
                    $y = 34.0;
                    $this->drawPdfHeader($pdf, $fab, $company, $y, true);
                    $pdf->rect(30, $y - 12, 535, 20, 'F', 0.9);
                    $pdf->text(36, $y, 'Detalle de insumos (continuacion)', 11, 'F2');
                    $y += 22;
                    $pdf->rect(30, $y - 11, 535, 16, 'F', 0.94);
                    $pdf->text(34, $y, 'Codigo', 9, 'F2');
                    $pdf->text(120, $y, 'Descripcion', 9, 'F2');
                    $pdf->text(420, $y, 'Cant', 9, 'F2');
                    $pdf->text(500, $y, 'Unidad', 9, 'F2');
                    $y += 16;
                }

                if ($rowIdx % 2 === 0) {
                    $pdf->rect(30, $y - 9, 535, 14, 'F', 0.985);
                }
                $codigo = $this->truncateForPdf((string) ($d['insumo_codigo'] ?? ''), 16);
                $descripcion = $this->truncateForPdf((string) ($d['insumo_descripcion'] ?? ''), 60);
                $cantidad = number_format((float) ($d['cantidad'] ?? 0), 4, '.', ',');
                $unidad = (string) ($d['unidad'] ?? '');

                $pdf->text(34, $y, $codigo, 9);
                $pdf->text(120, $y, $descripcion, 9);
                $pdf->text(420, $y, $cantidad, 9);
                $pdf->text(500, $y, $unidad, 9);
                $pdf->line(30, $y + 4, 565, $y + 4, 0.3);
                $y += 14;
                $rowIdx++;
            }
        }

        $filename = 'fabricacion_' . ($fab['codigo_fabricacion'] ?? ('FB' . $fabId)) . '.pdf';
        $bin = $pdf->output($filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', (string) $filename) . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bin;
        exit;
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    /** @param array<string,mixed> $fab @param array<string,mixed> $company */
    private function drawPdfHeader(SimplePdf $pdf, array $fab, array $company, float &$y, bool $continuacion = false): void
    {
        $titulo = $continuacion ? 'Orden de Fabricacion (continuacion)' : 'Orden de Fabricacion';
        $codigo = trim((string) ($fab['codigo_fabricacion'] ?? 'N/D'));
        $fecha = (string) ($fab['fecha'] ?? date('Y-m-d'));
        $producto = trim((string) ($fab['producto_descripcion'] ?? ''));
        $productoCodigo = trim((string) ($fab['producto_codigo'] ?? ''));
        $presentacion = trim((string) ($fab['presentacion_descripcion'] ?? ''));
        $empaque = trim((string) ($fab['empaque_descripcion'] ?? ''));
        $cantidad = number_format((float) ($fab['cantidad'] ?? 0), 0, '.', ',') . ' u';
        $empleado = trim((string) ($fab['empleado_nombre'] ?? ''));
        $comentario = trim((string) ($fab['comentario'] ?? ''));

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

        $pdf->rect(30, $y - 11, 535, 18, 'F', 0.98);
        $pdf->text(34, $y, 'Codigo: ' . $codigo, 10, 'F2');
        $pdf->text(300, $y, 'Cantidad: ' . $cantidad, 10, 'F2');
        $y += 14;

        $prodLine = $productoCodigo !== '' ? ($productoCodigo . ' - ' . $producto) : $producto;
        if ($presentacion !== '' || $empaque !== '') {
            $prodLine = trim($prodLine . ' / ' . $presentacion . ' / ' . $empaque, ' /');
        }
        if ($prodLine !== '') {
            $pdf->text(34, $y, 'Producto: ' . $this->truncateForPdf($prodLine, 70), 10);
            $y += 14;
        }
        if ($empleado !== '') {
            $pdf->text(34, $y, 'Empleado: ' . $this->truncateForPdf($empleado, 70), 9);
            $y += 12;
        }
        if ($comentario !== '') {
            $pdf->text(34, $y, 'Comentario: ' . $this->truncateForPdf($comentario, 80), 9);
            $y += 12;
        }

        $y += 6;
    }

    private function truncateForPdf(string $text, int $max): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text) <= $max) {
                return $text;
            }
            return mb_substr($text, 0, max(1, $max - 1)) . '...';
        }
        if (strlen($text) <= $max) {
            return $text;
        }
        return substr($text, 0, max(1, $max - 1)) . '...';
    }
}
