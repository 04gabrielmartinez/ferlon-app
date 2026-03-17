<?php
$empleado = is_array($empleado ?? null) ? $empleado : [];
$departamentos = is_array($departamentos ?? null) ? $departamentos : [];
$subdepartamentos = is_array($subdepartamentos ?? null) ? $subdepartamentos : [];
$puestos = is_array($puestos ?? null) ? $puestos : [];
$bancos = is_array($bancos ?? null) ? $bancos : [];
$supervisores = is_array($supervisores ?? null) ? $supervisores : [];

$fotoActual = (string) ($empleado['foto_path'] ?? '');
$estadoActual = (string) ($empleado['estado'] ?? 'activo');
$genero = (string) ($empleado['genero'] ?? '');
$tipoContrato = (string) ($empleado['tipo_contrato'] ?? '');
$jornada = (string) ($empleado['jornada'] ?? '');
$tipoCuenta = (string) ($empleado['tipo_cuenta'] ?? '');
$monedaCuenta = (string) ($empleado['moneda_cuenta'] ?? 'DOP');
$frecuenciaPago = (string) ($empleado['frecuencia_pago'] ?? '');
$parentesco = (string) ($empleado['contacto_emergencia_parentesco'] ?? '');
$estadoBadgeClass = $estadoActual === 'activo' ? 'text-bg-success' : 'text-bg-secondary';
?>
<div class="container-fluid px-0">
    <form method="post" action="/mantenimientos/terceros/empleados" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 employee-form">
        <div class="card-header bg-white border-0 py-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Terceros / Empleados</h2>
            <small class="text-muted">Ficha de empleado</small>
        </div>

        <div class="card-body px-3 px-md-4 py-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
            <input type="hidden" name="foto_actual" value="<?= htmlspecialchars($fotoActual) ?>">
            <input type="hidden" name="estado" id="estadoHidden" value="<?= htmlspecialchars($estadoActual) ?>">

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#emp-tab-1" type="button" role="tab">Datos personales</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#emp-tab-2" type="button" role="tab">Organizacion y beneficios</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#emp-tab-3" type="button" role="tab">Bancario y contacto</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#emp-tab-4" type="button" role="tab">Ubicacion y nomina</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="emp-tab-1" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Datos personales</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">ID empleado</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm" id="empleado_id" name="id" value="<?= htmlspecialchars((string) ($empleado['id'] ?? '')) ?>" readonly>
                                    <button type="button" class="btn btn-outline-secondary js-open-employee-picker" data-employee-target="#empleado_id" data-employee-redirect="/mantenimientos/terceros/empleados?id={id}" aria-label="Buscar empleado por ID">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Cedula</label>
                                <input type="text" class="form-control form-control-sm" name="cedula" maxlength="25" inputmode="numeric" placeholder="000-0000000-0" value="<?= htmlspecialchars((string) ($empleado['cedula'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Estado</label>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="estadoSwitch" <?= $estadoActual === 'activo' ? 'checked' : '' ?>>
                                    </div>
                                    <span id="estadoBadge" class="badge rounded-pill <?= $estadoBadgeClass ?>">
                                        <?= $estadoActual === 'activo' ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Nombre</label>
                                <input type="text" class="form-control form-control-sm" name="nombre" value="<?= htmlspecialchars((string) ($empleado['nombre'] ?? '')) ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Apellido</label>
                                <input type="text" class="form-control form-control-sm" name="apellido" value="<?= htmlspecialchars((string) ($empleado['apellido'] ?? '')) ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Genero</label>
                                <select class="form-select form-select-sm" name="genero">
                                    <option value="">Seleccione</option>
                                    <option value="Masculino" <?= $genero === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="Femenino" <?= $genero === 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                                    <option value="Otro" <?= $genero === 'Otro' ? 'selected' : '' ?>>Otro</option>
                                    <option value="Prefiero no decir" <?= $genero === 'Prefiero no decir' ? 'selected' : '' ?>>Prefiero no decir</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Cumpleanos</label>
                                <input type="date" class="form-control form-control-sm" name="cumpleanos" value="<?= htmlspecialchars((string) ($empleado['cumpleanos'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Email personal</label>
                                <input type="email" class="form-control form-control-sm" name="email_personal" value="<?= htmlspecialchars((string) ($empleado['email_personal'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Email empresa</label>
                                <input type="email" class="form-control form-control-sm" name="email_empresa" value="<?= htmlspecialchars((string) ($empleado['email_empresa'] ?? '')) ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Foto empleado (JPG/PNG, max 1MB)</label>
                                <input type="file" class="form-control form-control-sm" id="fotoEmpleadoInput" name="foto_empleado" accept="image/png,image/jpeg">
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label small text-muted mb-1">Vista previa foto</label>
                                <div class="border rounded-3 p-2 bg-light d-flex align-items-center justify-content-center" style="min-height:160px;">
                                    <div id="employeePhotoPreviewWrap" class="w-100 text-center">
                                        <?php if ($fotoActual !== ''): ?>
                                            <img id="employeePhotoPreview" src="<?= htmlspecialchars($fotoActual) ?>" alt="Foto empleado" class="img-fluid rounded-3 employee-photo-preview">
                                        <?php else: ?>
                                            <img id="employeePhotoPreview" src="" alt="Foto empleado" class="img-fluid rounded-3 employee-photo-preview d-none">
                                            <p id="employeePhotoPlaceholder" class="small text-muted mb-0">Sin foto cargada</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="emp-tab-2" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Organizacion y beneficios</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Departamento</label>
                                <select name="departamento_id" id="empleado_departamento_id" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <?php foreach ($departamentos as $dep): ?>
                                        <?php $depId = (int) ($dep['id'] ?? 0); ?>
                                        <?php $depName = trim((string) ($dep['nombre'] ?? '')); ?>
                                        <?php if ($depId <= 0 || $depName === '') { continue; } ?>
                                        <option value="<?= $depId ?>" <?= ((int) ($empleado['departamento_id'] ?? 0) === $depId) ? 'selected' : '' ?>><?= htmlspecialchars($depName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Subdepartamento</label>
                                <select name="subdepartamento_id" id="empleado_subdepartamento_id" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <?php foreach ($subdepartamentos as $sub): ?>
                                        <?php $subId = (int) ($sub['id'] ?? 0); ?>
                                        <?php $subName = trim((string) ($sub['nombre'] ?? '')); ?>
                                        <?php $subDepId = (int) ($sub['departamento_id'] ?? 0); ?>
                                        <?php if ($subId <= 0 || $subName === '') { continue; } ?>
                                        <option value="<?= $subId ?>" data-departamento-id="<?= $subDepId ?>" <?= ((int) ($empleado['subdepartamento_id'] ?? 0) === $subId) ? 'selected' : '' ?>><?= htmlspecialchars($subName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Cargo (catalogo de puestos)</label>
                                <select name="puesto_id" id="empleado_puesto_id" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <?php foreach ($puestos as $puesto): ?>
                                        <?php $puestoId = (int) ($puesto['id'] ?? 0); ?>
                                        <?php $puestoNombre = trim((string) ($puesto['nombre'] ?? '')); ?>
                                        <?php if ($puestoId <= 0 || $puestoNombre === '') { continue; } ?>
                                        <option value="<?= $puestoId ?>" <?= ((int) ($empleado['puesto_id'] ?? 0) === $puestoId) ? 'selected' : '' ?>><?= htmlspecialchars($puestoNombre) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Cargo (texto manual opcional)</label>
                                <input type="text" class="form-control form-control-sm" name="cargo" value="<?= htmlspecialchars((string) ($empleado['cargo'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Supervisor</label>
                                <select name="supervisor_id" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <?php foreach ($supervisores as $sup): ?>
                                        <?php $supId = (int) ($sup['id'] ?? 0); ?>
                                        <?php $supNombre = trim((string) (($sup['nombre'] ?? '') . ' ' . ($sup['apellido'] ?? ''))); ?>
                                        <?php if ($supId <= 0 || $supNombre === '') { continue; } ?>
                                        <option value="<?= $supId ?>" <?= ((int) ($empleado['supervisor_id'] ?? 0) === $supId) ? 'selected' : '' ?>><?= htmlspecialchars($supNombre) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Fecha entrada</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_entrada" value="<?= htmlspecialchars((string) ($empleado['fecha_entrada'] ?? '')) ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Fecha salida</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_salida" value="<?= htmlspecialchars((string) ($empleado['fecha_salida'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Tipo contrato</label>
                                <select name="tipo_contrato" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <option value="Fijo" <?= $tipoContrato === 'Fijo' ? 'selected' : '' ?>>Fijo</option>
                                    <option value="Temporal" <?= $tipoContrato === 'Temporal' ? 'selected' : '' ?>>Temporal</option>
                                    <option value="Servicios" <?= $tipoContrato === 'Servicios' ? 'selected' : '' ?>>Servicios</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Jornada</label>
                                <select name="jornada" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <option value="Completa" <?= $jornada === 'Completa' ? 'selected' : '' ?>>Completa</option>
                                    <option value="Media" <?= $jornada === 'Media' ? 'selected' : '' ?>>Media</option>
                                    <option value="Por horas" <?= $jornada === 'Por horas' ? 'selected' : '' ?>>Por horas</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Transporte (monto)</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="transporte" value="<?= htmlspecialchars((string) ($empleado['transporte'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Dieta (monto)</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="dieta" value="<?= htmlspecialchars((string) ($empleado['dieta'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="emp-tab-3" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Bancario y contacto</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Banco</label>
                                <select name="banco_id" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <?php foreach ($bancos as $banco): ?>
                                        <?php $bancoId = (int) ($banco['id'] ?? 0); ?>
                                        <?php $bancoNombre = trim((string) ($banco['nombre'] ?? '')); ?>
                                        <?php if ($bancoId <= 0 || $bancoNombre === '') { continue; } ?>
                                        <option value="<?= $bancoId ?>" <?= ((int) ($empleado['banco_id'] ?? 0) === $bancoId) ? 'selected' : '' ?>><?= htmlspecialchars($bancoNombre) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Tipo cuenta</label>
                                <select name="tipo_cuenta" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <option value="Ahorros" <?= $tipoCuenta === 'Ahorros' ? 'selected' : '' ?>>Ahorros</option>
                                    <option value="Corriente" <?= $tipoCuenta === 'Corriente' ? 'selected' : '' ?>>Corriente</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Cuenta banco</label>
                                <input type="text" class="form-control form-control-sm" name="cuenta_banco" value="<?= htmlspecialchars((string) ($empleado['cuenta_banco'] ?? '')) ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Titular cuenta</label>
                                <input type="text" class="form-control form-control-sm" name="titular_cuenta" value="<?= htmlspecialchars((string) ($empleado['titular_cuenta'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Moneda cuenta</label>
                                <select name="moneda_cuenta" class="form-select form-select-sm">
                                    <option value="DOP" <?= $monedaCuenta === 'DOP' ? 'selected' : '' ?>>DOP</option>
                                    <option value="USD" <?= $monedaCuenta === 'USD' ? 'selected' : '' ?>>USD</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">AFP complementario</label>
                                <input type="text" class="form-control form-control-sm" name="afp_complementario" value="<?= htmlspecialchars((string) ($empleado['afp_complementario'] ?? '')) ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Telefono personal</label>
                                <input type="text" class="form-control form-control-sm" name="telefono_personal" maxlength="20" inputmode="tel" placeholder="809-000-0000" value="<?= htmlspecialchars((string) ($empleado['telefono_personal'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label small text-muted mb-1">Informacion contacto</label>
                                <textarea class="form-control form-control-sm" rows="2" name="informacion_contacto"><?= htmlspecialchars((string) ($empleado['contacto_info'] ?? '')) ?></textarea>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Emergencia nombre</label>
                                <input type="text" class="form-control form-control-sm" name="contacto_emergencia_nombre" value="<?= htmlspecialchars((string) ($empleado['contacto_emergencia_nombre'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Emergencia parentesco</label>
                                <select name="contacto_emergencia_parentesco" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <option value="Padre" <?= $parentesco === 'Padre' ? 'selected' : '' ?>>Padre</option>
                                    <option value="Madre" <?= $parentesco === 'Madre' ? 'selected' : '' ?>>Madre</option>
                                    <option value="Hijo" <?= $parentesco === 'Hijo' ? 'selected' : '' ?>>Hijo</option>
                                    <option value="Espos@" <?= $parentesco === 'Espos@' ? 'selected' : '' ?>>Espos@</option>
                                    <option value="Amig@" <?= $parentesco === 'Amig@' ? 'selected' : '' ?>>Amig@</option>
                                    <option value="Otro" <?= $parentesco === 'Otro' ? 'selected' : '' ?>>Otro</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Emergencia telefono</label>
                                <input type="text" class="form-control form-control-sm" name="contacto_emergencia_telefono" maxlength="20" inputmode="tel" placeholder="809-000-0000" value="<?= htmlspecialchars((string) ($empleado['contacto_emergencia_telefono'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="emp-tab-4" role="tabpanel">
                    <div class="section-card">
                        <div class="section-title">Ubicacion y nomina basica</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-12">
                                <label class="form-label small text-muted mb-1">Direccion completa</label>
                                <textarea class="form-control form-control-sm" rows="2" name="direccion_completa"><?= htmlspecialchars((string) ($empleado['direccion_completa'] ?? ($empleado['ubicacion'] ?? ''))) ?></textarea>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Ciudad</label>
                                <input type="text" class="form-control form-control-sm" name="ciudad" value="<?= htmlspecialchars((string) ($empleado['ciudad'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Provincia</label>
                                <input type="text" class="form-control form-control-sm" name="provincia" value="<?= htmlspecialchars((string) ($empleado['provincia'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Pais</label>
                                <input type="text" class="form-control form-control-sm" name="pais" value="<?= htmlspecialchars((string) ($empleado['pais'] ?? '')) ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Codigo postal</label>
                                <input type="text" class="form-control form-control-sm" name="codigo_postal" value="<?= htmlspecialchars((string) ($empleado['codigo_postal'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Salario base</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="salario_base" value="<?= htmlspecialchars((string) ($empleado['salario_base'] ?? '')) ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Frecuencia pago</label>
                                <select name="frecuencia_pago" class="form-select form-select-sm">
                                    <option value="">Seleccione</option>
                                    <option value="Quincenal" <?= $frecuenciaPago === 'Quincenal' ? 'selected' : '' ?>>Quincenal</option>
                                    <option value="Mensual" <?= $frecuenciaPago === 'Mensual' ? 'selected' : '' ?>>Mensual</option>
                                    <option value="Semanal" <?= $frecuenciaPago === 'Semanal' ? 'selected' : '' ?>>Semanal</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Fecha ultimo aumento</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_ultimo_aumento" value="<?= htmlspecialchars((string) ($empleado['fecha_ultimo_aumento'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white border-0 py-3 px-3 px-md-4">
            <div class="d-flex flex-wrap justify-content-start gap-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-check2-circle me-1"></i>Guardar empleado</button>
                <a href="/mantenimientos/terceros/empleados" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-x-circle me-1"></i>Cancelar</a>
            </div>
        </div>
    </form>
</div>
<script>
(() => {
    const dep = document.getElementById('empleado_departamento_id');
    const sub = document.getElementById('empleado_subdepartamento_id');
    if (!dep || !sub) return;

    const syncSubs = () => {
        const depId = dep.value || '';
        if (depId === '') {
            Array.from(sub.options).forEach((opt, idx) => {
                opt.hidden = idx !== 0;
            });
            sub.value = '';
            return;
        }

        let selectedOk = false;
        Array.from(sub.options).forEach((opt, idx) => {
            if (idx === 0) {
                opt.hidden = false;
                return;
            }
            const optDep = opt.getAttribute('data-departamento-id') || '';
            const show = optDep === depId;
            opt.hidden = !show;
            if (!show && opt.selected) {
                opt.selected = false;
            }
            if (show && opt.selected) {
                selectedOk = true;
            }
        });

        if (!selectedOk && sub.value !== '') {
            sub.value = '';
        }
    };

    dep.addEventListener('change', syncSubs);
    syncSubs();
})();
</script>
