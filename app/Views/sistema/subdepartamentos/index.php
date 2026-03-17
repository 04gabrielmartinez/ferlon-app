<?php
$subdepartamento = is_array($subdepartamento ?? null) ? $subdepartamento : [];
$subdepartamentosModal = is_array($subdepartamentosModal ?? null) ? $subdepartamentosModal : [];
$departamentos = is_array($departamentos ?? null) ? $departamentos : [];
$estadoOptions = ['activo' => 'Activo', 'inactivo' => 'Inactivo'];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Organizacion / Subdepartamentos</h2>
            <small class="text-muted">Catalogo de subdepartamentos</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Subdepartamento</div>
                <form method="post" action="/mantenimientos/organizacion/subdepartamentos" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($subdepartamento['id'] ?? 0) ?>">
                    <input type="hidden" name="accion" value="save">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">ID</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control form-control-sm" value="<?= !empty($subdepartamento['id']) ? (int) $subdepartamento['id'] : '' ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary js-open-subdepartment-picker" data-subdepartment-redirect="/mantenimientos/organizacion/subdepartamentos?id={id}" aria-label="Buscar subdepartamento">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Codigo</label>
                            <input name="codigo" class="form-control form-control-sm text-uppercase" maxlength="30" value="<?= htmlspecialchars((string) ($subdepartamento['codigo'] ?? '')) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Estado</label>
                            <?php $estadoActual = strtolower((string) ($subdepartamento['estado'] ?? 'activo')); ?>
                            <select name="estado" class="form-select form-select-sm">
                                <?php foreach ($estadoOptions as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $estadoActual === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Departamento</label>
                            <select name="departamento_id" class="form-select form-select-sm" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($departamentos as $dep): ?>
                                    <?php $depId = (int) ($dep['id'] ?? 0); ?>
                                    <?php $depName = (string) ($dep['nombre'] ?? ''); ?>
                                    <?php if ($depId <= 0 || $depName === '') { continue; } ?>
                                    <option value="<?= $depId ?>" <?= ((int) ($subdepartamento['departamento_id'] ?? 0) === $depId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($depName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Nombre</label>
                            <input name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($subdepartamento['nombre'] ?? '')) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Descripcion</label>
                            <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($subdepartamento['descripcion'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar subdepartamento</button>
                        <?php if (!empty($subdepartamento['id'])): ?>
                            <button type="submit" name="accion" value="delete" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Deseas eliminar este subdepartamento?');">Eliminar</button>
                        <?php endif; ?>
                        <a href="/mantenimientos/organizacion/subdepartamentos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>

            <div class="modal fade employee-picker-modal" id="subdepartmentPickerModal" tabindex="-1" aria-labelledby="subdepartmentPickerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title d-flex align-items-center gap-2" id="subdepartmentPickerModalLabel">
                                    <i class="bi bi-grid-3x3-gap"></i>
                                    <span>Buscar subdepartamento</span>
                                </h5>
                                <small class="text-muted">Click sobre una fila para editar</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <table id="subdepartmentPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Departamento</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subdepartamentosModal as $row): ?>
                                        <tr class="js-subdepartment-row" data-subdepartment-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['codigo'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['nombre'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['departamento_nombre'] ?? '')) ?></td>
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
