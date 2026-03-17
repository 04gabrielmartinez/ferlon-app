<?php
$fechaActual = date('Y-m-d');
$clienteMap = is_array($clienteMap ?? null) ? $clienteMap : [];
$variantes = is_array($variantes ?? null) ? $variantes : [];
$cotizaciones = is_array($cotizaciones ?? null) ? $cotizaciones : [];
$canCrear = (bool) ($canCrear ?? false);
$localidadesMap = is_array($localidadesMap ?? null) ? $localidadesMap : [];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Procesos / Clientes / Cotizaciones</h2>
            <small class="text-muted">Cotizaciones con variantes, condiciones y control de vigencia.</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Cotizacion</div>
                <form method="post" action="/procesos/clientes/cotizaciones" id="cotizacionForm" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="cotizacion_id" id="cotizacion_id" value="">

                    <div class="row g-3 align-items-start">
                        <div class="col-12 col-xl-9">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Codigo Cotizacion</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="codigo_cotizacion" class="form-control" value="Se asigna al guardar" readonly>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#cotizacionPickerModal" aria-label="Buscar cotizacion">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Fecha</label>
                                    <input type="date" name="fecha" id="cotizacion_fecha" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaActual) ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Empleado</label>
                                    <input type="text" name="empleado_label" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($empleadoNombre ?? '')) ?>" readonly>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Cliente</label>
                                    <div class="input-group input-group-sm">
                                        <input type="hidden" name="cliente_id" id="cot_cliente_id" value="">
                                        <input type="text" id="cot_cliente_label" class="form-control" readonly placeholder="Buscar...">
                                        <button type="button" class="btn btn-outline-secondary js-open-client-picker" data-client-target="#cot_cliente_id" aria-label="Buscar cliente">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Telefono</label>
                                    <input type="text" id="cot_cliente_tel" class="form-control form-control-sm" readonly>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Cedula / RNC</label>
                                    <input type="text" id="cot_cliente_rnc" class="form-control form-control-sm" readonly>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Localidad</label>
                                    <select name="localidad_id" id="cot_localidad_id" class="form-select form-select-sm" disabled>
                                        <option value="">Seleccione</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Moneda</label>
                                    <select name="moneda" id="cot_moneda" class="form-select form-select-sm">
                                        <option value="DOP" selected>DOP</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <select name="estado" id="cot_estado" class="form-select form-select-sm">
                                        <option value="borrador" selected>Borrador</option>
                                        <option value="enviada">Enviada</option>
                                        <option value="aprobada">Aprobada</option>
                                        <option value="rechazada">Rechazada</option>
                                        <option value="vencida">Vencida</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small mb-1">Validez (dias)</label>
                                    <input type="number" min="0" step="1" name="validez_dias" id="cot_validez" class="form-control form-control-sm" value="15">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small mb-1">Fecha vencimiento</label>
                                    <input type="date" name="fecha_vencimiento" id="cot_fecha_venc" class="form-control form-control-sm" value="">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Comentario</label>
                                    <input type="text" name="comentario" id="cot_comentario" class="form-control form-control-sm" placeholder="Notas internas">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Condiciones comerciales</label>
                                    <textarea name="condiciones" id="cot_condiciones" class="form-control form-control-sm" rows="2" placeholder="Forma de pago, entrega, garantias, vigencia, etc."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-3">
        <div class="border rounded-4 p-3 bg-light-subtle cotizacion-totales-card">
            <div class="small text-muted mb-2">Totales</div>
            <div class="row g-2">
                <div class="col-12 col-sm-6 col-xl-6">
                    <label class="form-label small mb-1">Desc % general</label>
                    <input type="text" inputmode="decimal" pattern="[0-9]*[.]?[0-9]*" id="cot_desc_general_pct" name="descuento_general_pct" class="form-control form-control-sm" value="0">
                </div>
                <div class="col-12 col-sm-6 col-xl-6">
                    <label class="form-label small mb-1">Subtotal</label>
                    <input type="text" id="cot_subtotal" class="form-control form-control-sm" readonly>
                </div>
                <input type="hidden" id="cot_impuesto_pct" name="impuesto_pct" value="0">
                <div class="col-12 col-sm-6 col-xl-6">
                    <label class="form-label small mb-1">Valor desc</label>
                    <input type="text" id="cot_desc_total" class="form-control form-control-sm" readonly>
                </div>
                <div class="col-12 col-sm-6 col-xl-6">
                    <label class="form-label small mb-1">Impuesto</label>
                    <input type="text" id="cot_impuesto" class="form-control form-control-sm" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Total</label>
                    <input type="text" id="cot_total" class="form-control form-control-sm fw-semibold cotizacion-total-input" readonly>
                </div>
            </div>
        </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-title mb-0">Detalle de Productos</div>
                            <button type="button" id="cotizacionAddProductoBtn" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#cotizacionProductoModal" <?= ($canCrear || ($canEditar ?? false)) ? '' : 'disabled' ?>>
                                Agregar producto
                            </button>
                        </div>
                        <div class="table-responsive pedido-scroll">
                            <table class="table table-sm table-striped align-middle cotizacion-detalle-table" id="cotizacionDetalleTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Articulo</th>
                                        <th>Variante</th>
                                        <th style="width: 90px;">Cantidad</th>
                                        <th style="width: 110px;">Precio</th>
                                        <th style="width: 90px;">Desc %</th>
                                        <th style="width: 110px;">Total</th>
                                        <th style="width: 80px;">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Agrega un articulo para la cotizacion.</td>
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
                        <a href="#" id="cotizacionPrintBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3 disabled" target="_blank" rel="noopener">Imprimir</a>
                        <button type="button" id="cotizacionDeleteBtn" class="btn btn-outline-danger btn-sm rounded-pill px-3 disabled">Eliminar</button>
                        <a href="/procesos/clientes/cotizaciones" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<form method="post" action="/procesos/clientes/cotizaciones/eliminar" id="cotizacionDeleteForm" class="d-none">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
    <input type="hidden" name="cotizacion_id" id="cotizacion_delete_id" value="">
