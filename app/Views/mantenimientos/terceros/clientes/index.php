<?php
$cliente = is_array($cliente ?? null) ? $cliente : [];

$tipoClienteOptions = ['empresa' => 'Empresa', 'persona_fisica' => 'Persona Fisica'];
$estadoOptions = ['activo' => 'Activo', 'inactivo' => 'Inactivo'];
$condicionPagoOptions = ['contado' => 'Contado', 'credito' => 'Credito'];
$monedaOptions = ['DOP', 'USD', 'EUR'];
$canalVentaOptions = ['mostrador', 'telefonico', 'whatsapp', 'online', 'vendedor'];
$tipoCuentaOptions = ['ahorro', 'corriente'];
$prioridadOptions = ['baja', 'media', 'alta'];
$paisOptions = [
    'Republica Dominicana', 'Estados Unidos', 'Mexico', 'Colombia', 'Costa Rica', 'Panama', 'Guatemala',
    'Honduras', 'Nicaragua', 'El Salvador', 'Puerto Rico', 'Venezuela', 'Ecuador', 'Peru', 'Chile',
    'Argentina', 'Brasil', 'Espana', 'Canada', 'China', 'India'
];
$ncfOptions = [
    'consumidor_final', 'credito_fiscal', 'gubernamental', 'exportacion', 'regimen_especial'
];
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Terceros / Clientes</h2>
            <small class="text-muted">Registro maestro de clientes</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Cliente</div>
                <form method="post" action="/mantenimientos/terceros/clientes" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($cliente['id'] ?? 0) ?>">

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#cli-tab-1" type="button" role="tab">Base y contacto</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cli-tab-2" type="button" role="tab">Comercial y facturacion</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cli-tab-3" type="button" role="tab">Finanzas y clasificacion</button></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="cli-tab-1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">ID</label>
                                    <div class="input-group input-group-sm">
                                        <input class="form-control form-control-sm" value="<?= !empty($cliente['id']) ? (int) $cliente['id'] : '' ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary js-open-client-picker" data-client-redirect="/mantenimientos/terceros/clientes?id={id}" aria-label="Buscar cliente">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Razon social</label><input name="razon_social" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['razon_social'] ?? '')) ?>" required></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Nombre comercial</label><input name="nombre_comercial" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['nombre_comercial'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">RNC</label><input name="rnc" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['rnc'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Cedula</label><input name="cedula" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['cedula'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Tipo cliente</label><select name="tipo_cliente" class="form-select form-select-sm"><?php foreach ($tipoClienteOptions as $k => $v): ?><option value="<?= htmlspecialchars($k) ?>" <?= ((string) ($cliente['tipo_cliente'] ?? 'empresa') === $k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?></select></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Tipo NCF preferido</label><select name="tipo_ncf_preferido" class="form-select form-select-sm"><option value="">Seleccione</option><?php foreach ($ncfOptions as $opt): ?><option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($cliente['tipo_ncf_preferido'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('_', ' ', strtoupper($opt))) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Estado</label><select name="estado" class="form-select form-select-sm"><?php foreach ($estadoOptions as $k => $v): ?><option value="<?= htmlspecialchars($k) ?>" <?= ((string) ($cliente['estado'] ?? 'activo') === $k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Nombre contacto</label><input name="nombre_contacto" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['nombre_contacto'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Cargo contacto</label><input name="cargo_contacto" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['cargo_contacto'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Telefono</label><input name="telefono_cliente" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['telefono_cliente'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Telefono secundario</label><input name="telefono_secundario" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['telefono_secundario'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Whatsapp</label><input name="whatsapp" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['whatsapp'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Correo electronico</label><input type="email" name="correo_electronico" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['correo_electronico'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Correo facturacion</label><input type="email" name="correo_facturacion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['correo_facturacion'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Ciudad</label><input name="ciudad" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['ciudad'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Provincia</label><input name="provincia" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['provincia'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Pais</label><select name="pais" class="form-select form-select-sm"><option value="">Seleccione</option><?php foreach ($paisOptions as $opt): ?><option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($cliente['pais'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option><?php endforeach; ?></select></div>

                                <div class="col-12 col-md-8"><label class="form-label small mb-1">Direccion</label><input name="direccion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['direccion'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Codigo postal</label><input name="codigo_postal" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['codigo_postal'] ?? '')) ?>"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="cli-tab-2" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Condicion pago</label><select name="condicion_pago" class="form-select form-select-sm"><?php foreach ($condicionPagoOptions as $k => $v): ?><option value="<?= htmlspecialchars($k) ?>" <?= ((string) ($cliente['condicion_pago'] ?? 'contado') === $k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Dias credito</label><input type="number" name="dias_credito" min="0" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['dias_credito'] ?? '0')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Limite credito</label><input type="number" step="0.01" name="limite_credito" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['limite_credito'] ?? '0')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Balance actual</label><input type="number" step="0.01" name="balance_actual" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['balance_actual'] ?? '0')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Moneda</label><select name="moneda" class="form-select form-select-sm"><?php foreach ($monedaOptions as $opt): ?><option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($cliente['moneda'] ?? 'DOP') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Descuento default (%)</label><input type="number" step="0.01" min="0" max="100" name="descuento_default" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['descuento_default'] ?? '0')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Vendedor asignado</label><input name="vendedor_asignado" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['vendedor_asignado'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Canal venta</label><select name="canal_venta" class="form-select form-select-sm"><option value="">Seleccione</option><?php foreach ($canalVentaOptions as $opt): ?><option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($cliente['canal_venta'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($opt)) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Tipo comprobante preferido</label><select name="tipo_comprobante_preferido" class="form-select form-select-sm"><option value="">Seleccione</option><?php foreach ($ncfOptions as $opt): ?><option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($cliente['tipo_comprobante_preferido'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('_', ' ', strtoupper($opt))) ?></option><?php endforeach; ?></select></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Secuencia asignada</label><input name="secuencia_asignada" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['secuencia_asignada'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-2 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="aplica_itbis" <?= ((int) ($cliente['aplica_itbis'] ?? 0) === 1) ? 'checked' : '' ?>><label class="form-check-label">Aplica ITBIS</label></div></div>
                                <div class="col-12 col-md-2 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="exento_itbis" <?= ((int) ($cliente['exento_itbis'] ?? 0) === 1) ? 'checked' : '' ?>><label class="form-check-label">Exento ITBIS</label></div></div>
                                <div class="col-12 col-md-2 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="retencion_aplica" <?= ((int) ($cliente['retencion_aplica'] ?? 0) === 1) ? 'checked' : '' ?>><label class="form-check-label">Retencion aplica</label></div></div>
                                <div class="col-12 col-md-2 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="agente_retencion" <?= ((int) ($cliente['agente_retencion'] ?? 0) === 1) ? 'checked' : '' ?>><label class="form-check-label">Agente retencion</label></div></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="cli-tab-3" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Banco cliente</label><input name="banco_cliente" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['banco_cliente'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Numero cuenta cliente</label><input name="numero_cuenta_cliente" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['numero_cuenta_cliente'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Tipo cuenta cliente</label><select name="tipo_cuenta_cliente" class="form-select form-select-sm"><option value="">Seleccione</option><?php foreach ($tipoCuentaOptions as $opt): ?><option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($cliente['tipo_cuenta_cliente'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($opt)) ?></option><?php endforeach; ?></select></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Tipo negocio</label><input name="tipo_negocio" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['tipo_negocio'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Sector</label><input name="sector" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['sector'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Categoria cliente</label><input name="categoria_cliente" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['categoria_cliente'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Prioridad</label><select name="prioridad" class="form-select form-select-sm"><option value="">Seleccione</option><?php foreach ($prioridadOptions as $opt): ?><option value="<?= htmlspecialchars($opt) ?>" <?= ((string) ($cliente['prioridad'] ?? '') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($opt)) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Calificacion</label><input name="calificacion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['calificacion'] ?? '')) ?>"></div>
                                <div class="col-12 col-md-4"><label class="form-label small mb-1">Correos notificacion adicional</label><input name="correos_notificacion_adicional" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($cliente['correos_notificacion_adicional'] ?? '')) ?>"></div>

                                <div class="col-12 col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="recibir_factura_correo" <?= ((int) ($cliente['recibir_factura_correo'] ?? 0) === 1) ? 'checked' : '' ?>><label class="form-check-label">Recibir factura por correo</label></div></div>
                                <div class="col-12 col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="recibir_estado_cuenta" <?= ((int) ($cliente['recibir_estado_cuenta'] ?? 0) === 1) ? 'checked' : '' ?>><label class="form-check-label">Recibir estado de cuenta</label></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar cliente</button>
                        <?php if (!empty($cliente['id'])): ?>
                            <a href="/mantenimientos/terceros/clientes" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                        <?php else: ?>
                            <a href="/mantenimientos/terceros/clientes" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
