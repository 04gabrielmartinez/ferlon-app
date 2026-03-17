<?php
$receta = is_array($receta ?? null) ? $receta : [];
$productosRecetaProductoFinal = is_array($productosRecetaProductoFinal ?? null) ? $productosRecetaProductoFinal : [];
$productosBaseRecetaProductoFinal = is_array($productosBaseRecetaProductoFinal ?? null) ? $productosBaseRecetaProductoFinal : [];
$presentacionesProducto = is_array($presentacionesProducto ?? null) ? $presentacionesProducto : [];
$empaquesProducto = is_array($empaquesProducto ?? null) ? $empaquesProducto : [];
$variantesProductoReceta = is_array($variantesProductoReceta ?? null) ? $variantesProductoReceta : [];
$insumosReceta = is_array($insumosReceta ?? null) ? $insumosReceta : [];
$detalles = is_array($receta['detalles'] ?? null) ? $receta['detalles'] : [];
$productoSeleccionado = is_array($productoSeleccionado ?? null) ? $productoSeleccionado : [];
$varianteSeleccionada = is_array($varianteSeleccionada ?? null) ? $varianteSeleccionada : [];
$productosConReceta = 0;
foreach ($productosBaseRecetaProductoFinal as $p) {
    $totalP = max(0, (int) ($p['total_presentaciones'] ?? 0));
    $totalE = max(0, (int) ($p['total_empaques'] ?? 0));
    $totalV = $totalP * $totalE;
    $creadas = max(0, (int) ($p['recetas_configuradas'] ?? 0));
    if ($totalV > 0 && $creadas >= $totalV) {
        $productosConReceta++;
    }
}
$productosSinReceta = max(0, count($productosBaseRecetaProductoFinal) - $productosConReceta);
?>
<?php
$codProducto = (string) ($productoSeleccionado['codigo'] ?? '');
$mapaVariantes = [];
foreach ($variantesProductoReceta as $vr) {
    $k = ((int) ($vr['presentacion_id'] ?? 0)) . '-' . ((int) ($vr['empaque_id'] ?? 0));
    $mapaVariantes[$k] = [
        'receta_id' => (int) ($vr['receta_producto_final_id'] ?? 0),
        'presentacion' => (string) ($vr['presentacion_descripcion'] ?? ''),
        'empaque' => (string) ($vr['empaque_descripcion'] ?? ''),
    ];
}
$productoSeleccionadoId = (int) ($receta['producto_articulo_id'] ?? 0);
$imprimirUrl = $productoSeleccionadoId > 0
    ? ('/mantenimientos/organizacion/recetas-producto-final/imprimir?producto_id=' . $productoSeleccionadoId)
    : '';
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Organizacion / Receta Producto Final</h2>
            <small class="text-muted">Configuracion de productos base y sus insumos de receta</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Receta Producto Final</div>
                <form method="post" action="/mantenimientos/organizacion/recetas-producto-final" id="recetaProductoFinalForm" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($receta['id'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Cod producto</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($codProducto) ?>" readonly placeholder="Selecciona un producto">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#productoRecetaPickerModal" aria-label="Buscar producto receta producto final">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12 col-md-8">
                            <label class="form-label small mb-1">Producto (Receta producto final)</label>
                            <input type="hidden" name="producto_articulo_id" id="producto_articulo_id" value="<?= (int) ($receta['producto_articulo_id'] ?? 0) ?>">
                            <input type="hidden" name="presentacion_id" id="presentacion_id" value="<?= (int) ($receta['presentacion_id'] ?? 0) ?>">
                            <input type="hidden" name="empaque_id" id="empaque_id" value="<?= (int) ($receta['empaque_id'] ?? 0) ?>">
                            <input type="text" id="producto_receta_label" class="form-control form-control-sm" value="<?= htmlspecialchars(trim((string) ($productoSeleccionado['codigo'] ?? '') . ' - ' . (string) ($productoSeleccionado['descripcion'] ?? ''))) ?>" readonly placeholder="Seleccione un producto usando la lupa de Cod producto">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Presentacion variante</label>
                            <select id="presentacion_selector" class="form-select form-select-sm">
                                <option value="">Seleccione</option>
                                <?php foreach ($presentacionesProducto as $pp): $ppid = (int) ($pp['id'] ?? 0); if ($ppid <= 0) continue; ?>
                                    <option value="<?= $ppid ?>" <?= ((int) ($receta['presentacion_id'] ?? 0) === $ppid) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($pp['descripcion'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Empaque variante</label>
                            <select id="empaque_selector" class="form-select form-select-sm">
                                <option value="">Seleccione</option>
                                <?php foreach ($empaquesProducto as $ep): $epid = (int) ($ep['id'] ?? 0); if ($epid <= 0) continue; ?>
                                    <option value="<?= $epid ?>" <?= ((int) ($receta['empaque_id'] ?? 0) === $epid) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($ep['descripcion'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Precio de venta</label>
                            <input type="number" step="0.01" min="0.01" name="precio_venta" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($receta['precio_venta'] ?? '')) ?>" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Recetas de Este Producto</label>
                            <div class="border rounded-3 bg-light-subtle p-2" style="height: 190px; overflow: auto;">
                                <?php if ((int) ($receta['producto_articulo_id'] ?? 0) > 0): ?>
                                    <?php
                                    $totalVariantes = count($variantesProductoReceta);
                                    $creadasVariantes = 0;
                                    foreach ($variantesProductoReceta as $v) {
                                        if ((int) ($v['receta_producto_final_id'] ?? 0) > 0) $creadasVariantes++;
                                    }
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Variantes</small>
                                        <span class="badge text-bg-secondary"><?= $creadasVariantes ?> / <?= $totalVariantes ?></span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Variante</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($variantesProductoReceta as $v): ?>
                                                    <?php $ok = (int) ($v['receta_producto_final_id'] ?? 0) > 0; ?>
                                                    <tr class="js-variant-row <?= $ok ? 'table-success' : 'table-warning' ?>"
                                                        data-presentacion-id="<?= (int) ($v['presentacion_id'] ?? 0) ?>"
                                                        data-empaque-id="<?= (int) ($v['empaque_id'] ?? 0) ?>"
                                                        style="cursor:pointer;">
                                                        <td><?= htmlspecialchars((string) ($v['presentacion_descripcion'] ?? '') . ' / ' . (string) ($v['empaque_descripcion'] ?? '')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small">Selecciona un producto para ver sus variantes.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <hr class="my-3">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <div class="section-title mb-0">Detalle de Insumos</div>
                    </div>
                    <div id="detalle_lock_hint" class="alert alert-info py-2 px-3 small mb-3">
                        Selecciona un producto y luego una presentacion + empaque para habilitar el detalle de insumos.
                    </div>
                    <fieldset id="detalleInsumosFieldset">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label small mb-1">Insumo receta</label>
                                <div class="input-group input-group-sm">
                                    <input type="hidden" id="detalle_insumo_id_sel" value="">
                                    <input type="hidden" id="detalle_insumo_unidad_base_sel" value="">
                                    <input type="text" id="detalle_insumo_label_sel" class="form-control" readonly placeholder="Selecciona un insumo en la lupa">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#insumoPickerModal" aria-label="Buscar insumo receta">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small mb-1">Cantidad</label>
                                <input type="number" step="0.0001" min="0.0001" id="detalle_cantidad_sel" class="form-control form-control-sm" value="1">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label small mb-1">Unidad</label>
                                <select id="detalle_unidad_sel" class="form-select form-select-sm">
                                    <option value="g">g</option>
                                    <option value="kg">kg</option>
                                    <option value="lb">lb</option>
                                    <option value="oz">oz</option>
                                    <option value="u" selected>u</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                                <button type="button" class="btn btn-primary btn-sm w-100" id="btnDetalleGuardar">Bajar a tabla</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDetalleLimpiar" title="Limpiar seleccion"><i class="bi bi-eraser"></i></button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="detalleInsumosTable">
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Insumo</th>
                                        <th style="width: 170px;">Cantidad</th>
                                        <th style="width: 140px;">Unidad</th>
                                        <th style="width: 70px;">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detalles as $d): $insumoId = (int) ($d['insumo_articulo_id'] ?? 0); if ($insumoId <= 0) continue; ?>
                                        <?php $cant = (string) ($d['cantidad'] ?? '1'); $uni = strtolower((string) ($d['unidad'] ?? 'u')); $ubase = strtolower((string) ($d['insumo_unidad_base'] ?? 'u')); ?>
                                        <tr class="js-detalle-row" data-insumo-id="<?= $insumoId ?>" data-codigo="<?= htmlspecialchars((string) ($d['insumo_codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-descripcion="<?= htmlspecialchars((string) ($d['insumo_descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-cantidad="<?= htmlspecialchars($cant, ENT_QUOTES, 'UTF-8') ?>" data-unidad="<?= htmlspecialchars($uni, ENT_QUOTES, 'UTF-8') ?>" data-unidad-base="<?= htmlspecialchars($ubase, ENT_QUOTES, 'UTF-8') ?>">
                                            <td><?= htmlspecialchars((string) ($d['insumo_codigo'] ?? '')) ?></td>
                                            <td>
                                                <?= htmlspecialchars((string) ($d['insumo_descripcion'] ?? '')) ?>
                                                <input type="hidden" name="detalle_insumo_id[]" value="<?= $insumoId ?>">
                                            </td>
                                            <td class="js-detalle-cantidad-text"><?= htmlspecialchars($cant) ?><input type="hidden" name="detalle_cantidad[]" value="<?= htmlspecialchars($cant) ?>"></td>
                                            <td class="js-detalle-unidad-text"><?= htmlspecialchars($uni) ?><input type="hidden" name="detalle_unidad[]" value="<?= htmlspecialchars($uni) ?>"></td>
                                            <td><button type="button" class="btn btn-outline-danger btn-sm js-remove-insumo"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </fieldset>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                        <a href="<?= htmlspecialchars($imprimirUrl) ?>"
                           target="_blank"
                           rel="noopener"
                           class="btn btn-outline-primary btn-sm rounded-pill px-3 <?= $imprimirUrl === '' ? 'disabled' : '' ?>"
                           <?= $imprimirUrl === '' ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                            Imprimir
                        </a>
                        <a href="/mantenimientos/organizacion/recetas-producto-final" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="productoRecetaPickerModal" tabindex="-1" aria-labelledby="productoRecetaPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="productoRecetaPickerModalLabel">
                        <i class="bi bi-box-seam"></i>
                        <span>Seleccionar producto receta producto final</span>
                    </h5>
                    <small class="text-muted">Solo se muestran articulos con "Receta producto final" activo</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="productoRecetaPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Descripcion</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($productosBaseRecetaProductoFinal === []): ?>
                            <tr>
                                <td class="text-muted">&nbsp;</td>
                                <td class="text-muted">No hay productos con receta producto final.</td>
                                <td class="text-muted">&nbsp;</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productosBaseRecetaProductoFinal as $p): $pid = (int) ($p['id'] ?? 0); if ($pid <= 0) continue; ?>
                                <tr class="js-producto-receta-row" data-producto-id="<?= $pid ?>" data-producto-url="/mantenimientos/organizacion/recetas-producto-final?producto_id=<?= $pid ?>" style="cursor:pointer;">
                                    <td><a class="text-reset text-decoration-none d-block" href="/mantenimientos/organizacion/recetas-producto-final?producto_id=<?= $pid ?>"><?= htmlspecialchars((string) ($p['codigo'] ?? '')) ?></a></td>
                                    <td><a class="text-reset text-decoration-none d-block" href="/mantenimientos/organizacion/recetas-producto-final?producto_id=<?= $pid ?>"><?= htmlspecialchars((string) ($p['descripcion'] ?? '')) ?></a></td>
                                    <td>-</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="insumoPickerModal" tabindex="-1" aria-labelledby="insumoPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="insumoPickerModalLabel">
                        <i class="bi bi-boxes"></i>
                        <span>Seleccionar insumo receta</span>
                    </h5>
                    <small class="text-muted">Solo se muestran articulos con "Insumo receta" o "Receta base" activo</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="insumoPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Descripcion</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($insumosReceta === []): ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($insumosReceta as $ins): $iid = (int) ($ins['id'] ?? 0); if ($iid <= 0) continue; ?>
                                <?php
                                    $unidadBaseRow = strtolower((string) ($ins['unidad_base_id'] ?? 'u'));
                                    $stockNum = $unidadBaseRow === 'u'
                                        ? (float) ($ins['stock_actual'] ?? 0)
                                        : (float) ($ins['stock_actual_kg'] ?? 0);
                                    $stockFormatted = number_format($stockNum, 2, '.', '');
                                    $stockLabel = $unidadBaseRow === 'u' ? ($stockFormatted . ' u') : ($stockFormatted . ' kg');
                                ?>
                                <tr class="js-insumo-row"
                                    data-insumo-id="<?= $iid ?>"
                                    data-codigo="<?= htmlspecialchars((string) ($ins['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-descripcion="<?= htmlspecialchars((string) ($ins['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-unidad-base="<?= htmlspecialchars(strtolower((string) ($ins['unidad_base_id'] ?? 'u')), ENT_QUOTES, 'UTF-8') ?>">
                                    <td><?= htmlspecialchars((string) ($ins['codigo'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($ins['descripcion'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($stockLabel) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const detalleTableEl = document.getElementById('detalleInsumosTable');
    const modalEl = document.getElementById('insumoPickerModal');
    const detalleInsumoIdSel = document.getElementById('detalle_insumo_id_sel');
    const detalleInsumoUnidadBaseSel = document.getElementById('detalle_insumo_unidad_base_sel');
    const detalleInsumoLabelSel = document.getElementById('detalle_insumo_label_sel');
    const detalleCantidadSel = document.getElementById('detalle_cantidad_sel');
    const detalleUnidadSel = document.getElementById('detalle_unidad_sel');
    const btnDetalleGuardar = document.getElementById('btnDetalleGuardar');
    const btnDetalleLimpiar = document.getElementById('btnDetalleLimpiar');
    const productoIdHidden = document.getElementById('producto_articulo_id');
    const presentacionIdHidden = document.getElementById('presentacion_id');
    const empaqueIdHidden = document.getElementById('empaque_id');
    const presentacionSelector = document.getElementById('presentacion_selector');
    const empaqueSelector = document.getElementById('empaque_selector');
    const detalleFieldset = document.getElementById('detalleInsumosFieldset');
    const detalleLockHint = document.getElementById('detalle_lock_hint');
    const submitBtn = document.querySelector('#recetaProductoFinalForm button[type="submit"]');
    let detalleTable = null;
    let detalleItems = [];
    let selectedInsumoId = null;
    const variantesRecetaMap = <?= json_encode($mapaVariantes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const recetaIdActual = Number(document.querySelector('input[name="id"]')?.value || '0');

    const showToast = (message, type = 'danger', title = 'Notificacion') => {
        const text = (message || '').trim();
        if (!text) return;
        const payload = { message: text, type, title, delay: 5000 };
        if (window.AppToast && typeof window.AppToast.show === 'function') {
            window.AppToast.show(payload);
            return;
        }
        window.__pendingAppToasts = Array.isArray(window.__pendingAppToasts) ? window.__pendingAppToasts : [];
        window.__pendingAppToasts.push(payload);
    };
    const showErrorToast = (message) => showToast(message, 'danger', 'Receta Producto Final');

    const goToProducto = (productoId, productoUrl = '') => {
        const directUrl = String(productoUrl || '').trim();
        if (directUrl !== '') {
            window.location.href = directUrl;
            return;
        }
        const id = Number(productoId || 0);
        if (id <= 0) return;
        window.location.href = '/mantenimientos/organizacion/recetas-producto-final?producto_id=' + encodeURIComponent(String(id));
    };

    const getProductoIdFromRow = (row) => {
        if (!row) return 0;
        let id = Number(row.getAttribute('data-producto-id') || '0');
        if (id > 0) return id;
        if (row.classList.contains('child')) {
            const prev = row.previousElementSibling;
            id = Number(prev?.getAttribute('data-producto-id') || '0');
            if (id > 0) return id;
        }
        return 0;
    };

    const getProductoUrlFromRow = (row) => {
        if (!row) return '';
        let url = String(row.getAttribute('data-producto-url') || '').trim();
        if (url !== '') return url;
        if (row.classList.contains('child')) {
            const prev = row.previousElementSibling;
            url = String(prev?.getAttribute('data-producto-url') || '').trim();
            if (url !== '') return url;
        }
        return '';
    };

    const syncDetalleLockState = () => {
        const productoId = Number(productoIdHidden?.value || '0');
        const presentacionId = Number(presentacionSelector?.value || '0');
        const empaqueId = Number(empaqueSelector?.value || '0');
        const hasProducto = productoId > 0;
        const hasVariante = hasProducto && presentacionId > 0 && empaqueId > 0;

        if (presentacionSelector) presentacionSelector.disabled = !hasProducto;
        if (empaqueSelector) empaqueSelector.disabled = !hasProducto;
        if (detalleFieldset) detalleFieldset.disabled = !hasVariante;
        if (submitBtn) submitBtn.disabled = !hasVariante;

        if (detalleLockHint) {
            if (!hasProducto) {
                detalleLockHint.textContent = 'Selecciona un producto para habilitar presentacion, empaque y detalle de insumos.';
                detalleLockHint.classList.remove('d-none');
            } else if (!hasVariante) {
                detalleLockHint.textContent = 'Selecciona presentacion y empaque para habilitar el detalle de insumos.';
                detalleLockHint.classList.remove('d-none');
            } else {
                detalleLockHint.classList.add('d-none');
            }
        }
    };

    const limpiarSeleccionDetalle = () => {
        selectedInsumoId = null;
        if (detalleTableEl) {
            detalleTableEl.querySelectorAll('tbody tr').forEach((row) => row.classList.remove('table-primary'));
        }
        if (detalleInsumoIdSel) detalleInsumoIdSel.value = '';
        if (detalleInsumoUnidadBaseSel) detalleInsumoUnidadBaseSel.value = '';
        if (detalleInsumoLabelSel) detalleInsumoLabelSel.value = '';
        if (detalleCantidadSel) detalleCantidadSel.value = '1';
        if (detalleUnidadSel) detalleUnidadSel.value = 'u';
        filterUnidadOptions('u');
        if (btnDetalleGuardar) btnDetalleGuardar.textContent = 'Bajar a tabla';
    };

    const seleccionarDetallePorId = (insumoId) => {
        if (!insumoId) return;
        const item = detalleItems.find((it) => Number(it.insumoId) === Number(insumoId));
        if (!item) return;
        selectedInsumoId = Number(insumoId);
        if (detalleTableEl) {
            detalleTableEl.querySelectorAll('tbody tr').forEach((r) => {
                const rid = Number(r.getAttribute('data-insumo-id') || '0');
                r.classList.toggle('table-primary', rid === selectedInsumoId);
            });
        }
        if (detalleInsumoIdSel) detalleInsumoIdSel.value = String(item.insumoId);
        if (detalleInsumoUnidadBaseSel) detalleInsumoUnidadBaseSel.value = item.unidadBase;
        if (detalleInsumoLabelSel) {
            detalleInsumoLabelSel.value = `${item.codigo} - ${item.descripcion}`.trim();
        }
        if (detalleCantidadSel) detalleCantidadSel.value = String(item.cantidad);
        filterUnidadOptions(item.unidadBase);
        if (detalleUnidadSel) detalleUnidadSel.value = item.unidad;
        if (btnDetalleGuardar) btnDetalleGuardar.textContent = 'Actualizar fila';
    };

    const unidadCompatible = (unidadBase, unidadReceta) => {
        const ub = (unidadBase || '').toLowerCase();
        const ur = (unidadReceta || '').toLowerCase();
        const mass = ['g', 'kg', 'lb', 'oz'];
        if (mass.includes(ub)) {
            return mass.includes(ur);
        }
        if (ub === 'u') {
            return ur === 'u';
        }
        return ub === ur;
    };

    const filterUnidadOptions = (unidadBase) => {
        if (!detalleUnidadSel) return;
        const ub = (unidadBase || 'u').toLowerCase();
        const mass = ['g', 'kg', 'lb', 'oz'];
        const allowMass = mass.includes(ub);
        Array.from(detalleUnidadSel.options).forEach((opt) => {
            const val = String(opt.value || '').toLowerCase();
            const allowed = allowMass ? mass.includes(val) : val === 'u';
            opt.disabled = !allowed;
            opt.hidden = !allowed;
        });
        const current = String(detalleUnidadSel.value || '').toLowerCase();
        if (allowMass) {
            if (!mass.includes(current)) detalleUnidadSel.value = 'kg';
        } else {
            if (current !== 'u') detalleUnidadSel.value = 'u';
        }
    };

    const renderDetalleTable = () => {
        if (detalleTable) {
            detalleTable.clear();
            detalleItems.forEach((item) => {
                detalleTable.row.add({
                    insumoId: item.insumoId,
                    codigo: item.codigo,
                    descripcion: item.descripcion,
                    cantidad: item.cantidad,
                    unidad: item.unidad,
                    unidadBase: item.unidadBase,
                });
            });
            detalleTable.draw(false);
            if (selectedInsumoId !== null) {
                seleccionarDetallePorId(selectedInsumoId);
            }
            return;
        }

        if (!detalleTableEl) return;
        const tbody = detalleTableEl.querySelector('tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        detalleItems.forEach((item) => {
            const tr = document.createElement('tr');
            tr.className = 'js-detalle-row';
            tr.setAttribute('data-insumo-id', String(item.insumoId));
            tr.setAttribute('data-codigo', item.codigo);
            tr.setAttribute('data-descripcion', item.descripcion);
            tr.setAttribute('data-cantidad', String(item.cantidad));
            tr.setAttribute('data-unidad', item.unidad);
            tr.setAttribute('data-unidad-base', item.unidadBase);
            if (selectedInsumoId !== null && Number(item.insumoId) === Number(selectedInsumoId)) {
                tr.classList.add('table-primary');
            }
            tr.innerHTML = `
                <td>${item.codigo}</td>
                <td>${item.descripcion}<input type="hidden" name="detalle_insumo_id[]" value="${item.insumoId}"></td>
                <td>${item.cantidad}<input type="hidden" name="detalle_cantidad[]" value="${item.cantidad}"></td>
                <td>${item.unidad}<input type="hidden" name="detalle_unidad[]" value="${item.unidad}"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm js-remove-insumo"><i class="bi bi-trash"></i></button></td>
            `;
            tbody.appendChild(tr);
        });
    };

    const upsertInsumo = (id, codigo, descripcion, cantidad, unidad, unidadBase) => {
        const idxSel = selectedInsumoId !== null ? detalleItems.findIndex((it) => Number(it.insumoId) === Number(selectedInsumoId)) : -1;
        const idxExist = detalleItems.findIndex((it) => Number(it.insumoId) === Number(id));
        const payload = {
            insumoId: Number(id),
            codigo: codigo,
            descripcion: descripcion,
            cantidad: Number(cantidad),
            unidad: unidad,
            unidadBase: (unidadBase || 'u').toLowerCase(),
        };

        if (idxSel >= 0) {
            if (idxExist >= 0 && idxExist !== idxSel) {
                showErrorToast('Ese insumo ya existe en otra fila.');
                return false;
            }
            detalleItems[idxSel] = payload;
            selectedInsumoId = null;
            renderDetalleTable();
            return true;
        }
        if (idxExist >= 0) {
            detalleItems[idxExist].cantidad = Number(cantidad);
            detalleItems[idxExist].unidad = unidad;
            detalleItems[idxExist].unidadBase = (unidadBase || 'u').toLowerCase();
            selectedInsumoId = null;
            renderDetalleTable();
            return true;
        }
        detalleItems.push(payload);
        selectedInsumoId = null;
        renderDetalleTable();
        return true;
    };

    if (btnDetalleGuardar) {
        btnDetalleGuardar.addEventListener('click', () => {
            const id = Number(detalleInsumoIdSel?.value || '0');
            const label = (detalleInsumoLabelSel?.value || '').trim();
            const cantidad = (detalleCantidadSel?.value || '').trim();
            const unidad = (detalleUnidadSel?.value || 'u').trim().toLowerCase();
            const unidadBase = (detalleInsumoUnidadBaseSel?.value || 'u').trim().toLowerCase();
            if (id <= 0 || label === '') {
                showErrorToast('Selecciona un insumo en la lupa.');
                return;
            }
            if (!cantidad || Number(cantidad) <= 0) {
                showErrorToast('La cantidad debe ser mayor que cero.');
                return;
            }
            if (!unidadCompatible(unidadBase, unidad)) {
                showErrorToast(`La unidad "${unidad}" no coincide con la unidad base "${unidadBase}" del articulo.`);
                return;
            }
            const sepIdx = label.indexOf(' - ');
            const codigo = sepIdx >= 0 ? label.slice(0, sepIdx) : '';
            const descripcion = sepIdx >= 0 ? label.slice(sepIdx + 3) : label;
            const ok = upsertInsumo(id, codigo, descripcion, cantidad, unidad, unidadBase);
            if (ok) {
                limpiarSeleccionDetalle();
                renderDetalleTable();
            }
        });
    }

    if (btnDetalleLimpiar) {
        btnDetalleLimpiar.addEventListener('click', limpiarSeleccionDetalle);
    }

    document.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.js-remove-insumo');
        if (removeBtn) {
            const row = removeBtn.closest('tr');
            if (row) {
                const id = Number(row.getAttribute('data-insumo-id') || '0');
                detalleItems = detalleItems.filter((it) => Number(it.insumoId) !== id);
                if (selectedInsumoId !== null && selectedInsumoId === id) {
                    limpiarSeleccionDetalle();
                }
                renderDetalleTable();
            }
            return;
        }

        const detalleRow = event.target.closest('.js-detalle-row');
        if (detalleRow && !event.target.closest('.js-remove-insumo')) {
            const rid = Number(detalleRow.getAttribute('data-insumo-id') || '0');
            if (rid > 0) {
                seleccionarDetallePorId(rid);
            }
            return;
        }

        const productoRow = event.target.closest('.js-producto-receta-row');
        if (productoRow) {
            goToProducto(getProductoIdFromRow(productoRow), getProductoUrlFromRow(productoRow));
            return;
        }

        const variantRow = event.target.closest('.js-variant-row');
        if (variantRow) {
            const presentacionId = Number(variantRow.getAttribute('data-presentacion-id') || '0');
            const empaqueId = Number(variantRow.getAttribute('data-empaque-id') || '0');
            if (presentacionSelector && presentacionId > 0) {
                presentacionSelector.value = String(presentacionId);
            }
            if (empaqueSelector && empaqueId > 0) {
                empaqueSelector.value = String(empaqueId);
            }
            tryCargarVariante();
            return;
        }

        const insumoRow = event.target.closest('.js-insumo-row');
        if (!insumoRow) return;
        const insumoId = Number(insumoRow.getAttribute('data-insumo-id') || '0');
        if (insumoId <= 0) return;
        const codigo = insumoRow.getAttribute('data-codigo') || '';
        const descripcion = insumoRow.getAttribute('data-descripcion') || '';
        const unidadBase = (insumoRow.getAttribute('data-unidad-base') || 'u').toLowerCase();
        if (detalleInsumoIdSel) detalleInsumoIdSel.value = String(insumoId);
        if (detalleInsumoUnidadBaseSel) detalleInsumoUnidadBaseSel.value = unidadBase;
        if (detalleInsumoLabelSel) detalleInsumoLabelSel.value = `${codigo} - ${descripcion}`.trim();
        if (detalleCantidadSel && (!detalleCantidadSel.value || Number(detalleCantidadSel.value) <= 0)) {
            detalleCantidadSel.value = '1';
        }
        filterUnidadOptions(unidadBase);
        if (detalleUnidadSel) {
            const unidadExiste = Array.from(detalleUnidadSel.options).some((opt) => String(opt.value || '').toLowerCase() === unidadBase);
            detalleUnidadSel.value = unidadExiste ? unidadBase : (unidadBase === 'u' ? 'u' : 'kg');
        }
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }
    });

    if (detalleTableEl) {
        detalleItems = Array.from(detalleTableEl.querySelectorAll('tbody tr.js-detalle-row')).map((row) => ({
            insumoId: Number(row.getAttribute('data-insumo-id') || '0'),
            codigo: row.getAttribute('data-codigo') || '',
            descripcion: row.getAttribute('data-descripcion') || '',
            cantidad: Number(row.getAttribute('data-cantidad') || '0'),
            unidad: (row.getAttribute('data-unidad') || 'u').toLowerCase(),
            unidadBase: (row.getAttribute('data-unidad-base') || 'u').toLowerCase(),
        })).filter((it) => it.insumoId > 0);
        renderDetalleTable();
    }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
        if (detalleTableEl) {
            const detalleTableJQ = window.jQuery('#detalleInsumosTable');
            detalleTable = detalleTableJQ.DataTable({
                paging: false,
                searching: false,
                info: false,
                ordering: false,
                autoWidth: false,
                language: {
                    zeroRecords: 'Sin insumos agregados',
                },
                columns: [
                    { data: 'codigo' },
                    {
                        data: null,
                        render: (data) => `${data.descripcion}<input type="hidden" name="detalle_insumo_id[]" value="${data.insumoId}">`,
                    },
                    {
                        data: null,
                        render: (data) => `${data.cantidad}<input type="hidden" name="detalle_cantidad[]" value="${data.cantidad}">`,
                    },
                    {
                        data: null,
                        render: (data) => `${data.unidad}<input type="hidden" name="detalle_unidad[]" value="${data.unidad}">`,
                    },
                    {
                        data: null,
                        render: () => '<button type="button" class="btn btn-outline-danger btn-sm js-remove-insumo"><i class="bi bi-trash"></i></button>',
                    },
                ],
                createdRow: (row, data) => {
                    row.classList.add('js-detalle-row');
                    row.setAttribute('data-insumo-id', String(data.insumoId));
                    row.setAttribute('data-codigo', data.codigo);
                    row.setAttribute('data-descripcion', data.descripcion);
                    row.setAttribute('data-cantidad', String(data.cantidad));
                    row.setAttribute('data-unidad', data.unidad);
                    row.setAttribute('data-unidad-base', data.unidadBase);
                },
            });
            renderDetalleTable();
        }

        const productosTableEl = window.jQuery('#productoRecetaPickerTable');
        if (productosTableEl.length && !window.jQuery.fn.dataTable.isDataTable(productosTableEl)) {
            productosTableEl.DataTable({
                pageLength: 10,
                order: [[1, 'asc']],
                autoWidth: false,
                deferRender: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Buscar producto...',
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: { next: 'Sig', previous: 'Ant' },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                },
                columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
            });
        }
        if (productosTableEl.length) {
            productosTableEl.find('tbody').off('click.rpfPick').on('click.rpfPick', 'tr', function onPickProducto() {
                goToProducto(getProductoIdFromRow(this), getProductoUrlFromRow(this));
            });
        }

        const insumosTableEl = window.jQuery('#insumoPickerTable');
        if (insumosTableEl.length && !window.jQuery.fn.dataTable.isDataTable(insumosTableEl)) {
            insumosTableEl.DataTable({
                pageLength: 10,
                order: [[1, 'asc']],
                autoWidth: false,
                deferRender: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Buscar insumo...',
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: { next: 'Sig', previous: 'Ant' },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                },
                columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
            });
        }
    }

    const tryCargarVariante = () => {
        const productoId = Number(productoIdHidden?.value || '0');
        const presentacionId = Number(presentacionSelector?.value || '0');
        const empaqueId = Number(empaqueSelector?.value || '0');
        if (presentacionIdHidden) presentacionIdHidden.value = presentacionId > 0 ? String(presentacionId) : '';
        if (empaqueIdHidden) empaqueIdHidden.value = empaqueId > 0 ? String(empaqueId) : '';
        syncDetalleLockState();
        if (productoId > 0 && presentacionId > 0 && empaqueId > 0) {
            const key = `${presentacionId}-${empaqueId}`;
            const meta = variantesRecetaMap[key] || null;
            if (meta && Number(meta.receta_id || 0) > 0) {
                sessionStorage.setItem('rpf_notice', JSON.stringify({
                    type: 'info',
                    title: 'Receta Producto Final',
                    message: `Editando datos de receta: ${meta.presentacion} / ${meta.empaque}.`,
                }));
            } else {
                sessionStorage.setItem('rpf_notice', JSON.stringify({
                    type: 'success',
                    title: 'Receta Producto Final',
                    message: 'Creando receta para la variante seleccionada.',
                }));
            }
            window.location.href = '/mantenimientos/organizacion/recetas-producto-final?producto_id='
                + encodeURIComponent(String(productoId))
                + '&presentacion_id=' + encodeURIComponent(String(presentacionId))
                + '&empaque_id=' + encodeURIComponent(String(empaqueId));
        }
    };

    if (presentacionSelector) {
        presentacionSelector.addEventListener('change', tryCargarVariante);
    }
    if (empaqueSelector) {
        empaqueSelector.addEventListener('change', tryCargarVariante);
    }

    const pendingNotice = sessionStorage.getItem('rpf_notice');
    if (pendingNotice) {
        try {
            const data = JSON.parse(pendingNotice);
            showToast(String(data.message || ''), String(data.type || 'info'), String(data.title || 'Receta Producto Final'));
        } catch (_err) {}
        sessionStorage.removeItem('rpf_notice');
    } else if (recetaIdActual > 0) {
        const pTxt = presentacionSelector?.options[presentacionSelector.selectedIndex]?.text || '';
        const eTxt = empaqueSelector?.options[empaqueSelector.selectedIndex]?.text || '';
        const suffix = (pTxt && eTxt) ? ` (${pTxt} / ${eTxt})` : '';
        showToast(`Editando datos de receta${suffix}.`, 'info', 'Receta Producto Final');
    }
    syncDetalleLockState();
})();
</script>
