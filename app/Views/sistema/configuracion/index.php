<?php
$tab = (string) ($tab ?? 'general');
$settings = is_array($settings ?? null) ? $settings : [];
$secuencias = is_array($secuencias ?? null) ? $secuencias : [];
$secuenciaEdit = is_array($secuenciaEdit ?? null) ? $secuenciaEdit : [];
$aplicaAOptions = is_array($aplicaAOptions ?? null) ? $aplicaAOptions : [];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Sistema / Configuracion</h2>
            <small class="text-muted">Branding, timeout de sesion y correo SMTP</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'general' ? 'active' : '' ?>" href="/sistema/configuracion?tab=general">General</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'smtp' ? 'active' : '' ?>" href="/sistema/configuracion?tab=smtp">SMTP</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'secuencias' ? 'active' : '' ?>" href="/sistema/configuracion?tab=secuencias">Secuencias</a>
                </li>
            </ul>

            <?php if ($tab === 'general'): ?>
                <form method="post" action="/sistema/configuracion" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="accion" value="save_general">

                    <div class="col-12 col-lg-6">
                        <div class="section-card h-100">
                            <div class="section-title">Branding</div>
                            <div class="mb-3">
                                <label class="form-label small mb-1">Logo (PNG/JPG/SVG, max 1MB)</label>
                                <input class="form-control form-control-sm" type="file" name="logo" accept=".png,.jpg,.jpeg,.svg,image/png,image/jpeg,image/svg+xml">
                                <?php if (!empty($settings['logo_path'])): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars((string) $settings['logo_path']) ?>" alt="Logo actual" style="max-height:48px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="form-label small mb-1">Favicon (ICO/PNG, max 1MB)</label>
                                <input class="form-control form-control-sm" type="file" name="favicon" accept=".ico,.png,image/x-icon,image/vnd.microsoft.icon,image/png">
                                <?php if (!empty($settings['favicon_path'])): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars((string) $settings['favicon_path']) ?>" alt="Favicon actual" style="max-height:32px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="section-card h-100">
                            <div class="section-title">Empresa y reglas</div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Session timeout (minutos)</label>
                                    <input type="number" class="form-control form-control-sm" name="session_timeout" min="5" max="1440" value="<?= htmlspecialchars((string) ($settings['session_timeout'] ?? '60')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Expired order (dias)</label>
                                    <input type="number" class="form-control form-control-sm" name="expired_order" min="0" max="365" value="<?= htmlspecialchars((string) ($settings['expired_order'] ?? '0')) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Company name</label>
                                    <input type="text" class="form-control form-control-sm" name="company_name" value="<?= htmlspecialchars((string) ($settings['company_name'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Company phone</label>
                                    <input type="text" class="form-control form-control-sm" name="company_phone" value="<?= htmlspecialchars((string) ($settings['company_phone'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Company mail</label>
                                    <input type="email" class="form-control form-control-sm" name="company_mail" value="<?= htmlspecialchars((string) ($settings['company_mail'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Company address</label>
                                    <textarea class="form-control form-control-sm" name="company_address" rows="3"><?= htmlspecialchars((string) ($settings['company_address'] ?? '')) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar general</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($tab === 'smtp'): ?>
                <form method="post" action="/sistema/configuracion" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="accion" id="smtpActionInput" value="save_smtp">

                    <div class="col-12">
                        <div class="section-card">
                            <div class="section-title">Servidor SMTP</div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Servidor SMTP</label>
                                    <input type="text" class="form-control form-control-sm" name="smtp_host" value="<?= htmlspecialchars((string) ($settings['smtp_host'] ?? '')) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Puerto SMTP</label>
                                    <input type="number" class="form-control form-control-sm" name="smtp_port" min="1" max="65535" value="<?= htmlspecialchars((string) ($settings['smtp_port'] ?? '587')) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Correo SMTP</label>
                                    <input type="text" class="form-control form-control-sm" name="smtp_user" value="<?= htmlspecialchars((string) ($settings['smtp_user'] ?? '')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Contraseña SMTP</label>
                                    <input type="password" class="form-control form-control-sm" name="smtp_password" placeholder="<?= htmlspecialchars((string) ($settings['smtp_password_masked'] ?? '')) ?>">
                                    <small class="text-muted">Dejar vacio para mantener la actual.</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Dominio</label>
                                    <input type="text" class="form-control form-control-sm" name="dominio" value="<?= htmlspecialchars((string) ($settings['dominio'] ?? '')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Correo contacto</label>
                                    <input type="email" class="form-control form-control-sm" name="correo_contacto" value="<?= htmlspecialchars((string) ($settings['correo_contacto'] ?? '')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">SMTP encryption</label>
                                    <select class="form-select form-select-sm" name="smtp_encryption">
                                        <?php $enc = strtolower((string) ($settings['smtp_encryption'] ?? 'tls')); ?>
                                        <option value="none" <?= $enc === 'none' ? 'selected' : '' ?>>none</option>
                                        <option value="ssl" <?= $enc === 'ssl' ? 'selected' : '' ?>>ssl</option>
                                        <option value="tls" <?= $enc === 'tls' ? 'selected' : '' ?>>tls</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">SMTP test to</label>
                                    <input type="email" class="form-control form-control-sm" name="smtp_test_to" value="<?= htmlspecialchars((string) ($settings['smtp_test_to'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">From name</label>
                                    <input type="text" class="form-control form-control-sm" name="smtp_from_name" value="<?= htmlspecialchars((string) ($settings['smtp_from_name'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">From email</label>
                                    <input type="email" class="form-control form-control-sm" name="smtp_from_email" value="<?= htmlspecialchars((string) ($settings['smtp_from_email'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Reply-to</label>
                                    <input type="email" class="form-control form-control-sm" name="smtp_reply_to" value="<?= htmlspecialchars((string) ($settings['smtp_reply_to'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Correos pedidos (coma)</label>
                                    <textarea class="form-control form-control-sm" name="correos_pedidos" rows="2" placeholder="a@x.com, b@x.com"><?= htmlspecialchars((string) ($settings['correos_pedidos'] ?? '')) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Correos minimo inventario (coma)</label>
                                    <textarea class="form-control form-control-sm" name="correos_minimo_inventario" rows="2" placeholder="a@x.com, b@x.com"><?= htmlspecialchars((string) ($settings['correos_minimo_inventario'] ?? '')) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary btn-sm rounded-pill px-3"
                            onclick="document.getElementById('smtpActionInput').value='save_smtp';"
                        >
                            Guardar SMTP
                        </button>
                        <button
                            type="submit"
                            class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                            onclick="document.getElementById('smtpActionInput').value='test_smtp';"
                        >
                            Probar SMTP
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($tab === 'temporales'): ?>
                <?php $locks = is_array($locks ?? null) ? $locks : []; ?>
                <div class="section-card mb-3">
                    <div class="section-title">Bloqueos temporales</div>
                    <p class="text-muted mb-3">Los bloqueos de edicion expiran automaticamente en 10 minutos. Usa estas acciones si necesitas limpiar registros atascados.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="/sistema/configuracion">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="accion" value="clean_temporales">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Limpiar expirados</button>
                        </form>
                        <form method="post" action="/sistema/configuracion" onsubmit="return confirm('Eliminar todos los bloqueos temporales?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="accion" value="clean_temporales_all">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Limpiar todo</button>
                        </form>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title">Bloqueos activos</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Recurso</th>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Creado</th>
                                    <th>Expira</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($locks)): ?>
                                    <tr>
                                        <td class="text-muted py-3">&nbsp;</td>
                                        <td class="text-muted py-3">&nbsp;</td>
                                        <td class="text-center text-muted py-3">No hay bloqueos activos.</td>
                                        <td class="text-muted py-3">&nbsp;</td>
                                        <td class="text-muted py-3">&nbsp;</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($locks as $lock): ?>
                                        <?php
                                            $nombre = trim((string) ($lock['nombre'] ?? ''));
                                            if ($nombre === '') {
                                                $nombre = trim((string) ($lock['username'] ?? $lock['email'] ?? 'Usuario'));
                                            }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($lock['recurso_tipo'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($lock['recurso_id'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars($nombre) ?></td>
                                            <td><?= htmlspecialchars((string) ($lock['creado_en'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($lock['expira_en'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'secuencias'): ?>
                <div class="section-card">
                    <div class="section-title">Secuencias</div>
                    <form method="post" action="/sistema/configuracion" class="row g-2">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                        <input type="hidden" name="accion" value="save_secuencia">
                        <input type="hidden" name="secuencia_id" id="secuencia_id" value="<?= (int) ($secuenciaEdit['id'] ?? 0) ?>">

                        <div class="col-md-4">
                            <label class="form-label small mb-1">Clave (unica)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm" name="clave" id="secuencia_clave" value="<?= htmlspecialchars((string) ($secuenciaEdit['clave'] ?? '')) ?>" placeholder="cf, co, pe" required>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary js-open-secuencia-picker"
                                    data-secuencia-redirect="/sistema/configuracion?tab=secuencias&secuencia_id={id}"
                                    aria-label="Buscar secuencia"
                                >
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small mb-1">Aplica a</label>
                            <select class="form-select form-select-sm" name="aplica_a" id="secuencia_aplica_a" required>
                                <option value="">Seleccione</option>
                                <?php $aplicaActual = (string) ($secuenciaEdit['aplica_a'] ?? ''); ?>
                                <?php if ($aplicaActual !== '' && !in_array($aplicaActual, $aplicaAOptions, true)): ?>
                                    <option value="<?= htmlspecialchars($aplicaActual) ?>" selected><?= htmlspecialchars($aplicaActual) ?></option>
                                <?php endif; ?>
                                <?php foreach ($aplicaAOptions as $opt): ?>
                                    <option value="<?= htmlspecialchars((string) $opt) ?>" <?= ((string) ($secuenciaEdit['aplica_a'] ?? '') === (string) $opt) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $opt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small mb-1">Prefijo</label>
                            <input type="text" class="form-control form-control-sm" name="prefijo" id="secuencia_prefijo" value="<?= htmlspecialchars((string) ($secuenciaEdit['prefijo'] ?? '')) ?>" placeholder="A">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small mb-1">Longitud</label>
                            <input type="number" class="form-control form-control-sm" name="longitud" id="secuencia_longitud" min="2" value="<?= htmlspecialchars((string) ($secuenciaEdit['longitud'] ?? '5')) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">Valor actual</label>
                            <input type="number" class="form-control form-control-sm" name="valor_actual" id="secuencia_valor_actual" min="0" value="<?= htmlspecialchars((string) ($secuenciaEdit['valor_actual'] ?? '0')) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">Incremento</label>
                            <input type="number" class="form-control form-control-sm" name="incremento" id="secuencia_incremento" min="1" value="<?= htmlspecialchars((string) ($secuenciaEdit['incremento'] ?? '1')) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">Estado</label>
                            <select class="form-select form-select-sm" name="activo" id="secuencia_activo">
                                <option value="1" <?= ((int) ($secuenciaEdit['activo'] ?? 1) === 1) ? 'selected' : '' ?>>Activa</option>
                                <option value="0" <?= ((int) ($secuenciaEdit['activo'] ?? 1) === 0) ? 'selected' : '' ?>>Descartada</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">Uso total</label>
                            <input type="text" class="form-control form-control-sm" value="<?= (int) ($secuenciaEdit['uso_total'] ?? 0) ?>" readonly>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar secuencia</button>
                        </div>
                    </form>

                    <small class="text-muted d-block mt-3">
                        No se eliminan secuencias. Si ya fue usada, no se puede descartar para proteger la trazabilidad.
                    </small>
                </div>

                <div class="modal fade employee-picker-modal" id="secuenciaPickerModal" tabindex="-1" aria-labelledby="secuenciaPickerModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title d-flex align-items-center gap-2" id="secuenciaPickerModalLabel">
                                        <i class="bi bi-123"></i>
                                        <span>Secuencias registradas</span>
                                    </h5>
                                    <small class="text-muted">Click sobre una fila para editar</small>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <table id="secuenciaPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Clave</th>
                                            <th>Aplica a</th>
                                            <th>Preview</th>
                                            <th>Estado</th>
                                            <th>Uso</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($secuencias as $seq): ?>
                                            <?php
                                            $valorPreview = (int) ($seq['valor_actual'] ?? 0) + (int) ($seq['incremento'] ?? 1);
                                            $preview = (string) ($seq['prefijo'] ?? '') . str_pad((string) $valorPreview, (int) ($seq['longitud'] ?? 8), '0', STR_PAD_LEFT);
                                            $activo = (int) ($seq['activo'] ?? 1) === 1;
                                            ?>
                                            <tr class="js-secuencia-row" data-secuencia-id="<?= (int) ($seq['id'] ?? 0) ?>">
                                                <td><?= (int) ($seq['id'] ?? 0) ?></td>
                                                <td><?= htmlspecialchars((string) ($seq['clave'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string) ($seq['aplica_a'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars($preview) ?></td>
                                                <td>
                                                    <span class="badge <?= $activo ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                        <?= $activo ? 'Activa' : 'Descartada' ?>
                                                    </span>
                                                </td>
                                                <td><?= (int) ($seq['uso_total'] ?? 0) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