</form>

<?php include dirname(__DIR__, 4) . '/sistema/components/client-picker-modal.php'; ?>

<div class="modal fade employee-picker-modal" id="cotizacionPickerModal" tabindex="-1" aria-labelledby="cotizacionPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="cotizacionPickerModalLabel">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Buscar cotizacion</span>
                    </h5>
                    <small class="text-muted">Click sobre una fila para ver el codigo</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="cotizacionPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Codigo</th>
                            <th>Cliente</th>
                            <th>Empleado</th>
                            <th>Estado</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cotizaciones === []): ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cotizaciones as $c): ?>
                                <tr class="js-cotizacion-row"
                                    data-id="<?= (int) ($c['id'] ?? 0) ?>"
                                    data-codigo="<?= htmlspecialchars((string) ($c['codigo_cotizacion'] ?? '')) ?>"
                                    style="cursor:pointer;">
                                    <td><?= htmlspecialchars((string) ($c['fecha'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($c['codigo_cotizacion'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($c['cliente_nombre'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($c['empleado_nombre'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($c['estado'] ?? '')) ?></td>
                                    <td><?= number_format((float) ($c['total'] ?? 0), 2, '.', ',') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="cotizacionProductoModal" tabindex="-1" aria-labelledby="cotizacionProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="cotizacionProductoModalLabel">
                        <i class="bi bi-box-seam"></i>
                        <span>Seleccionar variante</span>
                    </h5>
                    <small class="text-muted">Solo variantes con receta producto final y precio de venta</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="cotizacionProductoTable" class="table table-hover align-middle w-100 employee-picker-table">
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
                            <tr class="js-cotizacion-variante-row"
                                data-articulo-id="<?= (int) ($v['articulo_id'] ?? 0) ?>"
                                data-presentacion-id="<?= (int) ($v['presentacion_id'] ?? 0) ?>"
                                data-empaque-id="<?= (int) ($v['empaque_id'] ?? 0) ?>"
                                data-codigo="<?= htmlspecialchars((string) ($v['articulo_codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-descripcion="<?= htmlspecialchars((string) ($v['articulo_descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-presentacion="<?= htmlspecialchars((string) ($v['presentacion_descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-empaque="<?= htmlspecialchars((string) ($v['empaque_descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-precio="<?= htmlspecialchars((string) ($v['precio_venta'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                data-impuestos="<?= htmlspecialchars((string) ($v['impuestos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
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

<script>
(() => {
    const variantesTable = document.getElementById('cotizacionProductoTable');
    const detalleBody = document.querySelector('#cotizacionDetalleTable tbody');
    const cotizacionForm = document.getElementById('cotizacionForm');
    const codigoCotizacionInput = document.getElementById('codigo_cotizacion');
    const cotizacionIdInput = document.getElementById('cotizacion_id');
    const deleteBtn = document.getElementById('cotizacionDeleteBtn');
    const printBtn = document.getElementById('cotizacionPrintBtn');
    const deleteFormId = document.getElementById('cotizacion_delete_id');
    const clienteIdInput = document.getElementById('cot_cliente_id');
    const clienteLabelInput = document.getElementById('cot_cliente_label');
    const clienteTelInput = document.getElementById('cot_cliente_tel');
    const clienteRncInput = document.getElementById('cot_cliente_rnc');
    const fechaInput = document.getElementById('cotizacion_fecha');
    const validezInput = document.getElementById('cot_validez');
    const vencInput = document.getElementById('cot_fecha_venc');
    const estadoSelect = document.getElementById('cot_estado');
    const monedaSelect = document.getElementById('cot_moneda');
    const comentarioInput = document.getElementById('cot_comentario');
    const condicionesInput = document.getElementById('cot_condiciones');
    const descGeneralPctInput = document.getElementById('cot_desc_general_pct');
    const impuestoPctInput = document.getElementById('cot_impuesto_pct');
    const subtotalInput = document.getElementById('cot_subtotal');
    const descLineasInput = document.getElementById('cot_desc_lineas');
    const descGeneralInput = document.getElementById('cot_desc_general');
    const descTotalInput = document.getElementById('cot_desc_total');
    const impuestoInput = document.getElementById('cot_impuesto');
    const totalInput = document.getElementById('cot_total');
    const addProductoBtn = document.getElementById('cotizacionAddProductoBtn');

    const localidadesMap = <?= json_encode($localidadesMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const clienteMap = <?= json_encode($clienteMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const canEditarPrecio = <?= json_encode((bool) ($canEditarPrecio ?? false)) ?>;
    let detalleItems = [];
    let allowSubmit = false;
    let clienteDescuentoDefault = 0;
    let clienteItbisActivo = true;
    let lastMoneda = monedaSelect?.value || 'DOP';
    const showToast = (message, type = 'warning', title = 'Cotizaciones') => {
        const text = String(message || '').trim();
        if (!text) return;
        if (window.AppToast && typeof window.AppToast.show === 'function') {
            window.AppToast.show({ message: text, type, title });
            return;
        }
        alert(text);
    };
    const formatMoney = (n) => {
        const val = Number(n);
        if (!Number.isFinite(val)) return '0.00';
        return val.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    const toDate = (str) => {
        if (!str) return null;
        const parts = str.split('-').map((p) => parseInt(p, 10));
        if (parts.length !== 3 || parts.some((p) => Number.isNaN(p))) return null;
        return new Date(parts[0], parts[1] - 1, parts[2]);
    };

    const formatDate = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    const syncFechaVencimiento = () => {
        const base = toDate(fechaInput?.value || '');
        const validez = parseInt(validezInput?.value || '0', 10);
        if (!base || Number.isNaN(validez) || validez < 0) return;
        const d = new Date(base.getTime());
        d.setDate(d.getDate() + validez);
        if (vencInput) vencInput.value = formatDate(d);
    };

    const resetForm = () => {
        if (cotizacionIdInput) cotizacionIdInput.value = '';
        if (codigoCotizacionInput) codigoCotizacionInput.value = 'Se asigna al guardar';
        if (clienteIdInput) clienteIdInput.value = '';
        if (clienteLabelInput) clienteLabelInput.value = '';
        if (clienteTelInput) clienteTelInput.value = '';
        if (clienteRncInput) clienteRncInput.value = '';
        if (fechaInput) fechaInput.value = '<?= htmlspecialchars($fechaActual) ?>';
        if (validezInput) validezInput.value = '15';
        if (vencInput) vencInput.value = '';
        if (estadoSelect) estadoSelect.value = 'borrador';
        if (monedaSelect) monedaSelect.value = 'DOP';
        if (comentarioInput) comentarioInput.value = '';
        if (condicionesInput) condicionesInput.value = '';
        if (descGeneralPctInput) descGeneralPctInput.value = '0';
        if (impuestoPctInput) impuestoPctInput.value = '0';
        clienteDescuentoDefault = 0;
        clienteItbisActivo = true;
        lastMoneda = monedaSelect?.value || 'DOP';
        const locSelect = document.getElementById('cot_localidad_id');
        if (locSelect) {
            locSelect.innerHTML = '<option value="">Seleccione</option>';
            locSelect.disabled = true;
        }
        detalleItems = [];
        renderDetalle();
        setActionButtons(false);
        syncFechaVencimiento();
        syncAddProductoState();
    };

    const setActionButtons = (enabled) => {
        if (deleteBtn) {
            if (enabled) {
                deleteBtn.classList.remove('disabled');
            } else {
                deleteBtn.classList.add('disabled');
            }
        }
        if (printBtn) {
            if (enabled) {
                const id = cotizacionIdInput?.value || '';
                printBtn.classList.remove('disabled');
                printBtn.setAttribute('href', `/procesos/clientes/cotizaciones/imprimir?id=${encodeURIComponent(String(id))}`);
            } else {
                printBtn.classList.add('disabled');
                printBtn.setAttribute('href', '#');
            }
        }
    };

    const syncAddProductoState = () => {
        if (!addProductoBtn) return;
        const clienteId = Number(clienteIdInput?.value || 0);
        if (clienteId > 0) {
            addProductoBtn.classList.remove('disabled');
            addProductoBtn.removeAttribute('disabled');
        } else {
            addProductoBtn.classList.add('disabled');
            addProductoBtn.setAttribute('disabled', 'disabled');
        }
    };

    const getClienteDescuentoDefault = () => {
        const cid = Number(clienteIdInput?.value || 0);
        if (!cid) return 0;
        const info = clienteMap?.[cid];
        const val = Number(info?.descuento_default || 0);
        return Number.isFinite(val) ? val : 0;
    };

    const getClienteItbisActivo = () => {
        const cid = Number(clienteIdInput?.value || 0);
        if (!cid) return true;
        const info = clienteMap?.[cid] || {};
        const aplica = Number(info?.aplica_itbis || 0) === 1;
        const exento = Number(info?.exento_itbis || 0) === 1;
        return aplica && !exento;
    };

    const aplicarDescuentoCliente = () => {
        clienteDescuentoDefault = getClienteDescuentoDefault();
        clienteItbisActivo = getClienteItbisActivo();
        if (detalleItems.length === 0) {
            recalcTotales();
            return;
        }
        detalleItems = detalleItems.map((d) => ({
            ...d,
            descuentoPct: clienteDescuentoDefault,
            impuestoPct: clienteItbisActivo ? calcArticleTaxPct(d.impuestosRaw || '') : 0,
        }));
        renderDetalle();
    };

    const syncLineaTotal = (row, item) => {
        if (!row || !item) return;
        const bruto = Number(item.cantidad) * Number(item.precio);
        const desc = bruto * (Number(item.descuentoPct || 0) / 100);
        const total = bruto - desc;
        const totalCell = row.querySelector('.js-line-total');
        if (totalCell) totalCell.textContent = formatMoney(total);
    };

    const resetDetallePlaceholder = () => {
        if (detalleItems.length > 0) return;
        detalleBody.innerHTML = '<tr><td class="text-muted">Agrega un articulo para la cotizacion.</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td></tr>';
    };

    const renderDetalle = () => {
        if (detalleItems.length === 0) {
            resetDetallePlaceholder();
            recalcTotales();
            return;
        }
        detalleBody.innerHTML = detalleItems.map((d) => {
            const bruto = Number(d.cantidad || 0) * Number(d.precio || 0);
            const desc = bruto * (Number(d.descuentoPct || 0) / 100);
            const total = bruto - desc;
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
                    <input type="number" min="1" step="1" class="form-control form-control-sm cotizacion-detalle-input js-detalle-cantidad text-end" value="${d.cantidad}">
                    <input type="hidden" name="detalle_cantidad[]" value="${d.cantidad}">
                </td>
                <td>
                    ${canEditarPrecio ? `
                        <input type="text" inputmode="decimal" pattern="[0-9]*[.]?[0-9]*" class="form-control form-control-sm cotizacion-detalle-input js-detalle-precio text-end" value="${Number(d.precio).toFixed(2)}">
                    ` : `${formatMoney(d.precio)}`}
                    <input type="hidden" name="detalle_precio[]" value="${Number(d.precio).toFixed(2)}">
                </td>
                <td>
                    <input type="text" inputmode="decimal" pattern="[0-9]*[.]?[0-9]*" class="form-control form-control-sm cotizacion-detalle-input js-detalle-descuento text-end" value="${Number(d.descuentoPct || 0) ? Number(d.descuentoPct || 0).toFixed(2) : ''}">
                    <input type="hidden" name="detalle_descuento_pct[]" value="${Number(d.descuentoPct || 0).toFixed(2)}">
                    <input type="hidden" name="detalle_impuesto_pct[]" value="${Number(d.impuestoPct || 0).toFixed(2)}">
                </td>
                <td class="fw-semibold js-line-total cotizacion-detalle-total">${formatMoney(total)}</td>
                <td><button type="button" class="btn btn-outline-danger btn-sm js-remove-detalle">Quitar</button></td>
            </tr>`;
        }).join('');
        recalcTotales();
    };

    const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
    const calcArticleTaxPct = (rawImpuestos) => {
        const txt = String(rawImpuestos || '').toUpperCase();
        return txt.includes('ITBIS') ? 18 : 0;
    };
    const toNum = (v) => {
        const n = parseFloat(v || '0');
        return Number.isFinite(n) ? n : 0;
    };

    const recalcTotales = () => {
        let subtotal = 0;
        let descLineas = 0;
        let netoLineas = [];
        detalleItems.forEach((d) => {
            const bruto = Number(d.cantidad || 0) * Number(d.precio || 0);
            const desc = bruto * (Number(d.descuentoPct || 0) / 100);
            subtotal += bruto;
            descLineas += desc;
            netoLineas.push(Math.max(0, bruto - desc));
        });
        const subtotalNeto = subtotal - descLineas;
        const descGenPct = toNum(descGeneralPctInput?.value || '0');
        const descGenMonto = subtotalNeto * (descGenPct / 100);
        const baseImp = subtotalNeto - descGenMonto;
        let impuestoMonto = 0;
        const totalBase = netoLineas.reduce((acc, v) => acc + v, 0);
        detalleItems.forEach((d, idx) => {
            const lineaNeta = netoLineas[idx] || 0;
            if (lineaNeta <= 0 || totalBase <= 0) return;
            const proporcion = lineaNeta / totalBase;
            const lineaBase = Math.max(0, lineaNeta - (descGenMonto * proporcion));
            const impPct = Number(d.impuestoPct || 0);
            impuestoMonto += lineaBase * (impPct / 100);
        });
        const total = baseImp + impuestoMonto;

        if (subtotalInput) subtotalInput.value = formatMoney(round2(subtotal));
        if (descLineasInput) descLineasInput.value = formatMoney(round2(descLineas));
        if (descGeneralInput) descGeneralInput.value = formatMoney(round2(descGenMonto));
        if (descTotalInput) descTotalInput.value = formatMoney(round2(descLineas + descGenMonto));
        if (impuestoInput) impuestoInput.value = formatMoney(round2(impuestoMonto));
        if (totalInput) totalInput.value = formatMoney(round2(total));
    };

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-cotizacion-variante-row');
        if (!row) return;
        const clienteId = Number(clienteIdInput?.value || 0);
        if (!clienteId) {
            showToast('Selecciona un cliente antes de agregar productos.', 'warning');
            return;
        }
        const articuloId = Number(row.getAttribute('data-articulo-id') || 0);
        const presentacionId = Number(row.getAttribute('data-presentacion-id') || 0);
        const empaqueId = Number(row.getAttribute('data-empaque-id') || 0);
        const codigo = row.getAttribute('data-codigo') || '';
        const descripcion = row.getAttribute('data-descripcion') || '';
        const presentacion = row.getAttribute('data-presentacion') || '';
        const empaque = row.getAttribute('data-empaque') || '';
        const precio = Number(row.getAttribute('data-precio') || 0);
        const impuestosRaw = row.getAttribute('data-impuestos') || '';
        const impuestoPct = clienteItbisActivo ? calcArticleTaxPct(impuestosRaw) : 0;
        const stock = Number(row.getAttribute('data-stock') || 0);
        if (stock <= 0) {
            showToast('Stock en cero. Esta cotizacion no afecta inventario.', 'info');
        }

        const key = `${articuloId}-${presentacionId}-${empaqueId}`;
        const existing = detalleItems.find((d) => d.key === key);
        if (existing) {
            showToast('Producto ya agregado.', 'warning');
        } else {
            const descDefault = getClienteDescuentoDefault();
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
                descuentoPct: descDefault,
                impuestoPct,
                impuestosRaw,
            });
        }
        renderDetalle();
        if (window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(document.getElementById('cotizacionProductoModal')).hide();
        }
    });

    addProductoBtn?.addEventListener('click', (event) => {
        const clienteId = Number(clienteIdInput?.value || 0);
        if (!clienteId) {
            event.preventDefault();
            showToast('Selecciona un cliente antes de agregar productos.', 'warning');
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

    const normalizeDecimalInput = (value) => {
        const cleaned = String(value || '').replace(',', '.');
        const parsed = parseFloat(cleaned);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const sanitizeDecimalString = (value) => {
        let v = String(value || '').replace(',', '.');
        v = v.replace(/[^0-9.]/g, '');
        const firstDot = v.indexOf('.');
        if (firstDot >= 0) {
            v = v.slice(0, firstDot + 1) + v.slice(firstDot + 1).replace(/\./g, '');
        }
        return v;
    };

    const wireDecimalInput = (input) => {
        if (!input) return;
        input.addEventListener('input', () => {
            const cleaned = sanitizeDecimalString(input.value);
            if (cleaned !== input.value) {
                input.value = cleaned;
            }
        });
    };

    wireDecimalInput(descGeneralPctInput);
    if (impuestoPctInput) {
        wireDecimalInput(impuestoPctInput);
    }

    const clampPctInput = (input, label) => {
        if (!input) return;
        const cleaned = sanitizeDecimalString(input.value);
        if (cleaned !== input.value) input.value = cleaned;
        let val = normalizeDecimalInput(input.value);
        if (!Number.isFinite(val) || val < 0) val = 0;
        if (val > 100) {
            val = 100;
            showToast(`${label} no puede ser mayor a 100.`, 'warning');
        }
        input.value = val === 0 ? '0' : val.toFixed(2);
    };
    descGeneralPctInput?.addEventListener('blur', () => clampPctInput(descGeneralPctInput, 'Descuento general'));
    descGeneralPctInput?.addEventListener('input', () => {
        const cleaned = sanitizeDecimalString(descGeneralPctInput.value);
        if (cleaned !== descGeneralPctInput.value) descGeneralPctInput.value = cleaned;
        const val = normalizeDecimalInput(descGeneralPctInput.value);
        if (val > 100) {
            descGeneralPctInput.value = '100';
            showToast('Descuento general no puede ser mayor a 100.', 'warning');
        }
    });

    detalleBody.addEventListener('input', (event) => {
        const qtyInput = event.target.closest('.js-detalle-cantidad');
        const descInput = event.target.closest('.js-detalle-descuento');
        const priceInput = event.target.closest('.js-detalle-precio');
        const input = qtyInput || descInput || priceInput;
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
            const cleaned = sanitizeDecimalString(descInput.value);
            if (cleaned !== descInput.value) {
                descInput.value = cleaned;
            }
            let val = normalizeDecimalInput(descInput.value);
            if (!Number.isFinite(val) || val < 0) val = 0;
            if (val > 100) val = 100;
            item.descuentoPct = val;
            const hiddenDesc = row.querySelector('input[name="detalle_descuento_pct[]"]');
            if (hiddenDesc) hiddenDesc.value = val.toFixed(2);
        }
        if (priceInput) {
            const cleaned = sanitizeDecimalString(priceInput.value);
            if (cleaned !== priceInput.value) {
                priceInput.value = cleaned;
            }
            let val = normalizeDecimalInput(priceInput.value);
            if (!Number.isFinite(val) || val < 0) val = 0;
            item.precio = val;
            const hiddenPrecio = row.querySelector('input[name="detalle_precio[]"]');
            if (hiddenPrecio) hiddenPrecio.value = val.toFixed(2);
        }

        syncLineaTotal(row, item);
        recalcTotales();
    });

    detalleBody.addEventListener('blur', (event) => {
        const descInput = event.target.closest('.js-detalle-descuento');
        const priceInput = event.target.closest('.js-detalle-precio');
        if (!descInput && !priceInput) return;
        const row = descInput?.closest('tr') || priceInput?.closest('tr');
        const key = row?.getAttribute('data-key') || '';
        const item = detalleItems.find((d) => d.key === key);
        if (!item) return;
        if (descInput) {
            const val = Math.min(100, Math.max(0, normalizeDecimalInput(descInput.value)));
            item.descuentoPct = val;
            descInput.value = val === 0 ? '' : val.toFixed(2);
            const hiddenDesc = row.querySelector('input[name="detalle_descuento_pct[]"]');
            if (hiddenDesc) hiddenDesc.value = val.toFixed(2);
        }
        if (priceInput) {
            const val = Math.max(0, normalizeDecimalInput(priceInput.value));
            item.precio = val;
            priceInput.value = val === 0 ? '' : val.toFixed(2);
            const hiddenPrecio = row.querySelector('input[name="detalle_precio[]"]');
            if (hiddenPrecio) hiddenPrecio.value = val.toFixed(2);
        }
        syncLineaTotal(row, item);
        recalcTotales();
    }, true);

    cotizacionForm?.addEventListener('submit', (event) => {
        if (allowSubmit) {
            return;
        }
        event.preventDefault();
        if (!clienteIdInput || Number(clienteIdInput.value || 0) <= 0) {
            showToast('Selecciona un cliente.', 'warning');
            return;
        }
        const locSelect = document.getElementById('cot_localidad_id');
        if (locSelect && !locSelect.disabled && !locSelect.value) {
            showToast('Selecciona una localidad del cliente.', 'warning');
            return;
        }
        if (detalleItems.length === 0) {
            showToast('Agrega al menos un articulo a la cotizacion.', 'warning');
            return;
        }

        allowSubmit = true;
        cotizacionForm?.submit();
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-client-row');
        if (!row) return;
        const cid = Number(row.getAttribute('data-client-id') || 0);
        const info = clienteMap?.[cid] || {};
        if (String(info?.estado || '') !== 'activo') {
            showToast('El cliente esta inactivo.', 'warning');
            return;
        }
        if (clienteIdInput) clienteIdInput.value = String(cid || '');
        if (clienteLabelInput) clienteLabelInput.value = row.getAttribute('data-client-razon') || '';
        if (clienteTelInput) clienteTelInput.value = row.getAttribute('data-client-telefono') || '';
        if (clienteRncInput) clienteRncInput.value = row.getAttribute('data-client-rnc') || '';
        aplicarDescuentoCliente();
        syncAddProductoState();
        const locSelect = document.getElementById('cot_localidad_id');
        if (locSelect) {
            const cid = Number(row.getAttribute('data-client-id') || 0);
            const locs = localidadesMap[cid] || [];
            locSelect.innerHTML = '<option value="">Seleccione</option>';
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
        const row = event.target.closest('.js-cotizacion-row');
        if (!row) return;
        const id = Number(row.getAttribute('data-id') || 0);
        if (!id) return;
        fetch(`/procesos/clientes/cotizaciones/detalle?id=${id}`)
            .then((res) => res.json())
            .then((payload) => {
                if (!payload || !payload.ok) {
                    showToast(payload?.message || 'No se pudo cargar la cotizacion.', 'danger');
                    return;
                }
                const cotizacion = payload.cotizacion || {};
                if (cotizacionIdInput) cotizacionIdInput.value = String(cotizacion.id || '');
                if (codigoCotizacionInput) codigoCotizacionInput.value = cotizacion.codigo_cotizacion || '';
                if (fechaInput) fechaInput.value = cotizacion.fecha || '<?= htmlspecialchars($fechaActual) ?>';
                if (clienteIdInput) clienteIdInput.value = String(cotizacion.cliente_id || '');
                if (clienteLabelInput) clienteLabelInput.value = cotizacion.cliente_nombre || '';
                if (clienteTelInput) clienteTelInput.value = cotizacion.cliente_telefono || '';
                if (clienteRncInput) clienteRncInput.value = cotizacion.cliente_rnc || '';
                clienteDescuentoDefault = getClienteDescuentoDefault();
                syncAddProductoState();
                if (validezInput) validezInput.value = String(cotizacion.validez_dias || 0);
                if (vencInput) vencInput.value = cotizacion.fecha_vencimiento || '';
                if (estadoSelect) estadoSelect.value = cotizacion.estado || 'borrador';
                if (monedaSelect) monedaSelect.value = cotizacion.moneda || 'DOP';
                lastMoneda = monedaSelect?.value || 'DOP';
                if (comentarioInput) comentarioInput.value = cotizacion.comentario || '';
                if (condicionesInput) condicionesInput.value = cotizacion.condiciones || '';
                if (descGeneralPctInput) descGeneralPctInput.value = String(cotizacion.descuento_general_pct || 0);
                if (impuestoPctInput) impuestoPctInput.value = String(cotizacion.impuesto_pct || 0);

                const locSelect = document.getElementById('cot_localidad_id');
                if (locSelect) {
                    const cid = Number(cotizacion.cliente_id || 0);
                    const locs = localidadesMap[cid] || [];
                    locSelect.innerHTML = '<option value="">Seleccione</option>';
                    if (locs.length > 0) {
                        locs.forEach((l) => {
                            const opt = document.createElement('option');
                            opt.value = String(l.id || '');
                            opt.textContent = String(l.nombre || '');
                            locSelect.appendChild(opt);
                        });
                        locSelect.disabled = false;
                        if (cotizacion.localidad_id) {
                            locSelect.value = String(cotizacion.localidad_id);
                        }
                    } else {
                        locSelect.disabled = true;
                    }
                }

                clienteItbisActivo = getClienteItbisActivo();
                detalleItems = (cotizacion.detalles || []).map((d) => ({
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
                    impuestoPct: clienteItbisActivo ? calcArticleTaxPct(d.impuestos || '') : 0,
                    impuestosRaw: d.impuestos || '',
                }));
                renderDetalle();
                recalcTotales();
                setActionButtons(true);

                if (window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(document.getElementById('cotizacionPickerModal')).hide();
                }
            })
            .catch(() => showToast('No se pudo cargar la cotizacion.', 'danger'));
    });

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
        const table = window.jQuery('#cotizacionProductoTable');
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
        const cotizacionesTable = window.jQuery('#cotizacionPickerTable');
        if (cotizacionesTable.length && !window.jQuery.fn.dataTable.isDataTable(cotizacionesTable)) {
            cotizacionesTable.DataTable({
                pageLength: 10,
                autoWidth: false,
                deferRender: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Buscar cotizacion...',
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
        const cotizacionId = Number(cotizacionIdInput?.value || 0);
        if (!cotizacionId) return;
        if (!confirm('¿Eliminar esta cotizacion?')) return;
        if (deleteFormId) deleteFormId.value = String(cotizacionId);
        document.getElementById('cotizacionDeleteForm')?.submit();
    });

    fechaInput?.addEventListener('change', syncFechaVencimiento);
    validezInput?.addEventListener('input', syncFechaVencimiento);
    descGeneralPctInput?.addEventListener('input', recalcTotales);
    impuestoPctInput?.addEventListener('input', recalcTotales);
    monedaSelect?.addEventListener('change', () => {
        const current = monedaSelect.value || 'DOP';
        if (detalleItems.length > 0 && current !== lastMoneda) {
            const ok = confirm('La moneda cambio. Los precios no se recalcularan automaticamente. ¿Deseas continuar?');
            if (!ok) {
                monedaSelect.value = lastMoneda;
                return;
            }
            showToast('Recuerda ajustar precios si aplica.', 'info');
        }
        lastMoneda = monedaSelect.value || 'DOP';
    });
    vencInput?.addEventListener('blur', () => {
        const base = toDate(fechaInput?.value || '');
        const venc = toDate(vencInput?.value || '');
        if (!base || !venc) return;
        const diffMs = venc.getTime() - base.getTime();
        const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));
        if (diffDays < 0) {
            showToast('La fecha de vencimiento no puede ser menor que la fecha.', 'warning');
            syncFechaVencimiento();
            return;
        }
        if (validezInput) validezInput.value = String(diffDays);
    });

    resetDetallePlaceholder();
    syncFechaVencimiento();
    recalcTotales();
    syncAddProductoState();
})();
</script>
