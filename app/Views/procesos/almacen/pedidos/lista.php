<?php
$pedidosEnProceso = is_array($pedidosEnProceso ?? null) ? $pedidosEnProceso : [];
$csrfToken = (string) ($csrf ?? '');
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Procesos / Almacen / Lista de pedidos</h2>
            <small class="text-muted">Seguimiento, revision y gestion administrativa de pedidos internos.</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle w-100" id="pedidosProcesoTable">
                    <thead class="table-light">
                        <tr>
                            <th>Codigo</th>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Fecha</th>
                            <th>Comentario</th>
                            <th>Departamento</th>
                            <th>Visto</th>
                            <th style="width: 80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pedidosEnProceso === []): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">No hay pedidos en proceso.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidosEnProceso as $p): ?>
                                <?php
                                $departamento = strtolower(trim((string) ($p['departamento'] ?? 'almacen')));
                                if (!in_array($departamento, ['almacen', 'facturacion'], true)) {
                                    $departamento = 'almacen';
                                }
                                $visto = (int) ($p['visto'] ?? 0) === 1;
                                $id = (int) ($p['id'] ?? 0);
                                ?>
                                <tr
                                    data-id="<?= $id ?>"
                                    data-departamento="<?= htmlspecialchars($departamento, ENT_QUOTES, 'UTF-8') ?>"
                                    data-visto="<?= $visto ? '1' : '0' ?>"
                                >
                                    <td class="js-col-codigo"><?= htmlspecialchars((string) ($p['codigo_pedido'] ?? '')) ?></td>
                                    <td class="js-col-orden"><?= htmlspecialchars((string) ($p['orden_no'] ?? '')) ?></td>
                                    <td class="js-col-cliente"><?= htmlspecialchars((string) ($p['cliente_nombre'] ?? '')) ?></td>
                                    <td class="js-col-vendedor"><?= htmlspecialchars((string) ($p['empleado_nombre'] ?? '')) ?></td>
                                    <td class="js-col-fecha"><?= htmlspecialchars((string) ($p['fecha'] ?? '')) ?></td>
                                    <td class="js-col-comentario"><?= htmlspecialchars((string) ($p['comentario'] ?? '')) ?></td>
                                    <td class="js-col-departamento">
                                        <span class="badge rounded-pill text-bg-light border text-uppercase"><?= htmlspecialchars($departamento) ?></span>
                                    </td>
                                    <td class="js-col-visto">
                                        <?php if ($visto): ?>
                                            <span class="badge rounded-pill text-bg-success">Visto</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill text-bg-danger">No visto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary btn-sm js-ver-pedido"
                                            data-pedido-id="<?= $id ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#pedidoVistaModal"
                                            aria-label="Ver pedido"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade pedido-gestion-modal" id="pedidoVistaModal" tabindex="-1" aria-labelledby="pedidoVistaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header pedido-modal-header">
                <div class="pedido-modal-title-wrap d-flex align-items-start gap-3">
                    <div class="pedido-modal-icon"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <h5 class="modal-title mb-1" id="pedidoVistaModalLabel">Pedido -</h5>
                        <small class="text-muted" id="pedidoModalSubTitle">Pedido para -</small>
                    </div>
                </div>
                <button type="button" class="btn btn-light btn-sm border-0 rounded-circle pedido-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="pedido-modal-tabs px-4 pt-3">
                <ul class="nav nav-tabs border-0 gap-2" id="pedidoDetalleTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pedido-info-tab" data-bs-toggle="tab" data-bs-target="#pedido-info-pane" type="button" role="tab" aria-controls="pedido-info-pane" aria-selected="true">Informacion</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pedido-historial-tab" data-bs-toggle="tab" data-bs-target="#pedido-historial-pane" type="button" role="tab" aria-controls="pedido-historial-pane" aria-selected="false">Historial</button>
                    </li>
                </ul>
            </div>

            <div class="modal-body pedido-modal-body px-4 pt-3 pb-0">
                <div id="pedidoAlert" class="alert d-none py-2 px-3 mb-3" role="alert"></div>
                <div class="tab-content" id="pedidoDetalleTabsContent">
                    <div class="tab-pane fade show active" id="pedido-info-pane" role="tabpanel" aria-labelledby="pedido-info-tab" tabindex="0">
                        <section class="pedido-card mb-3">
                            <div class="pedido-summary-grid" id="pedidoSummaryGrid"></div>
                        </section>

                        <section class="pedido-card mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-lg-8">
                                    <label class="form-label fw-semibold mb-1">Accion administrativa</label>
                                    <select id="pedidoAccion" class="form-select">
                                        <option value="">-- Selecciona accion --</option>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-semibold mb-1">Contexto</label>
                                    <div class="pedido-context-box" id="pedidoDeptoActual">Depto: -</div>
                                </div>
                            </div>
                        </section>

                        <section class="pedido-card mb-3">
                            <label class="form-label fw-semibold mb-1" for="pedidoComentario">Comentario (para historial)</label>
                            <textarea id="pedidoComentario" class="form-control" rows="3" placeholder="Agregar nota..."></textarea>
                            <small class="text-muted" id="pedidoCommentHint">Agrega contexto para dejar trazabilidad.</small>
                        </section>

                        <section class="pedido-card mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <h6 class="mb-0 fw-semibold">Detalle de productos</h6>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <label class="small text-muted mb-0" for="productosLength">Mostrar</label>
                                    <select id="productosLength" class="form-select form-select-sm" style="width: 78px;">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                    <span class="small text-muted">registros</span>
                                    <span class="badge text-bg-light border" id="productosTotalItems">0 items</span>
                                    <label class="small text-muted mb-0 ms-2" for="productosSearch">Buscar:</label>
                                    <input id="productosSearch" type="search" class="form-control form-control-sm" style="width: 220px;" placeholder="Filtrar productos">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle pedido-productos-table w-100" id="modalProductosTable">
                                    <thead>
                                        <tr>
                                            <th>Codigo</th>
                                            <th>Descripcion</th>
                                            <th class="text-end">Cantidad</th>
                                            <th class="text-end">Stock</th>
                                            <th style="width: 130px;">Solicitar</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="tab-pane fade" id="pedido-historial-pane" role="tabpanel" aria-labelledby="pedido-historial-tab" tabindex="0">
                        <section class="pedido-card mb-3">
                            <div id="pedidoHistorialTimeline" class="pedido-timeline"></div>
                        </section>
                    </div>
                </div>
            </div>

            <div class="modal-footer pedido-modal-footer px-4 py-3">
                <div class="small text-muted" id="pedidoFooterHint">Selecciona accion y pulsa Guardar.</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="pedidoGuardarBtn" disabled>Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pedido-gestion-modal .modal-content {
    border-radius: 1rem;
    max-height: 92vh;
}
.pedido-modal-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 1.35rem;
}
.pedido-modal-icon {
    width: 40px;
    height: 40px;
    border-radius: 0.75rem;
    background: #eff6ff;
    color: #1d4ed8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}
.pedido-modal-close {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.pedido-modal-tabs .nav-link {
    border: 1px solid #dbe5ef;
    border-radius: 999px;
    color: #4b647d;
    padding: 0.4rem 0.85rem;
    font-size: 0.85rem;
}
.pedido-modal-tabs .nav-link.active {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #fff;
}
.pedido-modal-body {
    overflow-y: auto;
}
.pedido-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    padding: 0.9rem;
}
.pedido-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem 1rem;
}
.pedido-field label {
    display: block;
    font-size: 0.78rem;
    color: #64748b;
    margin-bottom: 0.15rem;
    font-weight: 600;
}
.pedido-field .value {
    color: #0f172a;
    font-size: 0.9rem;
}
.pedido-context-box {
    min-height: 42px;
    border: 1px solid #dbe5ef;
    border-radius: 0.6rem;
    background: #f8fafc;
    padding: 0.55rem 0.75rem;
    font-weight: 600;
    color: #334155;
}
.pedido-productos-table th {
    background: #f8fafc;
    white-space: nowrap;
    font-size: 0.8rem;
}
.pedido-productos-table td {
    vertical-align: middle;
    font-size: 0.84rem;
}
.pedido-timeline {
    position: relative;
    padding-left: 1.5rem;
}
.pedido-timeline::before {
    content: '';
    position: absolute;
    left: 0.43rem;
    top: 0.2rem;
    bottom: 0.2rem;
    width: 2px;
    background: #dbe5ef;
}
.pedido-timeline-item {
    position: relative;
    margin-bottom: 1rem;
}
.pedido-timeline-item::before {
    content: '';
    position: absolute;
    left: -1.2rem;
    top: 0.4rem;
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #3b82f6;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #93c5fd;
}
.pedido-timeline-head {
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.pedido-timeline-card {
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 0.7rem;
    padding: 0.6rem 0.75rem;
}
.pedido-timeline-card strong {
    display: block;
    font-size: 0.9rem;
    color: #0f172a;
}
.pedido-modal-footer {
    border-top: 1px solid #e2e8f0;
    justify-content: space-between;
    background: #fcfdff;
}
@media (max-width: 991.98px) {
    .pedido-summary-grid {
        grid-template-columns: 1fr;
    }
    .pedido-gestion-modal .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
    #productosSearch {
        width: 100% !important;
    }
}
</style>

