<?php
$departamento = is_array($departamento ?? null) ? $departamento : [];
$departamentosModal = is_array($departamentosModal ?? null) ? $departamentosModal : [];
$estadoOptions = ['activo' => 'Activo', 'inactivo' => 'Inactivo'];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Organizacion / Departamentos</h2>
            <small class="text-muted">Catalogo de departamentos</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Departamento</div>
                <form method="post" action="/mantenimientos/organizacion/departamentos" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($departamento['id'] ?? 0) ?>">
                    <input type="hidden" name="accion" value="save">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">ID</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control form-control-sm" value="<?= !empty($departamento['id']) ? (int) $departamento['id'] : '' ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary js-open-department-picker" data-department-redirect="/mantenimientos/organizacion/departamentos?id={id}" aria-label="Buscar departamento">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Codigo</label>
                            <input name="codigo" class="form-control form-control-sm text-uppercase" maxlength="30" value="<?= htmlspecialchars((string) ($departamento['codigo'] ?? '')) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Estado</label>
                            <?php $estadoActual = strtolower((string) ($departamento['estado'] ?? 'activo')); ?>
                            <select name="estado" class="form-select form-select-sm">
                                <?php foreach ($estadoOptions as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $estadoActual === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1">Nombre</label>
                            <input name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($departamento['nombre'] ?? '')) ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1">Descripcion</label>
                            <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($departamento['descripcion'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar departamento</button>
                        <?php if (!empty($departamento['id'])): ?>
                            <button type="submit" name="accion" value="delete" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Deseas eliminar este departamento?');">Eliminar</button>
                        <?php endif; ?>
                        <a href="/mantenimientos/organizacion/departamentos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>

            <div class="modal fade employee-picker-modal" id="departmentPickerModal" tabindex="-1" aria-labelledby="departmentPickerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title d-flex align-items-center gap-2" id="departmentPickerModalLabel">
                                    <i class="bi bi-diagram-2"></i>
                                    <span>Buscar departamento</span>
                                </h5>
                                <small class="text-muted">Click sobre una fila para editar</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <table id="departmentPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departamentosModal as $row): ?>
                                        <tr class="js-department-row" data-department-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['codigo'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['nombre'] ?? '')) ?></td>
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
