<?php
$localidad = is_array($localidad ?? null) ? $localidad : [];
$clientes = is_array($clientes ?? null) ? $clientes : [];
$localidades = is_array($localidades ?? null) ? $localidades : [];
$estadoChecked = ((string) ($localidad['estado'] ?? 'activo') === 'activo');
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Terceros / Localidades</h2>
            <small class="text-muted">CRUD de localidades por cliente</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Localidad</div>
                <form method="post" action="/mantenimientos/terceros/localidades" id="localidadForm" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="accion" value="save">
                    <input type="hidden" name="id" value="<?= (int) ($localidad['id'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label small mb-1">ID Localidad</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="localidad_id_visual" class="form-control form-control-sm" value="<?= (int) ($localidad['id'] ?? 0) > 0 ? (int) ($localidad['id'] ?? 0) : '' ?>" readonly>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#localidadPickerModal"
                                    aria-label="Buscar localidad"
                                >
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label small mb-1">Cliente</label>
                            <div class="input-group input-group-sm">
                                <select name="cliente_id" id="localidad_cliente_id" class="form-select form-select-sm" required>
                                    <option value="">Selecciona un cliente...</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <?php $cid = (int) ($c['id'] ?? 0); ?>
                                        <?php $etq = trim((string) ($c['razon_social'] ?? '')); ?>
                                        <?php if ($etq === '') { continue; } ?>
                                        <option value="<?= $cid ?>" <?= ((int) ($localidad['cliente_id'] ?? 0) === $cid) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($etq) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary js-open-client-picker"
                                    data-client-target="#localidad_cliente_id"
                                    aria-label="Buscar cliente"
                                >
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-lg-2 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="localidad_estado" name="estado" <?= $estadoChecked ? 'checked' : '' ?>>
                                <label class="form-check-label" for="localidad_estado">Activo</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1">Nombre de la localidad</label>
                            <input type="text" name="nombre_localidad" id="localidad_nombre" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($localidad['nombre_localidad'] ?? '')) ?>" placeholder="Nombre de la localidad" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1">Referencia</label>
                            <textarea name="referencia" class="form-control form-control-sm" rows="2" placeholder="Referencia"><?= htmlspecialchars((string) ($localidad['referencia'] ?? '')) ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="section-title mb-2">Ubicacion</div>
                            <ul class="nav nav-pills nav-fill locality-mode-tabs mb-2" role="tablist">
                                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#loc-mode-manual" type="button" role="tab">Manual</button></li>
                                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#loc-mode-mine" type="button" role="tab">Mi ubicacion</button></li>
                                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#loc-mode-map" type="button" role="tab">Mapa</button></li>
                            </ul>

                            <div class="tab-content border rounded-3 p-2 p-md-3 bg-light-subtle">
                                <div class="tab-pane fade show active" id="loc-mode-manual" role="tabpanel">
                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button" id="btnEnableManualCoords" class="btn btn-outline-secondary btn-sm px-3">Editar</button>
                                    </div>
                                    <p class="small text-muted mb-0">Usa “Editar” para habilitar latitud/longitud manualmente.</p>
                                </div>

                                <div class="tab-pane fade" id="loc-mode-mine" role="tabpanel">
                                    <button type="button" id="btnUseMyLocation" class="btn btn-primary w-100 w-md-auto touch-btn mb-2">Usar mi ubicacion</button>
                                    <p id="myLocationMsg" class="small mb-0 text-muted"></p>
                                </div>

                                <div class="tab-pane fade" id="loc-mode-map" role="tabpanel">
                                    <button type="button" class="btn btn-outline-primary w-100 w-md-auto touch-btn" data-bs-toggle="modal" data-bs-target="#mapPickerModal">Seleccionar en mapa</button>
                                </div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Latitud</label>
                                    <input type="number" step="0.000001" id="localidad_latitud" name="latitud" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($localidad['latitud'] ?? '')) ?>" readonly required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Longitud</label>
                                    <input type="number" step="0.000001" id="localidad_longitud" name="longitud" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($localidad['longitud'] ?? '')) ?>" readonly required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                        <?php if (!empty($localidad['id'])): ?>
                            <button type="submit" name="accion" value="delete" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Deseas eliminar esta localidad?');">Eliminar</button>
                        <?php endif; ?>
                        <a href="/mantenimientos/terceros/localidades" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="localidadPickerModal" tabindex="-1" aria-labelledby="localidadPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="localidadPickerModalLabel">
                        <i class="bi bi-geo-alt"></i>
                        <span>Seleccionar localidad</span>
                    </h5>
                    <small class="text-muted">Click en una fila para editar</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="localidadPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Nombre localidad</th>
                            <th>Latitud</th>
                            <th>Longitud</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($localidades as $row): ?>
                            <tr class="js-localidad-row" data-localidad-id="<?= (int) ($row['id'] ?? 0) ?>">
                                <td><?= (int) ($row['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($row['cliente_nombre'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['nombre_localidad'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['latitud'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['longitud'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mapPickerModal" tabindex="-1" aria-labelledby="mapPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="mapPickerModalLabel">Seleccionar en mapa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-2 p-md-3">
                <div class="row g-2 mb-2">
                    <div class="col-12 col-md-9">
                        <input type="text" id="mapSearchInput" class="form-control form-control-lg" placeholder="Buscar lugar o direccion">
                    </div>
                    <div class="col-12 col-md-3 d-grid">
                        <button type="button" id="mapSearchBtn" class="btn btn-outline-primary touch-btn">Buscar</button>
                    </div>
                </div>
                <div id="mapPicker" class="locality-map"></div>
                <small id="mapCoordsPreview" class="text-muted d-block mt-2"></small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary touch-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="useMapCoordsBtn" class="btn btn-primary touch-btn">Usar estas coordenadas</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(() => {
    const form = document.getElementById('localidadForm');
    const clientSelect = document.getElementById('localidad_cliente_id');
    const latInput = document.getElementById('localidad_latitud');
    const lngInput = document.getElementById('localidad_longitud');
    const btnEnableManual = document.getElementById('btnEnableManualCoords');
    const btnUseMyLocation = document.getElementById('btnUseMyLocation');
    const myLocationMsg = document.getElementById('myLocationMsg');
    const mapModalEl = document.getElementById('mapPickerModal');
    const mapSearchInput = document.getElementById('mapSearchInput');
    const mapSearchBtn = document.getElementById('mapSearchBtn');
    const mapCoordsPreview = document.getElementById('mapCoordsPreview');
    const useMapCoordsBtn = document.getElementById('useMapCoordsBtn');
    const localidadPickerEl = document.getElementById('localidadPickerModal');

    let map = null;
    let marker = null;
    let selectedCoords = null;

    const setCoords = (lat, lng, readonly = true) => {
        latInput.value = String(lat);
        lngInput.value = String(lng);
        latInput.readOnly = readonly;
        lngInput.readOnly = readonly;
    };

    const renderCoordsPreview = () => {
        if (!selectedCoords) {
            mapCoordsPreview.textContent = '';
            return;
        }
        mapCoordsPreview.textContent = `Latitud: ${selectedCoords.lat.toFixed(6)} | Longitud: ${selectedCoords.lng.toFixed(6)}`;
    };

    if (btnEnableManual && latInput && lngInput) {
        btnEnableManual.addEventListener('click', () => {
            const editable = latInput.readOnly;
            latInput.readOnly = !editable;
            lngInput.readOnly = !editable;
            btnEnableManual.textContent = editable ? 'Bloquear' : 'Editar';
        });
    }

    if (btnUseMyLocation && latInput && lngInput) {
        btnUseMyLocation.addEventListener('click', () => {
            if (!navigator.geolocation) {
                myLocationMsg.textContent = 'Geolocalizacion no disponible en este dispositivo.';
                myLocationMsg.className = 'small text-danger mb-0';
                return;
            }

            myLocationMsg.textContent = 'Obteniendo ubicacion...';
            myLocationMsg.className = 'small text-muted mb-0';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    setCoords(lat, lng, true);
                    selectedCoords = { lat, lng };
                    renderCoordsPreview();
                    myLocationMsg.textContent = 'Ubicacion obtenida correctamente.';
                    myLocationMsg.className = 'small text-success mb-0';
                },
                (err) => {
                    let msg = 'No se pudo obtener la ubicacion.';
                    if (err.code === 1) msg = 'Permiso denegado para geolocalizacion.';
                    if (err.code === 2) msg = 'No se pudo determinar tu ubicacion.';
                    if (err.code === 3) msg = 'Tiempo agotado al obtener ubicacion.';
                    myLocationMsg.textContent = msg;
                    myLocationMsg.className = 'small text-danger mb-0';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    }

    const ensureMap = () => {
        if (map) {
            map.invalidateSize();
            return;
        }

        const defaultLat = parseFloat(latInput.value || '18.4861');
        const defaultLng = parseFloat(lngInput.value || '-69.9312');

        map = L.map('mapPicker').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
        selectedCoords = { lat: defaultLat, lng: defaultLng };
        renderCoordsPreview();

        marker.on('dragend', () => {
            const p = marker.getLatLng();
            selectedCoords = { lat: p.lat, lng: p.lng };
            renderCoordsPreview();
        });

        map.on('click', (e) => {
            marker.setLatLng(e.latlng);
            selectedCoords = { lat: e.latlng.lat, lng: e.latlng.lng };
            renderCoordsPreview();
        });
    };

    if (mapModalEl) {
        mapModalEl.addEventListener('shown.bs.modal', () => {
            ensureMap();
            setTimeout(() => map && map.invalidateSize(), 120);
        });
    }

    if (mapSearchBtn) {
        mapSearchBtn.addEventListener('click', async () => {
            const q = (mapSearchInput.value || '').trim();
            if (q === '') return;

            try {
                const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q));
                const data = await res.json();
                if (!Array.isArray(data) || data.length === 0) {
                    mapCoordsPreview.textContent = 'No se encontraron resultados para la busqueda.';
                    return;
                }

                const item = data[0];
                const lat = parseFloat(item.lat);
                const lng = parseFloat(item.lon);

                if (Number.isNaN(lat) || Number.isNaN(lng)) {
                    mapCoordsPreview.textContent = 'Resultado invalido de coordenadas.';
                    return;
                }

                ensureMap();
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                selectedCoords = { lat, lng };
                renderCoordsPreview();
            } catch (e) {
                mapCoordsPreview.textContent = 'Error al buscar direccion.';
            }
        });
    }

    if (useMapCoordsBtn && mapModalEl) {
        useMapCoordsBtn.addEventListener('click', () => {
            if (!selectedCoords) {
                mapCoordsPreview.textContent = 'Selecciona un punto en el mapa.';
                return;
            }

            setCoords(selectedCoords.lat, selectedCoords.lng, true);
            const modal = bootstrap.Modal.getInstance(mapModalEl);
            if (modal) modal.hide();
        });
    }

    if (localidadPickerEl) {
        localidadPickerEl.addEventListener('click', (event) => {
            const row = event.target.closest('.js-localidad-row');
            if (!row) return;
            const id = row.getAttribute('data-localidad-id') || '';
            if (id === '') return;
            window.location.href = '/mantenimientos/terceros/localidades?id=' + encodeURIComponent(id);
        });

        localidadPickerEl.addEventListener('shown.bs.modal', () => {
            if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable)) return;
            const table = window.jQuery('#localidadPickerTable');
            if (table.length === 0) return;
            if (window.jQuery.fn.dataTable.isDataTable('#localidadPickerTable')) {
                table.DataTable().columns.adjust().draw(false);
                return;
            }
            table.DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                autoWidth: false,
                language: {
                    search: '',
                    searchPlaceholder: 'Buscar localidad o cliente...',
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: { next: 'Sig', previous: 'Ant' },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                },
                columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                dom:
                    "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                    't' +
                    "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
            });
        });
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            const cliente = (clientSelect.value || '').trim();
            const nombre = (document.getElementById('localidad_nombre').value || '').trim();
            const lat = (latInput.value || '').trim();
            const lng = (lngInput.value || '').trim();

            if (cliente === '' || nombre === '' || lat === '' || lng === '') {
                event.preventDefault();
                myLocationMsg.textContent = 'Completa cliente, nombre y coordenadas para guardar.';
                myLocationMsg.className = 'small text-danger mb-0';
                return;
            }
        });
    }

})();
</script>
