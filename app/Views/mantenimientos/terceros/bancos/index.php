<?php
$banco = is_array($banco ?? null) ? $banco : [];
$bancosModal = is_array($bancosModal ?? null) ? $bancosModal : [];
$estadoOptions = ['activo' => 'Activo', 'inactivo' => 'Inactivo'];
$paisOptions = [
    'Republica Dominicana', 'Estados Unidos', 'Mexico', 'Colombia', 'Costa Rica', 'Panama', 'Guatemala',
    'Honduras', 'Nicaragua', 'El Salvador', 'Puerto Rico', 'Venezuela', 'Ecuador', 'Peru', 'Chile',
    'Argentina', 'Brasil', 'Espana', 'Canada', 'China', 'India'
];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Terceros / Bancos</h2>
            <small class="text-muted">Catalogo de bancos</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Banco</div>
                <form method="post" action="/mantenimientos/terceros/bancos" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($banco['id'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">ID</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control form-control-sm" value="<?= !empty($banco['id']) ? (int) $banco['id'] : '' ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary js-open-bank-picker" data-bank-redirect="/mantenimientos/terceros/bancos?id={id}" aria-label="Buscar banco">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Nombre banco</label>
                            <input name="nombre_banco" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($banco['nombre_banco'] ?? $banco['nombre'] ?? '')) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Codigo banco</label>
                            <input name="codigo_banco" class="form-control form-control-sm text-uppercase" maxlength="20" value="<?= htmlspecialchars((string) ($banco['codigo_banco'] ?? '')) ?>" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Estado</label>
                            <?php $estadoActual = strtolower((string) ($banco['estado'] ?? 'activo')); ?>
                            <select name="estado" class="form-select form-select-sm">
                                <?php foreach ($estadoOptions as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $estadoActual === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">RNC</label>
                            <input name="rnc" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($banco['rnc'] ?? '')) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Telefono</label>
                            <input name="telefono" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($banco['telefono'] ?? '')) ?>">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Correo contacto</label>
                            <input type="email" name="correo_contacto" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($banco['correo_contacto'] ?? '')) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Sitio web</label>
                            <input name="sitio_web" class="form-control form-control-sm" placeholder="https://" value="<?= htmlspecialchars((string) ($banco['sitio_web'] ?? '')) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Pais</label>
                            <select name="pais" class="form-select form-select-sm">
                                <option value="">Seleccione</option>
                                <?php foreach ($paisOptions as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($banco['pais'] ?? 'Republica Dominicana') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1">Direccion</label>
                            <input name="direccion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($banco['direccion'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar banco</button>
                        <?php if (!empty($banco['id'])): ?>
                            <a href="/mantenimientos/terceros/bancos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                        <?php else: ?>
                            <a href="/mantenimientos/terceros/bancos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="modal fade employee-picker-modal" id="bankPickerModal" tabindex="-1" aria-labelledby="bankPickerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title d-flex align-items-center gap-2" id="bankPickerModalLabel">
                                    <i class="bi bi-bank"></i>
                                    <span>Buscar banco</span>
                                </h5>
                                <small class="text-muted">Click sobre una fila para editar</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <table id="bankPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Codigo</th>
                                        <th>Nombre banco</th>
                                        <th>Telefono</th>
                                        <th>Pais</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bancosModal as $row): ?>
                                        <tr class="js-bank-row" data-bank-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['codigo_banco'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['nombre_banco'] ?? $row['nombre'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['telefono'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['pais'] ?? '')) ?></td>
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
