<?php

use App\Core\Auth;
use App\Core\Settings;

$usuarioActual = Auth::user();
$tituloPagina = $titulo ?? 'Aplicacion';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$avatarPath = '';
$avatarFallback = 'U';
$perfilNombre = '';
$logoPath = trim((string) Settings::get('logo_path', ''));
$faviconPath = trim((string) Settings::get('favicon_path', ''));
$companyName = trim((string) Settings::get('company_name', 'FERLON'));

if ($logoPath !== '' && $logoPath[0] !== '/') {
    $logoPath = '/' . ltrim($logoPath, '/');
}

if ($faviconPath !== '' && $faviconPath[0] !== '/') {
    $faviconPath = '/' . ltrim($faviconPath, '/');
}

if (is_array($usuarioActual)) {
    $avatarPath = trim((string) ($usuarioActual['foto_path'] ?? ''));
    if ($avatarPath !== '' && $avatarPath[0] !== '/') {
        $avatarPath = '/' . ltrim($avatarPath, '/');
    }

    $perfilNombre = trim((string) ($usuarioActual['nombre'] ?? ''));
    if ($perfilNombre === '') {
        $perfilNombre = trim((string) ($usuarioActual['username'] ?? 'Usuario'));
    }

    $avatarFallback = strtoupper(substr($perfilNombre !== '' ? $perfilNombre : 'U', 0, 1));
}

$twoFactorEnabledUser = is_array($usuarioActual) ? (bool) ($usuarioActual['two_factor_enabled'] ?? false) : false;

