<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Settings;
use App\Core\SimplePdf;
use App\Models\Empleado;
use App\Models\EdicionLock;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Throwable;

final class OrdenCompraController extends Controller
{
    public function index(): void
    {
        $ocId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (isset($_GET['reset']) && $_GET['reset'] === '1') {
            Secuencia::ensureExists('oc', 'Orden Compra', 'OC', 5, 1);
            Secuencia::reset('oc', 0, 0);
            OrdenCompra::resetAll();
            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Orden de compra',
                'message' => 'OC reiniciadas y secuencia reseteada.',
            ];
            $this->redirect('/procesos/almacen/orden-compra');
        }
        $usuario = Auth::user();
        $puedeCambiarEmpleado = Auth::hasPermission('empleados.ver');
        $empleadoNombre = trim((string) ($usuario['nombre'] ?? ''));
        if ($empleadoNombre === '') {
            $empleadoNombre = trim((string) ($usuario['username'] ?? 'Usuario'));
        }
        $empleadoId = (int) ($usuario['empleado_id'] ?? 0);
        if ($empleadoId <= 0) {
            $empleadoId = (int) ($usuario['id'] ?? 0);
        }
        $ocRegistros = OrdenCompra::listarRegistros();
        $ocSeleccionada = $ocId > 0 ? (OrdenCompra::buscarPorId($ocId) ?? []) : [];
        $lockInfo = null;
        if ($ocId > 0) {
            $usuario = Auth::user() ?? [];
            $usuarioId = (int) ($usuario['id'] ?? 0);
            $lockInfo = EdicionLock::acquire('orden_compra', $ocId, $usuarioId, 600);
        }

        $this->render('procesos/almacen/orden-compra/index', [
            'titulo' => 'Orden de compra',
            'csrf' => Csrf::token(),
            'codigoCompra' => (string) ($ocSeleccionada['codigo_compra'] ?? OrdenCompra::generarCodigoVista()),
            'fechaActual' => (string) ($ocSeleccionada['fecha'] ?? date('Y-m-d')),
            'empleadoNombre' => (string) ($ocSeleccionada['empleado_nombre'] ?? $empleadoNombre),
            'empleadoId' => (int) ($ocSeleccionada['empleado_id'] ?? $empleadoId),
            'puedeCambiarEmpleado' => $puedeCambiarEmpleado,
            'proveedores' => Proveedor::listarParaModal(),
            'articulos' => OrdenCompra::listarArticulosComprables(),
            'ocRegistros' => $ocRegistros,
            'ocSeleccionada' => $ocSeleccionada,
            'lockInfo' => $lockInfo,
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
            $this->redirect('/procesos/almacen/orden-compra');
        }

        $proveedorId = (int) ($_POST['proveedor_id'] ?? 0);
        $detalleCodigos = $_POST['detalle_codigo'] ?? [];
        $detalleCantidades = $_POST['detalle_cantidad'] ?? [];
        $detalleArticuloIds = $_POST['detalle_articulo_id'] ?? [];

        if ($proveedorId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Orden de compra',
                'message' => 'Debes seleccionar un proveedor.',
            ];
            $this->redirect('/procesos/almacen/orden-compra');
        }