<script>
(() => {
    const csrf = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const modalEl = document.getElementById('pedidoVistaModal');
    const titleEl = document.getElementById('pedidoVistaModalLabel');
    const subTitleEl = document.getElementById('pedidoModalSubTitle');
    const summaryGrid = document.getElementById('pedidoSummaryGrid');
    const deptoActualEl = document.getElementById('pedidoDeptoActual');
    const accionSelect = document.getElementById('pedidoAccion');
    const comentarioEl = document.getElementById('pedidoComentario');
    const commentHintEl = document.getElementById('pedidoCommentHint');
    const productosBody = document.querySelector('#modalProductosTable tbody');
    const productosSearch = document.getElementById('productosSearch');
    const productosLength = document.getElementById('productosLength');
    const totalItemsEl = document.getElementById('productosTotalItems');
    const historialEl = document.getElementById('pedidoHistorialTimeline');
    const saveBtn = document.getElementById('pedidoGuardarBtn');
    const alertEl = document.getElementById('pedidoAlert');

    let currentPedido = null;
    let productosDt = null;

    const escapeHtml = (txt) => String(txt ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const showAlert = (type, msg) => {
        if (!alertEl) return;
        alertEl.className = `alert alert-${type} py-2 px-3 mb-3`;
        alertEl.textContent = msg;
    };

    const clearAlert = () => {
        if (!alertEl) return;
        alertEl.className = 'alert d-none py-2 px-3 mb-3';
        alertEl.textContent = '';
    };

    const formatDate = (value) => {
        if (!value) return '-';
        const d = new Date(value + 'T00:00:00');
        if (Number.isNaN(d.getTime())) return value;
        return d.toLocaleDateString('es-DO', { year: 'numeric', month: '2-digit', day: '2-digit' });
    };

    const renderSummary = (pedido) => {
        const vistoBadge = pedido.estado_visto === 'visto'
            ? '<span class="badge rounded-pill text-bg-success">Visto</span>'
            : '<span class="badge rounded-pill text-bg-danger">No visto</span>';
        const deptBadge = `<span class="badge rounded-pill text-bg-light border text-uppercase">${escapeHtml(pedido.departamento || '')}</span>`;

        summaryGrid.innerHTML = `
            <div class="pedido-field"><label>Cliente</label><div class="value">${escapeHtml(pedido.cliente || '-')}</div></div>
            <div class="pedido-field"><label>Telefono</label><div class="value">${escapeHtml(pedido.telefono || '-')}</div></div>
            <div class="pedido-field"><label>Cedula / RNC</label><div class="value">${escapeHtml(pedido.cedula_rnc || '-')}</div></div>
            <div class="pedido-field"><label>Empleado</label><div class="value">${escapeHtml(pedido.empleado || '-')}</div></div>
            <div class="pedido-field"><label>Fecha pedido</label><div class="value">${escapeHtml(formatDate(pedido.fecha_pedido || ''))}</div></div>
            <div class="pedido-field"><label>Departamento</label><div class="value">${deptBadge}</div></div>
            <div class="pedido-field"><label>Visto</label><div class="value">${vistoBadge}</div></div>
            <div class="pedido-field"><label>Orden</label><div class="value">${escapeHtml(pedido.orden || '-')}</div></div>
        `;
    };

    const renderActions = (pedido) => {
        const actions = Array.isArray(pedido.acciones_disponibles) ? pedido.acciones_disponibles : [];
        accionSelect.innerHTML = '<option value="">-- Selecciona accion --</option>';
        actions.forEach((action) => {
            const opt = document.createElement('option');
            opt.value = String(action.value || '');
            opt.textContent = String(action.label || action.value || '');
            opt.dataset.requiresComment = action.requires_comment ? '1' : '0';
            opt.dataset.requiresQty = action.requires_qty ? '1' : '0';
            opt.dataset.targetDepartamento = String(action.target_departamento || '');
            accionSelect.appendChild(opt);
        });
        deptoActualEl.textContent = `Depto: ${pedido.departamento_actual || '-'}`;
        accionSelect.value = '';
    };

    const toggleSolicitarInputs = () => {
        const requiresQty = accionSelect.selectedOptions[0]?.dataset?.requiresQty === '1';
        const inputs = productosBody.querySelectorAll('.js-cantidad-solicitar');
        inputs.forEach((inp) => {
            inp.disabled = !requiresQty;
            if (!requiresQty) {
                inp.classList.add('bg-light');
            } else {
                inp.classList.remove('bg-light');
            }
        });
    };

    const renderProductos = (pedido) => {
        const tableSelector = '#modalProductosTable';
        const tableJq = window.jQuery ? window.jQuery(tableSelector) : null;

        if (tableJq && window.jQuery.fn && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(tableJq)) {
            tableJq.DataTable().clear().destroy();
            productosDt = null;
        }

        const productos = Array.isArray(pedido.productos) ? pedido.productos : [];
        totalItemsEl.textContent = `${productos.length} items`;
        productosBody.innerHTML = productos.length === 0
            ? '<tr><td colspan="5" class="text-center text-muted py-3">No hay productos para este pedido.</td></tr>'
            : productos.map((item) => `
                <tr data-detalle-id="${Number(item.detalle_id || 0)}">
                    <td>${escapeHtml(item.codigo || '')}</td>
                    <td>${escapeHtml(item.descripcion || '')}</td>
                    <td class="text-end">${Number(item.cantidad_pedida || 0).toFixed(2)}</td>
                    <td class="text-end">${Number(item.stock || 0).toFixed(2)}</td>
                    <td>
                        <input
                            type="number"
                            class="form-control form-control-sm js-cantidad-solicitar"
                            min="0"
                            step="0.01"
                            max="${Number(item.cantidad_pedida || 0)}"
                            value="${Number(item.cantidad_solicitar || 0).toFixed(2)}"
                        >
                    </td>
                </tr>
            `).join('');

        if (tableJq && window.jQuery.fn && window.jQuery.fn.DataTable) {
            productosDt = tableJq.DataTable({
                pageLength: Number(productosLength.value || 10),
                lengthChange: false,
                autoWidth: false,
                searching: true,
                info: true,
                paging: true,
                ordering: true,
                destroy: true,
                retrieve: true,
                order: [[1, 'asc']],
                language: {
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: { next: 'Sig', previous: 'Ant' },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                    infoFiltered: '(filtrado de _MAX_)',
                },
                dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
            });
        }

        toggleSolicitarInputs();
    };

    const renderHistorial = (pedido) => {
        const historial = Array.isArray(pedido.historial) ? pedido.historial : [];
        if (historial.length === 0) {
            historialEl.innerHTML = '<div class="text-center text-muted py-4">Sin historial registrado para este pedido.</div>';
            return;
        }
        historialEl.innerHTML = historial.map((item) => `
            <div class="pedido-timeline-item">
                <div class="pedido-timeline-head">${escapeHtml(item.usuario || 'Usuario')} • ${escapeHtml(item.fecha_hora || '-')}</div>
                <div class="pedido-timeline-card">
                    <strong>${escapeHtml(item.accion_realizada || '-')}</strong>
                    <div class="small text-muted">${escapeHtml(item.detalle || '')}</div>
                    ${item.comentario ? `<div class="mt-1">${escapeHtml(item.comentario)}</div>` : ''}
                </div>
            </div>
        `).join('');
    };

    const updateSaveState = () => {
        const hasAction = String(accionSelect.value || '').trim() !== '';
        if (!hasAction) {
            saveBtn.disabled = true;
            commentHintEl.textContent = 'Selecciona una accion para habilitar el guardado.';
            return;
        }
        const selected = accionSelect.selectedOptions[0];
        const requiresComment = selected?.dataset?.requiresComment === '1';
        const hasComment = String(comentarioEl.value || '').trim() !== '';
        saveBtn.disabled = requiresComment && !hasComment;
        commentHintEl.textContent = requiresComment
            ? 'Esta accion requiere comentario.'
            : 'Comentario opcional para trazabilidad.';
    };

    const markRowSeen = (pedidoId, departamento) => {
        const row = document.querySelector(`#pedidosProcesoTable tr[data-id="${pedidoId}"]`);
        if (!row) return;
        row.setAttribute('data-visto', '1');
        row.setAttribute('data-departamento', departamento || row.getAttribute('data-departamento') || 'almacen');
        const vistoCell = row.querySelector('.js-col-visto');
        if (vistoCell) {
            vistoCell.innerHTML = '<span class="badge rounded-pill text-bg-success">Visto</span>';
        }
        const deptCell = row.querySelector('.js-col-departamento');
        if (deptCell && departamento) {
            deptCell.innerHTML = `<span class="badge rounded-pill text-bg-light border text-uppercase">${escapeHtml(departamento)}</span>`;
        }
    };

    const updateRowFromPedido = (pedido) => {
        const row = document.querySelector(`#pedidosProcesoTable tr[data-id="${Number(pedido.id || 0)}"]`);
        if (!row) return;
        const setText = (selector, val) => {
            const node = row.querySelector(selector);
            if (node) node.textContent = val || '';
        };
        setText('.js-col-orden', pedido.orden || '');
        setText('.js-col-cliente', pedido.cliente || '');
        setText('.js-col-vendedor', pedido.empleado || '');
        setText('.js-col-fecha', pedido.fecha_pedido || '');
        setText('.js-col-comentario', pedido.comentario || '');
        markRowSeen(Number(pedido.id || 0), pedido.departamento || 'almacen');
    };

    const fetchDetalle = async (pedidoId) => {
        const res = await fetch(`/procesos/almacen/lista-pedidos/detalle?id=${pedidoId}`);
        const payload = await res.json();
        if (!payload?.ok) {
            throw new Error(payload?.message || 'No se pudo cargar el detalle del pedido.');
        }
        return payload.pedido || null;
    };

    const postForm = async (url, formData) => {
        const res = await fetch(url, { method: 'POST', body: formData });
        const payload = await res.json();
        if (!payload?.ok) {
            throw new Error(payload?.message || 'No se pudo procesar la solicitud.');
        }
        return payload;
    };

    const markSeenServer = async (pedidoId, departamento) => {
        const fd = new FormData();
        fd.append('_csrf', csrf);
        fd.append('pedido_id', String(pedidoId));
        fd.append('departamento', String(departamento || 'almacen'));
        await postForm('/procesos/almacen/lista-pedidos/marcar-visto', fd);
    };

    const renderPedido = (pedido) => {
        currentPedido = pedido;
        titleEl.textContent = `Pedido ${pedido.numero_pedido || '-'}`;
        subTitleEl.textContent = `Pedido para ${pedido.cliente || '-'}`;
        comentarioEl.value = '';
        renderSummary(pedido);
        renderActions(pedido);
        renderProductos(pedido);
        renderHistorial(pedido);
        updateSaveState();
    };

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('.js-ver-pedido');
        if (!btn) return;

        clearAlert();
        const pedidoId = Number(btn.getAttribute('data-pedido-id') || 0);
        if (!pedidoId) return;
        const row = btn.closest('tr');
        const departamento = row?.getAttribute('data-departamento') || 'almacen';

        titleEl.textContent = 'Pedido cargando...';
        subTitleEl.textContent = 'Pedido para -';
        summaryGrid.innerHTML = '<div class="text-muted">Cargando informacion...</div>';
        productosBody.innerHTML = '<tr><td colspan="5" class="text-muted py-3">Cargando productos...</td></tr>';
        historialEl.innerHTML = '<div class="text-muted py-3">Cargando historial...</div>';

        try {
            await markSeenServer(pedidoId, departamento);
            markRowSeen(pedidoId, departamento);
        } catch (e) {
            showAlert('warning', e.message || 'No se pudo marcar como visto.');
        }

        try {
            const pedido = await fetchDetalle(pedidoId);
            if (!pedido) throw new Error('No se encontro informacion del pedido.');
            pedido.estado_visto = 'visto';
            renderPedido(pedido);
            updateRowFromPedido(pedido);
        } catch (e) {
            showAlert('danger', e.message || 'No se pudo cargar el detalle del pedido.');
        }
    });

    accionSelect.addEventListener('change', () => {
        clearAlert();
        toggleSolicitarInputs();
        updateSaveState();
    });

    comentarioEl.addEventListener('input', updateSaveState);

    productosSearch.addEventListener('input', () => {
        if (!productosDt) return;
        productosDt.search(productosSearch.value || '').draw();
    });

    productosLength.addEventListener('change', () => {
        if (!productosDt) return;
        productosDt.page.len(Number(productosLength.value || 10)).draw();
    });

    saveBtn.addEventListener('click', async () => {
        clearAlert();
        if (!currentPedido?.id) {
            showAlert('danger', 'No hay pedido seleccionado.');
            return;
        }
        const accion = String(accionSelect.value || '').trim();
        if (!accion) {
            showAlert('warning', 'Debes seleccionar una accion.');
            return;
        }

        const selected = accionSelect.selectedOptions[0];
        const requiresComment = selected?.dataset?.requiresComment === '1';
        const requiresQty = selected?.dataset?.requiresQty === '1';
        const comentario = String(comentarioEl.value || '').trim();

        if (requiresComment && !comentario) {
            showAlert('warning', 'Esta accion requiere comentario.');
            return;
        }

        const cantidades = [];
        const rows = productosBody.querySelectorAll('tr[data-detalle-id]');
        for (const row of rows) {
            const detalleId = Number(row.getAttribute('data-detalle-id') || 0);
            const input = row.querySelector('.js-cantidad-solicitar');
            const cantidad = Number(input?.value || 0);
            const max = Number(input?.getAttribute('max') || 0);
            if (requiresQty) {
                if (!Number.isFinite(cantidad) || cantidad < 0) {
                    showAlert('warning', 'Las cantidades deben ser numericas y mayores o iguales a cero.');
                    return;
                }
                if (cantidad > max) {
                    showAlert('warning', 'La cantidad a solicitar no puede exceder la cantidad pedida.');
                    return;
                }
            }
            cantidades.push({ detalle_id: detalleId, cantidad_solicitar: cantidad });
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Guardando...';

        try {
            const fd = new FormData();
            fd.append('_csrf', csrf);
            fd.append('pedido_id', String(currentPedido.id));
            fd.append('accion', accion);
            fd.append('comentario', comentario);
            fd.append('cantidades', JSON.stringify(cantidades));
            const payload = await postForm('/procesos/almacen/lista-pedidos/gestionar', fd);
            const pedidoActualizado = payload.pedido || null;
            if (!pedidoActualizado) {
                throw new Error('No se pudo recargar el pedido actualizado.');
            }
            pedidoActualizado.estado_visto = 'visto';
            renderPedido(pedidoActualizado);
            updateRowFromPedido(pedidoActualizado);
            showAlert('success', payload.message || 'Gestion guardada correctamente.');
        } catch (e) {
            showAlert('danger', e.message || 'No se pudo guardar la gestion.');
        } finally {
            saveBtn.innerHTML = 'Guardar';
            updateSaveState();
        }
    });

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
        const table = window.jQuery('#pedidosProcesoTable');
        if (table.length && !window.jQuery.fn.dataTable.isDataTable(table)) {
            table.DataTable({
                pageLength: 15,
                autoWidth: false,
                deferRender: true,
                order: [[4, 'desc'], [0, 'desc']],
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
            });
        }
    }

    modalEl?.addEventListener('hidden.bs.modal', () => {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable('#modalProductosTable')) {
            window.jQuery('#modalProductosTable').DataTable().clear().destroy();
            productosDt = null;
        }
        clearAlert();
        comentarioEl.value = '';
        accionSelect.value = '';
        updateSaveState();
    });
})();
</script>