$menuSidebar = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'href' => '/dashboard', 'perm' => 'dashboard.ver'],
    [
        'id' => 'sistema',
        'label' => 'Sistema',
        'icon' => 'bi-sliders',
        'children' => [
            ['id' => 'configuracion', 'label' => 'Configuracion', 'icon' => 'bi-gear', 'href' => '/sistema/configuracion', 'perm' => 'configuracion.ver'],
            ['id' => 'registros-temporales', 'label' => 'Registros temporales', 'icon' => 'bi-clock-history', 'href' => '/sistema/registros-temporales', 'perm' => 'configuracion.ver'],
            ['id' => 'niveles-acceso', 'label' => 'Niveles de acceso', 'icon' => 'bi-shield-lock', 'href' => '/sistema/niveles-acceso', 'perm_any' => ['niveles.ver', 'cuentas_acceso.ver', 'niveles.permisos']],
            ['id' => 'ncf', 'label' => 'Mantenimiento NCF', 'icon' => 'bi-receipt-cutoff', 'href' => '/sistema/ncf', 'perm' => 'ncf.ver'],
            ['id' => 'puestos', 'label' => 'Puestos', 'icon' => 'bi-person-badge', 'href' => '/sistema/puestos', 'perm' => 'puestos.ver'],
        ],
    ],
    [
        'id' => 'mantenimientos',
        'label' => 'Mantenimientos',
        'icon' => 'bi-tools',
        'children' => [
            [
                'id' => 'terceros',
                'label' => 'Terceros',
                'icon' => 'bi-people',
                'children' => [
                    ['id' => 'clientes', 'label' => 'Clientes', 'icon' => 'bi-person-vcard', 'href' => '/mantenimientos/terceros/clientes', 'perm' => 'clientes.ver'],
                    ['id' => 'proveedores', 'label' => 'Proveedores', 'icon' => 'bi-truck-flatbed', 'href' => '/mantenimientos/terceros/proveedores', 'perm' => 'proveedores.ver'],
                    ['id' => 'empleados', 'label' => 'Empleados', 'icon' => 'bi-people-fill', 'href' => '/mantenimientos/terceros/empleados', 'perm' => 'empleados.ver'],
                    ['id' => 'localidades', 'label' => 'Localidades', 'icon' => 'bi-geo-alt', 'href' => '/mantenimientos/terceros/localidades', 'perm' => 'localidades.ver'],
                    ['id' => 'bancos', 'label' => 'Bancos', 'icon' => 'bi-bank', 'href' => '/mantenimientos/terceros/bancos', 'perm' => 'bancos.ver'],
                ],
            ],
            [
                'id' => 'organizacion',
                'label' => 'Organizacion',
                'icon' => 'bi-diagram-3',
                'children' => [
                    ['id' => 'catalogo', 'label' => 'Catalogo', 'icon' => 'bi-grid', 'href' => '/mantenimientos/organizacion/catalogo', 'perm' => 'catalogo.ver'],
                    ['id' => 'articulos', 'label' => 'Articulos', 'icon' => 'bi-box-seam', 'href' => '/mantenimientos/organizacion/articulos', 'perm' => 'articulos.ver'],
                    ['id' => 'recetas-base', 'label' => 'Receta Base', 'icon' => 'bi-journal-check', 'href' => '/mantenimientos/organizacion/recetas-base', 'perm' => 'recetas_base.ver'],
                    ['id' => 'recetas-producto-final', 'label' => 'Receta Producto Final', 'icon' => 'bi-journal-medical', 'href' => '/mantenimientos/organizacion/recetas-producto-final', 'perm_any' => ['recetas_producto_final.ver', 'recetas_base.ver']],
                    ['id' => 'marcas', 'label' => 'Marcas', 'icon' => 'bi-bookmark', 'href' => '/mantenimientos/organizacion/marcas', 'perm' => 'marcas.ver'],
                    ['id' => 'familia', 'label' => 'Familias', 'icon' => 'bi-diagram-2', 'href' => '/mantenimientos/organizacion/familias', 'perm' => 'familias.ver'],
                ],
            ],
        ],
    ],
    [
        'id' => 'procesos',
        'label' => 'Procesos',
        'icon' => 'bi-diagram-3',
        'children' => [
            [
                'id' => 'procesos-clientes',
                'label' => 'Clientes',
                'icon' => 'bi-people',
                'children' => [
                    ['id' => 'procesos-clientes-factura', 'label' => 'Factura', 'icon' => 'bi-receipt', 'href' => '#'],
                    ['id' => 'procesos-clientes-pedido', 'label' => 'Pedido', 'icon' => 'bi-cart-check', 'href' => '/procesos/almacen/pedidos', 'perm_any' => ['pedidos.ver', 'pedidos.crear', 'pedidos.editar']],
                    ['id' => 'procesos-clientes-cotizaciones', 'label' => 'Cotizaciones', 'icon' => 'bi-file-earmark-text', 'href' => '/procesos/clientes/cotizaciones', 'perm_any' => ['cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar']],
                ],
            ],
            [
                'id' => 'procesos-almacen',
                'label' => 'Almacen',
                'icon' => 'bi-box-seam',
                'children' => [
                    ['id' => 'procesos-almacen-orden-compra', 'label' => 'Orden de compra', 'icon' => 'bi-bag-check', 'href' => '/procesos/almacen/orden-compra'],
                    ['id' => 'procesos-almacen-entradas', 'label' => 'Entradas', 'icon' => 'bi-box-arrow-in-down', 'href' => '/procesos/almacen/entradas'],
                    ['id' => 'procesos-almacen-lista-pedidos', 'label' => 'Lista de pedidos', 'icon' => 'bi-card-checklist', 'href' => '/procesos/almacen/lista-pedidos', 'perm_any' => ['pedidos.ver', 'pedidos.crear', 'pedidos.editar']],
                    ['id' => 'procesos-almacen-solicitudes', 'label' => 'Solicitudes', 'icon' => 'bi-envelope-paper', 'href' => '#'],
                    ['id' => 'procesos-almacen-produccion', 'label' => 'Produccion', 'icon' => 'bi-gear-wide-connected', 'href' => '/procesos/almacen/produccion', 'perm_any' => ['produccion.ver', 'produccion.crear', 'produccion.editar']],
                    ['id' => 'procesos-almacen-fabricacion', 'label' => 'Fabricacion', 'icon' => 'bi-hammer', 'href' => '/procesos/almacen/fabricacion', 'perm_any' => ['fabricacion.ver', 'fabricacion.crear', 'fabricacion.editar']],
                    ['id' => 'procesos-almacen-descartes', 'label' => 'Descartes', 'icon' => 'bi-trash', 'href' => '#'],
                ],
            ],
            [
                'id' => 'procesos-contabilidad',
                'label' => 'Contabilidad',
                'icon' => 'bi-calculator',
                'children' => [
                    ['id' => 'procesos-contabilidad-cxc', 'label' => 'CXC', 'icon' => 'bi-journal-check', 'href' => '#'],
                    ['id' => 'procesos-contabilidad-cxp', 'label' => 'CXP', 'icon' => 'bi-journal-x', 'href' => '#'],
                ],
            ],
        ],
    ],
    [
        'id' => 'reportes',
        'label' => 'Reportes',
        'icon' => 'bi-graph-up',
        'children' => [
            [
                'id' => 'reportes-procesos',
                'label' => 'Procesos',
                'icon' => 'bi-diagram-3',
                'children' => [
                    ['id' => 'reportes-procesos-historial-compras', 'label' => 'Historial de compras', 'icon' => 'bi-clipboard-data', 'href' => '/reportes/procesos/historial-compras'],
                ],
            ],
        ],
    ],
];

