<?php
$tab = (string) ($tab ?? 'departamentos');
$departamento = is_array($departamento ?? null) ? $departamento : [];
$subdepartamento = is_array($subdepartamento ?? null) ? $subdepartamento : [];
$puesto = is_array($puesto ?? null) ? $puesto : [];
$departamentosModal = is_array($departamentosModal ?? null) ? $departamentosModal : [];
$subdepartamentosModal = is_array($subdepartamentosModal ?? null) ? $subdepartamentosModal : [];
$puestosModal = is_array($puestosModal ?? null) ? $puestosModal : [];
$departamentosActivos = is_array($departamentosActivos ?? null) ? $departamentosActivos : [];
$subdepartamentosActivos = is_array($subdepartamentosActivos ?? null) ? $subdepartamentosActivos : [];
$estadoOptions = ['activo' => 'Activo', 'inactivo' => 'Inactivo'];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Sistema / Puestos</h2>
            <small class="text-muted">Gestion de departamentos, subdepartamentos y puestos</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $tab === 'departamentos' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#org-tab-departamentos" type="button" role="tab">Departamentos</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $tab === 'subdepartamentos' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#org-tab-subdepartamentos" type="button" role="tab">Subdepartamentos</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $tab === 'puestos' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#org-tab-puestos" type="button" role="tab">Puestos</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade <?= $tab === 'departamentos' ? 'show active' : '' ?>" id="org-tab-departamentos" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Departamento</div>
                        <form method="post" action="/sistema/puestos" class="employee-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="entity" value="departamento">
                            <input type="hidden" name="accion" value="save">
                            <input type="hidden" name="id" value="<?= (int) ($departamento['id'] ?? 0) ?>">

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">ID</label>
                                    <div class="input-group input-group-sm">
                                        <input class="form-control form-control-sm" value="<?= !empty($departamento['id']) ? (int) $departamento['id'] : '' ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary js-open-department-picker" data-department-redirect="/sistema/puestos?tab=departamentos&departamento_id={id}" aria-label="Buscar departamento">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Nombre</label>
                                    <input name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($departamento['nombre'] ?? '')) ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <?php $estadoDep = strtolower((string) ($departamento['estado'] ?? 'activo')); ?>
                                    <select name="estado" class="form-select form-select-sm">
                                        <?php foreach ($estadoOptions as $k => $label): ?>
                                            <option value="<?= htmlspecialchars($k) ?>" <?= $estadoDep === $k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($departamento['descripcion'] ?? '')) ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                                <?php if (!empty($departamento['id'])): ?>
                                    <button type="submit" name="accion" value="delete" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Deseas eliminar este departamento?');">Eliminar</button>
                                <?php endif; ?>
                                <a href="/sistema/puestos?tab=departamentos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade <?= $tab === 'subdepartamentos' ? 'show active' : '' ?>" id="org-tab-subdepartamentos" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Subdepartamento</div>
                        <form method="post" action="/sistema/puestos" class="employee-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="entity" value="subdepartamento">
                            <input type="hidden" name="accion" value="save">
                            <input type="hidden" name="id" value="<?= (int) ($subdepartamento['id'] ?? 0) ?>">

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">ID</label>
                                    <div class="input-group input-group-sm">
                                        <input class="form-control form-control-sm" value="<?= !empty($subdepartamento['id']) ? (int) $subdepartamento['id'] : '' ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary js-open-subdepartment-picker" data-subdepartment-redirect="/sistema/puestos?tab=subdepartamentos&subdepartamento_id={id}" aria-label="Buscar subdepartamento">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Departamento</label>
                                    <select name="departamento_id" class="form-select form-select-sm" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($departamentosActivos as $dep): ?>
                                            <?php $depId = (int) ($dep['id'] ?? 0); ?>
                                            <?php $depName = (string) ($dep['nombre'] ?? ''); ?>
                                            <?php if ($depId <= 0 || $depName === '') { continue; } ?>
                                            <option value="<?= $depId ?>" <?= ((int) ($subdepartamento['departamento_id'] ?? 0) === $depId) ? 'selected' : '' ?>><?= htmlspecialchars($depName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <?php $estadoSub = strtolower((string) ($subdepartamento['estado'] ?? 'activo')); ?>
                                    <select name="estado" class="form-select form-select-sm">
                                        <?php foreach ($estadoOptions as $k => $label): ?>
                                            <option value="<?= htmlspecialchars($k) ?>" <?= $estadoSub === $k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Nombre</label>
                                    <input name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($subdepartamento['nombre'] ?? '')) ?>" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($subdepartamento['descripcion'] ?? '')) ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                                <?php if (!empty($subdepartamento['id'])): ?>
                                    <button type="submit" name="accion" value="delete" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Deseas eliminar este subdepartamento?');">Eliminar</button>
                                <?php endif; ?>
                                <a href="/sistema/puestos?tab=subdepartamentos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade <?= $tab === 'puestos' ? 'show active' : '' ?>" id="org-tab-puestos" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Puesto</div>
                        <form method="post" action="/sistema/puestos" class="employee-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="entity" value="puesto">
                            <input type="hidden" name="accion" value="save">
                            <input type="hidden" name="id" value="<?= (int) ($puesto['id'] ?? 0) ?>">

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">ID</label>
                                    <div class="input-group input-group-sm">
                                        <input class="form-control form-control-sm" value="<?= !empty($puesto['id']) ? (int) $puesto['id'] : '' ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary js-open-position-picker" data-position-redirect="/sistema/puestos?tab=puestos&puesto_id={id}" aria-label="Buscar puesto">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Nombre</label>
                                    <input name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($puesto['nombre'] ?? '')) ?>" required>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small mb-1">Estado</label>
                                    <?php $estadoPuesto = strtolower((string) ($puesto['estado'] ?? 'activo')); ?>
                                    <select name="estado" class="form-select form-select-sm">
                                        <?php foreach ($estadoOptions as $k => $label): ?>
                                            <option value="<?= htmlspecialchars($k) ?>" <?= $estadoPuesto === $k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($puesto['descripcion'] ?? '')) ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                                <?php if (!empty($puesto['id'])): ?>
                                    <button type="submit" name="accion" value="delete" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Deseas eliminar este puesto?');">Eliminar</button>
                                <?php endif; ?>
                                <a href="/sistema/puestos?tab=puestos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
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
                                <thead><tr><th>ID</th><th>Nombre</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <?php foreach ($departamentosModal as $row): ?>
                                        <tr class="js-department-row" data-department-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
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
                                <thead><tr><th>ID</th><th>Nombre</th><th>Departamento</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <?php foreach ($subdepartamentosModal as $row): ?>
                                        <tr class="js-subdepartment-row" data-subdepartment-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
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

            <div class="modal fade employee-picker-modal" id="positionPickerModal" tabindex="-1" aria-labelledby="positionPickerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title d-flex align-items-center gap-2" id="positionPickerModalLabel">
                                    <i class="bi bi-person-badge"></i>
                                    <span>Buscar puesto</span>
                                </h5>
                                <small class="text-muted">Click sobre una fila para editar</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <table id="positionPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                                <thead><tr><th>ID</th><th>Puesto</th><th>Descripcion</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <?php foreach ($puestosModal as $row): ?>
                                        <tr class="js-position-row" data-position-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['nombre'] ?? '')) ?></td>
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
