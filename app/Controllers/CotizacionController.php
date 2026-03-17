<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\SimplePdf;
use App\Core\Settings;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Localidad;
use App\Models\Secuencia;

final class CotizacionController extends Controller
{
    public function index(): void
    {
        Secuencia::ensureExists('ct', 'Cotizacion', 'CT', 5, 1);
        $cotizacionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $cotizacion = $cotizacionId > 0 ? (Cotizacion::buscarPorId($cotizacionId) ?: []) : [];
        $clienteMap = [];
        foreach (Cliente::listarParaModal() as $c) {
            $clienteMap[(int) ($c['id'] ?? 0)] = [
                'id' => (int) ($c['id'] ?? 0),
                'razon_social' => (string) ($c['razon_social'] ?? ''),
                'rnc' => (string) ($c['rnc'] ?? ''),
                'telefono' => (string) ($c['telefono_cliente'] ?? ''),
                'descuento_default' => (float) ($c['descuento_default'] ?? 0),
                'aplica_itbis' => (int) ($c['aplica_itbis'] ?? 0),
                'exento_itbis' => (int) ($c['exento_itbis'] ?? 0),
                'estado' => (string) ($c['estado'] ?? ''),
            ];
        }

        $canCrear = Auth::hasPermission('cotizaciones.crear');
        $canEditar = Auth::hasPermission('cotizaciones.editar');
        $canEditarPrecio = Auth::hasPermission('cotizaciones.precio.editar');
        $canVer = Auth::hasPermission('cotizaciones.ver') || $canCrear || $canEditar;
        $usuario = Auth::user() ?? [];
        $localidadesMap = [];
        foreach (Localidad::listar() as $l) {
            $cid = (int) ($l['cliente_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            if (!isset($localidadesMap[$cid])) {
                $localidadesMap[$cid] = [];
            }
            $localidadesMap[$cid][] = [
                'id' => (int) ($l['id'] ?? 0),
                'nombre' => (string) ($l['nombre_localidad'] ?? ''),
                'referencia' => (string) ($l['referencia'] ?? ''),
            ];
        }

        $this->render('procesos/clientes/cotizaciones/index', [
            'titulo' => 'Cotizaciones',
            'csrf' => Csrf::token(),
            'cotizacion' => $cotizacion,
            'clienteMap' => $clienteMap,
            'variantes' => Cotizacion::listarVariantesConPrecio(),
            'cotizaciones' => Cotizacion::listarRegistros(),
            'empleadoNombre' => (string) ($usuario['nombre'] ?? $usuario['username'] ?? ''),
            'localidadesMap' => $localidadesMap,
            'canCrear' => $canCrear,
            'canEditar' => $canEditar,
            'canEditarPrecio' => $canEditarPrecio,
            'canVer' => $canVer,
        ]);
    }

    public function guardar(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Cotizaciones',
                'message' => 'Token CSRF invalido.',
            ];
            $this->redirect('/procesos/clientes/cotizaciones');
        }

