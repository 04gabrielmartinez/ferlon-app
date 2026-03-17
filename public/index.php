<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ArticuloController;
use App\Controllers\ConfiguracionController;
use App\Controllers\ClienteController;
use App\Controllers\CotizacionController;
use App\Controllers\CatalogoController;
use App\Controllers\DashboardController;
use App\Controllers\EmpleadoController;
use App\Controllers\EntradaCompraController;
use App\Controllers\FabricacionController;
use App\Controllers\PedidoController;
use App\Controllers\ReportesController;
use App\Controllers\FamiliaController;
use App\Controllers\LocalidadController;
use App\Controllers\MarcaController;
use App\Controllers\MiCuentaController;
use App\Controllers\NcfController;
use App\Controllers\NivelesAccesoController;
use App\Controllers\OrdenCompraController;
use App\Controllers\PuestosController;
use App\Controllers\ProduccionController;
use App\Controllers\ProveedorController;
use App\Controllers\RecetaBaseController;
use App\Controllers\RecetaProductoFinalController;
use App\Controllers\BancoController;
use App\Core\Router;
use App\Core\Session;

require_once dirname(__DIR__) . '/app/Core/Autoload.php';

cargarEnv(dirname(__DIR__) . '/.env');
configurarLogs(dirname(__DIR__) . '/storage/logs/app.log');

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');

Session::iniciar();
aplicarHeadersSeguridad();