        if (!is_array($detalleCodigos) || !is_array($detalleCantidades) || count($detalleCodigos) === 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Orden de compra',
                'message' => 'Debes agregar al menos un articulo al detalle.',
            ];
            $this->redirect('/procesos/almacen/orden-compra');
        }

        $ocId = (int) ($_POST['oc_id'] ?? 0);
        $ocActual = $ocId > 0 ? OrdenCompra::buscarPorId($ocId) : null;
        $isEdit = is_array($ocActual) && (int) ($ocActual['id'] ?? 0) > 0;
        $usuario = Auth::user() ?? [];
        $usuarioId = (int) ($usuario['id'] ?? 0);
        if ($isEdit && !EdicionLock::isOwnerActive('orden_compra', $ocId, $usuarioId)) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Orden de compra',
                'message' => 'Este registro esta siendo editado por otro usuario o el bloqueo expiro. Recarga la pantalla.',
            ];
            $this->redirect('/procesos/almacen/orden-compra?id=' . $ocId);
        }
        if ($isEdit) {
            $codigo = (string) ($ocActual['codigo_compra'] ?? ($_POST['codigo_compra'] ?? ''));
            if ($codigo === '') {
                $codigo = OrdenCompra::generarCodigoVista();
            }
        } else {
            $codigo = '';
        }

        $detalleDescripcion = $_POST['detalle_descripcion'] ?? [];
        $detalleUnidades = $_POST['detalle_unidad'] ?? [];
        $detalleCantPorUnidad = $_POST['detalle_cant_por_unidad'] ?? [];
        $detallePesoPorUnidad = $_POST['detalle_peso_por_unidad'] ?? [];
        $detallePesoUnidad = $_POST['detalle_peso_unidad'] ?? [];
        $detalleCostos = $_POST['detalle_costo'] ?? [];
        $detalleDescs = $_POST['detalle_desc_pct'] ?? [];
        $detalleImpuestos = $_POST['detalle_impuesto_pct'] ?? [];
        $detalleTotales = $_POST['detalle_total'] ?? [];
        $detalles = [];
        foreach ($detalleCodigos as $idx => $cod) {
            $unidad = (string) ($detalleUnidades[$idx] ?? 'u');
            $cantPorUnidad = (float) ($detalleCantPorUnidad[$idx] ?? 0);
            $pesoPorUnidad = (float) ($detallePesoPorUnidad[$idx] ?? 0);
            $costo = (float) ($detalleCostos[$idx] ?? 0);
            if ($cantPorUnidad <= 0) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Orden de compra',
                    'message' => 'Cantidad x unidad es obligatoria en el detalle.',
                ];
                $this->redirect('/procesos/almacen/orden-compra');
            }
            if ($costo <= 0) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Orden de compra',
                    'message' => 'El costo debe ser mayor que cero.',
                ];
                $this->redirect('/procesos/almacen/orden-compra');
            }
            $articuloId = (int) ($detalleArticuloIds[$idx] ?? 0);
            if ($articuloId > 0) {
                $stmtCosto = Db::conexion()->prepare('SELECT costo_ultimo FROM articulos WHERE id = :id LIMIT 1');
                $stmtCosto->execute(['id' => $articuloId]);
                $rowCosto = $stmtCosto->fetch();
                $costoUltimo = (float) ($rowCosto['costo_ultimo'] ?? 0);
                if ($costoUltimo > 0 && $costo < $costoUltimo) {
                    $_SESSION['flash_toast'] = [
                        'type' => 'warning',
                        'title' => 'Orden de compra',
                        'message' => 'El costo no puede ser menor al ultimo costo registrado.',
                    ];
                    $this->redirect('/procesos/almacen/orden-compra');
                }
            }
            if ($unidad !== 'u' && $pesoPorUnidad <= 0) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Orden de compra',
                    'message' => 'Peso x unidad es obligatorio cuando la unidad no es "u".',
                ];
                $this->redirect('/procesos/almacen/orden-compra');
            }
            $detalles[] = [
                'articulo_id' => (int) ($detalleArticuloIds[$idx] ?? 0),
                'codigo' => (string) $cod,
                'descripcion' => (string) ($detalleDescripcion[$idx] ?? ''),
                'cantidad' => (string) ($detalleCantidades[$idx] ?? '0'),
                'unidad' => $unidad,
                'cant_por_unidad' => (string) ($detalleCantPorUnidad[$idx] ?? ''),
                'peso_por_unidad' => (string) ($detallePesoPorUnidad[$idx] ?? ''),
                'peso_unidad' => (string) ($detallePesoUnidad[$idx] ?? 'g'),
                'costo' => (string) ($detalleCostos[$idx] ?? '0'),
                'desc_pct' => (string) ($detalleDescs[$idx] ?? '0'),
                'impuesto_pct' => (string) ($detalleImpuestos[$idx] ?? '0'),
                'total' => (string) ($detalleTotales[$idx] ?? '0'),
            ];
        }

        $empleadoId = (int) ($usuario['empleado_id'] ?? 0);
        if ($empleadoId <= 0) {
            $empleadoId = (int) ($usuario['id'] ?? 0);
        }
        $empleadoNombre = (string) ($usuario['nombre'] ?? $usuario['username'] ?? 'Usuario');
        if (Auth::hasPermission('empleados.ver')) {
            $empleadoReqId = (int) ($_POST['empleado_id'] ?? 0);
            if ($empleadoReqId > 0) {
                $emp = Empleado::buscarPorId($empleadoReqId);
                if (is_array($emp)) {
                    $empleadoId = $empleadoReqId;
                    $nombre = trim((string) (($emp['nombre'] ?? '') . ' ' . ($emp['apellido'] ?? '')));
                    if ($nombre !== '') {
                        $empleadoNombre = $nombre;
                    }
                }
            }
        }

        $record = [
            'id' => $isEdit ? $ocId : null,
            'codigo_compra' => $codigo,
            'fecha' => (string) ($_POST['fecha'] ?? date('Y-m-d')),
            'empleado_id' => $empleadoId,
            'empleado_nombre' => $empleadoNombre,
            'proveedor_id' => $proveedorId,
            'proveedor_label' => (string) ($_POST['proveedor_label'] ?? ''),
            'proveedor_rnc' => (string) ($_POST['proveedor_rnc'] ?? ''),
            'condicion_pago' => (string) ($_POST['condicion_pago'] ?? ''),
            'comentario' => (string) ($_POST['comentario'] ?? ''),
            'subtotal' => (string) ($_POST['subtotal'] ?? '0'),
            'total_descuento' => (string) ($_POST['total_descuento'] ?? '0'),
            'descuento_general_pct' => (string) ($_POST['descuento_general_pct'] ?? '0'),
            'moneda' => (string) ($_POST['moneda'] ?? 'DOP'),
            'impuesto' => (string) ($_POST['impuesto'] ?? '0'),
            'total_compra' => (string) ($_POST['total_compra'] ?? '0'),
            'detalles' => $detalles,
            'estado' => $isEdit ? (string) ($ocActual['estado'] ?? 'abierta') : 'abierta',
            'created_by' => $isEdit ? (int) ($ocActual['created_by'] ?? 0) : (int) ($usuario['id'] ?? 0),
            'updated_by' => (int) ($usuario['id'] ?? 0),
        ];
        if (!$isEdit) {
            $record['secuencia_clave'] = 'oc';
        }
        try {
            $result = OrdenCompra::guardarOC($record, $detalles);
            $codigo = (string) ($result['codigo_compra'] ?? $codigo);
            if ($usuarioId > 0) {
                EdicionLock::releaseAllByUser($usuarioId);
            }

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Orden de compra',
                'message' => ($isEdit ? 'OC actualizada correctamente: ' : 'OC guardada correctamente: ') . $codigo,
            ];
            $this->redirect('/procesos/almacen/orden-compra');
        } catch (Throwable $e) {
            AuditLog::write('orden_compra.error', [
                'tipo_accion' => 'orden_compra_error_guardar',
                'apartado' => '/procesos/almacen/orden-compra',
                'descripcion' => 'Error guardando orden de compra',
                'orden_compra_id' => $ocId,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Orden de compra',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la orden de compra.',
            ];
            $this->redirect('/procesos/almacen/orden-compra');
        }
    }

    public function imprimir(): void
    {
        $ocId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($ocId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Orden de compra',
                'message' => 'Selecciona una orden de compra para imprimir.',
            ];
            $this->redirect('/procesos/almacen/orden-compra');
        }

        $oc = OrdenCompra::buscarPorId($ocId);
        if (!$oc) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Orden de compra',
                'message' => 'Orden de compra no valida.',
            ];
            $this->redirect('/procesos/almacen/orden-compra');
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
        $this->drawPdfHeader($pdf, $oc, $company, $y);

        $detalles = is_array($oc['detalles'] ?? null) ? $oc['detalles'] : [];

        $pdf->rect(30, $y - 12, 535, 20, 'F', 0.9);
        $pdf->text(36, $y, 'Detalle de articulos', 11, 'F2');
        $y += 22;

        $pdf->rect(30, $y - 11, 535, 16, 'F', 0.94);
        $pdf->text(34, $y, 'Codigo', 9, 'F2');
        $pdf->text(110, $y, 'Descripcion', 9, 'F2');
        $pdf->text(350, $y, 'Cant', 9, 'F2');
        $pdf->text(392, $y, 'Costo', 9, 'F2');
        $pdf->text(450, $y, 'Desc%', 9, 'F2');
        $pdf->text(495, $y, 'Imp%', 9, 'F2');
        $pdf->text(535, $y, 'Total', 9, 'F2');
        $y += 16;

        if ($detalles === []) {
            $pdf->text(34, $y, 'Sin articulos en esta orden de compra.', 9);
            $y += 16;
        } else {
            $rowIdx = 0;
            foreach ($detalles as $d) {
                if ($y > 760) {
                    $pdf->addPage();
                    $y = 34.0;
                    $this->drawPdfHeader($pdf, $oc, $company, $y, true);
                    $pdf->rect(30, $y - 12, 535, 20, 'F', 0.9);
                    $pdf->text(36, $y, 'Detalle de articulos (continuacion)', 11, 'F2');
                    $y += 22;
                    $pdf->rect(30, $y - 11, 535, 16, 'F', 0.94);
                    $pdf->text(34, $y, 'Codigo', 9, 'F2');
                    $pdf->text(110, $y, 'Descripcion', 9, 'F2');
                    $pdf->text(350, $y, 'Cant', 9, 'F2');
                    $pdf->text(392, $y, 'Costo', 9, 'F2');
                    $pdf->text(450, $y, 'Desc%', 9, 'F2');
                    $pdf->text(495, $y, 'Imp%', 9, 'F2');
                    $pdf->text(535, $y, 'Total', 9, 'F2');
                    $y += 16;
                }

                if ($rowIdx % 2 === 0) {
                    $pdf->rect(30, $y - 9, 535, 14, 'F', 0.985);
                }
                $codigo = $this->truncateForPdf((string) ($d['codigo'] ?? ''), 14);
                $descripcion = $this->truncateForPdf((string) ($d['descripcion'] ?? ''), 50);
                $cantidad = (string) ($d['cantidad'] ?? '0');
                $unidad = (string) ($d['unidad'] ?? 'u');
                $costo = number_format((float) ($d['costo'] ?? 0), 2, '.', ',');
                $descPct = number_format((float) ($d['desc_pct'] ?? 0), 2, '.', ',');
                $impPct = number_format((float) ($d['impuesto_pct'] ?? 0), 2, '.', ',');
                $total = number_format((float) ($d['total'] ?? 0), 2, '.', ',');

                $pdf->text(34, $y, $codigo, 9);
                $pdf->text(110, $y, $descripcion, 9);
                $pdf->text(350, $y, $cantidad . ' ' . $unidad, 9);
                $pdf->text(392, $y, $costo, 9);
                $pdf->text(450, $y, $descPct, 9);
                $pdf->text(495, $y, $impPct, 9);
                $pdf->text(535, $y, $total, 9);
                $pdf->line(30, $y + 4, 565, $y + 4, 0.3);
                $y += 14;
                $rowIdx++;
            }
        }

        if ($y > 760) {
            $pdf->addPage();
            $y = 34.0;
            $this->drawPdfHeader($pdf, $oc, $company, $y, true);
        }

        $y += 10;

        $filename = 'orden_compra_' . ($oc['codigo_compra'] ?? ('OC' . $ocId)) . '.pdf';
        $bin = $pdf->output($filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', (string) $filename) . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bin;
        exit;
    }

    /** @param array<string,mixed> $oc @param array<string,mixed> $company */
    private function drawPdfHeader(SimplePdf $pdf, array $oc, array $company, float &$y, bool $continuacion = false): void
    {
        $titulo = $continuacion ? 'Orden de Compra (continuacion)' : 'Orden de Compra';
        $codigo = trim((string) ($oc['codigo_compra'] ?? 'N/D'));
        $fecha = (string) ($oc['fecha'] ?? date('Y-m-d'));
        $proveedor = trim((string) ($oc['proveedor_label'] ?? 'N/D'));
        $rnc = trim((string) ($oc['proveedor_rnc'] ?? ''));
        $condicion = trim((string) ($oc['condicion_pago'] ?? ''));
        $empleado = trim((string) ($oc['empleado_nombre'] ?? ''));
        $estado = trim((string) ($oc['estado'] ?? ''));
        $moneda = trim((string) ($oc['moneda'] ?? 'DOP'));
        $comentario = trim((string) ($oc['comentario'] ?? ''));

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
        $pdf->text(300, $y, 'Moneda: ' . $moneda, 10, 'F2');
        $y += 14;

        $pdf->text(34, $y, 'Proveedor: ' . $this->truncateForPdf($proveedor, 70), 10);
        if ($rnc !== '') {
            $pdf->text(400, $y, 'RNC: ' . $this->truncateForPdf($rnc, 22), 10);
        }
        $y += 14;

        if ($condicion !== '') {
            $pdf->text(34, $y, 'Condicion pago: ' . $this->truncateForPdf($condicion, 70), 9);
            $y += 12;
        }
        if ($empleado !== '') {
            $pdf->text(34, $y, 'Empleado: ' . $this->truncateForPdf($empleado, 70), 9);
            $y += 12;
        }
        if ($estado !== '') {
            $pdf->text(34, $y, 'Estado: ' . $this->truncateForPdf($estado, 20), 9);
            $y += 12;
        }
        if ($comentario !== '') {
            $pdf->text(34, $y, 'Comentario: ' . $this->truncateForPdf($comentario, 90), 9);
            $y += 14;
        } else {
            $y += 6;
        }
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
