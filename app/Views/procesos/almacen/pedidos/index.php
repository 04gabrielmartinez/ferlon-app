<?php
$fechaActual = date('Y-m-d');
$clienteMap = is_array($clienteMap ?? null) ? $clienteMap : [];
$variantes = is_array($variantes ?? null) ? $variantes : [];
$pedidos = is_array($pedidos ?? null) ? $pedidos : [];
$canCrear = (bool) ($canCrear ?? false);
$localidadesMap = is_array($localidadesMap ?? null) ? $localidadesMap : [];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Procesos / Almacen / Pedidos</h2>
            <small class="text-muted">Pedidos de vendedores con variantes y precios por receta.</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Pedido</div>
                <form method="post" action="/procesos/almacen/pedidos" id="pedidoForm" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="pedido_id" id="pedido_id" value="">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Codigo Pedido</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="codigo_pedido" class="form-control" value="Se asigna al guardar" readonly>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#pedidoPickerModal" aria-label="Buscar pedido">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Fecha</label>
                            <input type="date" name="fecha" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaActual) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Empleado</label>
                            <input type="text" name="empleado_label" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($empleadoNombre ?? '')) ?>" readonly>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Cliente</label>
                            <div class="input-group input-group-sm">
                                <input type="hidden" name="cliente_id" id="pedido_cliente_id" value="">
                                <input type="text" id="pedido_cliente_label" class="form-control" readonly placeholder="Buscar...">
                                <button type="button" class="btn btn-outline-secondary js-open-client-picker" data-client-target="#pedido_cliente_id" aria-label="Buscar cliente">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Telefono</label>
                            <input type="text" id="pedido_cliente_tel" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Cedula / RNC</label>
                            <input type="text" id="pedido_cliente_rnc" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Localidad</label>
                            <select name="localidad_id" id="pedido_localidad_id" class="form-select form-select-sm" disabled>
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">No. Orden</label>
                            <input type="text" name="orden_no" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label small mb-1">Comentario</label>
                            <input type="text" name="comentario" class="form-control form-control-sm" placeholder="Notas del pedido">
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-title mb-0">Detalle de Productos</div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#pedidoProductoModal" <?= ($canCrear || ($canEditar ?? false)) ? '' : 'disabled' ?>>
                                Agregar producto
                            </button>
                        </div>
                        <div class="table-responsive pedido-scroll">
                            <table class="table table-sm table-striped align-middle" id="pedidoDetalleTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Articulo</th>
                                        <th>Variante</th>
                                        <th style="width: 120px;">Cantidad</th>
                                        <th style="width: 140px;">Precio</th>
                                        <th style="width: 120px;">Desc %</th>
                                        <th style="width: 140px;">Total</th>
                                        <th style="width: 90px;">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Agrega un articulo para el pedido.</td>
                                        <td class="text-muted">&nbsp;</td>
                                        <td class="text-muted">&nbsp;</td>
                                        <td class="text-muted">&nbsp;</td>
                                        <td class="text-muted">&nbsp;</td>
                                        <td class="text-muted">&nbsp;</td>
                                        <td class="text-muted">&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3" <?= ($canCrear || ($canEditar ?? false)) ? '' : 'disabled' ?>>Guardar</button>
                        <a href="#" id="pedidoPrintBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3 disabled" target="_blank" rel="noopener">Imprimir</a>
                        <button type="button" id="pedidoDeleteBtn" class="btn btn-outline-danger btn-sm rounded-pill px-3 disabled">Eliminar</button>
                        <a href="/procesos/almacen/pedidos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<form method="post" action="/procesos/almacen/pedidos/eliminar" id="pedidoDeleteForm" class="d-none">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
    <input type="hidden" name="pedido_id" id="pedido_delete_id" value="">
</form>

<?php include dirname(__DIR__, 3) . '/sistema/components/client-picker-modal.php'; ?>