$router = new Router();
$router->get('/', [AuthController::class, 'mostrarLogin'], ['guest']);
$router->get('/login', [AuthController::class, 'mostrarLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth', 'role:admin', 'perm:dashboard.ver']);
$router->get('/mantenimientos/terceros/empleados', [EmpleadoController::class, 'index'], ['auth', 'role:admin', 'perm:empleados.ver']);
$router->post('/mantenimientos/terceros/empleados', [EmpleadoController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:empleados.crear,empleados.editar']);
$router->get('/mantenimientos/terceros/proveedores', [ProveedorController::class, 'index'], ['auth', 'role:admin', 'perm:proveedores.ver']);
$router->post('/mantenimientos/terceros/proveedores', [ProveedorController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:proveedores.crear,proveedores.editar']);
$router->get('/mantenimientos/terceros/clientes', [ClienteController::class, 'index'], ['auth', 'role:admin', 'perm:clientes.ver']);
$router->post('/mantenimientos/terceros/clientes', [ClienteController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:clientes.crear,clientes.editar']);
$router->get('/mantenimientos/terceros/localidades', [LocalidadController::class, 'index'], ['auth', 'role:admin', 'perm:localidades.ver']);
$router->post('/mantenimientos/terceros/localidades', [LocalidadController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:localidades.crear,localidades.editar']);
$router->get('/mantenimientos/terceros/bancos', [BancoController::class, 'index'], ['auth', 'role:admin', 'perm:bancos.ver']);
$router->post('/mantenimientos/terceros/bancos', [BancoController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:bancos.crear,bancos.editar']);
$router->get('/sistema/puestos', [PuestosController::class, 'index'], ['auth', 'role:admin', 'perm:puestos.ver']);
$router->post('/sistema/puestos', [PuestosController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:puestos.crear,puestos.editar']);
$router->get('/mantenimientos/organizacion/catalogo', [CatalogoController::class, 'index'], ['auth', 'role:admin', 'perm:catalogo.ver']);
$router->post('/mantenimientos/organizacion/catalogo', [CatalogoController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:catalogo.crear,catalogo.editar']);
$router->get('/mantenimientos/organizacion/articulos', [ArticuloController::class, 'index'], ['auth', 'role:admin', 'perm:articulos.ver']);
$router->post('/mantenimientos/organizacion/articulos', [ArticuloController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:articulos.crear,articulos.editar']);
$router->get('/mantenimientos/organizacion/marcas', [MarcaController::class, 'index'], ['auth', 'role:admin', 'perm:marcas.ver']);
$router->post('/mantenimientos/organizacion/marcas', [MarcaController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:marcas.crear,marcas.editar']);
$router->get('/mantenimientos/organizacion/familias', [FamiliaController::class, 'index'], ['auth', 'role:admin', 'perm:familias.ver']);
$router->post('/mantenimientos/organizacion/familias', [FamiliaController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:familias.crear,familias.editar']);
$router->get('/mantenimientos/organizacion/recetas-base', [RecetaBaseController::class, 'index'], ['auth', 'role:admin', 'perm:recetas_base.ver']);
$router->get('/mantenimientos/organizacion/recetas-base/imprimir', [RecetaBaseController::class, 'imprimir'], ['auth', 'role:admin', 'perm:recetas_base.ver']);
$router->post('/mantenimientos/organizacion/recetas-base', [RecetaBaseController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:recetas_base.crear,recetas_base.editar']);
$router->get('/mantenimientos/organizacion/recetas-producto-final', [RecetaProductoFinalController::class, 'index'], ['auth', 'role:admin', 'perm_any:recetas_producto_final.ver,recetas_base.ver']);
$router->get('/mantenimientos/organizacion/recetas-producto-final/imprimir', [RecetaProductoFinalController::class, 'imprimir'], ['auth', 'role:admin', 'perm_any:recetas_producto_final.ver,recetas_base.ver']);
$router->post('/mantenimientos/organizacion/recetas-producto-final', [RecetaProductoFinalController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:recetas_producto_final.crear,recetas_producto_final.editar,recetas_base.crear,recetas_base.editar']);
$router->get('/sistema/ncf', [NcfController::class, 'index'], ['auth', 'role:admin', 'perm:ncf.ver']);
$router->post('/sistema/ncf', [NcfController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:ncf.crear,ncf.editar']);
$router->get('/sistema/configuracion', [ConfiguracionController::class, 'index'], ['auth', 'role:admin', 'perm:configuracion.ver']);
$router->post('/sistema/configuracion', [ConfiguracionController::class, 'guardar'], ['auth', 'role:admin', 'perm:configuracion.editar']);
$router->get('/sistema/registros-temporales', [ConfiguracionController::class, 'registrosTemporales'], ['auth', 'role:admin', 'perm:configuracion.ver']);
$router->get('/sistema/niveles-acceso', [NivelesAccesoController::class, 'index'], ['auth', 'role:admin', 'perm_any:niveles.ver,cuentas_acceso.ver,niveles.permisos']);
$router->post('/sistema/niveles-acceso', [NivelesAccesoController::class, 'guardar'], ['auth', 'role:admin', 'perm_any:niveles.crear,niveles.permisos,cuentas_acceso.crear,cuentas_acceso.editar']);
$router->post('/mi-cuenta/password', [MiCuentaController::class, 'cambiarPassword'], ['auth']);
$router->post('/mi-cuenta/2fa', [MiCuentaController::class, 'configurar2fa'], ['auth']);
$router->post('/mi-cuenta/2fa/prompt', [MiCuentaController::class, 'configurarPrompt2fa'], ['auth']);
$router->get('/procesos/almacen/orden-compra', [OrdenCompraController::class, 'index'], ['auth', 'role:admin']);
$router->post('/procesos/almacen/orden-compra', [OrdenCompraController::class, 'guardar'], ['auth', 'role:admin']);
$router->get('/procesos/almacen/orden-compra/imprimir', [OrdenCompraController::class, 'imprimir'], ['auth', 'role:admin']);
$router->get('/procesos/almacen/entradas', [EntradaCompraController::class, 'index'], ['auth', 'role:admin']);
$router->post('/procesos/almacen/entradas', [EntradaCompraController::class, 'guardar'], ['auth', 'role:admin']);
$router->get('/procesos/almacen/entradas/imprimir', [EntradaCompraController::class, 'imprimir'], ['auth', 'role:admin']);
$router->get('/procesos/almacen/produccion', [ProduccionController::class, 'index'], ['auth', 'perm_any:produccion.ver,produccion.crear,produccion.editar']);
$router->post('/procesos/almacen/produccion', [ProduccionController::class, 'guardar'], ['auth', 'perm_any:produccion.crear,produccion.editar']);
$router->get('/procesos/almacen/produccion/receta', [ProduccionController::class, 'receta'], ['auth', 'perm_any:produccion.ver,produccion.crear,produccion.editar']);
$router->get('/procesos/almacen/produccion/detalle', [ProduccionController::class, 'detalle'], ['auth', 'perm_any:produccion.ver,produccion.editar']);
$router->get('/procesos/almacen/produccion/imprimir', [ProduccionController::class, 'imprimir'], ['auth', 'perm_any:produccion.ver,produccion.editar']);
$router->get('/procesos/almacen/fabricacion', [FabricacionController::class, 'index'], ['auth', 'perm_any:fabricacion.ver,fabricacion.crear,fabricacion.editar']);
$router->post('/procesos/almacen/fabricacion', [FabricacionController::class, 'guardar'], ['auth', 'perm_any:fabricacion.crear,fabricacion.editar']);
$router->get('/procesos/almacen/fabricacion/receta', [FabricacionController::class, 'receta'], ['auth', 'perm_any:fabricacion.ver,fabricacion.crear,fabricacion.editar']);
$router->get('/procesos/almacen/fabricacion/detalle', [FabricacionController::class, 'detalle'], ['auth', 'perm_any:fabricacion.ver,fabricacion.editar']);
$router->get('/procesos/almacen/fabricacion/imprimir', [FabricacionController::class, 'imprimir'], ['auth', 'perm_any:fabricacion.ver,fabricacion.editar']);
$router->get('/procesos/almacen/pedidos', [PedidoController::class, 'index'], ['auth', 'perm_any:pedidos.ver,pedidos.crear,pedidos.editar']);
$router->get('/procesos/almacen/lista-pedidos', [PedidoController::class, 'lista'], ['auth', 'perm_any:pedidos.ver,pedidos.crear,pedidos.editar']);
$router->get('/procesos/almacen/lista-pedidos/detalle', [PedidoController::class, 'gestionDetalle'], ['auth', 'perm_any:pedidos.ver,pedidos.crear,pedidos.editar']);
$router->post('/procesos/almacen/lista-pedidos/marcar-visto', [PedidoController::class, 'marcarVisto'], ['auth', 'perm_any:pedidos.ver,pedidos.crear,pedidos.editar']);
$router->post('/procesos/almacen/lista-pedidos/gestionar', [PedidoController::class, 'gestionar'], ['auth', 'perm_any:pedidos.crear,pedidos.editar']);
$router->post('/procesos/almacen/pedidos', [PedidoController::class, 'guardar'], ['auth', 'perm_any:pedidos.crear,pedidos.editar']);
$router->get('/procesos/almacen/pedidos/detalle', [PedidoController::class, 'detalle'], ['auth', 'perm_any:pedidos.ver,pedidos.editar']);
$router->post('/procesos/almacen/pedidos/eliminar', [PedidoController::class, 'eliminar'], ['auth', 'perm_any:pedidos.editar']);
$router->get('/procesos/almacen/pedidos/imprimir', [PedidoController::class, 'imprimir'], ['auth', 'perm_any:pedidos.ver,pedidos.editar']);
$router->get('/procesos/clientes/cotizaciones', [CotizacionController::class, 'index'], ['auth', 'perm_any:cotizaciones.ver,cotizaciones.crear,cotizaciones.editar']);
$router->post('/procesos/clientes/cotizaciones', [CotizacionController::class, 'guardar'], ['auth', 'perm_any:cotizaciones.crear,cotizaciones.editar']);
$router->get('/procesos/clientes/cotizaciones/imprimir', [CotizacionController::class, 'imprimir'], ['auth', 'perm_any:cotizaciones.ver,cotizaciones.crear,cotizaciones.editar']);
$router->get('/procesos/clientes/cotizaciones/detalle', [CotizacionController::class, 'detalle'], ['auth', 'perm_any:cotizaciones.ver,cotizaciones.editar']);
$router->post('/procesos/clientes/cotizaciones/eliminar', [CotizacionController::class, 'eliminar'], ['auth', 'perm_any:cotizaciones.editar']);
$router->get('/reportes/procesos/historial-compras', [ReportesController::class, 'historialCompras'], ['auth', 'role:admin']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $e) {
    \App\Core\AuditLog::write('app.unhandled_exception', [
        'tipo_accion' => 'error_no_controlado',
        'apartado' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
        'descripcion' => 'Excepcion no controlada en dispatcher',
        'error' => $e->getMessage(),
    ]);
    http_response_code(500);
    echo '500 - Error interno';
}

function cargarEnv(string $ruta): void
{
    if (!is_file($ruta)) {
        return;
    }

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        $linea = trim($linea);

        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }

        [$llave, $valor] = explode('=', $linea, 2);
        $llave = trim($llave);
        $valor = trim($valor, " \t\n\r\0\x0B\"");

        if ($llave !== '' && getenv($llave) === false) {
            putenv($llave . '=' . $valor);
            $_ENV[$llave] = $valor;
            $_SERVER[$llave] = $valor;
        }
    }
}

function configurarLogs(string $rutaLog): void
{
    $directorio = dirname($rutaLog);
    if (!is_dir($directorio)) {
        mkdir($directorio, 0775, true);
    }

    ini_set('log_errors', '1');
    ini_set('error_log', $rutaLog);
}

function aplicarHeadersSeguridad(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');
    header('Content-Security-Policy: frame-ancestors \'self\'; base-uri \'self\'; form-action \'self\'');
}
