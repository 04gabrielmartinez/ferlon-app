<?php
$proveedor = is_array($proveedor ?? null) ? $proveedor : [];
$proveedoresModal = is_array($proveedoresModal ?? null) ? $proveedoresModal : [];
$bancos = is_array($bancos ?? null) ? $bancos : [];

$tipoProveedorOptions = ['fabricante', 'distribuidor', 'servicios', 'importador', 'otro'];
$paisOptions = [
    'Republica Dominicana', 'Estados Unidos', 'Mexico', 'Colombia', 'Costa Rica', 'Panama', 'Guatemala',
    'Honduras', 'Nicaragua', 'El Salvador', 'Puerto Rico', 'Venezuela', 'Ecuador', 'Peru', 'Chile',
    'Argentina', 'Brasil', 'Espana', 'Canada', 'China', 'India'
];
$monedaOptions = ['DOP', 'USD', 'EUR', 'MXN', 'COP', 'CLP'];
$tipoBancoOptions = ['comercial', 'estatal', 'internacional', 'ahorro_credito', 'ahorro_prestamo', 'cooperativa', 'otro'];
$condicionPagoOptions = ['contado', 'credito_15', 'credito_30', 'credito_45', 'credito_60', 'credito_90'];
$categoriaProveedorOptions = ['local', 'internacional', 'estrategico', 'critico', 'ocasional', 'servicios'];
$estadoOptions = ['activo', 'inactivo', 'bloqueado'];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Terceros / Proveedores</h2>
            <small class="text-muted">Registro maestro de proveedores</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Proveedor</div>
                <form method="post" action="/mantenimientos/terceros/proveedores" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($proveedor['id'] ?? 0) ?>">

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#prov-tab-1" type="button" role="tab">Datos generales</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#prov-tab-2" type="button" role="tab">Financiero</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#prov-tab-3" type="button" role="tab">Operativo y control</button></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="prov-tab-1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">ID proveedor</label>
                                    <div class="input-group input-group-sm">
                                        <input class="form-control form-control-sm" value="<?= (int) ($proveedor['id'] ?? 0) ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary js-open-provider-picker" data-provider-redirect="/mantenimientos/terceros/proveedores?id={id}" aria-label="Buscar proveedor">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Razon social</label><input name="razon_social" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['razon_social'] ?? '')) ?>" required></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Nombre comercial</label><input name="nombre_comercial" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['nombre_comercial'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">RNC</label><input name="rnc" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['rnc'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Cedula</label><input name="cedula" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['cedula'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Tipo proveedor</label>
                                    <select name="tipo_proveedor" class="form-select form-select-sm">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($tipoProveedorOptions as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($proveedor['tipo_proveedor'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $opt))) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <select name="estado" class="form-select form-select-sm">
                                        <?php foreach ($estadoOptions as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($proveedor['estado'] ?? 'activo') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($opt)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Correo</label><input type="email" name="correo" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['correo'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Telefono</label><input name="telefono" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['telefono'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Telefono secundario</label><input name="telefono_secundario" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['telefono_secundario'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Whatsapp</label><input name="whatsapp" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['whatsapp'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Contacto nombre</label><input name="contacto_nombre" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['contacto_nombre'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Contacto telefono</label><input name="contacto_telefono" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['contacto_telefono'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Contacto email</label><input type="email" name="contacto_email" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['contacto_email'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Contacto cargo</label><input name="contacto_cargo" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['contacto_cargo'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Ciudad</label><input name="ciudad" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['ciudad'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Provincia</label><input name="provincia" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['provincia'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Pais</label>
                                    <select name="pais" class="form-select form-select-sm">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($paisOptions as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($proveedor['pais'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Direccion</label><input name="direccion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['direccion'] ?? '')) ?>" placeholder="Direccion completa"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Codigo postal</label><input name="codigo_postal" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['codigo_postal'] ?? '')) ?>"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="prov-tab-2" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Condicion pago</label>
                                    <select name="condicion_pago" class="form-select form-select-sm">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($condicionPagoOptions as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($proveedor['condicion_pago'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $opt))) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Dias credito</label><input type="number" name="dias_credito" class="form-control form-control-sm" min="0" value="<?= htmlspecialchars((string) ($proveedor['dias_credito'] ?? '0')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Limite credito</label><input type="number" step="0.01" name="limite_credito" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['limite_credito'] ?? '0')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Balance actual</label><input type="number" step="0.01" name="balance_actual" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['balance_actual'] ?? '0')) ?>"></div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Moneda</label>
                                    <select name="moneda" class="form-select form-select-sm">
                                        <?php foreach ($monedaOptions as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($proveedor['moneda'] ?? 'DOP') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Banco</label>
                                    <select name="banco" class="form-select form-select-sm">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($bancos as $banco): ?>
                                            <?php $nombreBanco = (string) ($banco['nombre'] ?? ''); ?>
                                            <?php if ($nombreBanco !== ''): ?>
                                                <option value="<?= htmlspecialchars($nombreBanco) ?>" <?= ((string) ($proveedor['banco'] ?? '') === $nombreBanco) ? 'selected' : '' ?>><?= htmlspecialchars($nombreBanco) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Tipo de banco</label>
                                    <?php $tipoBancoActual = (string) ($proveedor['tipo_banco'] ?? ''); ?>
                                    <select name="tipo_banco" class="form-select form-select-sm">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($tipoBancoOptions as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>" <?= ($tipoBancoActual === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $opt))) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Numero cuenta</label><input name="numero_cuenta" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['numero_cuenta'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Titular cuenta</label><input name="titular_cuenta" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['titular_cuenta'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">RNC titular</label><input name="rnc_titular" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['rnc_titular'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Cuenta contable</label><input name="cuenta_contable" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['cuenta_contable'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Tipo gasto</label><input name="tipo_gasto" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['tipo_gasto'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Retencion ITBIS</label><input type="number" step="0.01" name="retencion_itbis" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['retencion_itbis'] ?? '0')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Retencion ISR</label><input type="number" step="0.01" name="retencion_isr" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['retencion_isr'] ?? '0')) ?>"></div>
                                <div class="col-12 col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="aplica_impuestos" <?= ((int) ($proveedor['aplica_impuestos'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Aplica impuestos</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="prov-tab-3" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Categoria proveedor</label>
                                    <select name="categoria_proveedor" class="form-select form-select-sm">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($categoriaProveedorOptions as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($proveedor['categoria_proveedor'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($opt)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Rubro</label><input name="rubro" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['rubro'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Tiempo entrega</label><input name="tiempo_entrega" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['tiempo_entrega'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Calificacion</label><input name="calificacion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['calificacion'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Contrato</label><input name="contrato" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['contrato'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Documento RNC</label><input name="documento_rnc" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['documento_rnc'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Documento identidad</label><input name="documento_identidad" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['documento_identidad'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Otros documentos</label><input name="otros_documentos" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['otros_documentos'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Correos notificacion (coma)</label><input name="correos_notificacion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['correos_notificacion'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Observaciones</label><input name="observaciones" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['observaciones'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="recibir_ordenes_correo" <?= ((int) ($proveedor['recibir_ordenes_correo'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Recibir ordenes correo</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="recibir_pagos_correo" <?= ((int) ($proveedor['recibir_pagos_correo'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Recibir pagos correo</label>
                                    </div>
                                </div>

                                <div class="col-12 col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="inactivo" <?= ((int) ($proveedor['inactivo'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Inactivo</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Fecha inactivo</label><input type="date" name="fecha_inactivo" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['fecha_inactivo'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Motivo inactivo</label><input name="motivo_inactivo" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($proveedor['motivo_inactivo'] ?? '')) ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar proveedor</button>
                        <?php if (!empty($proveedor['id'])): ?>
                            <a href="/mantenimientos/terceros/proveedores" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                        <?php else: ?>
                            <a href="/mantenimientos/terceros/proveedores" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="modal fade employee-picker-modal" id="providerPickerModal" tabindex="-1" aria-labelledby="providerPickerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title d-flex align-items-center gap-2" id="providerPickerModalLabel">
                                    <i class="bi bi-truck"></i>
                                    <span>Buscar proveedor</span>
                                </h5>
                                <small class="text-muted">Click sobre una fila para editar</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <table id="providerPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Razon social</th>
                                        <th>RNC</th>
                                        <th>Telefono</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proveedoresModal as $row): ?>
                                        <tr class="js-provider-row" data-provider-id="<?= (int) ($row['id'] ?? 0) ?>">
                                            <td><?= (int) ($row['id'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['razon_social'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['rnc'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['telefono'] ?? '')) ?></td>
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