        try {
            $cotizacionId = (int) ($_POST['cotizacion_id'] ?? 0);
            $isEdit = $cotizacionId > 0;
            $canEditarPrecio = Auth::hasPermission('cotizaciones.precio.editar');
            if ($isEdit) {
                if (!Auth::hasPermission('cotizaciones.editar')) {
                    throw new \RuntimeException('No tienes permiso para editar cotizaciones.');
                }
            } else {
                if (!Auth::hasPermission('cotizaciones.crear')) {
                    throw new \RuntimeException('No tienes permiso para crear cotizaciones.');
                }
            }
            $usuario = Auth::user() ?? [];
            $userId = (int) ($usuario['id'] ?? 0);

            $clienteId = (int) ($_POST['cliente_id'] ?? 0);
            $cliente = $clienteId > 0 ? Cliente::buscarPorId($clienteId) : null;
            if (!$cliente) {
                throw new \RuntimeException('Cliente no valido.');
            }
            if ((string) ($cliente['estado'] ?? '') !== 'activo') {
                throw new \RuntimeException('El cliente esta inactivo.');
            }
            $clienteNombre = (string) ($cliente['razon_social'] ?? $cliente['nombre_comercial'] ?? '');
            $clienteTelefono = (string) ($cliente['telefono_cliente'] ?? '');
            $clienteRnc = (string) ($cliente['rnc'] ?? '');
            if ($clienteRnc === '') {
                $clienteRnc = (string) ($cliente['cedula'] ?? '');
            }
            $clienteAplicaItbis = (int) ($cliente['aplica_itbis'] ?? 0);
            $clienteExentoItbis = (int) ($cliente['exento_itbis'] ?? 0);
            $clienteItbisActivo = $clienteAplicaItbis === 1 && $clienteExentoItbis === 0;
            $localidadId = (int) ($_POST['localidad_id'] ?? 0);
            $localidadesCliente = Localidad::listarPorCliente($clienteId);
            if ($localidadesCliente !== []) {
                $valid = false;
                $localidadNombre = '';
                foreach ($localidadesCliente as $loc) {
                    if ((int) ($loc['id'] ?? 0) === $localidadId) {
                        $valid = true;
                        $localidadNombre = (string) ($loc['nombre_localidad'] ?? '');
                        break;
                    }
                }
                if (!$valid) {
                    throw new \RuntimeException('Debes seleccionar una localidad del cliente.');
                }
            } else {
                $localidadId = 0;
                $localidadNombre = '';
            }

            $detalles = [];
            $articuloIds = $_POST['detalle_articulo_id'] ?? [];
            $presentaciones = $_POST['detalle_presentacion_id'] ?? [];
            $empaques = $_POST['detalle_empaque_id'] ?? [];
            $cantidades = $_POST['detalle_cantidad'] ?? [];
            $precios = $_POST['detalle_precio'] ?? [];
            $descuentos = $_POST['detalle_descuento_pct'] ?? [];
            $impuestosDetalle = $_POST['detalle_impuesto_pct'] ?? [];
            $codigos = $_POST['detalle_codigo'] ?? [];
            $descs = $_POST['detalle_descripcion'] ?? [];
            $presentDesc = $_POST['detalle_presentacion_desc'] ?? [];
            $empaqueDesc = $_POST['detalle_empaque_desc'] ?? [];

            if (!is_array($articuloIds) || count($articuloIds) === 0) {
                throw new \RuntimeException('Debes agregar al menos un articulo a la cotizacion.');
            }

            foreach ($articuloIds as $idx => $aidRaw) {
                $aid = (int) $aidRaw;
                if ($aid <= 0) {
                    continue;
                }
                $precio = $precios[$idx] ?? null;
                if (!$canEditarPrecio) {
                    $precioDb = Cotizacion::precioVentaPorVariante(
                        $aid,
                        (int) ($presentaciones[$idx] ?? 0),
                        (int) ($empaques[$idx] ?? 0)
                    );
                    if ($precioDb === null) {
                        throw new \RuntimeException('No se pudo validar el precio de la cotizacion.');
                    }
                    $precio = $precioDb;
                } else {
                    $precioDb = Cotizacion::precioVentaPorVariante(
                        $aid,
                        (int) ($presentaciones[$idx] ?? 0),
                        (int) ($empaques[$idx] ?? 0)
                    );
                    if ($precioDb !== null && is_numeric($precio)) {
                        $diff = abs((float) $precio - (float) $precioDb);
                        if ($diff >= 0.01) {
                            AuditLog::write('cotizaciones.precio.override', [
                                'tipo_accion' => 'cotizacion_precio_editar',
                                'apartado' => '/procesos/clientes/cotizaciones',
                                'descripcion' => 'Precio editado manualmente en cotizacion',
                                'user_id' => $userId,
                                'articulo_id' => $aid,
                                'presentacion_id' => (int) ($presentaciones[$idx] ?? 0),
                                'empaque_id' => (int) ($empaques[$idx] ?? 0),
                                'precio_base' => $precioDb,
                                'precio_nuevo' => (float) $precio,
                            ]);
                        }
                    }
                }
                $detalles[] = [
                    'articulo_id' => $aid,
                    'presentacion_id' => (int) ($presentaciones[$idx] ?? 0),
                    'empaque_id' => (int) ($empaques[$idx] ?? 0),
                    'cantidad' => $cantidades[$idx] ?? null,
                    'precio' => $precio,
                    'descuento_pct' => $descuentos[$idx] ?? 0,
                    'impuesto_pct' => $clienteItbisActivo ? ($impuestosDetalle[$idx] ?? 0) : 0,
                    'articulo_codigo' => (string) ($codigos[$idx] ?? ''),
                    'articulo_descripcion' => (string) ($descs[$idx] ?? ''),
                    'presentacion_descripcion' => (string) ($presentDesc[$idx] ?? ''),
                    'empaque_descripcion' => (string) ($empaqueDesc[$idx] ?? ''),
                ];
            }

            $record = [
                'id' => $cotizacionId,
                'fecha' => (string) ($_POST['fecha'] ?? date('Y-m-d')),
                'validez_dias' => (int) ($_POST['validez_dias'] ?? 0),
                'fecha_vencimiento' => (string) ($_POST['fecha_vencimiento'] ?? ''),
                'estado' => (string) ($_POST['estado'] ?? 'borrador'),
                'moneda' => (string) ($_POST['moneda'] ?? 'DOP'),
                'descuento_general_pct' => (float) ($_POST['descuento_general_pct'] ?? 0),
                'impuesto_pct' => (float) ($_POST['impuesto_pct'] ?? 0),
                'cliente_id' => $clienteId,
                'cliente_nombre' => $clienteNombre,
                'cliente_telefono' => $clienteTelefono,
                'cliente_rnc' => $clienteRnc,
                'localidad_id' => $localidadId,
                'localidad_nombre' => $localidadNombre ?? '',
                'empleado_id' => (int) ($usuario['empleado_id'] ?? 0),
                'empleado_nombre' => (string) ($usuario['nombre'] ?? $usuario['username'] ?? ''),
                'comentario' => (string) ($_POST['comentario'] ?? ''),
                'condiciones' => (string) ($_POST['condiciones'] ?? ''),
                'cliente_aplica_itbis' => $clienteAplicaItbis,
                'cliente_exento_itbis' => $clienteExentoItbis,
                'created_by' => $userId,
                'updated_by' => $userId,
                'secuencia_clave' => 'ct',
            ];

            $res = $isEdit ? Cotizacion::actualizarCotizacion($record, $detalles) : Cotizacion::guardarCotizacion($record, $detalles);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Cotizaciones',
                'message' => ($isEdit ? 'Cotizacion actualizada correctamente: ' : 'Cotizacion guardada correctamente: ') . ($res['codigo_cotizacion'] ?? ''),
            ];
            $this->redirect('/procesos/clientes/cotizaciones');
        } catch (\Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Cotizaciones',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la cotizacion.',
            ];
            $this->redirect('/procesos/clientes/cotizaciones');
        }
    }

    public function detalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'Cotizacion no valida.']);
            return;
        }
        $cotizacion = Cotizacion::buscarPorId($id);
        if (!$cotizacion) {
            $this->json(['ok' => false, 'message' => 'Cotizacion no encontrada.']);
            return;
        }
        $this->json(['ok' => true, 'cotizacion' => $cotizacion]);
    }

    public function eliminar(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Cotizaciones',
                'message' => 'Token CSRF invalido.',
            ];
            $this->redirect('/procesos/clientes/cotizaciones');
        }
        if (!Auth::hasPermission('cotizaciones.editar')) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Cotizaciones',
                'message' => 'No tienes permiso para eliminar cotizaciones.',
            ];
            $this->redirect('/procesos/clientes/cotizaciones');
        }
        $cotizacionId = (int) ($_POST['cotizacion_id'] ?? 0);
        if ($cotizacionId > 0) {
            Cotizacion::eliminar($cotizacionId);
            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Cotizaciones',
                'message' => 'Cotizacion eliminada correctamente.',
            ];
        }
        $this->redirect('/procesos/clientes/cotizaciones');
    }

    public function imprimir(): void
    {
        $cotizacionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($cotizacionId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Cotizaciones',
                'message' => 'Selecciona una cotizacion para imprimir.',
            ];
            $this->redirect('/procesos/clientes/cotizaciones');
        }

        $cotizacion = Cotizacion::buscarPorId($cotizacionId);
        if (!$cotizacion) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Cotizaciones',
                'message' => 'Cotizacion no valida.',
            ];
            $this->redirect('/procesos/clientes/cotizaciones');
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

        $titulo = 'Cotizacion';
        $pdf->rect(30, $y - 14, 535, 44, 'F', 0.96);
        $pdf->line(30, $y + 31, 565, $y + 31, 1.0);
        $pdf->text(34, $y + 2, (string) ($company['company_name'] ?? 'FERLON'), 13, 'F2');
        $line2 = trim((string) ($company['company_phone'] ?? '') . ' | ' . (string) ($company['company_mail'] ?? ''));
        $pdf->text(34, $y + 14, $this->truncateForPdf($line2, 84), 9);
        $pdf->text(395, $y + 4, $titulo, 11, 'F2');
        $pdf->text(395, $y + 17, 'Fecha: ' . (string) ($cotizacion['fecha'] ?? ''), 9);
        $y += 48;

        $pdf->rect(30, $y - 11, 535, 18, 'F', 0.98);
        $pdf->text(34, $y, 'Codigo: ' . (string) ($cotizacion['codigo_cotizacion'] ?? ''), 10, 'F2');
        $pdf->text(300, $y, 'Total: ' . number_format((float) ($cotizacion['total'] ?? 0), 2, '.', ','), 10, 'F2');
        $y += 14;

        $pdf->text(34, $y, 'Cliente: ' . $this->truncateForPdf((string) ($cotizacion['cliente_nombre'] ?? ''), 70), 9);
        $y += 12;
        if (!empty($cotizacion['cliente_rnc'])) {
            $pdf->text(34, $y, 'RNC/Cedula: ' . $this->truncateForPdf((string) ($cotizacion['cliente_rnc'] ?? ''), 70), 9);
            $y += 12;
        }
        if (!empty($cotizacion['localidad_nombre'])) {
            $pdf->text(34, $y, 'Localidad: ' . $this->truncateForPdf((string) ($cotizacion['localidad_nombre'] ?? ''), 70), 9);
            $y += 12;
        }
        if (!empty($cotizacion['moneda'])) {
            $pdf->text(34, $y, 'Moneda: ' . $this->truncateForPdf((string) ($cotizacion['moneda'] ?? ''), 20), 9);
            $y += 12;
        }

        $detalles = is_array($cotizacion['detalles'] ?? null) ? $cotizacion['detalles'] : [];
        $pdf->rect(30, $y - 12, 535, 20, 'F', 0.9);
        $pdf->text(36, $y, 'Detalle', 11, 'F2');
        $y += 22;
        $pdf->rect(30, $y - 11, 535, 16, 'F', 0.94);
        $pdf->text(34, $y, 'Articulo', 9, 'F2');
        $pdf->text(300, $y, 'Cant', 9, 'F2');
        $pdf->text(350, $y, 'Precio', 9, 'F2');
        $pdf->text(410, $y, 'Desc%', 9, 'F2');
        $pdf->text(470, $y, 'Total', 9, 'F2');
        $y += 16;
        foreach ($detalles as $d) {
            if ($y > 760) {
                $pdf->addPage();
                $y = 34.0;
            }
            $art = $this->truncateForPdf((string) ($d['articulo_codigo'] ?? ''), 20);
            $pdf->text(34, $y, $art, 9);
            $pdf->text(300, $y, (string) ($d['cantidad'] ?? 0), 9);
            $pdf->text(350, $y, number_format((float) ($d['precio'] ?? 0), 2, '.', ','), 9);
            $pdf->text(410, $y, number_format((float) ($d['descuento_pct'] ?? 0), 2, '.', ','), 9);
            $pdf->text(470, $y, number_format((float) ($d['total'] ?? 0), 2, '.', ','), 9);
            $y += 12;
        }

        $cliente = Cliente::buscarPorId((int) ($cotizacion['cliente_id'] ?? 0)) ?: [];
        $clienteAplicaItbis = (int) ($cliente['aplica_itbis'] ?? 0) === 1;
        $clienteExentoItbis = (int) ($cliente['exento_itbis'] ?? 0) === 1;
        $aplicaItbis = $clienteAplicaItbis && !$clienteExentoItbis;

        $subtotal = 0.0;
        $descLineas = 0.0;
        $lineNetos = [];
        foreach ($detalles as $idx => $d) {
            $cant = (float) ($d['cantidad'] ?? 0);
            $precio = (float) ($d['precio'] ?? 0);
            $bruto = $cant * $precio;
            $descPct = (float) ($d['descuento_pct'] ?? 0);
            $descMonto = $bruto * ($descPct / 100);
            $subtotal += $bruto;
            $descLineas += $descMonto;
            $lineNetos[$idx] = max(0.0, $bruto - $descMonto);
        }
        $subtotalNeto = $subtotal - $descLineas;
        $descGenPct = (float) ($cotizacion['descuento_general_pct'] ?? 0);
        $descGenMonto = $subtotalNeto * ($descGenPct / 100);
        $baseImp = $subtotalNeto - $descGenMonto;
        $impMonto = 0.0;
        $totalBase = array_sum($lineNetos);
        foreach ($detalles as $idx => $d) {
            $lineNeto = $lineNetos[$idx] ?? 0.0;
            if (!$aplicaItbis || $lineNeto <= 0 || $totalBase <= 0) {
                continue;
            }
            $proporcion = $lineNeto / $totalBase;
            $lineaBase = max(0.0, $lineNeto - ($descGenMonto * $proporcion));
            $txtImp = strtoupper(trim((string) ($d['impuestos'] ?? '')));
            $impPct = str_contains($txtImp, 'ITBIS') ? 18.0 : 0.0;
            $impMonto += $lineaBase * ($impPct / 100);
        }
        $total = $baseImp + $impMonto;

        if ($y > 720) {
            $pdf->addPage();
            $y = 34.0;
        }
        $y += 8;
        $pdf->rect(30, $y - 10, 535, 16, 'F', 0.96);
        $pdf->text(34, $y, 'Resumen de Totales', 10, 'F2');
        $y += 18;
        $pdf->text(360, $y, 'Subtotal:', 9);
        $pdf->text(470, $y, number_format($subtotal, 2, '.', ','), 9, 'F2');
        $y += 12;
        $pdf->text(360, $y, 'Desc. lineas:', 9);
        $pdf->text(470, $y, number_format($descLineas, 2, '.', ','), 9);
        $y += 12;
        $pdf->text(360, $y, 'Desc. general (' . number_format($descGenPct, 2, '.', ',') . '%):', 9);
        $pdf->text(470, $y, number_format($descGenMonto, 2, '.', ','), 9);
        $y += 12;
        $pdf->text(360, $y, 'Impuesto:', 9);
        $pdf->text(470, $y, number_format($impMonto, 2, '.', ','), 9);
        $y += 12;
        $pdf->text(360, $y, 'Total:', 10, 'F2');
        $pdf->text(470, $y, number_format($total, 2, '.', ','), 10, 'F2');

        $filename = 'cotizacion_' . ($cotizacion['codigo_cotizacion'] ?? ('CT' . $cotizacionId)) . '.pdf';
        $bin = $pdf->output($filename);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', (string) $filename) . '"');
        header('Content-Length: ' . strlen($bin));
        echo $bin;
        exit;
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