/**
 * @param array<int, array<string, mixed>> $items
 */
function normalizeMenuPath(string $path): string
{
    $path = trim($path);
    if ($path === '' || $path === '#') {
        return '#';
    }

    $normalized = '/' . trim($path, '/');
    return $normalized === '//' ? '/' : $normalized;
}

/**
 * @param array<string, mixed> $item
 */
function hasActiveMenu(array $item, string $currentPath): bool
{
    $href = normalizeMenuPath((string) ($item['href'] ?? '#'));
    if ($href !== '#' && $href === $currentPath) {
        return true;
    }

    $children = $item['children'] ?? null;
    if (!is_array($children)) {
        return false;
    }

    foreach ($children as $child) {
        if (is_array($child) && hasActiveMenu($child, $currentPath)) {
            return true;
        }
    }

    return false;
}

/**
 * @param array<string, mixed> $item
 */
function canRenderMenuItem(array $item): bool
{
    $perm = trim((string) ($item['perm'] ?? ''));
    if ($perm !== '' && !Auth::hasPermission($perm)) {
        return false;
    }

    $permAny = $item['perm_any'] ?? [];
    if (is_array($permAny) && $permAny !== [] && !Auth::hasAnyPermission(...array_map(static fn ($v): string => (string) $v, $permAny))) {
        return false;
    }

    return true;
}

/**
 * @param array<int, array<string, mixed>> $items
 * @return array<int, array<string, mixed>>
 */
function filterMenuByPermissions(array $items): array
{
    $filtered = [];

    foreach ($items as $item) {
        if (!is_array($item) || !canRenderMenuItem($item)) {
            continue;
        }

        $children = $item['children'] ?? null;
        if (is_array($children)) {
            $childrenFiltered = filterMenuByPermissions($children);
            if ($childrenFiltered === []) {
                continue;
            }
            $item['children'] = $childrenFiltered;
        }

        $filtered[] = $item;
    }

    return $filtered;
}

$menuSidebar = filterMenuByPermissions($menuSidebar);

/**
 * @param array<int, array<string, mixed>> $items
 */
