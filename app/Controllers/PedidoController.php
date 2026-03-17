<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Cliente;
use App\Models\Localidad;
use App\Models\Pedido;
use App\Models\Secuencia;

final class PedidoController extends Controller
{
    public function lista(): void
    {
        $canCrear = Auth::hasPermission('pedidos.crear');
        $canEditar = Auth::hasPermission('pedidos.editar');
        $canVer = Auth::hasPermission('pedidos.ver') || $canCrear || $canEditar;

        $this->render('procesos/almacen/pedidos/lista', [
            'titulo' => 'Lista de pedidos',
            'csrf' => Csrf::token(),
            'pedidosEnProceso' => Pedido::listarEnProceso(),
            'canCrear' => $canCrear,
            'canEditar' => $canEditar,
            'canVer' => $canVer,
        ]);
    }

    public function gestionDetalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'Pedido no valido.']);
            return;
        }

        $data = Pedido::obtenerDatosGestion($id);
        if (!$data) {
            $this->json(['ok' => false, 'message' => 'Pedido no encontrado.']);
            return;
        }

        $this->json(['ok' => true, 'pedido' => $data]);
    }

    public function marcarVisto(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token CSRF invalido.']);
            return;
        }

        $id = (int) ($_POST['pedido_id'] ?? 0);
        $departamento = trim((string) ($_POST['departamento'] ?? ''));
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'Pedido no valido.']);
            return;
        }

        $usuario = Auth::user() ?? [];
        $userId = (int) ($usuario['id'] ?? 0);
        $updated = Pedido::marcarVisto($id, $departamento, $userId, (string) ($usuario['nombre'] ?? $usuario['username'] ?? ''));

        $this->json(['ok' => true, 'visto' => $updated ? 1 : 0]);
    }

    public function gestionar(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token CSRF invalido.']);
            return;
        }

        $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
        $accion = trim((string) ($_POST['accion'] ?? ''));
        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        $cantidadesRaw = trim((string) ($_POST['cantidades'] ?? '[]'));
        $cantidades = json_decode($cantidadesRaw, true);
        if (!is_array($cantidades)) {
            $cantidades = [];
        }

        $usuario = Auth::user() ?? [];
        $userId = (int) ($usuario['id'] ?? 0);
        $userName = (string) ($usuario['nombre'] ?? $usuario['username'] ?? 'Usuario');

        try {
            $res = Pedido::guardarGestion($pedidoId, $accion, $comentario, $cantidades, $userId, $userName);
            $this->json([
                'ok' => true,
                'message' => 'Gestion guardada correctamente.',
                'pedido' => $res,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'ok' => false,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la gestion del pedido.',
            ]);
        }
    }

    public function index(): void
    {
        Secuencia::ensureExists('pd', 'Pedido', 'PD', 5, 1);
        $pedidoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $pedido = $pedidoId > 0 ? (Pedido::buscarPorId($pedidoId) ?: []) : [];
        $clienteMap = [];
        foreach (Cliente::listarParaModal() as $c) {
            $clienteMap[(int) ($c['id'] ?? 0)] = [
                'id' => (int) ($c['id'] ?? 0),
                'razon_social' => (string) ($c['razon_social'] ?? ''),
                'rnc' => (string) ($c['rnc'] ?? ''),
                'telefono' => (string) ($c['telefono_cliente'] ?? ''),
            ];
        }

        $canCrear = Auth::hasPermission('pedidos.crear');
        $canEditar = Auth::hasPermission('pedidos.editar');
        $canVer = Auth::hasPermission('pedidos.ver') || $canCrear || $canEditar;
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

        $this->render('procesos/almacen/pedidos/index', [
            'titulo' => 'Pedidos',
            'csrf' => Csrf::token(),
            'pedido' => $pedido,
            'clienteMap' => $clienteMap,
            'variantes' => Pedido::listarVariantesConPrecio(),
            'pedidos' => Pedido::listarRegistros(),
            'empleadoNombre' => (string) ($usuario['nombre'] ?? $usuario['username'] ?? ''),
            'localidadesMap' => $localidadesMap,
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
                'title' => 'Pedidos',
                'message' => 'Token CSRF invalido.',
            ];
            $this->redirect('/procesos/almacen/pedidos');
        }

        try {
            $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
            $isEdit = $pedidoId > 0;
            if ($isEdit) {
                if (!Auth::hasPermission('pedidos.editar')) {
                    throw new \RuntimeException('No tienes permiso para editar pedidos.');
                }
            } else {
                if (!Auth::hasPermission('pedidos.crear')) {
                    throw new \RuntimeException('No tienes permiso para crear pedidos.');
                }
            }
            $usuario = Auth::user() ?? [];
            $userId = (int) ($usuario['id'] ?? 0);

            $clienteId = (int) ($_POST['cliente_id'] ?? 0);
            $cliente = $clienteId > 0 ? Cliente::buscarPorId($clienteId) : null;
            if (!$cliente) {
                throw new \RuntimeException('Cliente no valido.');
            }
            $clienteNombre = (string) ($cliente['razon_social'] ?? $cliente['nombre_comercial'] ?? '');
            $clienteTelefono = (string) ($cliente['telefono_cliente'] ?? '');
            $clienteRnc = (string) ($cliente['rnc'] ?? '');
            if ($clienteRnc === '') {
                $clienteRnc = (string) ($cliente['cedula'] ?? '');
            }
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
            $codigos = $_POST['detalle_codigo'] ?? [];
            $descs = $_POST['detalle_descripcion'] ?? [];
            $presentDesc = $_POST['detalle_presentacion_desc'] ?? [];
            $empaqueDesc = $_POST['detalle_empaque_desc'] ?? [];

            if (!is_array($articuloIds) || count($articuloIds) === 0) {
                throw new \RuntimeException('Debes agregar al menos un articulo al pedido.');
            }

            foreach ($articuloIds as $idx => $aidRaw) {
                $aid = (int) $aidRaw;
                if ($aid <= 0) {
                    continue;
                }
                $detalles[] = [
                    'articulo_id' => $aid,
                    'presentacion_id' => (int) ($presentaciones[$idx] ?? 0),
                    'empaque_id' => (int) ($empaques[$idx] ?? 0),
                    'cantidad' => $cantidades[$idx] ?? null,
                    'precio' => $precios[$idx] ?? null,
                    'descuento_pct' => $descuentos[$idx] ?? 0,
                    'articulo_codigo' => (string) ($codigos[$idx] ?? ''),
                    'articulo_descripcion' => (string) ($descs[$idx] ?? ''),
                    'presentacion_descripcion' => (string) ($presentDesc[$idx] ?? ''),
                    'empaque_descripcion' => (string) ($empaqueDesc[$idx] ?? ''),
                ];
            }

            $record = [
                'id' => $pedidoId,
                'fecha' => (string) ($_POST['fecha'] ?? date('Y-m-d')),
                'cliente_id' => $clienteId,
                'cliente_nombre' => $clienteNombre,
                'cliente_telefono' => $clienteTelefono,
                'cliente_rnc' => $clienteRnc,
                'localidad_id' => $localidadId,
                'localidad_nombre' => $localidadNombre ?? '',
                'empleado_id' => (int) ($usuario['empleado_id'] ?? 0),
                'empleado_nombre' => (string) ($usuario['nombre'] ?? $usuario['username'] ?? ''),
                'orden_no' => (string) ($_POST['orden_no'] ?? ''),
                'comentario' => (string) ($_POST['comentario'] ?? ''),
                'created_by' => $userId,
                'updated_by' => $userId,
                'secuencia_clave' => 'pd',
            ];

            $res = $isEdit ? Pedido::actualizarPedido($record, $detalles) : Pedido::guardarPedido($record, $detalles);

            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Pedidos',
                'message' => ($isEdit ? 'Pedido actualizado correctamente: ' : 'Pedido guardado correctamente: ') . ($res['codigo_pedido'] ?? ''),
            ];
            $this->redirect('/procesos/almacen/pedidos');
        } catch (\Throwable $e) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Pedidos',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el pedido.',
            ];
            $this->redirect('/procesos/almacen/pedidos');
        }
    }

    public function detalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'Pedido no valido.']);
            return;
        }
        $pedido = Pedido::buscarPorId($id);
        if (!$pedido) {
            $this->json(['ok' => false, 'message' => 'Pedido no encontrado.']);
            return;
        }
        $this->json(['ok' => true, 'pedido' => $pedido]);
    }

    public function eliminar(): void
    {
        if (!Csrf::validar($_POST['_csrf'] ?? null)) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Pedidos',
                'message' => 'Token CSRF invalido.',
            ];
            $this->redirect('/procesos/almacen/pedidos');
        }
        if (!Auth::hasPermission('pedidos.editar')) {
            $_SESSION['flash_toast'] = [
                'type' => 'danger',
                'title' => 'Pedidos',
                'message' => 'No tienes permiso para eliminar pedidos.',
            ];
            $this->redirect('/procesos/almacen/pedidos');
        }
        $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
        if ($pedidoId > 0) {
            Pedido::eliminar($pedidoId);
            $_SESSION['flash_toast'] = [
                'type' => 'success',
                'title' => 'Pedidos',
                'message' => 'Pedido eliminado correctamente.',
            ];
        }
        $this->redirect('/procesos/almacen/pedidos');
    }

    public function imprimir(): void
    {
        $pedidoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($pedidoId <= 0) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Pedidos',
                'message' => 'Selecciona un pedido para imprimir.',
            ];
            $this->redirect('/procesos/almacen/pedidos');
        }

        $pedido = Pedido::buscarPorId($pedidoId);
        if (!$pedido) {
            $_SESSION['flash_toast'] = [
                'type' => 'warning',
                'title' => 'Pedidos',
                'message' => 'Pedido no valido.',
            ];
            $this->redirect('/procesos/almacen/pedidos');
        }

        $company = \App\Core\Settings::many([
            'company_name',
            'company_phone',
            'company_mail',
            'company_address',
        ]);

        $pdf = new \App\Core\SimplePdf();
        $pdf->addPage();
        $y = 34.0;

        $titulo = 'Pedido';
        $pdf->rect(30, $y - 14, 535, 44, 'F', 0.96);
        $pdf->line(30, $y + 31, 565, $y + 31, 1.0);
        $pdf->text(34, $y + 2, (string) ($company['company_name'] ?? 'FERLON'), 13, 'F2');
        $line2 = trim((string) ($company['company_phone'] ?? '') . ' | ' . (string) ($company['company_mail'] ?? ''));
        $pdf->text(34, $y + 14, $this->truncateForPdf($line2, 84), 9);
        $pdf->text(395, $y + 4, $titulo, 11, 'F2');
        $pdf->text(395, $y + 17, 'Fecha: ' . (string) ($pedido['fecha'] ?? ''), 9);
        $y += 48;

        $pdf->rect(30, $y - 11, 535, 18, 'F', 0.98);
        $pdf->text(34, $y, 'Codigo: ' . (string) ($pedido['codigo_pedido'] ?? ''), 10, 'F2');
        $pdf->text(300, $y, 'Total: ' . number_format((float) ($pedido['total'] ?? 0), 2, '.', ','), 10, 'F2');
        $y += 14;

        $pdf->text(34, $y, 'Cliente: ' . $this->truncateForPdf((string) ($pedido['cliente_nombre'] ?? ''), 70), 9);
        $y += 12;
        if (!empty($pedido['localidad_nombre'])) {
            $pdf->text(34, $y, 'Localidad: ' . $this->truncateForPdf((string) ($pedido['localidad_nombre'] ?? ''), 70), 9);
            $y += 12;
        }

        $detalles = is_array($pedido['detalles'] ?? null) ? $pedido['detalles'] : [];
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

        $filename = 'pedido_' . ($pedido['codigo_pedido'] ?? ('PD' . $pedidoId)) . '.pdf';
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
