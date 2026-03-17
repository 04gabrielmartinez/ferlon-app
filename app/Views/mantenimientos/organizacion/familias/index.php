<?php
$familia = is_array($familia ?? null) ? $familia : [];
$familiasModal = is_array($familiasModal ?? null) ? $familiasModal : [];
$marcasActivas = is_array($marcasActivas ?? null) ? $marcasActivas : [];
$estadoActual = strtolower((string) ($familia['estado'] ?? 'activo'));
$estadoChecked = $estadoActual === 'activo';
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Organizacion / Familias</h2>
            <small class="text-muted">Catalogo de familias por marca</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Familia</div>
                <form method="post" action="/mantenimientos/organizacion/familias" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($familia['id'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">ID</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control form-control-sm" value="<?= !empty($familia['id']) ? (int) $familia['id'] : '' ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary js-open-family-picker" data-family-redirect="/mantenimientos/organizacion/familias?id={id}" aria-label="Buscar familia">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Marca</label>
                            <select name="marca_id" class="form-select form-select-sm" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($marcasActivas as $marca): ?>
                                    <?php $mid = (int) ($marca['id'] ?? 0); ?>
                                    <?php $mDesc = (string) ($marca['descripcion'] ?? ''); ?>
                                    <?php if ($mid <= 0 || $mDesc === '') { continue; } ?>
                                    <option value="<?= $mid ?>" <?= ((int) ($familia['marca_id'] ?? 0) === $mid) ? 'selected' : '' ?>><?= htmlspecialchars($mDesc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Estado</label>
                            <input type="hidden" name="estado" id="estadoFamiliaHidden" value="<?= $estadoChecked ? 'activo' : 'inactivo' ?>">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="estadoFamiliaSwitch" <?= $estadoChecked ? 'checked' : '' ?>>
                                </div>
                                <span id="estadoFamiliaBadge" class="badge rounded-pill <?= $estadoChecked ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $estadoChecked ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label small mb-1">Descripcion</label>
                            <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($familia['descripcion'] ?? '')) ?>" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar familia</button>
                        <a href="/mantenimientos/organizacion/familias" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>

            <div class="modal fade employee-picker-modal" id="familyPickerModal" tabindex="-1" aria-labelledby="familyPickerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title d-flex align-items-center gap-2" id="familyPickerModalLabel">
                                    <i class="bi bi-diagram-2"></i>
                                    <span>Buscar familia</span>
                                </h5>
                                <small class="text-muted">Click sobre una fila para editar</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <table id="familyPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Marca</th>
                                        <th>Descripcion</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($familiasModal as $row): ?>
                                        <tr class="js-family-row" data-family-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['marca_descripcion'] ?? '')) ?></td>
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
    const sw = document.getElementById('estadoFamiliaSwitch');
    const hidden = document.getElementById('estadoFamiliaHidden');
    const badge = document.getElementById('estadoFamiliaBadge');
    if (!sw || !hidden || !badge) return;

    const sync = () => {
        const activo = sw.checked;
        hidden.value = activo ? 'activo' : 'inactivo';
        badge.textContent = activo ? 'Activo' : 'Inactivo';
        badge.classList.toggle('text-bg-success', activo);
        badge.classList.toggle('text-bg-secondary', !activo);
    };

    sw.addEventListener('change', sync);
    sync();
})();
</script>
