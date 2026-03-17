<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Settings;
use App\Core\SimplePdf;
use App\Models\Proveedor;

final class ReportesController extends Controller
{
    public function historialCompras(): void
    {
        $desde = trim((string) ($_GET['desde'] ?? ''));
        $hasta = trim((string) ($_GET['hasta'] ?? ''));
        $proveedorId = (int) ($_GET['proveedor_id'] ?? 0);
        $articuloId = (int) ($_GET['articulo_id'] ?? 0);
        $moneda = trim((string) ($_GET['moneda'] ?? ''));

        $params = [];
        $where = [];
        if ($desde !== '') {
            $where[] = 'h.fecha >= :desde';
            $params['desde'] = $desde;
        }
        if ($hasta !== '') {
            $where[] = 'h.fecha <= :hasta';
            $params['hasta'] = $hasta;
        }
        if ($proveedorId > 0) {
            $where[] = 'h.proveedor_id = :proveedor_id';
            $params['proveedor_id'] = $proveedorId;
        }
        if ($articuloId > 0) {
            $where[] = 'h.articulo_id = :articulo_id';
            $params['articulo_id'] = $articuloId;
        }
        if ($moneda !== '') {
            $where[] = 'h.moneda = :moneda';
            $params['moneda'] = $moneda;
        }

        $sql = 'SELECT h.id,
                       h.fecha,
                       h.articulo_id,
                       a.codigo AS articulo_codigo,
                       a.nombre AS articulo_nombre,
                       h.proveedor_id,
                       p.razon_social AS proveedor_nombre,
                       h.cantidad,
                       h.unidad,
                       h.cant_por_unidad,
                       h.peso_por_unidad,
                       h.peso_unidad,
                       h.total_kg,
                       h.costo_unitario,
                       h.moneda,
                       h.total_linea,
                       h.entrada_compra_id,
                       h.orden_compra_id,
                       e.codigo_entrada AS entrada_codigo,
                       oc.codigo_compra AS oc_codigo
                FROM articulos_costo_historial h
                LEFT JOIN articulos a ON a.id = h.articulo_id
                LEFT JOIN proveedores p ON p.id = h.proveedor_id
                LEFT JOIN entradas_compra e ON e.id = h.entrada_compra_id
                LEFT JOIN ordenes_compra oc ON oc.id = h.orden_compra_id';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY h.fecha DESC, h.id DESC';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $export = trim((string) ($_GET['export'] ?? ''));
        if ($export === 'excel') {
            $this->exportExcel($rows);
            return;
        }
        if ($export === 'pdf') {
            $this->exportPdf($rows);
            return;
        }

        $articulos = Db::conexion()->query('SELECT id, codigo, nombre FROM articulos WHERE COALESCE(es_comprable, 0) = 1 ORDER BY nombre ASC')->fetchAll() ?: [];
        $proveedores = Proveedor::listarActivos();

        $this->render('reportes/procesos/historial-compras/index', [
            'titulo' => 'Reporte Historial de Compras',
            'csrf' => Csrf::token(),
            'articulos' => $articulos,
            'proveedores' => $proveedores,
            'filters' => [
                'desde' => $desde,
                'hasta' => $hasta,
                'proveedor_id' => $proveedorId,
                'articulo_id' => $articuloId,
                'moneda' => $moneda,
            ],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function exportExcel(array $rows): void
    {
        $filename = 'historial_compras_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Fecha', 'Articulo', 'Proveedor', 'Cantidad', 'Cant x unidad', 'Peso x unidad', 'Kg', 'Costo', 'Total', 'Moneda', 'Entrada', 'OC'], ',', '"', "\\");
        foreach ($rows as $r) {
            $articulo = trim((string) ($r['articulo_codigo'] ?? '')) . ' - ' . trim((string) ($r['articulo_nombre'] ?? ''));
            $unidad = (string) ($r['unidad'] ?? '');
            $cantUnidad = $unidad === 'u' ? '-' : (string) ($r['cant_por_unidad'] ?? '');
            $pesoUnidad = $unidad === 'u' ? '-' : (string) ($r['peso_por_unidad'] ?? '');
            fputcsv($out, [
                (string) ($r['fecha'] ?? ''),
                $articulo,
                (string) ($r['proveedor_nombre'] ?? ''),
                (string) ($r['cantidad'] ?? '0') . ' ' . $unidad,
                $cantUnidad,
                $pesoUnidad,
                number_format((float) ($r['total_kg'] ?? 0), 4, '.', ''),
                number_format((float) ($r['costo_unitario'] ?? 0), 2, '.', ''),
                number_format((float) ($r['total_linea'] ?? 0), 2, '.', ''),
                (string) ($r['moneda'] ?? ''),
                (string) ($r['entrada_codigo'] ?? ''),
                (string) ($r['oc_codigo'] ?? ''),
            ], ',', '"', "\\");
        }
        fclose($out);
        exit;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function exportPdf(array $rows): void
    {
        $company = Settings::many([
            'company_name',
            'company_phone',
            'company_mail',
            'company_address',
        ]);
        $pdf = new SimplePdf(841.89, 595.28);
        $pdf->addPage();
        $y = 0.0;
        $this->drawHistorialHeader($pdf, $company, $y, false);
        $totalKg = 0.0;
        $totalLinea = 0.0;
        $totalRows = 0;

        foreach ($rows as $r) {
            if ($y > 545) {
                $pdf->addPage();
                $this->drawHistorialHeader($pdf, $company, $y, true);
            }
            $articulo = trim((string) ($r['articulo_codigo'] ?? '')) . ' - ' . trim((string) ($r['articulo_nombre'] ?? ''));
            $unidad = (string) ($r['unidad'] ?? '');
            $cantUnidad = $unidad === 'u' ? '-' : (string) ($r['cant_por_unidad'] ?? '');
            $pesoUnidad = $unidad === 'u' ? '-' : (string) ($r['peso_por_unidad'] ?? '');
            $pdf->text(16, $y, (string) ($r['fecha'] ?? ''), 8);
            $pdf->text(70, $y, $this->truncateForPdf($articulo, 34), 8);
            $pdf->text(270, $y, $this->truncateForPdf((string) ($r['proveedor_nombre'] ?? ''), 18), 8);
            $pdf->text(410, $y, number_format((float) ($r['cantidad'] ?? 0), 2, '.', '') . ' ' . $unidad, 8);
            $pdf->text(460, $y, $cantUnidad === '-' ? '-' : number_format((float) $cantUnidad, 2, '.', ''), 8);
            $pdf->text(510, $y, $pesoUnidad === '-' ? '-' : number_format((float) $pesoUnidad, 2, '.', ''), 8);
            $rowKg = (float) ($r['total_kg'] ?? 0);
            $rowTotal = (float) ($r['total_linea'] ?? 0);
            $pdf->text(560, $y, number_format($rowKg, 2, '.', ''), 8);
            $pdf->text(620, $y, number_format($rowTotal, 2, '.', ''), 8);
            $entradaCodigo = (string) ($r['entrada_codigo'] ?? '');
            $ocCodigo = (string) ($r['oc_codigo'] ?? '');
            $entOc = trim($entradaCodigo . ($ocCodigo !== '' ? ('/' . $ocCodigo) : ''));
            if ($entOc === '') {
                $entOc = '-';
            }
            $pdf->text(700, $y, $this->truncateForPdf($entOc, 16), 8);
            $y += 12;
            $totalKg += $rowKg;
            $totalLinea += $rowTotal;
            $totalRows++;
        }

        if ($y > 545) {
            $pdf->addPage();
            $this->drawHistorialHeader($pdf, $company, $y, true);
        }
        $pdf->rect(12, $y - 8, 816, 18, 'F', 0.95);
        $pdf->text(16, $y + 2, 'Totales', 9, 'F2');
        $pdf->text(160, $y + 2, 'Registros: ' . $totalRows, 9);
        $pdf->text(560, $y + 2, 'Kg: ' . number_format($totalKg, 2, '.', ''), 9, 'F2');
        $pdf->text(660, $y + 2, 'Total: ' . number_format($totalLinea, 2, '.', ''), 9, 'F2');

        $bin = $pdf->output('historial_compras.pdf');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="historial_compras.pdf"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bin;
        exit;
    }

    /**
     * @param array<string, mixed> $company
     */
    private function drawHistorialHeader(SimplePdf $pdf, array $company, float &$y, bool $continuacion = false): void
    {
        $companyName = trim((string) ($company['company_name'] ?? 'FERLON'));
        $companyPhone = trim((string) ($company['company_phone'] ?? ''));
        $companyMail = trim((string) ($company['company_mail'] ?? ''));
        $companyAddress = trim((string) ($company['company_address'] ?? ''));
        $title = $continuacion ? 'Historial de compras (continuacion)' : 'Historial de compras';

        $y = 26.0;
        $pdf->rect(12, $y - 14, 816, 40, 'F', 0.96);
        $pdf->line(12, $y + 25, 828, $y + 25, 1.0);
        $pdf->text(16, $y + 2, $companyName !== '' ? $companyName : 'FERLON', 13, 'F2');
        $line2 = trim($companyPhone . ($companyPhone !== '' && $companyMail !== '' ? ' | ' : '') . $companyMail);
        if ($line2 !== '') {
            $pdf->text(16, $y + 14, $this->truncateForPdf($line2, 120), 9);
        }
        if ($companyAddress !== '') {
            $pdf->text(16, $y + 26, $this->truncateForPdf($companyAddress, 120), 9);
        }
        $pdf->text(610, $y + 6, $title, 11, 'F2');

        $y += 46;
        $pdf->rect(12, $y - 10, 816, 16, 'F', 0.96);
        $pdf->text(16, $y, 'Fecha', 9, 'F2');
        $pdf->text(70, $y, 'Articulo', 9, 'F2');
        $pdf->text(270, $y, 'Proveedor', 9, 'F2');
        $pdf->text(410, $y, 'Cant', 9, 'F2');
        $pdf->text(460, $y, 'CxU', 9, 'F2');
        $pdf->text(510, $y, 'PxU', 9, 'F2');
        $pdf->text(560, $y, 'Kg', 9, 'F2');
        $pdf->text(620, $y, 'Total', 9, 'F2');
        $pdf->text(700, $y, 'Ent/OC', 9, 'F2');
        $y += 14;
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
