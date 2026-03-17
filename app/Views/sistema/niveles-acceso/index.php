<?php
$tab = (string) ($tab ?? 'cuentas');
$niveles = is_array($niveles ?? null) ? $niveles : [];
$permisos = is_array($permisos ?? null) ? $permisos : [];
$permisosNivel = is_array($permisosNivel ?? null) ? $permisosNivel : [];
$cuentas = is_array($cuentas ?? null) ? $cuentas : [];
$cuentasPicker = is_array($cuentasPicker ?? null) ? $cuentasPicker : [];
$cuentaSeleccionada = is_array($cuentaSeleccionada ?? null) ? $cuentaSeleccionada : [];
$dominio = (string) ($dominio ?? '');
$nivelSeleccionado = (int) ($nivelSeleccionado ?? 0);
$nivelEdicionId = (int) ($nivelEdicionId ?? 0);
$nivelEdicion = is_array($nivelEdicion ?? null) ? $nivelEdicion : [];
$empleadosNivel = is_array($empleadosNivel ?? null) ? $empleadosNivel : [];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Sistema / Niveles de acceso</h2>
            <small class="text-muted">Gestiona cuentas, niveles y permisos</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'cuentas' ? 'active' : '' ?>" href="/sistema/niveles-acceso?tab=cuentas">Acceso de empleado</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'niveles' ? 'active' : '' ?>" href="/sistema/niveles-acceso?tab=niveles">Niveles</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'permisos' ? 'active' : '' ?>" href="/sistema/niveles-acceso?tab=permisos">Accesos de niveles</a>
                </li>
            </ul>

            <?php if ($tab === 'cuentas'): ?>
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="section-card h-100">
                            <div class="section-title">Cuenta de acceso</div>
                            <form method="post" action="/sistema/niveles-acceso">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                                <input type="hidden" name="accion" value="cuenta_empleado">
                                <input type="hidden" name="user_id" id="acceso_user_id" value="<?= (int) ($cuentaSeleccionada['id'] ?? 0) ?>">

                                <div class="mb-2">
                                    <label class="form-label small mb-1">Usuario</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control form-control-sm" id="username_input" name="username" value="<?= htmlspecialchars((string) ($cuentaSeleccionada['username'] ?? '')) ?>" required>
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary js-open-user-picker"
                                            data-user-target="#acceso_user_id"
                                            data-username-target="#username_input"
                                            data-user-redirect="/sistema/niveles-acceso?tab=cuentas&user_id={id}"
                                            aria-label="Buscar usuario creado"
                                        >
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small mb-1">Empleado</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control form-control-sm" id="empleado_id_acceso" name="empleado_id" value="<?= htmlspecialchars((string) ($cuentaSeleccionada['empleado_id'] ?? '')) ?>" readonly required>
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary js-open-employee-picker"
                                            data-employee-target="#empleado_id_acceso"
                                            data-employee-filter="activo"
                                            aria-label="Buscar empleado"
                                        >
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Nivel de acceso</label>
                                    <select class="form-select form-select-sm" name="nivel_acceso_id" required>
                                        <option value="">Seleccione nivel</option>
                                        <?php foreach ($niveles as $nivel): ?>
                                            <option value="<?= (int) ($nivel['id'] ?? 0) ?>" <?= (int) ($nivel['id'] ?? 0) === (int) ($cuentaSeleccionada['nivel_acceso_id'] ?? 0) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) ($nivel['nombre'] ?? '')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small mb-1">Password</label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" class="form-control form-control-sm js-pass-main" name="password" id="password_main" placeholder="********" required>
                                        <button class="btn btn-outline-secondary js-toggle-pass" type="button" data-target="#password_main" aria-label="Mostrar/ocultar password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Confirmar password</label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" class="form-control form-control-sm js-pass-confirm" name="password_confirm" id="password_confirm" placeholder="********" required>
                                        <button class="btn btn-outline-secondary js-toggle-pass" type="button" data-target="#password_confirm" aria-label="Mostrar/ocultar confirmacion">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm rounded-pill px-3 mt-2" type="submit">Guardar acceso</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="section-card h-100">
                            <div class="section-title">Cumplimiento de password</div>
                            <ul class="list-group list-group-flush small" id="passwordRules">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Minimo 8 caracteres
                                    <i class="bi bi-circle text-muted js-pass-rule" data-rule="len"></i>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Al menos una mayuscula
                                    <i class="bi bi-circle text-muted js-pass-rule" data-rule="upper"></i>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Al menos un numero
                                    <i class="bi bi-circle text-muted js-pass-rule" data-rule="num"></i>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Al menos un caracter especial
                                    <i class="bi bi-circle text-muted js-pass-rule" data-rule="special"></i>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Passwords coinciden
                                    <i class="bi bi-circle text-muted js-pass-rule" data-rule="match"></i>
                                </li>
                            </ul>
                            <small class="text-muted d-block mt-2">El estado del usuario se controla desde el estado del empleado.</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'niveles'): ?>
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="section-card h-100">
                            <div class="section-title"><?= $nivelEdicionId > 0 ? 'Editar nivel' : 'Crear nivel' ?></div>
                            <form method="post" action="/sistema/niveles-acceso">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                                <input type="hidden" name="accion" value="guardar_nivel">
                                <input type="hidden" name="nivel_id" value="<?= $nivelEdicionId ?>">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Nombre</label>
                                    <input type="text" class="form-control form-control-sm" name="nombre_nivel" value="<?= htmlspecialchars((string) ($nivelEdicion['nombre'] ?? '')) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input type="text" class="form-control form-control-sm" name="descripcion_nivel" value="<?= htmlspecialchars((string) ($nivelEdicion['descripcion'] ?? '')) ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Estado</label>
                                    <select class="form-select form-select-sm" name="activo_nivel">
                                        <option value="1" <?= ((int) ($nivelEdicion['activo'] ?? 1) === 1) ? 'selected' : '' ?>>Activo</option>
                                        <option value="0" <?= ((int) ($nivelEdicion['activo'] ?? 1) === 0) ? 'selected' : '' ?>>Inactivo</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button class="btn btn-primary btn-sm rounded-pill px-3" type="submit"><?= $nivelEdicionId > 0 ? 'Actualizar nivel' : 'Guardar nivel' ?></button>
                                    <?php if ($nivelEdicionId > 0): ?>
                                        <a class="btn btn-light btn-sm rounded-pill px-3" href="/sistema/niveles-acceso?tab=niveles">Nuevo</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                            <?php if ($nivelEdicionId > 0): ?>
                                <form method="post" action="/sistema/niveles-acceso" class="mt-2" onsubmit="return confirm('Deseas eliminar este nivel?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                                    <input type="hidden" name="accion" value="eliminar_nivel">
                                    <input type="hidden" name="nivel_id" value="<?= $nivelEdicionId ?>">
                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3" type="submit" <?= count($empleadosNivel) > 0 ? 'disabled' : '' ?>>
                                        Eliminar nivel
                                    </button>
                                    <?php if (count($empleadosNivel) > 0): ?>
                                        <small class="text-muted d-block mt-1">No se puede eliminar porque tiene empleados asignados.</small>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="section-card h-100">
                            <div class="section-title">Niveles existentes (click para editar)</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Descripcion</th>
                                            <th>Activo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($niveles as $nivel): ?>
                                            <?php $rowNivelId = (int) ($nivel['id'] ?? 0); ?>
                                            <tr class="<?= $rowNivelId === $nivelEdicionId ? 'table-primary' : '' ?>" style="cursor:pointer;" onclick="window.location.href='/sistema/niveles-acceso?tab=niveles&nivel_edit_id=<?= $rowNivelId ?>'">
                                                <td><?= (int) ($nivel['id'] ?? 0) ?></td>
                                                <td><?= htmlspecialchars((string) ($nivel['nombre'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string) ($nivel['descripcion'] ?? '')) ?></td>
                                                <td>
                                                    <span class="badge <?= ((int) ($nivel['activo'] ?? 0) === 1) ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                        <?= ((int) ($nivel['activo'] ?? 0) === 1) ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($nivelEdicionId > 0): ?>
                    <div class="section-card mt-3">
                        <div class="section-title">Empleados asignados a este nivel (<?= count($empleadosNivel) ?>)</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Empleado</th>
                                        <th>Usuario</th>
                                        <th>Correo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($empleadosNivel === []): ?>
                                        <tr>
                                            <td class="text-muted">Sin registros</td>
                                            <td class="text-muted">Este nivel no tiene empleados asignados.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($empleadosNivel as $empleadoNivel): ?>
                                            <tr>
                                                <td><?= (int) ($empleadoNivel['empleado_id'] ?? 0) ?></td>
                                                <td><?= htmlspecialchars(trim((string) (($empleadoNivel['nombre'] ?? '') . ' ' . ($empleadoNivel['apellido'] ?? '')))) ?></td>
                                                <td><?= htmlspecialchars((string) ($empleadoNivel['username'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string) ($empleadoNivel['email'] ?? '')) ?></td>
                                                <td>
                                                    <span class="badge <?= ((int) ($empleadoNivel['is_active'] ?? 0) === 1) ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                        <?= ((int) ($empleadoNivel['is_active'] ?? 0) === 1) ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($tab === 'permisos'): ?>
                <?php
                $toLabel = static function (string $value): string {
                    $map = [
                        'cuentas_acceso' => 'Nivel acceso',
                    ];
                    $key = strtolower(trim($value));
                    if (isset($map[$key])) {
                        return $map[$key];
                    }
                    $value = str_replace(['_', '-'], ' ', trim($value));
                    $value = preg_replace('/\s+/', ' ', $value) ?? $value;
                    return ucwords($value);
                };

                $gruposPermisos = [];
                foreach ($permisos as $permiso) {
                    $pid = (int) ($permiso['id'] ?? 0);
                    $clave = trim((string) ($permiso['clave'] ?? ''));
                    if ($pid <= 0 || $clave === '') {
                        continue;
                    }

                    $partes = explode('.', $clave, 2);
                    $recurso = trim($partes[0] ?? '');
                    $accion = trim($partes[1] ?? '');
                    if ($recurso === '') {
                        $recurso = 'general';
                    }
                    if ($accion === '') {
                        $accion = $clave;
                    }

                    $modulo = trim((string) ($permiso['modulo'] ?? 'General'));
                    $groupKey = strtolower($modulo . '|' . $recurso);
                    if (!isset($gruposPermisos[$groupKey])) {
                        $gruposPermisos[$groupKey] = [
                            'modulo' => $modulo !== '' ? $modulo : 'General',
                            'recurso' => $recurso,
                            'items' => [],
                        ];
                    }

                    $gruposPermisos[$groupKey]['items'][] = [
                        'id' => $pid,
                        'accion' => $accion,
                    ];
                }
                ?>
                <div class="section-card">
                    <div class="section-title">Accesos por nivel</div>
                    <style>
                        .permisos-compact .accordion-button { padding: .45rem .75rem; font-size: .9rem; }
                        .permisos-compact .accordion-body { padding: .5rem .75rem; }
                        .permisos-compact .form-check { margin-bottom: .35rem; min-height: auto; }
                        .permisos-compact .form-check-input { margin-top: .15rem; }
                    </style>
                    <form method="get" action="/sistema/niveles-acceso" class="row g-2 mb-3">
                        <input type="hidden" name="tab" value="permisos">
                        <div class="col-md-5">
                            <select class="form-select form-select-sm" name="nivel_id" onchange="this.form.submit()">
                                <?php foreach ($niveles as $nivel): ?>
                                    <option value="<?= (int) ($nivel['id'] ?? 0) ?>" <?= (int) ($nivel['id'] ?? 0) === $nivelSeleccionado ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($nivel['nombre'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>

                    <form method="post" action="/sistema/niveles-acceso">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                        <input type="hidden" name="accion" value="asignar_permisos">
                        <input type="hidden" name="nivel_id" value="<?= $nivelSeleccionado ?>">
                        <div class="accordion accordion-flush permisos-compact" id="permisosAccordion">
                            <?php $idx = 0; ?>
                            <?php foreach ($gruposPermisos as $grupo): ?>
                                <?php
                                $idx++;
                                $headingId = 'permisoHeading' . $idx;
                                $collapseId = 'permisoCollapse' . $idx;
                                $itemsGrupo = is_array($grupo['items'] ?? null) ? $grupo['items'] : [];
                                $totalGrupo = count($itemsGrupo);
                                $seleccionadosGrupo = 0;
                                foreach ($itemsGrupo as $it) {
                                    if (in_array((int) ($it['id'] ?? 0), $permisosNivel, true)) {
                                        $seleccionadosGrupo++;
                                    }
                                }
                                ?>
                                <div class="accordion-item border rounded-3 mb-2">
                                    <h2 class="accordion-header" id="<?= $headingId ?>">
                                        <button class="accordion-button <?= $idx === 1 ? '' : 'collapsed' ?> d-flex justify-content-between" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="<?= $idx === 1 ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>">
                                            <span class="fw-semibold"><?= htmlspecialchars($toLabel((string) ($grupo['recurso'] ?? 'General'))) ?></span>
                                            <span class="text-muted small ms-2"><?= $seleccionadosGrupo ?>/<?= $totalGrupo ?></span>
                                        </button>
                                    </h2>
                                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $idx === 1 ? 'show' : '' ?>" aria-labelledby="<?= $headingId ?>" data-bs-parent="#permisosAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-1">
                                                <?php foreach ($itemsGrupo as $item): ?>
                                                    <?php
                                                    $pid = (int) ($item['id'] ?? 0);
                                                    $checked = in_array($pid, $permisosNivel, true);
                                                    ?>
                                                    <div class="col-md-4">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" role="switch" name="permisos[]" value="<?= $pid ?>" id="perm_<?= $pid ?>" <?= $checked ? 'checked' : '' ?>>
                                                            <label class="form-check-label ms-2 small" for="perm_<?= $pid ?>"><?= htmlspecialchars($toLabel((string) ($item['accion'] ?? ''))) ?></label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-primary btn-sm rounded-pill px-3 mt-3" type="submit">Guardar accesos del nivel</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($tab === 'cuentas'): ?>
    <div class="modal fade employee-picker-modal" id="userPickerModal" tabindex="-1" aria-labelledby="userPickerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title d-flex align-items-center gap-2" id="userPickerModalLabel">
                            <i class="bi bi-person-badge"></i>
                            <span>Usuarios creados</span>
                        </h5>
                        <small class="text-muted">Haz click sobre una fila para seleccionar</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <table id="userPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuentasPicker as $u): ?>
                                <tr class="js-user-row" data-user-id="<?= (int) ($u['id'] ?? 0) ?>" data-username="<?= htmlspecialchars((string) ($u['username'] ?? '')) ?>">
                                    <td><?= (int) ($u['id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string) ($u['nombre'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($u['username'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
