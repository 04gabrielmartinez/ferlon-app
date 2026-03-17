<?php
$productosReceta = is_array($productosReceta ?? null) ? $productosReceta : [];
$producciones = is_array($producciones ?? null) ? $producciones : [];
$fechaActual = date('Y-m-d');
$canCrear = (bool) ($canCrear ?? false);
$canEditar = (bool) ($canEditar ?? false);
$canVer = (bool) ($canVer ?? false);
$canGuardar = $canCrear || $canEditar;
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Procesos / Almacen / Produccion</h2>
            <small class="text-muted">Ejecuta recetas base y descuenta insumos del inventario.</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Orden de Produccion</div>
                <form method="post" action="/procesos/almacen/produccion" id="produccionForm" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="produccion_id" id="produccion_id" value="">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Codigo produccion</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="codigo_produccion" class="form-control" value="Se asigna al guardar" readonly>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#produccionPickerModal" aria-label="Buscar produccion">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Fecha</label>
                            <input type="date" name="fecha" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaActual) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Comentario</label>
                            <input type="text" name="comentario" class="form-control form-control-sm" placeholder="Observaciones">
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-title mb-0">Productos a producir (KG)</div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addProductoBtn" data-bs-toggle="modal" data-bs-target="#productoRecetaPickerModal" <?= $canGuardar ? '' : 'disabled' ?>>
                                Agregar producto
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle" id="produccionItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th style="width: 160px;">Rendimiento</th>
                                        <th style="width: 200px;">Cantidad a producir (KG)</th>
                                        <th style="width: 90px;">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Agrega un producto con receta base.</td>
                                        <td class="text-muted">&nbsp;</td>
                                        <td class="text-muted">&nbsp;</td>
                                        <td class="text-muted">&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-title mb-0">Insumos requeridos</div>
                            <small class="text-muted">Calculados segun las recetas base.</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle" id="insumosTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 140px;">Codigo</th>
                                        <th>Descripcion</th>
                                        <th style="width: 120px;">Unidad base</th>
                                        <th style="width: 160px;">Requerido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-muted">-</td>
                                        <td class="text-muted">Agrega productos para calcular insumos.</td>
                                        <td class="text-muted">-</td>
                                        <td class="text-muted">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3" <?= $canGuardar ? '' : 'disabled' ?>>Guardar</button>
                        <a href="#" id="produccionPrintBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3 disabled">Imprimir</a>
                        <a href="/procesos/almacen/produccion" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="produccionPickerModal" tabindex="-1" aria-labelledby="produccionPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="produccionPickerModalLabel">
                        <i class="bi bi-gear-wide-connected"></i>
                        <span>Buscar produccion</span>
                    </h5>
                    <small class="text-muted">Click en una fila para copiar el codigo</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover align-middle w-100 employee-picker-table" id="produccionPickerTable">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Codigo</th>
                            <th>Articulo</th>
                            <th>Cantidad</th>
                            <th>Empleado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($producciones === []): ?>
                            <tr>
                                <td class="text-muted">&nbsp;</td>
                                <td class="text-muted">No hay producciones registradas.</td>
                                <td class="text-muted">&nbsp;</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($producciones as $p): ?>
                                <tr class="js-produccion-row" data-id="<?= (int) ($p['id'] ?? 0) ?>" data-codigo="<?= htmlspecialchars((string) ($p['codigo_produccion'] ?? '')) ?>" style="cursor:pointer;">
                                    <td><?= htmlspecialchars((string) ($p['fecha'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($p['codigo_produccion'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars(trim((string) ($p['producto_codigo'] ?? '')) . ' - ' . (string) ($p['producto_descripcion'] ?? '')) ?></td>
                                    <td><?= number_format((float) ($p['cantidad'] ?? 0), 2, '.', ',') ?> <?= htmlspecialchars((string) ($p['unidad'] ?? 'kg')) ?></td>
                                    <td><?= htmlspecialchars((string) ($p['empleado_nombre'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="productoRecetaPickerModal" tabindex="-1" aria-labelledby="productoRecetaPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productoRecetaPickerModalLabel">Seleccionar producto receta base</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <small class="text-muted">Solo se muestran productos con receta base configurada.</small>
                <table class="table table-hover align-middle mt-2" id="productoRecetaTable">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Descripcion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productosReceta as $p): ?>
                            <?php $pid = (int) ($p['id'] ?? 0); ?>
                            <tr class="js-producto-receta-row" data-producto-id="<?= $pid ?>" style="cursor:pointer;">
                                <td><?= htmlspecialchars((string) ($p['codigo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['descripcion'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const codigoInput = document.getElementById('codigo_produccion');
        const insumosBody = document.querySelector('#insumosTable tbody');
        const itemsBody = document.querySelector('#produccionItemsTable tbody');
        const modalEl = document.getElementById('productoRecetaPickerModal');
        const pickerModalEl = document.getElementById('produccionPickerModal');
        const produccionIdInput = document.getElementById('produccion_id');
        const printBtn = document.getElementById('produccionPrintBtn');
        const addProductoBtn = document.getElementById('addProductoBtn');
        const recetasCache = new Map();
        const canEditar = <?= $canEditar ? 'true' : 'false' ?>;
        const canGuardar = <?= $canGuardar ? 'true' : 'false' ?>;
        let editMode = false;
        let produccionSeleccionadaId = 0;

        const massUnits = ['kg', 'g', 'lb', 'oz'];

        const toKg = (cantidad, unidad) => {
            const u = String(unidad || '').toLowerCase();
            const factor = u === 'kg' ? 1 : u === 'g' ? 0.001 : u === 'lb' ? 0.45359237 : u === 'oz' ? 0.028349523125 : null;
            return factor === null ? null : cantidad * factor;
        };

        const fromKg = (kg, unidad) => {
            const u = String(unidad || '').toLowerCase();
            const factor = u === 'kg' ? 1 : u === 'g' ? 1000 : u === 'lb' ? 2.2046226218 : u === 'oz' ? 35.27396195 : null;
            return factor === null ? null : kg * factor;
        };

        const resetInsumos = (mensaje) => {
            insumosBody.innerHTML = '<tr><td class="text-muted">-</td><td class="text-muted">' + mensaje + '</td><td class="text-muted">-</td><td class="text-muted">-</td></tr>';
        };

        const getRows = () => Array.from(itemsBody.querySelectorAll('tr[data-producto-id]'));

        const renderInsumos = () => {
            const rows = getRows();
            if (rows.length === 0) {
                resetInsumos('Agrega productos para calcular insumos.');
                return;
            }

            const agregados = new Map();
            for (const row of rows) {
                const productoId = Number(row.getAttribute('data-producto-id') || 0);
                const receta = recetasCache.get(productoId);
                if (!receta) continue;

                const qtyInput = row.querySelector('input[name="cantidad[]"]');
                const cantidadKg = parseFloat(qtyInput?.value || '0');
                if (!cantidadKg || cantidadKg <= 0) continue;

                const rendimiento = parseFloat(receta.rendimiento || '0');
                if (!rendimiento || rendimiento <= 0) continue;

                const unidadR = String(receta.unidad_rendimiento || '').toLowerCase();
                const cantidadReceta = fromKg(cantidadKg, unidadR);
                if (cantidadReceta === null) continue;
                const factor = cantidadReceta / rendimiento;

                (receta.detalles || []).forEach((d) => {
                    const base = parseFloat(d.cantidad || '0');
                    if (!base || base <= 0) return;
                    const requerido = base * factor;
                    const insumoId = Number(d.insumo_articulo_id || 0);
                    const unidadDetalle = String(d.unidad || '').toLowerCase();
                    const unidadBase = String(d.insumo_unidad_base || '').toLowerCase();
                    const key = insumoId + ':' + unidadBase;
                    let registro = agregados.get(key);
                    if (!registro) {
                        registro = {
                            codigo: d.insumo_codigo || '',
                            descripcion: d.insumo_descripcion || '',
                            unidad_base: unidadBase || unidadDetalle,
                            requerido: 0,
                        };
                        agregados.set(key, registro);
                    }
                    if (massUnits.includes(registro.unidad_base)) {
                        const reqKg = toKg(requerido, unidadDetalle);
                        if (reqKg !== null) registro.requerido += reqKg;
                    } else {
                        registro.requerido += requerido;
                    }
                });
            }

            if (agregados.size === 0) {
                resetInsumos('Completa cantidades para calcular insumos.');
                return;
            }

            const rowsHtml = Array.from(agregados.values()).map((r) => {
                const req = Number(r.requerido || 0).toFixed(4);
                const unidad = r.unidad_base || '';
                return '<tr>'
                    + '<td>' + (r.codigo || '') + '</td>'
                    + '<td>' + (r.descripcion || '') + '</td>'
                    + '<td>' + (unidad || '') + '</td>'
                    + '<td class="fw-semibold">' + req + '</td>'
                    + '</tr>';
            });
            insumosBody.innerHTML = rowsHtml.join('');
        };

        const setPrintUrl = (id) => {
            if (!printBtn) return;
            if (id > 0) {
                printBtn.href = '/procesos/almacen/produccion/imprimir?id=' + encodeURIComponent(String(id));
                printBtn.classList.remove('disabled');
                printBtn.setAttribute('target', '_blank');
            } else {
                printBtn.href = '#';
                printBtn.classList.add('disabled');
                printBtn.removeAttribute('target');
            }
        };

        const setEditMode = (enabled) => {
            editMode = enabled;
            if (addProductoBtn) {
                addProductoBtn.disabled = !canGuardar || enabled;
            }
        };

        const addProductoRow = (productoId, label, receta, cantidad = '') => {
            if (editMode && getRows().length > 0) {
                itemsBody.innerHTML = '';
            }
            if (getRows().some((r) => Number(r.getAttribute('data-producto-id')) === productoId)) {
                const existing = getRows().find((r) => Number(r.getAttribute('data-producto-id')) === productoId);
                existing?.querySelector('input[name="cantidad[]"]')?.focus();
                return;
            }

            const rendimiento = receta.rendimiento || '';
            const unidadR = receta.unidad_rendimiento || '';
            const placeholder = itemsBody.querySelector('tr:not([data-producto-id])');
            if (placeholder) {
                itemsBody.innerHTML = '';
            }
            const row = document.createElement('tr');
            row.setAttribute('data-producto-id', String(productoId));
            row.innerHTML = ''
                + '<td>' + label + '<input type="hidden" name="producto_articulo_id[]" value="' + productoId + '"></td>'
                + '<td>' + rendimiento + ' ' + unidadR + '</td>'
                + '<td><input type="number" step="0.0001" min="0.0001" name="cantidad[]" class="form-control form-control-sm" placeholder="Kg" value="' + cantidad + '"></td>'
                + '<td><button type="button" class="btn btn-outline-danger btn-sm js-remove-prod">Quitar</button></td>';
            itemsBody.appendChild(row);
        };

        const cargarReceta = async (productoId) => {
            if (recetasCache.has(productoId)) return recetasCache.get(productoId);
            const resp = await fetch('/procesos/almacen/produccion/receta?producto_id=' + encodeURIComponent(String(productoId)));
            const data = await resp.json();
            if (!data || !data.ok) {
                throw new Error(data && data.message ? data.message : 'No se pudo cargar la receta.');
            }
            const receta = data.receta || null;
            const producto = data.producto || {};
            const unidadR = String(receta?.unidad_rendimiento || '').toLowerCase();
            const unidadProd = String(producto.unidad_base_id || '').toLowerCase();
            if (unidadR === 'u' || unidadProd === 'u') {
                throw new Error('El producto seleccionado no esta en unidad de peso. Produccion solo en KG.');
            }
            recetasCache.set(productoId, receta);
            return receta;
        };

        document.addEventListener('click', async (event) => {
            const row = event.target.closest('.js-producto-receta-row');
            if (!row) return;
            const pid = Number(row.getAttribute('data-producto-id') || 0);
            if (!pid) return;
            const label = row.children[0]?.textContent.trim() + ' - ' + row.children[1]?.textContent.trim();
            try {
                const receta = await cargarReceta(pid);
                addProductoRow(pid, label, receta);
                renderInsumos();
                if (modalEl && window.bootstrap) {
                    const modal = window.bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
            } catch (e) {
                alert(e.message || 'No se pudo cargar la receta.');
            }
        });

        itemsBody.addEventListener('input', (event) => {
            if (event.target.matches('input[name=\"cantidad[]\"]')) {
                renderInsumos();
            }
        });

        itemsBody.addEventListener('click', (event) => {
            const btn = event.target.closest('.js-remove-prod');
            if (!btn) return;
            const tr = btn.closest('tr');
            if (tr) tr.remove();
            if (getRows().length === 0) {
                itemsBody.innerHTML = '<tr><td class=\"text-muted\">Agrega un producto con receta base.</td><td class=\"text-muted\">&nbsp;</td><td class=\"text-muted\">&nbsp;</td><td class=\"text-muted\">&nbsp;</td></tr>';
            }
            renderInsumos();
        });

        const form = document.getElementById('produccionForm');
        form?.addEventListener('submit', (event) => {
            const rows = getRows();
            if (rows.length === 0) {
                event.preventDefault();
                alert('Debes agregar al menos un producto a producir.');
                return;
            }
            for (const row of rows) {
                const qtyInput = row.querySelector('input[name="cantidad[]"]');
                const val = parseFloat(qtyInput?.value || '0');
                if (!val || val <= 0) {
                    event.preventDefault();
                    alert('Completa la cantidad a producir (KG) en todos los productos.');
                    qtyInput?.focus();
                    return;
                }
            }
        });

        const cargarProduccion = async (id) => {
            const resp = await fetch('/procesos/almacen/produccion/detalle?id=' + encodeURIComponent(String(id)));
            const data = await resp.json();
            if (!data || !data.ok) {
                throw new Error(data && data.message ? data.message : 'No se pudo cargar la produccion.');
            }
            const prod = data.produccion || {};
            const productoId = Number(prod.producto_articulo_id || 0);
            if (!productoId) {
                throw new Error('Produccion sin producto valido.');
            }
            const receta = await cargarReceta(productoId);
            const label = (prod.producto_codigo || '') + ' - ' + (prod.producto_descripcion || '');
            itemsBody.innerHTML = '';
            addProductoRow(productoId, label.trim(), receta, prod.cantidad || '');
            if (produccionIdInput) {
                produccionIdInput.value = String(prod.id || '');
            }
            const fechaInput = form?.querySelector('input[name="fecha"]');
            if (fechaInput && prod.fecha) {
                fechaInput.value = prod.fecha;
            }
            const comentarioInput = form?.querySelector('input[name="comentario"]');
            if (comentarioInput) {
                comentarioInput.value = prod.comentario || '';
            }
            if (codigoInput) {
                codigoInput.value = prod.codigo_produccion || '';
            }
            setEditMode(true);
            renderInsumos();
        };

        document.addEventListener('click', async (event) => {
            const row = event.target.closest('.js-produccion-row');
            if (!row) return;
            const codigo = row.getAttribute('data-codigo') || '';
            const id = Number(row.getAttribute('data-id') || 0);
            produccionSeleccionadaId = id;
            if (codigoInput) codigoInput.value = codigo;
            setPrintUrl(id);
            if (pickerModalEl && window.bootstrap) {
                const modal = window.bootstrap.Modal.getInstance(pickerModalEl);
                if (modal) modal.hide();
            }
            if (canEditar && id > 0) {
                try {
                    await cargarProduccion(id);
                } catch (e) {
                    alert(e.message || 'No se pudo cargar la produccion.');
                }
            }
        });

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
            const table = window.jQuery('#produccionPickerTable');
            if (table.length && !window.jQuery.fn.dataTable.isDataTable(table)) {
                table.DataTable({
                    pageLength: 8,
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                        infoFiltered: '(filtrado de _MAX_)',
                    },
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        "t" +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            }
        }

        if (!canGuardar) {
            setEditMode(false);
        }
        setPrintUrl(0);
        resetInsumos('Agrega productos para calcular insumos.');
    })();
</script>
