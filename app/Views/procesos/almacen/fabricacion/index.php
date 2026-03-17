<?php
$productosReceta = is_array($productosReceta ?? null) ? $productosReceta : [];
$fabricaciones = is_array($fabricaciones ?? null) ? $fabricaciones : [];
$fechaActual = date('Y-m-d');
$canCrear = (bool) ($canCrear ?? false);
$canEditar = (bool) ($canEditar ?? false);
$canVer = (bool) ($canVer ?? false);
$canGuardar = $canCrear || $canEditar;
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Procesos / Almacen / Fabricacion</h2>
            <small class="text-muted">Ejecuta recetas de producto final y descuenta insumos.</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Orden de Fabricacion</div>
                <form method="post" action="/procesos/almacen/fabricacion" id="fabricacionForm" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="fabricacion_id" id="fabricacion_id" value="">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Codigo fabricacion</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="codigo_fabricacion" class="form-control" value="Se asigna al guardar" readonly>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#fabricacionPickerModal" aria-label="Buscar fabricacion">
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
                            <div class="section-title mb-0">Productos a fabricar (Unidades)</div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addProductoBtn" data-bs-toggle="modal" data-bs-target="#productoRecetaPickerModal" <?= $canGuardar ? '' : 'disabled' ?>>
                                Agregar producto
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle" id="fabricacionItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th style="width: 160px;">Rendimiento</th>
                                        <th style="width: 200px;">Cantidad a fabricar (u)</th>
                                        <th style="width: 90px;">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Agrega un producto con receta producto final.</td>
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
                            <small class="text-muted">Calculados segun las recetas.</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle" id="fabricacionInsumosTable">
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
                        <a href="#" id="fabricacionPrintBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3 disabled">Imprimir</a>
                        <a href="/procesos/almacen/fabricacion" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="fabricacionPickerModal" tabindex="-1" aria-labelledby="fabricacionPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="fabricacionPickerModalLabel">
                        <i class="bi bi-hammer"></i>
                        <span>Buscar fabricacion</span>
                    </h5>
                    <small class="text-muted">Click en una fila para cargar la fabricacion</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover align-middle w-100 employee-picker-table" id="fabricacionPickerTable">
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
                        <?php if ($fabricaciones === []): ?>
                            <tr>
                                <td class="text-muted">&nbsp;</td>
                                <td class="text-muted">No hay fabricaciones registradas.</td>
                                <td class="text-muted">&nbsp;</td>
                                <td class="text-muted">&nbsp;</td>
                                <td class="text-muted">&nbsp;</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fabricaciones as $f): ?>
                                <?php
                                    $desc = trim((string) ($f['producto_descripcion'] ?? ''));
                                    $codigo = trim((string) ($f['producto_codigo'] ?? ''));
                                    $presentacion = trim((string) ($f['presentacion_descripcion'] ?? ''));
                                    $empaque = trim((string) ($f['empaque_descripcion'] ?? ''));
                                    $label = trim(($codigo !== '' ? $codigo . ' - ' : '') . $desc);
                                    if ($presentacion !== '' || $empaque !== '') {
                                        $label = trim($label . ' / ' . $presentacion . ' / ' . $empaque, ' /');
                                    }
                                ?>
                                <tr class="js-fabricacion-row" data-id="<?= (int) ($f['id'] ?? 0) ?>" data-codigo="<?= htmlspecialchars((string) ($f['codigo_fabricacion'] ?? '')) ?>" style="cursor:pointer;">
                                    <td><?= htmlspecialchars((string) ($f['fecha'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($f['codigo_fabricacion'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($label) ?></td>
                                    <td><?= number_format((float) ($f['cantidad'] ?? 0), 0, '.', ',') ?> <?= htmlspecialchars((string) ($f['unidad'] ?? 'u')) ?></td>
                                    <td><?= htmlspecialchars((string) ($f['empleado_nombre'] ?? '')) ?></td>
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
                <h5 class="modal-title" id="productoRecetaPickerModalLabel">Seleccionar producto receta producto final</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <small class="text-muted">Solo se muestran articulos con receta producto final configurada.</small>
                <table class="table table-hover align-middle mt-2" id="productoRecetaTable">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Descripcion</th>
                            <th>Presentacion</th>
                            <th>Empaque</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productosReceta as $p): ?>
                            <?php $pid = (int) ($p['id'] ?? 0); if ($pid <= 0) continue; ?>
                            <tr class="js-producto-receta-row"
                                data-producto-id="<?= $pid ?>"
                                data-presentacion-id="<?= (int) ($p['presentacion_id'] ?? 0) ?>"
                                data-empaque-id="<?= (int) ($p['empaque_id'] ?? 0) ?>"
                                style="cursor:pointer;">
                                <td><?= htmlspecialchars((string) ($p['codigo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['descripcion'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['presentacion_descripcion'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['empaque_descripcion'] ?? '')) ?></td>
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
        const codigoInput = document.getElementById('codigo_fabricacion');
        const insumosBody = document.querySelector('#fabricacionInsumosTable tbody');
        const itemsBody = document.querySelector('#fabricacionItemsTable tbody');
        const modalEl = document.getElementById('productoRecetaPickerModal');
        const pickerModalEl = document.getElementById('fabricacionPickerModal');
        const fabricacionIdInput = document.getElementById('fabricacion_id');
        const printBtn = document.getElementById('fabricacionPrintBtn');
        const addProductoBtn = document.getElementById('addProductoBtn');
        const recetasCache = new Map();
        const canEditar = <?= $canEditar ? 'true' : 'false' ?>;
        const canGuardar = <?= $canGuardar ? 'true' : 'false' ?>;
        let editMode = false;

        const massUnits = ['kg', 'g', 'lb', 'oz'];

        const toKg = (cantidad, unidad) => {
            const u = String(unidad || '').toLowerCase();
            const factor = u === 'kg' ? 1 : u === 'g' ? 0.001 : u === 'lb' ? 0.45359237 : u === 'oz' ? 0.028349523125 : null;
            return factor === null ? null : cantidad * factor;
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
                const presentacionId = Number(row.getAttribute('data-presentacion-id') || 0);
                const empaqueId = Number(row.getAttribute('data-empaque-id') || 0);
                const recetaKey = productoId + ':' + presentacionId + ':' + empaqueId;
                const receta = recetasCache.get(recetaKey);
                if (!receta) continue;

                const qtyInput = row.querySelector('input[name="cantidad[]"]');
                const cantidadU = parseFloat(qtyInput?.value || '0');
                if (!cantidadU || cantidadU <= 0) continue;

                const rendimiento = parseFloat(receta.rendimiento || '0');
                if (!rendimiento || rendimiento <= 0) continue;

                const factor = cantidadU / rendimiento;

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
                printBtn.href = '/procesos/almacen/fabricacion/imprimir?id=' + encodeURIComponent(String(id));
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

        const formatEntero = (value) => {
            const num = Number(value);
            if (!Number.isFinite(num)) return String(value || '');
            return Math.round(num).toString();
        };

        const addProductoRow = (productoId, presentacionId, empaqueId, label, receta, cantidad = '') => {
            if (editMode && getRows().length > 0) {
                itemsBody.innerHTML = '';
            }
            if (getRows().some((r) => Number(r.getAttribute('data-producto-id')) === productoId
                && Number(r.getAttribute('data-presentacion-id')) === presentacionId
                && Number(r.getAttribute('data-empaque-id')) === empaqueId)) {
                const existing = getRows().find((r) => Number(r.getAttribute('data-producto-id')) === productoId
                    && Number(r.getAttribute('data-presentacion-id')) === presentacionId
                    && Number(r.getAttribute('data-empaque-id')) === empaqueId);
                existing?.querySelector('input[name="cantidad[]"]')?.focus();
                return;
            }

            const rendimiento = formatEntero(receta.rendimiento || '');
            const unidadR = receta.unidad_rendimiento || '';
            const placeholder = itemsBody.querySelector('tr:not([data-producto-id])');
            if (placeholder) {
                itemsBody.innerHTML = '';
            }
            const row = document.createElement('tr');
            row.setAttribute('data-producto-id', String(productoId));
            row.setAttribute('data-presentacion-id', String(presentacionId));
            row.setAttribute('data-empaque-id', String(empaqueId));
            row.innerHTML = ''
                + '<td>' + label
                + '<input type="hidden" name="producto_articulo_id[]" value="' + productoId + '">'
                + '<input type="hidden" name="presentacion_id[]" value="' + presentacionId + '">'
                + '<input type="hidden" name="empaque_id[]" value="' + empaqueId + '"></td>'
                + '<td>' + rendimiento + ' ' + unidadR + '</td>'
                + '<td><input type="number" step="1" min="1" name="cantidad[]" class="form-control form-control-sm" placeholder="Unidades" value="' + cantidad + '"></td>'
                + '<td><button type="button" class="btn btn-outline-danger btn-sm js-remove-prod">Quitar</button></td>';
            itemsBody.appendChild(row);
        };

        const cargarReceta = async (productoId, presentacionId, empaqueId) => {
            const key = productoId + ':' + presentacionId + ':' + empaqueId;
            if (recetasCache.has(key)) return recetasCache.get(key);
            const url = '/procesos/almacen/fabricacion/receta?producto_id=' + encodeURIComponent(String(productoId))
                + '&presentacion_id=' + encodeURIComponent(String(presentacionId))
                + '&empaque_id=' + encodeURIComponent(String(empaqueId));
            const resp = await fetch(url);
            const data = await resp.json();
            if (!data || !data.ok) {
                throw new Error(data && data.message ? data.message : 'No se pudo cargar la receta.');
            }
            const receta = data.receta || null;
            recetasCache.set(key, receta);
            return receta;
        };

        document.addEventListener('click', async (event) => {
            const row = event.target.closest('.js-producto-receta-row');
            if (!row) return;
            const pid = Number(row.getAttribute('data-producto-id') || 0);
            const presentacionId = Number(row.getAttribute('data-presentacion-id') || 0);
            const empaqueId = Number(row.getAttribute('data-empaque-id') || 0);
            if (!pid) return;
            const label = row.children[0]?.textContent.trim()
                + ' - ' + row.children[1]?.textContent.trim()
                + ' / ' + row.children[2]?.textContent.trim()
                + ' / ' + row.children[3]?.textContent.trim();
            try {
                const receta = await cargarReceta(pid, presentacionId, empaqueId);
                addProductoRow(pid, presentacionId, empaqueId, label, receta);
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
            if (event.target.matches('input[name="cantidad[]"]')) {
                renderInsumos();
            }
        });

        itemsBody.addEventListener('click', (event) => {
            const btn = event.target.closest('.js-remove-prod');
            if (!btn) return;
            const tr = btn.closest('tr');
            if (tr) tr.remove();
            if (getRows().length === 0) {
                itemsBody.innerHTML = '<tr><td class="text-muted">Agrega un producto con receta producto final.</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td><td class="text-muted">&nbsp;</td></tr>';
            }
            renderInsumos();
        });

        const form = document.getElementById('fabricacionForm');
        form?.addEventListener('submit', (event) => {
            const rows = getRows();
            if (rows.length === 0) {
                event.preventDefault();
                alert('Debes agregar al menos un producto a fabricar.');
                return;
            }
            for (const row of rows) {
                const qtyInput = row.querySelector('input[name="cantidad[]"]');
                const valRaw = qtyInput?.value || '0';
                const val = parseFloat(valRaw);
                if (!val || val <= 0 || Math.floor(val) !== val) {
                    event.preventDefault();
                    alert('La cantidad a fabricar debe ser un numero entero mayor que cero.');
                    qtyInput?.focus();
                    return;
                }
            }
        });

        const cargarFabricacion = async (id) => {
            const resp = await fetch('/procesos/almacen/fabricacion/detalle?id=' + encodeURIComponent(String(id)));
            const data = await resp.json();
            if (!data || !data.ok) {
                throw new Error(data && data.message ? data.message : 'No se pudo cargar la fabricacion.');
            }
            const fab = data.fabricacion || {};
            const productoId = Number(fab.producto_articulo_id || 0);
            const presentacionId = Number(fab.presentacion_id || 0);
            const empaqueId = Number(fab.empaque_id || 0);
            if (!productoId) {
                throw new Error('Fabricacion sin producto valido.');
            }
            const receta = await cargarReceta(productoId, presentacionId, empaqueId);
            const label = (fab.producto_codigo || '') + ' - ' + (fab.producto_descripcion || '')
                + ' / ' + (fab.presentacion_descripcion || '') + ' / ' + (fab.empaque_descripcion || '');
            itemsBody.innerHTML = '';
            addProductoRow(productoId, presentacionId, empaqueId, label.trim(), receta, fab.cantidad || '');
            if (fabricacionIdInput) {
                fabricacionIdInput.value = String(fab.id || '');
            }
            const fechaInput = form?.querySelector('input[name="fecha"]');
            if (fechaInput && fab.fecha) {
                fechaInput.value = fab.fecha;
            }
            const comentarioInput = form?.querySelector('input[name="comentario"]');
            if (comentarioInput) {
                comentarioInput.value = fab.comentario || '';
            }
            if (codigoInput) {
                codigoInput.value = fab.codigo_fabricacion || '';
            }
            setEditMode(true);
            renderInsumos();
        };

        document.addEventListener('click', async (event) => {
            const row = event.target.closest('.js-fabricacion-row');
            if (!row) return;
            const codigo = row.getAttribute('data-codigo') || '';
            const id = Number(row.getAttribute('data-id') || 0);
            if (codigoInput) codigoInput.value = codigo;
            setPrintUrl(id);
            if (pickerModalEl && window.bootstrap) {
                const modal = window.bootstrap.Modal.getInstance(pickerModalEl);
                if (modal) modal.hide();
            }
            if (canEditar && id > 0) {
                try {
                    await cargarFabricacion(id);
                } catch (e) {
                    alert(e.message || 'No se pudo cargar la fabricacion.');
                }
            }
        });

        if (!canGuardar) {
            setEditMode(false);
        }
        setPrintUrl(0);
        resetInsumos('Agrega productos para calcular insumos.');
    })();
</script>