function renderMenuItems(array $items, string $scope, string $listId, string $currentPath, int $level = 1): void
{
    echo '<ul id="' . htmlspecialchars($listId) . '" class="menu-level menu-level-' . $level . ' list-unstyled m-0">';

    foreach ($items as $index => $item) {
        $id = preg_replace('/[^a-z0-9_-]/i', '-', (string) ($item['id'] ?? ('menu-' . $index)));
        $label = (string) ($item['label'] ?? 'Menu');
        $icon = (string) ($item['icon'] ?? 'bi-circle');
        $href = (string) ($item['href'] ?? '#');
        $children = $item['children'] ?? null;
        $hasChildren = is_array($children);

        echo '<li class="menu-item">';

        if ($hasChildren) {
            $collapseId = $scope . '-collapse-' . $level . '-' . $id;
            $isOpen = hasActiveMenu($item, $currentPath);
            $expanded = $isOpen ? 'true' : 'false';
            $collapsedClass = $isOpen ? '' : ' collapsed';
            $showClass = $isOpen ? ' show' : '';

            echo '<button class="menu-link menu-toggle level-' . $level . $collapsedClass . '" type="button" data-bs-toggle="collapse" data-bs-target="#' . htmlspecialchars($collapseId) . '" aria-expanded="' . $expanded . '" aria-controls="' . htmlspecialchars($collapseId) . '">';
            echo '<span class="menu-link-left"><i class="bi ' . htmlspecialchars($icon) . '"></i><span>' . htmlspecialchars($label) . '</span></span>';
            echo '<i class="bi bi-chevron-right menu-chevron"></i>';
            echo '</button>';

            echo '<div id="' . htmlspecialchars($collapseId) . '" class="collapse menu-collapse' . $showClass . '" data-bs-parent="#' . htmlspecialchars($listId) . '">';
            renderMenuItems($children, $scope, $collapseId . '-list', $currentPath, $level + 1);
            echo '</div>';
        } else {
            $normalizedHref = normalizeMenuPath($href);
            $isActive = $normalizedHref !== '#' && $normalizedHref === $currentPath;
            $activeClass = $isActive ? ' menu-link-active' : '';
            $currentAttr = $isActive ? ' aria-current="page"' : '';

            echo '<a class="menu-link level-' . $level . $activeClass . '" href="' . htmlspecialchars($href) . '"' . $currentAttr . '>';
            echo '<span class="menu-link-left"><i class="bi ' . htmlspecialchars($icon) . '"></i><span>' . htmlspecialchars($label) . '</span></span>';
            echo '</a>';
        }

        echo '</li>';
    }

    echo '</ul>';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($tituloPagina) ?></title>
    <?php if ($faviconPath !== ''): ?>
        <link rel="icon" href="<?= htmlspecialchars($faviconPath) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <?php if (!$usuarioActual): ?>
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <?php endif; ?>
</head>
<body class="bg-soft">
<?php if ($usuarioActual): ?>
    <div class="app-shell">
        <aside class="sidebar d-none d-lg-flex flex-column">
            <div class="sidebar-brand">
                <a href="/dashboard" class="sidebar-logo">
                    <?php if ($logoPath !== ''): ?>
                        <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="sidebar-logo-image">
                    <?php else: ?>
                        <?= htmlspecialchars($companyName !== '' ? $companyName : 'FERLON') ?>
                    <?php endif; ?>
                </a>
            </div>
            <div class="sidebar-menu-scroll">
                <nav class="sidebar-nav">
                    <?php renderMenuItems($menuSidebar, 'desktop', 'desktop-menu-root', normalizeMenuPath($currentPath), 1); ?>
                </nav>
            </div>
            <div class="sidebar-footer">
                <a href="/logout" class="btn btn-light btn-sm w-100 rounded-pill">Cerrar sesion</a>
            </div>
        </aside>

        <div class="main">
            <header class="topbar border-bottom">
                <button
                    class="menu-fab d-inline-flex d-lg-none align-items-center justify-content-center"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileNav"
                    aria-controls="mobileNav"
                    aria-label="Abrir menu de navegacion"
                >
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-heading">
                    <h1 class="h6 mb-0"><?= htmlspecialchars($tituloPagina) ?></h1>
                    <small class="text-muted">Panel administrativo</small>
                </div>
                <div class="ms-auto">
                    <div class="dropdown">
                        <button
                            class="btn topbar-avatar-btn dropdown-toggle"
                            type="button"
                            id="profileMenuButton"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false"
                            aria-label="Abrir opciones de cuenta"
                        >
                            <?php if ($avatarPath !== ''): ?>
                                <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Foto de perfil" class="topbar-avatar-img">
                            <?php else: ?>
                                <span class="topbar-avatar-fallback"><?= htmlspecialchars($avatarFallback) ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 topbar-profile-menu" aria-labelledby="profileMenuButton">
                            <div class="profile-menu-header">
                                <strong class="d-block"><?= htmlspecialchars($perfilNombre) ?></strong>
                                <small class="text-muted">
                                    <?= $twoFactorEnabledUser ? '2FA activo' : '2FA desactivado' ?>
                                </small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="bi bi-key"></i>
                                <span>Cambiar contraseña</span>
                            </button>
                            <button class="dropdown-item d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#twoFactorModal">
                                <i class="bi bi-shield-check"></i>
                                <span>Configurar 2FA</span>
                            </button>
                            <div class="dropdown-divider"></div>
                            <a href="/logout" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Cerrar sesion</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            <section class="main-content">

            <div class="offcanvas offcanvas-top mobile-menu" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
                <div class="offcanvas-header border-bottom mobile-menu-header">
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($logoPath !== ''): ?>
                            <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="offcanvas-logo-image">
                        <?php endif; ?>
                        <div>
                            <div class="mobile-menu-brand"><?= htmlspecialchars($companyName !== '' ? $companyName : 'FERLON') ?></div>
                            <div class="mobile-menu-subtitle">Navegacion rapida</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
                </div>
                <div class="offcanvas-body mobile-menu-body">
                    <nav class="sidebar-nav">
                        <?php renderMenuItems($menuSidebar, 'mobile', 'mobile-menu-root', normalizeMenuPath($currentPath), 1); ?>
                    </nav>
                </div>
            </div>
<?php else: ?>
    <main>
<?php endif; ?>