<div class="modal fade employee-picker-modal" id="pedidoPickerModal" tabindex="-1" aria-labelledby="pedidoPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="pedidoPickerModalLabel">
                        <i class="bi bi-card-checklist"></i>
                        <span>Buscar pedido</span>
                    </h5>
                    <small class="text-muted">Click sobre una fila para ver el codigo</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="pedidoPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Codigo</th>
                            <th>Cliente</th>
                            <th>Empleado</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pedidos === []): ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $p): ?>
                                <tr class="js-pedido-row"
                                    data-id="<?= (int) ($p['id'] ?? 0) ?>"
                                    data-codigo="<?= htmlspecialchars((string) ($p['codigo_pedido'] ?? '')) ?>"
                                    style="cursor:pointer;">
                                    <td><?= htmlspecialchars((string) ($p['fecha'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($p['codigo_pedido'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($p['cliente_nombre'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($p['empleado_nombre'] ?? '')) ?></td>
                                    <td><?= number_format((float) ($p['total'] ?? 0), 2, '.', ',') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="pedidoProductoModal" tabindex="-1" aria-labelledby="pedidoProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="pedidoProductoModalLabel">
                        <i class="bi bi-box-seam"></i>
                        <span>Seleccionar variante</span>
                    </h5>
                    <small class="text-muted">Solo variantes con receta producto final y precio de venta</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="pedidoProductoTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Descripcion</th>
                            <th>Presentacion</th>
                            <th>Empaque</th>
                            <th>Precio</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variantes as $v): ?>
                            <tr class="js-pedido-variante-row"
                                data-articulo-id="<?= (int) ($v['articulo_id'] ?? 0) ?>"
                                data-presentacion-id="<?= (int) ($v['presentacion_id'] ?? 0) ?>"
                                data-empaque-id="<?= (int) ($v['empaque_id'] ?? 0) ?>"
                                data-codigo="<?= htmlspecialchars((string) ($v['articulo_codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-descripcion="<?= htmlspecialchars((string) ($v['articulo_descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-presentacion="<?= htmlspecialchars((string) ($v['presentacion_descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-empaque="<?= htmlspecialchars((string) ($v['empaque_descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-precio="<?= htmlspecialchars((string) ($v['precio_venta'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                data-stock="<?= htmlspecialchars((string) ($v['stock_actual'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                style="cursor:pointer;">
                                <td><?= htmlspecialchars((string) ($v['articulo_codigo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($v['articulo_descripcion'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($v['presentacion_descripcion'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($v['empaque_descripcion'] ?? '')) ?></td>
                                <td><?= number_format((float) ($v['precio_venta'] ?? 0), 2, '.', ',') ?></td>
                                <td><?= number_format((float) ($v['stock_actual'] ?? 0), 2, '.', ',') ?> u</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pedidoResumenModal" tabindex="-1" aria-labelledby="pedidoResumenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow pedido-recibo">
            <div class="modal-header border-0 pedido-recibo-header">
                <div>
                    <div class="pedido-recibo-title" id="pedidoResumenModalLabel">Resumen del Pedido</div>
                    <small class="pedido-recibo-subtitle">Confirma antes de guardar</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pedido-recibo-body">
                <div id="pedidoResumenBody"></div>
            </div>
            <div class="modal-footer border-0 pedido-recibo-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Editar</button>
                <button type="button" id="pedidoConfirmBtn" class="btn btn-primary btn-sm rounded-pill px-3" disabled>Confirmar y Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const variantesTable = document.getElementById('pedidoProductoTable');
    const detalleBody = document.querySelector('#pedidoDetalleTable tbody');
    const pedidoForm = document.getElementById('pedidoForm');
    const codigoPedidoInput = document.getElementById('codigo_pedido');
    const pedidoIdInput = document.getElementById('pedido_id');
    const printBtn = document.getElementById('pedidoPrintBtn');
    const deleteBtn = document.getElementById('pedidoDeleteBtn');
    const deleteFormId = document.getElementById('pedido_delete_id');
    const clienteIdInput = document.getElementById('pedido_cliente_id');
    const clienteLabelInput = document.getElementById('pedido_cliente_label');
    const clienteTelInput = document.getElementById('pedido_cliente_tel');
    const clienteRncInput = document.getElementById('pedido_cliente_rnc');
    const resumenModalEl = document.getElementById('pedidoResumenModal');
    const resumenBody = document.getElementById('pedidoResumenBody');
    const confirmBtn = document.getElementById('pedidoConfirmBtn');

    const localidadesMap = <?= json_encode($localidadesMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let detalleItems = [];
    let allowSubmit = false;
    let isEditMode = false;

    const resetForm = () => {
        if (pedidoIdInput) pedidoIdInput.value = '';
        if (codigoPedidoInput) codigoPedidoInput.value = 'Se asigna al guardar';
        if (clienteIdInput) clienteIdInput.value = '';
        if (clienteLabelInput) clienteLabelInput.value = '';
        if (clienteTelInput) clienteTelInput.value = '';
        if (clienteRncInput) clienteRncInput.value = '';
        const fechaInput = pedidoForm?.querySelector('input[name=\"fecha\"]');
        if (fechaInput) fechaInput.value = '<?= htmlspecialchars($fechaActual) ?>';
        const ordenInput = pedidoForm?.querySelector('input[name=\"orden_no\"]');
        if (ordenInput) ordenInput.value = '';
        const comentarioInput = pedidoForm?.querySelector('input[name=\"comentario\"]');
        if (comentarioInput) comentarioInput.value = '';
        const locSelect = document.getElementById('pedido_localidad_id');
        if (locSelect) {
            locSelect.innerHTML = '<option value=\"\">Seleccione</option>';
            locSelect.disabled = true;
        }
        detalleItems = [];
        renderDetalle();
        setActionButtons(false);
    };

    const setActionButtons = (enabled) => {
        if (printBtn) {
            if (enabled) {
                printBtn.classList.remove('disabled');
                printBtn.setAttribute('href', `/procesos/almacen/pedidos/imprimir?id=${pedidoIdInput?.value || ''}`);
            } else {
                printBtn.classList.add('disabled');
                printBtn.removeAttribute('href');
            }
        }
        if (deleteBtn) {
            if (enabled) {
                deleteBtn.classList.remove('disabled');
            } else {
                deleteBtn.classList.add('disabled');
            }
        }
    };

    const syncLineaTotal = (row, item) => {
        if (!row || !item) return;
        const bruto = Number(item.cantidad) * Number(item.precio);
        const desc = bruto * (Number(item.descuentoPct || 0) / 100);
        const total = (bruto - desc).toFixed(2);
        const totalCell = row.querySelector('.js-line-total');
        if (totalCell) totalCell.textContent = total;
    };
    const resetDetallePlaceholder = () => {
        if (detalleItems.length > 0) return;
        detalleBody.innerHTML = '<tr><td class="text-muted">Agrega un articulo para el pedido.</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td></tr>';
    };

    const renderDetalle = () => {
        if (detalleItems.length === 0) {
            resetDetallePlaceholder();
            return;
        }
        detalleBody.innerHTML = detalleItems.map((d) => {
            const bruto = Number(d.cantidad || 0) * Number(d.precio || 0);
            const desc = bruto * (Number(d.descuentoPct || 0) / 100);
            const total = (bruto - desc).toFixed(2);
            return `<tr data-key="${d.key}">
                <td>${d.codigo} - ${d.descripcion}
                    <input type="hidden" name="detalle_articulo_id[]" value="${d.articuloId}">
                    <input type="hidden" name="detalle_codigo[]" value="${d.codigo}">
                    <input type="hidden" name="detalle_descripcion[]" value="${d.descripcion}">
                </td>
                <td>${d.presentacion} / ${d.empaque}
                    <input type="hidden" name="detalle_presentacion_id[]" value="${d.presentacionId}">
                    <input type="hidden" name="detalle_empaque_id[]" value="${d.empaqueId}">
                    <input type="hidden" name="detalle_presentacion_desc[]" value="${d.presentacion}">
                    <input type="hidden" name="detalle_empaque_desc[]" value="${d.empaque}">
                </td>
                <td>
                    <input type="number" min="1" step="1" class="form-control form-control-sm js-detalle-cantidad" value="${d.cantidad}">
                    <input type="hidden" name="detalle_cantidad[]" value="${d.cantidad}">
                </td>
                <td>${Number(d.precio).toFixed(2)}<input type="hidden" name="detalle_precio[]" value="${d.precio}"></td>
                <td>
                    <input type="number" min="0" step="0.01" class="form-control form-control-sm js-detalle-descuento" value="${Number(d.descuentoPct || 0) ? Number(d.descuentoPct || 0).toFixed(2) : ''}">
                    <input type="hidden" name="detalle_descuento_pct[]" value="${Number(d.descuentoPct || 0).toFixed(2)}">
                </td>
                <td class="fw-semibold js-line-total">${total}</td>
                <td><button type="button" class="btn btn-outline-danger btn-sm js-remove-detalle">Quitar</button></td>
            </tr>`;
        }).join('');
    };

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-pedido-variante-row');
        if (!row) return;
        const articuloId = Number(row.getAttribute('data-articulo-id') || 0);
        const presentacionId = Number(row.getAttribute('data-presentacion-id') || 0);
        const empaqueId = Number(row.getAttribute('data-empaque-id') || 0);
        const codigo = row.getAttribute('data-codigo') || '';
        const descripcion = row.getAttribute('data-descripcion') || '';
        const presentacion = row.getAttribute('data-presentacion') || '';
        const empaque = row.getAttribute('data-empaque') || '';
        const precio = Number(row.getAttribute('data-precio') || 0);

        const key = `${articuloId}-${presentacionId}-${empaqueId}`;
        const existing = detalleItems.find((d) => d.key === key);
        if (existing) {
            existing.cantidad += 1;
        } else {
            detalleItems.push({
                key,
                articuloId,
                presentacionId,
                empaqueId,
                codigo,
                descripcion,
                presentacion,
                empaque,
                cantidad: 1,
                precio,
                descuentoPct: 0,
            });
        }
        renderDetalle();
        if (window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(document.getElementById('pedidoProductoModal')).hide();
        }
    });

    detalleBody.addEventListener('click', (event) => {
        const btn = event.target.closest('.js-remove-detalle');
        if (!btn) return;
        const row = btn.closest('tr');
        const key = row?.getAttribute('data-key') || '';
        detalleItems = detalleItems.filter((d) => d.key !== key);
        renderDetalle();
    });

    detalleBody.addEventListener('input', (event) => {
        const qtyInput = event.target.closest('.js-detalle-cantidad');
        const descInput = event.target.closest('.js-detalle-descuento');
        const input = qtyInput || descInput;
        if (!input) return;
        const row = input.closest('tr');
        const key = row?.getAttribute('data-key') || '';
        const item = detalleItems.find((d) => d.key === key);
        if (!item) return;

        if (qtyInput) {
            const val = parseInt(qtyInput.value || '0', 10);
            item.cantidad = Number.isFinite(val) && val > 0 ? val : 1;
            const hiddenQty = row.querySelector('input[name="detalle_cantidad[]"]');
            if (hiddenQty) hiddenQty.value = String(item.cantidad);
        }
        if (descInput) {
            let val = parseFloat(descInput.value || '0');
            if (!Number.isFinite(val) || val < 0) val = 0;
            if (val > 100) val = 100;
            item.descuentoPct = val;
            descInput.value = val.toFixed(2);
            const hiddenDesc = row.querySelector('input[name="detalle_descuento_pct[]"]');
            if (hiddenDesc) hiddenDesc.value = val.toFixed(2);
        }

        syncLineaTotal(row, item);
    });

    pedidoForm?.addEventListener('submit', (event) => {
        if (allowSubmit) {
            return;
        }
        event.preventDefault();
        if (!clienteIdInput || Number(clienteIdInput.value || 0) <= 0) {
            alert('Selecciona un cliente.');
            return;
        }
        if (detalleItems.length === 0) {
            alert('Agrega al menos un articulo al pedido.');
            return;
        }

        const total = detalleItems.reduce((acc, d) => {
            const bruto = d.cantidad * d.precio;
            const desc = bruto * (Number(d.descuentoPct || 0) / 100);
            return acc + (bruto - desc);
        }, 0);
        const resumenHtml = `
            <div class="pedido-recibo-meta">
                <div class="pedido-recibo-chip">
                    <span>Cliente</span>
                    <strong>${clienteLabelInput?.value || ''}</strong>
                </div>
                <div class="pedido-recibo-chip">
                    <span>Telefono</span>
                    <strong>${clienteTelInput?.value || '-'}</strong>
                </div>
                <div class="pedido-recibo-chip">
                    <span>RNC / Cedula</span>
                    <strong>${clienteRncInput?.value || '-'}</strong>
                </div>
            </div>
            <div class="pedido-recibo-total">
                <span>Total</span>
                <strong>${total.toFixed(2)}</strong>
            </div>
            <div class="pedido-recibo-list">
                ${detalleItems.map((d) => `
                    <div class="pedido-recibo-item">
                        <div class="pedido-recibo-item-main">
                            <div class="pedido-recibo-item-title">${d.codigo} - ${d.descripcion}</div>
                            <div class="pedido-recibo-item-sub">${d.presentacion} / ${d.empaque}</div>
                        </div>
                        <div class="pedido-recibo-item-meta">
                            <div><span>Cant</span><strong>${d.cantidad}</strong></div>
                            <div><span>Precio</span><strong>${d.precio.toFixed(2)}</strong></div>
                            <div><span>Desc %</span><strong>${Number(d.descuentoPct || 0).toFixed(2)}</strong></div>
                            <div><span>Total</span><strong>${(d.cantidad * d.precio - (d.cantidad * d.precio * (Number(d.descuentoPct || 0) / 100))).toFixed(2)}</strong></div>
                        </div>
                    </div>
                `).join('')}
            </div>`;
        if (resumenBody) resumenBody.innerHTML = resumenHtml;
        if (confirmBtn) {
            let seconds = 10;
            confirmBtn.disabled = true;
            confirmBtn.textContent = `Confirmar y Guardar (${seconds}s)`;
            const interval = setInterval(() => {
                seconds -= 1;
                if (seconds <= 0) {
                    clearInterval(interval);
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Confirmar y Guardar';
                    return;
                }
                confirmBtn.textContent = `Confirmar y Guardar (${seconds}s)`;
            }, 1000);
        }
        if (window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(resumenModalEl).show();
        }
    });

    confirmBtn?.addEventListener('click', () => {
        allowSubmit = true;
        pedidoForm?.submit();
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-client-row');
        if (!row) return;
        if (clienteIdInput) clienteIdInput.value = row.getAttribute('data-client-id') || '';
        if (clienteLabelInput) clienteLabelInput.value = row.getAttribute('data-client-razon') || '';
        if (clienteTelInput) clienteTelInput.value = row.getAttribute('data-client-telefono') || '';
        if (clienteRncInput) clienteRncInput.value = row.getAttribute('data-client-rnc') || '';
        const locSelect = document.getElementById('pedido_localidad_id');
        if (locSelect) {
            const cid = Number(row.getAttribute('data-client-id') || 0);
            const locs = localidadesMap[cid] || [];
            locSelect.innerHTML = '<option value=\"\">Seleccione</option>';
            if (locs.length > 0) {
                locs.forEach((l) => {
                    const opt = document.createElement('option');
                    opt.value = String(l.id || '');
                    opt.textContent = String(l.nombre || '');
                    locSelect.appendChild(opt);
                });
                locSelect.disabled = false;
                if (locs.length === 1) {
                    locSelect.value = String(locs[0].id || '');
                }
            } else {
                locSelect.disabled = true;
            }
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-pedido-row');
        if (!row) return;
        const id = Number(row.getAttribute('data-id') || 0);
        if (!id) return;
        fetch(`/procesos/almacen/pedidos/detalle?id=${id}`)
            .then((res) => res.json())
            .then((payload) => {
                if (!payload || !payload.ok) {
                    alert(payload?.message || 'No se pudo cargar el pedido.');
                    return;
                }
                const pedido = payload.pedido || {};
                isEditMode = true;
                if (pedidoIdInput) pedidoIdInput.value = String(pedido.id || '');
                if (codigoPedidoInput) codigoPedidoInput.value = pedido.codigo_pedido || '';
                const fechaInput = pedidoForm?.querySelector('input[name=\"fecha\"]');
                if (fechaInput) fechaInput.value = pedido.fecha || '<?= htmlspecialchars($fechaActual) ?>';
                if (clienteIdInput) clienteIdInput.value = String(pedido.cliente_id || '');
                if (clienteLabelInput) clienteLabelInput.value = pedido.cliente_nombre || '';
                if (clienteTelInput) clienteTelInput.value = pedido.cliente_telefono || '';
                if (clienteRncInput) clienteRncInput.value = pedido.cliente_rnc || '';
                const ordenInput = pedidoForm?.querySelector('input[name=\"orden_no\"]');
                if (ordenInput) ordenInput.value = pedido.orden_no || '';
                const comentarioInput = pedidoForm?.querySelector('input[name=\"comentario\"]');
                if (comentarioInput) comentarioInput.value = pedido.comentario || '';

                const locSelect = document.getElementById('pedido_localidad_id');
                if (locSelect) {
                    const cid = Number(pedido.cliente_id || 0);
                    const locs = localidadesMap[cid] || [];
                    locSelect.innerHTML = '<option value=\"\">Seleccione</option>';
                    if (locs.length > 0) {
                        locs.forEach((l) => {
                            const opt = document.createElement('option');
                            opt.value = String(l.id || '');
                            opt.textContent = String(l.nombre || '');
                            locSelect.appendChild(opt);
                        });
                        locSelect.disabled = false;
                        if (pedido.localidad_id) {
                            locSelect.value = String(pedido.localidad_id);
                        }
                    } else {
                        locSelect.disabled = true;
                    }
                }

                detalleItems = (pedido.detalles || []).map((d) => ({
                    key: `${d.articulo_id}-${d.presentacion_id}-${d.empaque_id}`,
                    articuloId: Number(d.articulo_id || 0),
                    presentacionId: Number(d.presentacion_id || 0),
                    empaqueId: Number(d.empaque_id || 0),
                    codigo: d.articulo_codigo || '',
                    descripcion: d.articulo_descripcion || '',
                    presentacion: d.presentacion_descripcion || '',
                    empaque: d.empaque_descripcion || '',
                    cantidad: Number(d.cantidad || 1),
                    precio: Number(d.precio || 0),
                    descuentoPct: Number(d.descuento_pct || 0),
                }));
                renderDetalle();
                setActionButtons(true);

                if (window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(document.getElementById('pedidoPickerModal')).hide();
                }
            })
            .catch(() => alert('No se pudo cargar el pedido.'));
    });

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
        const table = window.jQuery('#pedidoProductoTable');
        if (table.length && !window.jQuery.fn.dataTable.isDataTable(table)) {
            table.DataTable({
                pageLength: 10,
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
                    infoFiltered: '(filtrado de _MAX_)',
                },
                columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
            });
        }
        const pedidosTable = window.jQuery('#pedidoPickerTable');
        if (pedidosTable.length && !window.jQuery.fn.dataTable.isDataTable(pedidosTable)) {
            pedidosTable.DataTable({
                pageLength: 10,
                autoWidth: false,
                deferRender: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Buscar pedido...',
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: { next: 'Sig', previous: 'Ant' },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                    infoFiltered: '(filtrado de _MAX_)',
                },
                columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
            });
        }
    }

    deleteBtn?.addEventListener('click', () => {
        if (deleteBtn.classList.contains('disabled')) return;
        const pedidoId = Number(pedidoIdInput?.value || 0);
        if (!pedidoId) return;
        if (!confirm('¿Eliminar este pedido?')) return;
        if (deleteFormId) deleteFormId.value = String(pedidoId);
        document.getElementById('pedidoDeleteForm')?.submit();
    });

    resetDetallePlaceholder();
})();
</script>
