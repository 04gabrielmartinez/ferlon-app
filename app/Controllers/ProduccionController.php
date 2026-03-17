<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Produccion;
use App\Models\RecetaBase;
use App\Models\Secuencia;
use App\Core\Settings;
use App\Core\SimplePdf;

final class ProduccionController extends Controller
{
    public function index(): void
    {
        Secuencia::ensureExists('pr', 'Produccion', 'PR', 5, 1);
        $recetasProductos = RecetaBase::listarProductosRecetaBase();
        $recetasProductos = array_values(array_filter($recetasProductos, static fn ($r): bool => (int) ($r['receta_base_id'] ?? 0) > 0));
        $canCrear = Auth::hasPermission('produccion.crear');
        $canEditar = Auth::hasPermission('produccion.editar');
        $canVer = Auth::hasPermission('produccion.ver') || $canCrear || $canEditar;

        $this->render('procesos/almacen/produccion/index', [
            'titulo' => 'Produccion',
            'csrf' => Csrf::token(),
            'productosReceta' => $recetasProductos,
            'producciones' => Produccion::listarRegistros(),
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
                'title' => 'Produccion',
                'message' => 'Token CSRF invalido.',
            ];
            $this->redirect('/procesos/almacen/produccion');
        }

        try {
            $isEdit = (int) ($_POST['produccion_id'] ?? 0) > 0;
            if ($isEdit) {
                if (!Auth::hasPermission('produccion.editar')) {
                    throw new \RuntimeException('No tienes permiso para editar producciones.');
                }
            } elseif (!Auth::hasPermission('produccion.crear')) {
                throw new \RuntimeException('No tienes permiso para crear producciones.');
            }

            $usuario = Auth::user() ?? [];
            $userId = (int) ($usuario['id'] ?? 0);
            $fecha = (string) ($_POST['fecha'] ?? date('Y-m-d'));
            $comentario = (string) ($_POST['comentario'] ?? '');

            $productos = $_POST['producto_articulo_id'] ?? [];
            $cantidades = $_POST['cantidad'] ?? [];
            if (!is_array($productos) || !is_array($cantidades) || count($productos) === 0) {
                throw new \RuntimeException('Debes agregar al menos un producto para producir.');
            }

            if ($isEdit && count($productos) > 1) {
                throw new \RuntimeException('Solo puedes editar una produccion a la vez.');
            }

            $creadas = 0;
            $codigos = [];
            foreach ($productos as $idx => $prodRaw) {
                $prodId = (int) $prodRaw;
                $cant = $cantidades[$idx] ?? null;
                if ($prodId <= 0) {
                    continue;
                }
                $record = [
                    'producto_articulo_id' => $prodId,
                    'cantidad' => $cant,
                    'fecha' => $fecha,
                    'comentario' => $comentario,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'secuencia_clave' => 'pr',
                ];
                if ($isEdit) {
                    $record['id'] = (int) ($_POST['produccion_id'] ?? 0);
                    $res = Produccion::actualizarProduccion($record);
                } else {
                    $res = Produccion::guardarProduccion($record);
                }
                $creadas++;
                if (!empty($res['codigo_produccion'])) {
                    $codigos[] = $res['codigo_produccion'];
                }
            }

            if ($creadas === 0) {
                throw new \RuntimeException('No se pudo crear ninguna produccion. Verifica los datos.');
            }

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Produccion',
                'message' => ($isEdit ? 'Produccion actualizada: ' : 'Producciones creadas: ') . $creadas . (empty($codigos) ? '' : (' (' . implode(', ', $codigos) . ')')),
            ];
            $this->redirect('/procesos/almacen/produccion');
        } catch (\Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Produccion',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la produccion.',
            ];
            $this->redirect('/procesos/almacen/produccion');
        }
    }

    public function receta(): void
    {
        $productoId = (int) ($_GET['producto_id'] ?? 0);
        $receta = Produccion::obtenerRecetaParaProduccion($productoId);
        if (!$receta) {
            $this->json(['ok' => false, 'message' => 'Receta base no encontrada.']);
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
            $this->json(['ok' => false, 'message' => 'Produccion no valida.']);
            return;
        }
        $prod = Produccion::buscarPorId($id);
        if (!$prod) {
            $this->json(['ok' => false, 'message' => 'Produccion no encontrada.']);
            return;
        }
        $this->json(['ok' => true, 'produccion' => $prod]);
    }

    public function imprimir(): void
    {
        $produccionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($produccionId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Produccion',
                'message' => 'Selecciona una produccion para imprimir.',
            ];
            $this->redirect('/procesos/almacen/produccion');
        }

        $produccion = Produccion::buscarPorId($produccionId);
        if (!$produccion) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Produccion',
                'message' => 'Produccion no valida.',
            ];
            $this->redirect('/procesos/almacen/produccion');
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
        $this->drawPdfHeader($pdf, $produccion, $company, $y);

        $detalles = is_array($produccion['detalles'] ?? null) ? $produccion['detalles'] : [];
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
                    $this->drawPdfHeader($pdf, $produccion, $company, $y, true);
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

        $filename = 'produccion_' . ($produccion['codigo_produccion'] ?? ('PR' . $produccionId)) . '.pdf';
        $bin = $pdf->output($filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', (string) $filename) . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bin;
        exit;
    }

    /** @param array<string,mixed> $produccion @param array<string,mixed> $company */
    private function drawPdfHeader(SimplePdf $pdf, array $produccion, array $company, float &$y, bool $continuacion = false): void
    {
        $titulo = $continuacion ? 'Orden de Produccion (continuacion)' : 'Orden de Produccion';
        $codigo = trim((string) ($produccion['codigo_produccion'] ?? 'N/D'));
        $fecha = (string) ($produccion['fecha'] ?? date('Y-m-d'));
        $producto = trim((string) ($produccion['producto_descripcion'] ?? ''));
        $productoCodigo = trim((string) ($produccion['producto_codigo'] ?? ''));
        $cantidad = number_format((float) ($produccion['cantidad'] ?? 0), 4, '.', ',') . ' KG';
        $empleado = trim((string) ($produccion['empleado_nombre'] ?? ''));
        $comentario = trim((string) ($produccion['comentario'] ?? ''));

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

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
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
