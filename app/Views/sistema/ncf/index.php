<?php
$tipos = is_array($tipos ?? null) ? $tipos : [];
$ncf = is_array($ncf ?? null) ? $ncf : [];
$listado = is_array($listado ?? null) ? $listado : [];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Sistema / Mantenimiento NCF</h2>
            <small class="text-muted">Control de autorización y numeración fiscal</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario NCF</div>
                <form method="post" action="/sistema/ncf">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="accion" value="save">
                    <input type="hidden" name="id" value="<?= (int) ($ncf['id'] ?? 0) ?>">

                    <div class="mb-2">
                        <label class="form-label small mb-1">Tipo de NCF</label>
                        <select class="form-select form-select-sm" name="tipo_ncf" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <option value="<?= htmlspecialchars((string) $tipo) ?>" <?= ((string) ($ncf['tipo_ncf'] ?? '') === (string) $tipo) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $tipo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Prefijo</label>
                            <input type="text" class="form-control form-control-sm" name="prefijo" maxlength="20" value="<?= htmlspecialchars((string) ($ncf['prefijo'] ?? '')) ?>" placeholder="A" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Autorización</label>
                            <input type="text" class="form-control form-control-sm" name="autorizacion" maxlength="30" value="<?= htmlspecialchars((string) ($ncf['autorizacion'] ?? '')) ?>" placeholder="15000000001" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Contador inicial</label>
                            <input type="number" class="form-control form-control-sm" name="contador_inicial" min="0" value="<?= htmlspecialchars((string) ($ncf['contador_inicial'] ?? '0')) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Final</label>
                            <input type="number" class="form-control form-control-sm" name="final_numero" min="1" value="<?= htmlspecialchars((string) ($ncf['final_numero'] ?? '0')) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Alerta cuando falten</label>
                            <input type="number" class="form-control form-control-sm" name="alerta_faltan" min="0" value="<?= htmlspecialchars((string) ($ncf['alerta_faltan'] ?? '10')) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Fecha de vencimiento</label>
                            <input type="date" class="form-control form-control-sm" name="fecha_vencimiento" value="<?= htmlspecialchars((string) ($ncf['fecha_vencimiento'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                        </div>
                        <?php if (!empty($ncf['id'])): ?>
                            <div class="col-md-3 d-flex align-items-end">
                                <a href="/sistema/ncf" class="btn btn-light btn-sm rounded-pill px-3">Nuevo</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <div class="section-title">Listado NCF</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" data-page-length="20">
                        <thead>
                            <tr>
                                <th>Tipo NCF</th>
                                <th>Prefijo</th>
                                <th>Autorización</th>
                                <th>Inicial</th>
                                <th>Actual</th>
                                <th>Final</th>
                                <th>Vence</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listado as $row): ?>
                                <tr class="js-ncf-row" data-ncf-id="<?= (int) ($row['id'] ?? 0) ?>" style="cursor:pointer;">
                                    <td><?= htmlspecialchars((string) ($row['tipo_ncf'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['prefijo'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['autorizacion'] ?? '')) ?></td>
                                    <td><?= (int) ($row['contador_inicial'] ?? 0) ?></td>
                                    <td><?= (int) ($row['contador_actual'] ?? 0) ?></td>
                                    <td><?= (int) ($row['final_numero'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['fecha_vencimiento'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$listado): ?>
                                <tr>
                                    <td class="text-muted">Sin registros NCF.</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mt-2">Click en una fila para editar.</small>
            </div>
        </div>
    </div>
</div>
