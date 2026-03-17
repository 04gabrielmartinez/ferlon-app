<?php
$marca = is_array($marca ?? null) ? $marca : [];
$marcasModal = is_array($marcasModal ?? null) ? $marcasModal : [];
$familiasMarca = is_array($familiasMarca ?? null) ? $familiasMarca : [];
$estadoActual = strtolower((string) ($marca['estado'] ?? 'activo'));
$estadoChecked = $estadoActual === 'activo';
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Organizacion / Marcas</h2>
            <small class="text-muted">Catalogo de marcas</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Marca</div>
                <form method="post" action="/mantenimientos/organizacion/marcas" class="employee-form" id="marcaForm">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($marca['id'] ?? 0) ?>">
                    <input type="hidden" id="estadoOriginalMarca" value="<?= htmlspecialchars($estadoActual) ?>">
                    <input type="hidden" name="activar_familias_modo" id="activarFamiliasModo" value="">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">ID</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control form-control-sm" value="<?= !empty($marca['id']) ? (int) $marca['id'] : '' ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary js-open-brand-picker" data-brand-redirect="/mantenimientos/organizacion/marcas?id={id}" aria-label="Buscar marca">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Descripcion</label>
                            <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($marca['descripcion'] ?? '')) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Estado</label>
                            <input type="hidden" name="estado" id="estadoMarcaHidden" value="<?= $estadoChecked ? 'activo' : 'inactivo' ?>">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="estadoMarcaSwitch" <?= $estadoChecked ? 'checked' : '' ?>>
                                </div>
                                <span id="estadoMarcaBadge" class="badge rounded-pill <?= $estadoChecked ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $estadoChecked ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar marca</button>
                        <a href="/mantenimientos/organizacion/marcas" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>

                    <div class="modal fade" id="activarFamiliasModal" tabindex="-1" aria-labelledby="activarFamiliasModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow-sm">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="activarFamiliasModalLabel">Activar familias de la marca</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted mb-2">Esta marca tiene familias inactivas. Elige como deseas activarlas:</p>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="modo_activacion_tmp" id="modoTodas" value="todas" checked>
                                        <label class="form-check-label" for="modoTodas">Activar todas las familias</label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="modo_activacion_tmp" id="modoAlgunas" value="algunas">
                                        <label class="form-check-label" for="modoAlgunas">Seleccionar algunas familias</label>
                                    </div>

                                    <div id="listaFamiliasWrap" class="border rounded-3 p-2 bg-light-subtle d-none" style="max-height: 220px; overflow: auto;">
                                        <?php foreach ($familiasMarca as $f): ?>
                                            <?php
                                            $fid = (int) ($f['id'] ?? 0);
                                            $fdesc = (string) ($f['descripcion'] ?? '');
                                            if ($fid <= 0 || $fdesc === '') {
                                                continue;
                                            }
                                            ?>
                                            <label class="form-check d-block">
                                                <input class="form-check-input me-2 js-familia-check" type="checkbox" name="familias_activar[]" value="<?= $fid ?>">
                                                <span class="small"><?= htmlspecialchars($fdesc) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" id="confirmarActivacionFamilias">Continuar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal fade employee-picker-modal" id="brandPickerModal" tabindex="-1" aria-labelledby="brandPickerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title d-flex align-items-center gap-2" id="brandPickerModalLabel">
                                    <i class="bi bi-bookmark"></i>
                                    <span>Buscar marca</span>
                                </h5>
                                <small class="text-muted">Click sobre una fila para editar</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <table id="brandPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Descripcion</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($marcasModal as $row): ?>
                                        <tr class="js-brand-row" data-brand-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['descripcion'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['estado'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const form = document.getElementById('marcaForm');
    const estadoOriginalEl = document.getElementById('estadoOriginalMarca');
    const estadoSwitchEl = document.getElementById('estadoMarcaSwitch');
    const estadoHiddenEl = document.getElementById('estadoMarcaHidden');
    const estadoBadgeEl = document.getElementById('estadoMarcaBadge');
    const modalEl = document.getElementById('activarFamiliasModal');
    const inputModo = document.getElementById('activarFamiliasModo');
    const listaWrap = document.getElementById('listaFamiliasWrap');
    const btnConfirmar = document.getElementById('confirmarActivacionFamilias');
    const modoTodas = document.getElementById('modoTodas');
    const modoAlgunas = document.getElementById('modoAlgunas');

    if (!form || !estadoOriginalEl || !estadoSwitchEl || !estadoHiddenEl || !estadoBadgeEl || !modalEl || !inputModo || !btnConfirmar || !modoTodas || !modoAlgunas || !listaWrap) {
        return;
    }

    let allowSubmit = false;

    const syncEstado = () => {
        const activo = estadoSwitchEl.checked;
        estadoHiddenEl.value = activo ? 'activo' : 'inactivo';
        estadoBadgeEl.textContent = activo ? 'Activo' : 'Inactivo';
        estadoBadgeEl.classList.toggle('text-bg-success', activo);
        estadoBadgeEl.classList.toggle('text-bg-secondary', !activo);
    };
    estadoSwitchEl.addEventListener('change', syncEstado);
    syncEstado();

    const syncLista = () => {
        listaWrap.classList.toggle('d-none', !modoAlgunas.checked);
    };

    modoTodas.addEventListener('change', syncLista);
    modoAlgunas.addEventListener('change', syncLista);
    syncLista();

    form.addEventListener('submit', (event) => {
        if (allowSubmit) {
            return;
        }

        const originalEstado = (estadoOriginalEl.value || '').toLowerCase();
        const nuevoEstado = (estadoHiddenEl.value || '').toLowerCase();
        const hayFamilias = listaWrap.querySelectorAll('.js-familia-check').length > 0;
        const transicionInactivoAActivo = originalEstado === 'inactivo' && nuevoEstado === 'activo';

        if (!transicionInactivoAActivo || !hayFamilias) {
            inputModo.value = '';
            return;
        }

        event.preventDefault();
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    });

    btnConfirmar.addEventListener('click', () => {
        if (modoTodas.checked) {
            inputModo.value = 'todas';
        } else {
            inputModo.value = 'algunas';
        }

        allowSubmit = true;
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        form.submit();
    });
})();
</script>
