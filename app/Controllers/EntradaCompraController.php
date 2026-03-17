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
use App\Models\EntradaCompra;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Throwable;

final class EntradaCompraController extends Controller
{
    public function index(): void
    {
        $entradaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $ocId = isset($_GET['oc_id']) ? (int) $_GET['oc_id'] : 0;

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

        $entradaSeleccionada = $entradaId > 0 ? (EntradaCompra::buscarPorId($entradaId) ?? []) : [];
        $ocSeleccionada = $ocId > 0 ? (OrdenCompra::buscarPorId($ocId) ?? []) : [];
        if ($ocSeleccionada === [] && $entradaSeleccionada !== []) {
            $ocFromEntrada = (int) ($entradaSeleccionada['orden_compra_id'] ?? 0);
            if ($ocFromEntrada > 0) {
                $ocSeleccionada = OrdenCompra::buscarPorId($ocFromEntrada) ?? [];
            }
        }
        $detallesDesdeOc = $ocId > 0 ? EntradaCompra::prepararDetallesDesdeOc($ocId) : [];
        if ($ocId > 0 && $ocSeleccionada !== [] && $detallesDesdeOc === []) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Entrada de compra',
                'message' => 'La orden de compra no tiene articulos pendientes de entrada.',
            ];
        }

        $lockInfo = null;
        if ($entradaId > 0) {
            $usuarioId = (int) ($usuario['id'] ?? 0);
            $lockInfo = EdicionLock::acquire('entrada_compra', $entradaId, $usuarioId, 600);
        }

        $this->render('procesos/almacen/entradas/index', [
            'titulo' => 'Entrada de compra',
            'csrf' => Csrf::token(),
            'entradaSeleccionada' => $entradaSeleccionada,
            'ocSeleccionada' => $ocSeleccionada,
            'detallesDesdeOc' => $detallesDesdeOc,
            'empleadoNombre' => $empleadoNombre,
            'empleadoId' => $empleadoId,
            'puedeCambiarEmpleado' => $puedeCambiarEmpleado,
            'proveedores' => Proveedor::listarParaModal(),
            'articulos' => OrdenCompra::listarArticulosComprables(),
            'ocRegistros' => OrdenCompra::listarRegistros(),
            'entradaRegistros' => EntradaCompra::listarRegistros(),
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
            $this->redirect('/procesos/almacen/entradas');
        }

        $proveedorId = (int) ($_POST['proveedor_id'] ?? 0);
        $detalleCodigos = $_POST['detalle_codigo'] ?? [];
        $detalleCantidades = $_POST['detalle_cantidad'] ?? [];
        $detalleArticuloIds = $_POST['detalle_articulo_id'] ?? [];
        $detalleOcDetalleIds = $_POST['detalle_oc_detalle_id'] ?? [];
        $ocId = (int) ($_POST['oc_id'] ?? 0);

        if ($proveedorId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Entrada de compra',
                'message' => 'Debes seleccionar un proveedor.',
            ];
            $this->redirect('/procesos/almacen/entradas');
        }

        if (!is_array($detalleCodigos) || !is_array($detalleCantidades) || count($detalleCodigos) === 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Entrada de compra',
                'message' => 'Debes agregar al menos un articulo al detalle.',
            ];
            $this->redirect('/procesos/almacen/entradas');
        }

        if ($ocId > 0) {
            $ocRef = OrdenCompra::buscarPorId($ocId);
            if (!$ocRef) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Entrada de compra',
                    'message' => 'La orden de compra seleccionada no es valida.',
                ];
                $this->redirect('/procesos/almacen/entradas');
            }
            if ((int) ($ocRef['proveedor_id'] ?? 0) !== $proveedorId) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Entrada de compra',
                    'message' => 'El proveedor no coincide con la orden de compra seleccionada.',
                ];
                $this->redirect('/procesos/almacen/entradas?oc_id=' . $ocId);
            }
        }

        $entradaId = (int) ($_POST['entrada_id'] ?? 0);
        $entradaActual = $entradaId > 0 ? EntradaCompra::buscarPorId($entradaId) : null;
        $isEdit = is_array($entradaActual) && (int) ($entradaActual['id'] ?? 0) > 0;

        $usuario = Auth::user() ?? [];
        $usuarioId = (int) ($usuario['id'] ?? 0);
        if ($isEdit && !EdicionLock::isOwnerActive('entrada_compra', $entradaId, $usuarioId)) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Entrada de compra',
                'message' => 'Este registro esta siendo editado por otro usuario o el bloqueo expiro. Recarga la pantalla.',
            ];
            $this->redirect('/procesos/almacen/entradas?id=' . $entradaId);
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
            $articuloId = (int) ($detalleArticuloIds[$idx] ?? 0);
            $ocDetalleId = (int) ($detalleOcDetalleIds[$idx] ?? 0);
            if ($articuloId <= 0) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Entrada de compra',
                    'message' => 'Articulo invalido en el detalle.',
                ];
                $this->redirect('/procesos/almacen/entradas');
            }
            if ($ocId > 0 && $ocDetalleId > 0) {
                $stmtDet = Db::conexion()->prepare('SELECT id, orden_compra_id, articulo_id
                                                    FROM ordenes_compra_detalles
                                                    WHERE id = :id
                                                    LIMIT 1');
                $stmtDet->execute(['id' => $ocDetalleId]);
                $rowDet = $stmtDet->fetch();
                if (!$rowDet || (int) ($rowDet['orden_compra_id'] ?? 0) !== $ocId) {
                    $_SESSION['flash_toast'] = [
                        'type' => 'warning',
                        'title' => 'Entrada de compra',
                        'message' => 'El detalle no pertenece a la orden de compra seleccionada.',
                    ];
                    $this->redirect('/procesos/almacen/entradas?oc_id=' . $ocId);
                }
                if ((int) ($rowDet['articulo_id'] ?? 0) !== $articuloId) {
                    $_SESSION['flash_toast'] = [
                        'type' => 'warning',
                        'title' => 'Entrada de compra',
                        'message' => 'El articulo no coincide con el detalle de la OC.',
                    ];
                    $this->redirect('/procesos/almacen/entradas?oc_id=' . $ocId);
                }
            }
            if ($cantPorUnidad <= 0) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Entrada de compra',
                    'message' => 'Cantidad x unidad es obligatoria en el detalle.',
                ];
                $this->redirect('/procesos/almacen/entradas');
            }
            if ($costo <= 0) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Entrada de compra',
                    'message' => 'El costo debe ser mayor que cero.',
                ];
                $this->redirect('/procesos/almacen/entradas');
            }
            if ($articuloId > 0) {
                $stmtCosto = Db::conexion()->prepare('SELECT costo_ultimo FROM articulos WHERE id = :id LIMIT 1');
                $stmtCosto->execute(['id' => $articuloId]);
                $rowCosto = $stmtCosto->fetch();
                $costoUltimo = (float) ($rowCosto['costo_ultimo'] ?? 0);
                if ($costoUltimo > 0 && $costo < $costoUltimo) {
                    $_SESSION['flash_toast'] = [
                        'type' => 'warning',
                        'title' => 'Entrada de compra',
                        'message' => 'El costo no puede ser menor al ultimo costo registrado.',
                    ];
                    $this->redirect('/procesos/almacen/entradas');
                }
            }
            if ($unidad === 'u' && $pesoPorUnidad > 0) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Entrada de compra',
                    'message' => 'No debes indicar peso x unidad cuando la unidad es "u".',
                ];
                $this->redirect('/procesos/almacen/entradas');
            }
            if ($unidad !== 'u' && $pesoPorUnidad <= 0) {
                $_SESSION['flash_toast'] = [
                    'type' => 'warning',
                    'title' => 'Entrada de compra',
                    'message' => 'Peso x unidad es obligatorio cuando la unidad no es "u".',
                ];
                $this->redirect('/procesos/almacen/entradas');
            }
            $detalles[] = [
                'oc_detalle_id' => (int) ($detalleOcDetalleIds[$idx] ?? 0),
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

        $estado = (string) ($_POST['estado'] ?? ($entradaActual['estado'] ?? 'abierta'));
        if (!in_array($estado, ['abierta', 'cerrada'], true)) {
            $estado = 'abierta';
        }

        $record = [
            'id' => $isEdit ? $entradaId : null,
            'codigo_entrada' => (string) ($entradaActual['codigo_entrada'] ?? ''),
            'fecha' => (string) ($_POST['fecha'] ?? date('Y-m-d')),
            'empleado_id' => $empleadoId,
            'empleado_nombre' => $empleadoNombre,
            'proveedor_id' => $proveedorId,
            'proveedor_label' => (string) ($_POST['proveedor_label'] ?? ''),
            'proveedor_rnc' => (string) ($_POST['proveedor_rnc'] ?? ''),
            'condicion_pago' => (string) ($_POST['condicion_pago'] ?? ''),
            'ncf' => (string) ($_POST['ncf'] ?? ''),
            'orden_no' => (string) ($_POST['orden_no'] ?? ''),
            'factura_no' => (string) ($_POST['factura_no'] ?? ''),
            'pedido_no' => (string) ($_POST['pedido_no'] ?? ''),
            'comentario' => (string) ($_POST['comentario'] ?? ''),
            'subtotal' => (string) ($_POST['subtotal'] ?? '0'),
            'total_descuento' => (string) ($_POST['total_descuento'] ?? '0'),
            'descuento_general_pct' => (string) ($_POST['descuento_general_pct'] ?? '0'),
            'impuesto' => (string) ($_POST['impuesto'] ?? '0'),
            'total_compra' => (string) ($_POST['total_compra'] ?? '0'),
            'moneda' => (string) ($_POST['moneda'] ?? 'DOP'),
            'estado' => $estado,
            'orden_compra_id' => $ocId,
            'created_by' => $isEdit ? (int) ($entradaActual['created_by'] ?? 0) : $usuarioId,
            'updated_by' => $usuarioId,
        ];
        if (!$isEdit) {
            $record['secuencia_clave'] = 'ec';
        }

        try {
            $result = EntradaCompra::guardarEntrada($record, $detalles);
            $codigo = (string) ($result['codigo_entrada'] ?? $record['codigo_entrada'] ?? '');
            EdicionLock::releaseAllByUser($usuarioId);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Entrada de compra',
                'message' => ($isEdit ? 'Entrada actualizada correctamente: ' : 'Entrada guardada correctamente: ') . $codigo,
            ];
            $this->redirect('/procesos/almacen/entradas');
        } catch (Throwable $e) {
            AuditLog::write('entrada_compra.error', [
                'tipo_accion' => 'entrada_compra_error_guardar',
                'apartado' => '/procesos/almacen/entradas',
                'descripcion' => 'Error guardando entrada de compra',
                'entrada_compra_id' => $entradaId,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Entrada de compra',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la entrada de compra.',
            ];
            $this->redirect('/procesos/almacen/entradas');
        }
    }

    public function imprimir(): void
    {
        $entradaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($entradaId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Entrada de compra',
                'message' => 'Selecciona una entrada para imprimir.',
            ];
            $this->redirect('/procesos/almacen/entradas');
        }

        $entrada = EntradaCompra::buscarPorId($entradaId);
        if (!$entrada) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Entrada de compra',
                'message' => 'Entrada no valida.',
            ];
            $this->redirect('/procesos/almacen/entradas');
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
        $this->drawPdfHeader($pdf, $entrada, $company, $y);

        $detalles = is_array($entrada['detalles'] ?? null) ? $entrada['detalles'] : [];

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
            $pdf->text(34, $y, 'Sin articulos en esta entrada.', 9);
            $y += 16;
        } else {
            $rowIdx = 0;
            foreach ($detalles as $d) {
                if ($y > 760) {
                    $pdf->addPage();
                    $y = 34.0;
                    $this->drawPdfHeader($pdf, $entrada, $company, $y, true);
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
            $this->drawPdfHeader($pdf, $entrada, $company, $y, true);
        }

        $y += 10;

        $filename = 'entrada_compra_' . ($entrada['codigo_entrada'] ?? ('EC' . $entradaId)) . '.pdf';
        $bin = $pdf->output($filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', (string) $filename) . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bin;
        exit;
    }

    /** @param array<string,mixed> $entrada @param array<string,mixed> $company */
    private function drawPdfHeader(SimplePdf $pdf, array $entrada, array $company, float &$y, bool $continuacion = false): void
    {
        $titulo = $continuacion ? 'Entrada de Compra (continuacion)' : 'Entrada de Compra';
        $codigo = trim((string) ($entrada['codigo_entrada'] ?? 'N/D'));
        $fecha = (string) ($entrada['fecha'] ?? date('Y-m-d'));
        $proveedor = trim((string) ($entrada['proveedor_label'] ?? 'N/D'));
        $rnc = trim((string) ($entrada['proveedor_rnc'] ?? ''));
        $condicion = trim((string) ($entrada['condicion_pago'] ?? ''));
        $empleado = trim((string) ($entrada['empleado_nombre'] ?? ''));
        $estado = trim((string) ($entrada['estado'] ?? ''));
        $moneda = trim((string) ($entrada['moneda'] ?? 'DOP'));
        $comentario = trim((string) ($entrada['comentario'] ?? ''));
        $ncf = trim((string) ($entrada['ncf'] ?? ''));
        $ordenNo = trim((string) ($entrada['orden_no'] ?? ''));
        $facturaNo = trim((string) ($entrada['factura_no'] ?? ''));
        $pedidoNo = trim((string) ($entrada['pedido_no'] ?? ''));

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
        if ($ncf !== '') {
            $pdf->text(34, $y, 'NCF: ' . $this->truncateForPdf($ncf, 40), 9);
            $y += 12;
        }
        if ($ordenNo !== '' || $facturaNo !== '' || $pedidoNo !== '') {
            $line = trim(
                ($ordenNo !== '' ? 'Orden: ' . $ordenNo : '') .
                ($facturaNo !== '' ? ' | Factura: ' . $facturaNo : '') .
                ($pedidoNo !== '' ? ' | Pedido: ' . $pedidoNo : '')
            );
            if ($line !== '') {
                $pdf->text(34, $y, $this->truncateForPdf($line, 90), 9);
                $y += 12;
            }
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
