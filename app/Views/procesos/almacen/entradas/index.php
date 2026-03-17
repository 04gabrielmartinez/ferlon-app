<?php
$proveedores = is_array($proveedores ?? null) ? $proveedores : [];
$articulos = is_array($articulos ?? null) ? $articulos : [];
$ocRegistros = is_array($ocRegistros ?? null) ? $ocRegistros : [];
$entradaRegistros = is_array($entradaRegistros ?? null) ? $entradaRegistros : [];
$entradaSeleccionada = is_array($entradaSeleccionada ?? null) ? $entradaSeleccionada : [];
$ocSeleccionada = is_array($ocSeleccionada ?? null) ? $ocSeleccionada : [];
$detallesDesdeOc = is_array($detallesDesdeOc ?? null) ? $detallesDesdeOc : [];
$entradaIdSel = (int) ($entradaSeleccionada['id'] ?? 0);
$ocIdSel = (int) ($entradaSeleccionada['orden_compra_id'] ?? ($ocSeleccionada['id'] ?? 0));
$ocCodigoSel = (string) ($ocSeleccionada['codigo_compra'] ?? ($entradaSeleccionada['oc_codigo'] ?? ''));
$codigoEntrada = (string) ($entradaSeleccionada['codigo_entrada'] ?? '');
$fechaActual = (string) ($entradaSeleccionada['fecha'] ?? date('Y-m-d'));
$empleadoNombre = (string) ($empleadoNombre ?? 'Usuario');
$empleadoId = (int) ($empleadoId ?? 0);
$puedeCambiarEmpleado = (bool) ($puedeCambiarEmpleado ?? false);
$proveedorSelId = (int) ($entradaSeleccionada['proveedor_id'] ?? ($ocSeleccionada['proveedor_id'] ?? 0));
$proveedorSelLabel = (string) ($entradaSeleccionada['proveedor_label'] ?? ($ocSeleccionada['proveedor_label'] ?? ''));
$proveedorSelRnc = (string) ($entradaSeleccionada['proveedor_rnc'] ?? ($ocSeleccionada['proveedor_rnc'] ?? ''));
$condicionPagoSel = (string) ($entradaSeleccionada['condicion_pago'] ?? ($ocSeleccionada['condicion_pago'] ?? ''));
$ncfSel = (string) ($entradaSeleccionada['ncf'] ?? '');
$ordenNoSel = (string) ($entradaSeleccionada['orden_no'] ?? '');
$facturaNoSel = (string) ($entradaSeleccionada['factura_no'] ?? '');
$pedidoNoSel = (string) ($entradaSeleccionada['pedido_no'] ?? '');
$comentarioSel = (string) ($entradaSeleccionada['comentario'] ?? '');
$subtotalSel = (string) ($entradaSeleccionada['subtotal'] ?? '0');
$descSel = (string) ($entradaSeleccionada['total_descuento'] ?? '0');
$descPctSel = (string) ($entradaSeleccionada['descuento_general_pct'] ?? ($ocSeleccionada['descuento_general_pct'] ?? '0'));
$impuestoSel = (string) ($entradaSeleccionada['impuesto'] ?? '0');
$totalSel = (string) ($entradaSeleccionada['total_compra'] ?? '0');
$monedaSel = (string) ($entradaSeleccionada['moneda'] ?? ($ocSeleccionada['moneda'] ?? 'DOP'));
$estadoSel = (string) ($entradaSeleccionada['estado'] ?? 'abierta');
$detallesSel = is_array($entradaSeleccionada['detalles'] ?? null) ? $entradaSeleccionada['detalles'] : $detallesDesdeOc;
$lockInfo = is_array($lockInfo ?? null) ? $lockInfo : [];
$lockActivo = (bool) ($lockInfo['locked'] ?? false);
$lockOwner = (bool) ($lockInfo['owner'] ?? false);
$lockBloqueado = $lockActivo && !$lockOwner;
$lockData = is_array($lockInfo['lock'] ?? null) ? $lockInfo['lock'] : [];
$lockNombre = trim((string) ($lockData['nombre'] ?? ''));
if ($lockNombre === '') {
    $lockNombre = trim((string) ($lockData['username'] ?? $lockData['email'] ?? 'Usuario'));
}
$lockExpira = (string) ($lockData['expira_en'] ?? '');
$codigoEntradaDisplay = $entradaIdSel > 0 ? $codigoEntrada : 'Se asigna al guardar';
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Procesos / Almacen / Entradas</h2>
            <small class="text-muted">Registro de entradas de compra con o sin orden de compra</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-3">
                <div class="section-title">Formulario de Entrada de Compra</div>
                <form method="post" action="/procesos/almacen/entradas" id="entradaCompraForm" class="employee-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="entrada_id" value="<?= $entradaIdSel ?>">
                    <input type="hidden" name="oc_id" id="oc_id" value="<?= $ocIdSel ?>">
                    <input type="hidden" name="empleado_id" id="empleado_id_entrada" value="<?= $empleadoId ?>">
                    <input type="hidden" name="proveedor_id" id="proveedor_id" value="<?= $proveedorSelId ?>">
                    <input type="hidden" name="proveedor_label" id="proveedor_label_hidden" value="<?= htmlspecialchars($proveedorSelLabel) ?>">

                    <?php if ($lockBloqueado): ?>
                        <div class="alert alert-warning small d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-lock-fill"></i>
                            <span>Este registro esta siendo editado por <?= htmlspecialchars($lockNombre) ?>. Bloqueo valido hasta <?= htmlspecialchars($lockExpira) ?>.</span>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small mb-1">Codigo entrada</label>
                                    <div class="input-group input-group-sm">
                                        <?php if ($entradaIdSel > 0): ?>
                                            <input type="text" class="form-control" name="codigo_entrada" value="<?= htmlspecialchars($codigoEntrada) ?>" readonly>
                                        <?php else: ?>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($codigoEntradaDisplay) ?>" readonly>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#entradaPickerModal" aria-label="Buscar entrada">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Orden de compra (opcional)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="oc_codigo" class="form-control" value="<?= htmlspecialchars($ocCodigoSel) ?>" readonly placeholder="Selecciona una OC">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#ocPickerModal" aria-label="Buscar orden de compra">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Si seleccionas OC, se cargan los articulos pendientes.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Fecha</label>
                                    <input type="date" class="form-control form-control-sm" name="fecha" value="<?= htmlspecialchars($fechaActual) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Empleado</label>
                                    <?php if ($puedeCambiarEmpleado): ?>
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="empleado_nombre_entrada" class="form-control" value="<?= htmlspecialchars($empleadoNombre) ?>" readonly>
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary js-open-employee-picker"
                                                data-employee-target="#empleado_id_entrada"
                                                aria-label="Buscar empleado"
                                            >
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <input type="text" id="empleado_nombre_entrada" class="form-control form-control-sm" value="<?= htmlspecialchars($empleadoNombre) ?>" readonly>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">NCF</label>
                                    <input type="text" name="ncf" class="form-control form-control-sm" value="<?= htmlspecialchars($ncfSel) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Orden No.</label>
                                    <input type="text" name="orden_no" class="form-control form-control-sm" value="<?= htmlspecialchars($ordenNoSel) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small mb-1">Proveedor</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="proveedor_label" class="form-control" readonly placeholder="Selecciona proveedor" value="<?= htmlspecialchars($proveedorSelLabel) ?>">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#providerPickerModal" aria-label="Buscar proveedor">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">RNC</label>
                                    <input type="text" id="proveedor_rnc" name="proveedor_rnc" class="form-control form-control-sm" readonly value="<?= htmlspecialchars($proveedorSelRnc) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Condicion pago</label>
                                    <select id="proveedor_condicion_pago" name="condicion_pago" class="form-select form-select-sm">
                                        <option value="">Seleccione</option>
                                        <option value="contado" <?= $condicionPagoSel === 'contado' ? 'selected' : '' ?>>CONTADO</option>
                                        <option value="credito_15" <?= $condicionPagoSel === 'credito_15' ? 'selected' : '' ?>>CREDITO 15</option>
                                        <option value="credito_30" <?= $condicionPagoSel === 'credito_30' ? 'selected' : '' ?>>CREDITO 30</option>
                                        <option value="credito_45" <?= $condicionPagoSel === 'credito_45' ? 'selected' : '' ?>>CREDITO 45</option>
                                        <option value="credito_60" <?= $condicionPagoSel === 'credito_60' ? 'selected' : '' ?>>CREDITO 60</option>
                                        <option value="credito_90" <?= $condicionPagoSel === 'credito_90' ? 'selected' : '' ?>>CREDITO 90</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Estado</label>
                                    <select name="estado" class="form-select form-select-sm">
                                        <option value="abierta" <?= $estadoSel === 'abierta' ? 'selected' : '' ?>>ABIERTA</option>
                                        <option value="cerrada" <?= $estadoSel === 'cerrada' ? 'selected' : '' ?>>CERRADA</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Factura No.</label>
                                    <input type="text" name="factura_no" class="form-control form-control-sm" value="<?= htmlspecialchars($facturaNoSel) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1">Pedido No.</label>
                                    <input type="text" name="pedido_no" class="form-control form-control-sm" value="<?= htmlspecialchars($pedidoNoSel) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="border rounded-4 p-3 bg-light-subtle h-100">
                                <div class="small text-muted mb-2">Totales</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Moneda</label>
                                        <select id="moneda_compra" name="moneda" class="form-select form-select-sm">
                                            <option value="DOP" <?= $monedaSel === 'DOP' ? 'selected' : '' ?>>DOP</option>
                                            <option value="USD" <?= $monedaSel === 'USD' ? 'selected' : '' ?>>USD</option>
                                            <option value="EUR" <?= $monedaSel === 'EUR' ? 'selected' : '' ?>>EUR</option>
                                            <option value="MXN" <?= $monedaSel === 'MXN' ? 'selected' : '' ?>>MXN</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Desc %</label>
                                        <input type="number" step="0.01" min="0" max="100" id="descuento_general_pct" name="descuento_general_pct" class="form-control form-control-sm" value="<?= htmlspecialchars($descPctSel) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Subtotal</label>
                                        <input type="text" id="subtotal_compra" class="form-control form-control-sm" value="<?= number_format((float) $subtotalSel, 2, '.', ',') ?>" readonly>
                                        <input type="hidden" id="subtotal_compra_raw" name="subtotal" value="<?= htmlspecialchars($subtotalSel) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Descuento</label>
                                        <input type="text" id="total_descuento_compra" class="form-control form-control-sm" value="<?= number_format((float) $descSel, 2, '.', ',') ?>" readonly>
                                        <input type="hidden" id="total_descuento_compra_raw" name="total_descuento" value="<?= htmlspecialchars($descSel) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Impuesto</label>
                                        <input type="text" id="impuesto_total_compra" class="form-control form-control-sm" value="<?= number_format((float) $impuestoSel, 2, '.', ',') ?>" readonly>
                                        <input type="hidden" id="impuesto_total_compra_raw" name="impuesto" value="<?= htmlspecialchars($impuestoSel) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Total entrada</label>
                                        <input type="text" id="total_compra" class="form-control fw-semibold fs-4" value="<?= number_format((float) $totalSel, 2, '.', ',') ?>" readonly>
                                        <input type="hidden" id="total_compra_raw" name="total_compra" value="<?= htmlspecialchars($totalSel) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label class="form-label small mb-1">Comentario</label>
                            <textarea name="comentario" class="form-control form-control-sm" rows="2" placeholder="Observaciones de la entrada"><?= htmlspecialchars($comentarioSel) ?></textarea>
                        </div>
                    </div>

                    <hr class="my-3">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <div class="section-title mb-0">Detalle de Articulos</div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Codigo / busqueda</label>
                            <div class="input-group input-group-sm">
                                <input type="hidden" id="detalle_articulo_id_sel" value="">
                                <input type="text" id="detalle_articulo_codigo_sel" class="form-control" readonly placeholder="Selecciona articulo">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#articuloCompraPickerModal" aria-label="Buscar articulo">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Descripcion</label>
                            <input type="text" id="detalle_articulo_desc_sel" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Cantidad</label>
                            <input type="number" step="0.0001" min="0.0001" id="detalle_cantidad_sel" class="form-control form-control-sm" value="1">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Unidad (empaque)</label>
                            <select id="detalle_unidad_sel" class="form-select form-select-sm">
                                <option value="u" selected>Unidad (u)</option>
                                <option value="caja">Caja</option>
                                <option value="funda">Funda</option>
                                <option value="paquete">Paquete</option>
                                <option value="saco">Saco</option>
                                <option value="bulto">Bulto</option>
                                <option value="paca">Paca</option>
                                <option value="pallet">Pallet</option>
                                <option value="tina">Tina</option>
                                <option value="cubeta">Cubeta</option>
                                <option value="tambor">Tambor</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Costo</label>
                            <input type="number" step="0.0001" min="0" id="detalle_costo_sel" class="form-control form-control-sm" value="0">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Cant x unidad</label>
                            <input type="number" step="0.0001" min="0" id="detalle_cant_por_unidad_sel" class="form-control form-control-sm" value="">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Peso x unidad</label>
                            <input type="number" step="0.0001" min="0" id="detalle_peso_por_unidad_sel" class="form-control form-control-sm" value="">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Peso uni</label>
                            <select id="detalle_peso_unidad_sel" class="form-select form-select-sm">
                                <option value="g" selected>g</option>
                                <option value="kg">kg</option>
                                <option value="lb">lb</option>
                                <option value="oz">oz</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-1">Desc %</label>
                            <input type="number" step="0.01" min="0" max="100" id="detalle_desc_pct_sel" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-end gap-1">
                            <button type="button" class="btn btn-primary btn-sm w-100" id="btnDetalleGuardar">Bajar</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDetalleLimpiar" title="Limpiar"><i class="bi bi-eraser"></i></button>
                        </div>
                        <div class="col-12 col-md-2 d-none d-md-block"></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="detalleCompraTable">
                            <thead>
                                <tr>
                                    <th style="width:90px;">Codigo</th>
                                    <th>Descripcion</th>
                                    <th style="width:140px;">Cantidad</th>
                                    <th style="width:120px;">Cant/unidad</th>
                                    <th style="width:150px;">Peso/unidad</th>
                                    <th style="width:130px;">Costo</th>
                                    <th style="width:90px;">Desc %</th>
                                    <th style="width:130px;">Total</th>
                                    <th style="width:70px;">Accion</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3" <?= $lockBloqueado ? 'disabled' : '' ?>>Guardar entrada</button>
                        <?php if ($entradaIdSel > 0): ?>
                            <a class="btn btn-outline-secondary btn-sm rounded-pill px-3" target="_blank" href="/procesos/almacen/entradas/imprimir?id=<?= $entradaIdSel ?>">Imprimir</a>
                        <?php endif; ?>
                        <a href="/procesos/almacen/entradas" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="ocPickerModal" tabindex="-1" aria-labelledby="ocPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="ocPickerModalLabel">
                        <i class="bi bi-receipt"></i>
                        <span>Buscar orden de compra</span>
                    </h5>
                    <small class="text-muted">Click en una fila para cargar</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="ocPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ocRegistros as $oc): $oid = (int) ($oc['id'] ?? 0); if ($oid <= 0) continue; ?>
                            <tr class="js-oc-row" data-oc-id="<?= $oid ?>">
                                <td><?= htmlspecialchars((string) ($oc['codigo_compra'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($oc['fecha'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($oc['proveedor_label'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($oc['total_compra'] ?? '0')) ?></td>
                                <td><?= htmlspecialchars((string) ($oc['estado'] ?? 'abierta')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="entradaPickerModal" tabindex="-1" aria-labelledby="entradaPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="entradaPickerModalLabel">
                        <i class="bi bi-box-arrow-in-down"></i>
                        <span>Buscar entrada</span>
                    </h5>
                    <small class="text-muted">Click en una fila para cargar</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="entradaPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>OC</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entradaRegistros as $e): $eid = (int) ($e['id'] ?? 0); if ($eid <= 0) continue; ?>
                            <tr class="js-entrada-row" data-entrada-id="<?= $eid ?>">
                                <td><?= htmlspecialchars((string) ($e['codigo_entrada'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($e['fecha'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($e['proveedor_label'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($e['oc_codigo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($e['total_compra'] ?? '0')) ?></td>
                                <td><?= htmlspecialchars((string) ($e['estado'] ?? 'abierta')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="providerPickerModal" tabindex="-1" aria-labelledby="providerPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="providerPickerModalLabel">
                        <i class="bi bi-truck"></i>
                        <span>Seleccionar proveedor</span>
                    </h5>
                    <small class="text-muted">Click en una fila para seleccionar</small>
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
                            <th>Condicion pago</th>
                            <th>Telefono</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $p): $pid = (int) ($p['id'] ?? 0); if ($pid <= 0) continue; ?>
                            <tr class="js-oc-proveedor-row"
                                data-proveedor-id="<?= $pid ?>"
                                data-razon="<?= htmlspecialchars((string) ($p['razon_social'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-rnc="<?= htmlspecialchars((string) ($p['rnc'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-condicion-pago="<?= htmlspecialchars((string) ($p['condicion_pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-telefono="<?= htmlspecialchars((string) ($p['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= $pid ?></td>
                                <td><?= htmlspecialchars((string) ($p['razon_social'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['rnc'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['condicion_pago'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['telefono'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['estado'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="articuloCompraPickerModal" tabindex="-1" aria-labelledby="articuloCompraPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="articuloCompraPickerModalLabel">
                        <i class="bi bi-box-seam"></i>
                        <span>Seleccionar articulo</span>
                    </h5>
                    <small class="text-muted">Solo articulos comprables activos</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="articuloCompraPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Descripcion</th>
                            <th>Unidad</th>
                            <th>Costo ref.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articulos as $a): $aid = (int) ($a['id'] ?? 0); if ($aid <= 0) continue; ?>
                            <tr class="js-oc-articulo-row"
                                data-articulo-id="<?= $aid ?>"
                                data-codigo="<?= htmlspecialchars((string) ($a['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-descripcion="<?= htmlspecialchars((string) ($a['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-unidad="<?= htmlspecialchars((string) ($a['unidad_base_id'] ?? 'u'), ENT_QUOTES, 'UTF-8') ?>"
                                data-costo="<?= htmlspecialchars((string) ($a['costo_ultimo'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                data-impuestos="<?= htmlspecialchars((string) ($a['impuestos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= htmlspecialchars((string) ($a['codigo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($a['descripcion'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($a['unidad_base_id'] ?? 'u')) ?></td>
                                <td><?= number_format((float) ($a['costo_ultimo'] ?? 0), 2, '.', ',') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const formEl = document.getElementById('entradaCompraForm');
    const empleadoIdEl = document.getElementById('empleado_id_entrada');
    const empleadoNombreEl = document.getElementById('empleado_nombre_entrada');
    const empleadoIdBadgeEl = document.getElementById('empleado_id_badge');
    const proveedorIdEl = document.getElementById('proveedor_id');
    const proveedorLabelEl = document.getElementById('proveedor_label');
    const proveedorLabelHiddenEl = document.getElementById('proveedor_label_hidden');
    const proveedorRncEl = document.getElementById('proveedor_rnc');
    const proveedorCondicionPagoEl = document.getElementById('proveedor_condicion_pago');
    const proveedorModalEl = document.getElementById('providerPickerModal');
    const articuloModalEl = document.getElementById('articuloCompraPickerModal');
    const ocIdEl = document.getElementById('oc_id');
    const ocCodigoEl = document.getElementById('oc_codigo');

    const detalleTableEl = document.getElementById('detalleCompraTable');
    const detalleArticuloIdSel = document.getElementById('detalle_articulo_id_sel');
    const detalleArticuloCodigoSel = document.getElementById('detalle_articulo_codigo_sel');
    const detalleArticuloDescSel = document.getElementById('detalle_articulo_desc_sel');
    const detalleCantidadSel = document.getElementById('detalle_cantidad_sel');
    const detalleUnidadSel = document.getElementById('detalle_unidad_sel');
    const detalleCantPorUnidadSel = document.getElementById('detalle_cant_por_unidad_sel');
    const detallePesoPorUnidadSel = document.getElementById('detalle_peso_por_unidad_sel');
    const detallePesoUnidadSel = document.getElementById('detalle_peso_unidad_sel');
    const detalleCostoSel = document.getElementById('detalle_costo_sel');
    const detalleDescPctSel = document.getElementById('detalle_desc_pct_sel');
    const btnDetalleGuardar = document.getElementById('btnDetalleGuardar');
    const btnDetalleLimpiar = document.getElementById('btnDetalleLimpiar');

    const subtotalEl = document.getElementById('subtotal_compra');
    const totalDescEl = document.getElementById('total_descuento_compra');
    const impuestoTotalEl = document.getElementById('impuesto_total_compra');
    const totalCompraEl = document.getElementById('total_compra');
    const subtotalRawEl = document.getElementById('subtotal_compra_raw');
    const totalDescRawEl = document.getElementById('total_descuento_compra_raw');
    const impuestoRawEl = document.getElementById('impuesto_total_compra_raw');
    const totalRawEl = document.getElementById('total_compra_raw');
    const descuentoGeneralPctEl = document.getElementById('descuento_general_pct');

    let detalleItems = <?= json_encode($detallesSel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let selectedArticuloId = null;
    let selectedOcDetalleId = null;
    let selectedArticuloImpuestoPct = 0;

    const showToast = (message, type = 'danger', title = 'Entrada de compra') => {
        const text = (message || '').trim();
        if (!text) return;
        const payload = { message: text, type, title, delay: 5000 };
        if (window.AppToast && typeof window.AppToast.show === 'function') {
            window.AppToast.show(payload);
            return;
        }
        window.__pendingAppToasts = Array.isArray(window.__pendingAppToasts) ? window.__pendingAppToasts : [];
        window.__pendingAppToasts.push(payload);
    };

    const toNum = (v) => Number.isFinite(Number(v)) ? Number(v) : 0;
    const round4 = (v) => Math.round(toNum(v) * 10000) / 10000;
    const round2 = (v) => Math.round(toNum(v) * 100) / 100;
    const normalizeUnidad = (unidadRaw, fallback = 'u') => {
        const unidad = String(unidadRaw || fallback).trim().toLowerCase();
        return unidad === '' ? fallback : unidad;
    };
    const isPesoUnidad = (unidad) => ['kg', 'g', 'lb', 'oz'].includes(unidad);
    const formatNumber = (value, decimals = 2) => {
        const v = Number(value);
        if (!Number.isFinite(v)) return '';
        return v.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    };
    const calcArticleTaxPct = (rawImpuestos) => {
        const txt = String(rawImpuestos || '').toUpperCase();
        if (txt.includes('ITBIS')) return 18;
        return 0;
    };

    const limpiarSeleccion = () => {
        selectedArticuloId = null;
        selectedOcDetalleId = null;
        selectedArticuloImpuestoPct = 0;
        if (detalleArticuloIdSel) detalleArticuloIdSel.value = '';
        if (detalleArticuloCodigoSel) detalleArticuloCodigoSel.value = '';
        if (detalleArticuloDescSel) detalleArticuloDescSel.value = '';
        if (detalleCantidadSel) detalleCantidadSel.value = '1';
        if (detalleUnidadSel) detalleUnidadSel.value = 'u';
        if (detalleCantPorUnidadSel) detalleCantPorUnidadSel.value = '';
        if (detallePesoPorUnidadSel) detallePesoPorUnidadSel.value = '';
        if (detallePesoUnidadSel) detallePesoUnidadSel.value = 'g';
        if (detalleCostoSel) detalleCostoSel.value = '0';
        if (detalleDescPctSel) detalleDescPctSel.value = '0';
        if (btnDetalleGuardar) btnDetalleGuardar.textContent = 'Bajar';
        detalleTableEl?.querySelectorAll('tbody tr').forEach((tr) => tr.classList.remove('table-primary'));
    };

    const syncPesoInputs = () => {
        const unidad = normalizeUnidad(detalleUnidadSel?.value || 'u', 'u');
        const disablePeso = unidad === 'u';
        if (detallePesoPorUnidadSel) {
            detallePesoPorUnidadSel.disabled = disablePeso;
            if (disablePeso) detallePesoPorUnidadSel.value = '';
        }
        if (detallePesoUnidadSel) {
            detallePesoUnidadSel.disabled = disablePeso;
            if (disablePeso) detallePesoUnidadSel.value = 'g';
        }
    };

    const recalcTotales = () => {
        let subtotal = 0;
        let totalDescuento = 0;
        let totalImpuesto = 0;
        detalleItems.forEach((it) => {
            const base = round4(toNum(it.cantidad) * toNum(it.costo));
            const desc = round4(base * (toNum(it.desc_pct ?? it.descPct) / 100));
            const neto = round4(base - desc);
            const impuestoPct = round4(toNum(it.impuesto_pct ?? it.impuestoPct));
            const imp = round4(neto * (impuestoPct / 100));
            subtotal += base;
            totalDescuento += desc;
            totalImpuesto += imp;
        });
        const descGeneralPct = round4(toNum(descuentoGeneralPctEl?.value || '0'));
        const descGeneral = round4(subtotal * (descGeneralPct / 100));
        totalDescuento += descGeneral;
        subtotal = round2(subtotal);
        totalDescuento = round2(totalDescuento);
        totalImpuesto = round2(totalImpuesto);
        const baseImp = round2(subtotal - totalDescuento);
        const total = round2(baseImp + totalImpuesto);
        if (subtotalEl) subtotalEl.value = formatNumber(subtotal, 2);
        if (totalDescEl) totalDescEl.value = formatNumber(totalDescuento, 2);
        if (impuestoTotalEl) impuestoTotalEl.value = formatNumber(totalImpuesto, 2);
        if (totalCompraEl) totalCompraEl.value = formatNumber(total, 2);
        if (subtotalRawEl) subtotalRawEl.value = String(subtotal.toFixed(2));
        if (totalDescRawEl) totalDescRawEl.value = String(totalDescuento.toFixed(2));
        if (impuestoRawEl) impuestoRawEl.value = String(totalImpuesto.toFixed(2));
        if (totalRawEl) totalRawEl.value = String(total.toFixed(2));
    };

    let detalleTable = null;
    const renderDetalle = () => {
        if (detalleTable) {
            detalleTable.clear();
            detalleTable.rows.add(detalleItems);
            detalleTable.draw(false);
            recalcTotales();
            return;
        }
        const tbody = detalleTableEl?.querySelector('tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        detalleItems.forEach((it) => {
            const cantidad = round4(toNum(it.cantidad));
            const costo = round4(toNum(it.costo));
            const descPct = round4(toNum(it.desc_pct ?? it.descPct));
            const impuestoPct = round4(toNum(it.impuesto_pct ?? it.impuestoPct));
            const base = round4(cantidad * costo);
            const descuento = round4(base * (descPct / 100));
            const totalLinea = round4(base - descuento);
            const codigo = String(it.codigo || '');
            const descripcion = String(it.descripcion || '');
            const unidad = normalizeUnidad(it.unidad || 'u', 'u');
            const cantPorUnidad = round4(toNum(it.cant_por_unidad ?? it.cantidad_por_unidad ?? '0'));
            const pesoPorUnidad = round4(toNum(it.peso_por_unidad ?? '0'));
            const pesoUnidad = normalizeUnidad(it.peso_unidad || 'g', 'g');
            const aid = Number(it.articuloId || it.articulo_id || 0);
            const ocDetalleId = Number(it.ocDetalleId || it.oc_detalle_id || it.orden_compra_detalle_id || 0);
            const tr = document.createElement('tr');
            tr.className = 'js-detalle-row';
            tr.setAttribute('data-articulo-id', String(aid));
            tr.innerHTML = `
                <td>${codigo}<input type="hidden" name="detalle_codigo[]" value="${codigo}"><input type="hidden" name="detalle_articulo_id[]" value="${aid}"><input type="hidden" name="detalle_oc_detalle_id[]" value="${ocDetalleId}"></td>
                <td>${descripcion}<input type="hidden" name="detalle_descripcion[]" value="${descripcion}"></td>
                <td>${cantidad} ${unidad}<input type="hidden" name="detalle_cantidad[]" value="${cantidad}"><input type="hidden" name="detalle_unidad[]" value="${unidad}"></td>
                <td>${cantPorUnidad || ''}<input type="hidden" name="detalle_cant_por_unidad[]" value="${cantPorUnidad || ''}"></td>
                <td>${pesoPorUnidad || ''} ${pesoPorUnidad ? pesoUnidad : ''}<input type="hidden" name="detalle_peso_por_unidad[]" value="${pesoPorUnidad || ''}"><input type="hidden" name="detalle_peso_unidad[]" value="${pesoUnidad}"></td>
                <td>${costo.toFixed(4)}<input type="hidden" name="detalle_costo[]" value="${costo}"></td>
                <td>${descPct.toFixed(2)}<input type="hidden" name="detalle_desc_pct[]" value="${descPct}"></td>
                <td>${totalLinea.toFixed(4)}<input type="hidden" name="detalle_impuesto_pct[]" value="${impuestoPct}"><input type="hidden" name="detalle_total[]" value="${totalLinea}"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm js-remove-detalle"><i class="bi bi-trash"></i></button></td>
            `;
            tbody.appendChild(tr);
        });
        recalcTotales();
    };

    const upsertDetalle = () => {
        const aid = Number(detalleArticuloIdSel?.value || '0');
        const codigo = (detalleArticuloCodigoSel?.value || '').trim();
        const descripcion = (detalleArticuloDescSel?.value || '').trim();
        const cantidad = round4(toNum(detalleCantidadSel?.value || '0'));
        const unidad = normalizeUnidad(detalleUnidadSel?.value || 'u', 'u');
        const cantPorUnidad = round4(toNum(detalleCantPorUnidadSel?.value || '0'));
        const pesoPorUnidad = round4(toNum(detallePesoPorUnidadSel?.value || '0'));
        const pesoUnidad = normalizeUnidad(detallePesoUnidadSel?.value || 'g', 'g');
        const costo = round4(toNum(detalleCostoSel?.value || '0'));
        const descPct = round4(toNum(detalleDescPctSel?.value || '0'));
        if (aid <= 0 || codigo === '') return showToast('Selecciona un articulo.', 'warning');
        if (cantidad <= 0) return showToast('La cantidad debe ser mayor que cero.', 'warning');
        if (cantPorUnidad <= 0) return showToast('Cantidad x unidad es obligatoria.', 'warning');
        if (cantPorUnidad <= 0) {
            return showToast('Cantidad x unidad es obligatoria.', 'warning');
        }
        if (unidad !== 'u' && !isPesoUnidad(unidad) && pesoPorUnidad <= 0) {
            return showToast('Para unidades distintas de "u" debes indicar peso x unidad.', 'warning');
        }
        if (unidad !== 'u' && isPesoUnidad(unidad) && pesoPorUnidad <= 0) {
            return showToast('Peso x unidad es obligatorio.', 'warning');
        }
        if (costo <= 0) return showToast('El costo debe ser mayor que cero.', 'warning');
        if (costo < 0) return showToast('El costo no puede ser negativo.', 'warning');
        if (descPct < 0 || descPct > 100) return showToast('Desc % debe estar entre 0 y 100.', 'warning');
        let ocDetalleId = selectedOcDetalleId ?? 0;
        const idxExisting = detalleItems.findIndex((it) => Number(it.articuloId || it.articulo_id) === aid);
        if (!ocDetalleId && idxExisting >= 0) {
            ocDetalleId = Number(
                detalleItems[idxExisting].ocDetalleId
                || detalleItems[idxExisting].oc_detalle_id
                || detalleItems[idxExisting].orden_compra_detalle_id
                || 0
            );
        }
        const payload = {
            ocDetalleId,
            articuloId: aid,
            codigo,
            descripcion,
            cantidad,
            unidad,
            cant_por_unidad: cantPorUnidad,
            peso_por_unidad: pesoPorUnidad,
            peso_unidad: pesoUnidad,
            costo,
            descPct,
            impuestoPct: selectedArticuloImpuestoPct,
        };
        if (selectedArticuloId !== null) {
            const idxSel = detalleItems.findIndex((it) => Number(it.articuloId || it.articulo_id) === selectedArticuloId);
            if (idxSel >= 0) {
                if (idxExisting >= 0 && idxExisting !== idxSel) return showToast('Ese articulo ya existe en otra fila.', 'warning');
                detalleItems[idxSel] = payload;
            }
        } else if (idxExisting >= 0) {
            detalleItems[idxExisting] = payload;
        } else {
            detalleItems.push(payload);
        }
        limpiarSeleccion();
        renderDetalle();
    };

    btnDetalleGuardar?.addEventListener('click', upsertDetalle);
    btnDetalleLimpiar?.addEventListener('click', limpiarSeleccion);
    detalleUnidadSel?.addEventListener('change', syncPesoInputs);
    descuentoGeneralPctEl?.addEventListener('input', recalcTotales);

    document.addEventListener('click', (event) => {
        const entradaRow = event.target.closest('.js-entrada-row');
        if (entradaRow) {
            const id = Number(entradaRow.getAttribute('data-entrada-id') || '0');
            if (id > 0) window.location.href = '/procesos/almacen/entradas?id=' + encodeURIComponent(String(id));
            return;
        }
        const ocRow = event.target.closest('.js-oc-row');
        if (ocRow) {
            const id = Number(ocRow.getAttribute('data-oc-id') || '0');
            if (id > 0) window.location.href = '/procesos/almacen/entradas?oc_id=' + encodeURIComponent(String(id));
            return;
        }

        const eRow = event.target.closest('.js-employee-row');
        if (eRow && empleadoIdEl && empleadoNombreEl) {
            const eid = Number(eRow.getAttribute('data-employee-id') || '0');
            if (eid > 0) {
                empleadoIdEl.value = String(eid);
                empleadoNombreEl.value = eRow.getAttribute('data-employee-name') || empleadoNombreEl.value;
                if (empleadoIdBadgeEl) empleadoIdBadgeEl.textContent = String(eid);
            }
            return;
        }

        const pRow = event.target.closest('.js-oc-proveedor-row');
        if (pRow) {
            const pid = Number(pRow.getAttribute('data-proveedor-id') || '0');
            if (pid <= 0) return;
            const razon = pRow.getAttribute('data-razon') || '';
            const rnc = pRow.getAttribute('data-rnc') || '';
            const condicionPago = pRow.getAttribute('data-condicion-pago') || '';
            proveedorIdEl.value = String(pid);
            const label = razon;
            proveedorLabelEl.value = label;
            proveedorLabelHiddenEl.value = label;
            proveedorRncEl.value = rnc;
            proveedorCondicionPagoEl.value = condicionPago;
            if (proveedorModalEl && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(proveedorModalEl).hide();
            return;
        }

        const aRow = event.target.closest('.js-oc-articulo-row');
        if (aRow) {
            const aid = Number(aRow.getAttribute('data-articulo-id') || '0');
            if (aid <= 0) return;
            detalleArticuloIdSel.value = String(aid);
            detalleArticuloCodigoSel.value = aRow.getAttribute('data-codigo') || '';
            detalleArticuloDescSel.value = aRow.getAttribute('data-descripcion') || '';
            selectedOcDetalleId = null;
            detalleUnidadSel.value = normalizeUnidad(aRow.getAttribute('data-unidad') || 'u', 'u');
            if (detalleCantPorUnidadSel) detalleCantPorUnidadSel.value = '';
            if (detallePesoPorUnidadSel) detallePesoPorUnidadSel.value = '';
            if (detallePesoUnidadSel) detallePesoUnidadSel.value = 'g';
            detalleCostoSel.value = String(round4(toNum(aRow.getAttribute('data-costo') || '0')));
            selectedArticuloImpuestoPct = calcArticleTaxPct(aRow.getAttribute('data-impuestos') || '');
            if (!detalleCantidadSel.value || toNum(detalleCantidadSel.value) <= 0) detalleCantidadSel.value = '1';
            if (!detalleDescPctSel.value) detalleDescPctSel.value = '0';
            syncPesoInputs();
            if (articuloModalEl && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(articuloModalEl).hide();
            return;
        }

        const removeBtn = event.target.closest('.js-remove-detalle');
        if (removeBtn) {
            const row = removeBtn.closest('tr');
            const aid = Number(row?.getAttribute('data-articulo-id') || '0');
            detalleItems = detalleItems.filter((it) => Number(it.articuloId || it.articulo_id) !== aid);
            renderDetalle();
            return;
        }

        const row = event.target.closest('.js-detalle-row');
        if (row) {
            const aid = Number(row.getAttribute('data-articulo-id') || '0');
            const item = detalleItems.find((it) => Number(it.articuloId || it.articulo_id) === aid);
            if (!item) return;
            selectedArticuloId = aid;
            selectedOcDetalleId = Number(item.ocDetalleId || item.oc_detalle_id || item.orden_compra_detalle_id || 0);
            detalleArticuloIdSel.value = String(aid);
            detalleArticuloCodigoSel.value = String(item.codigo || '');
            detalleArticuloDescSel.value = String(item.descripcion || '');
            detalleCantidadSel.value = String(item.cantidad || '1');
            detalleUnidadSel.value = normalizeUnidad(String(item.unidad || 'u'), 'u');
            if (detalleCantPorUnidadSel) detalleCantPorUnidadSel.value = String(item.cant_por_unidad ?? item.cantidad_por_unidad ?? '');
            if (detallePesoPorUnidadSel) detallePesoPorUnidadSel.value = String(item.peso_por_unidad ?? '');
            if (detallePesoUnidadSel) detallePesoUnidadSel.value = normalizeUnidad(String(item.peso_unidad || 'g'), 'g');
            detalleCostoSel.value = String(item.costo || '0');
            detalleDescPctSel.value = String(item.desc_pct ?? item.descPct ?? '0');
            selectedArticuloImpuestoPct = round4(toNum(item.impuesto_pct ?? item.impuestoPct));
            syncPesoInputs();
            btnDetalleGuardar.textContent = 'Actualizar';
            detalleTableEl?.querySelectorAll('tbody tr').forEach((r) => r.classList.remove('table-primary'));
            row.classList.add('table-primary');
        }
    });

    renderDetalle();
    syncPesoInputs();

    if (window.jQuery?.fn?.DataTable) {
        if (detalleTableEl) {
            const detalleTableJQ = window.jQuery('#detalleCompraTable');
            detalleTable = detalleTableJQ.DataTable({
                paging: false,
                searching: false,
                info: false,
                ordering: false,
                autoWidth: false,
                language: {
                    zeroRecords: 'Sin articulos agregados',
                },
                columns: [
                    {
                        data: 'codigo',
                        render: (data, _type, row) => {
                            const codigo = String(data || '');
                            const aid = Number(row.articuloId || row.articulo_id || 0);
                            const ocDetalleId = Number(row.ocDetalleId || row.oc_detalle_id || row.orden_compra_detalle_id || 0);
                            return `${codigo}<input type="hidden" name="detalle_codigo[]" value="${codigo}"><input type="hidden" name="detalle_articulo_id[]" value="${aid}"><input type="hidden" name="detalle_oc_detalle_id[]" value="${ocDetalleId}">`;
                        },
                    },
                    {
                        data: 'descripcion',
                        render: (data) => {
                            const descripcion = String(data || '');
                            return `${descripcion}<input type="hidden" name="detalle_descripcion[]" value="${descripcion}">`;
                        },
                    },
                    {
                        data: null,
                        render: (_data, _type, row) => {
                            const cantidad = round4(toNum(row.cantidad));
                            const unidad = normalizeUnidad(row.unidad || 'u', 'u');
                            return `${cantidad} ${unidad}<input type="hidden" name="detalle_cantidad[]" value="${cantidad}"><input type="hidden" name="detalle_unidad[]" value="${unidad}">`;
                        },
                    },
                    {
                        data: null,
                        render: (_data, _type, row) => {
                            const cantPorUnidad = round4(toNum(row.cant_por_unidad ?? row.cantidad_por_unidad ?? '0'));
                            return `${cantPorUnidad || ''}<input type="hidden" name="detalle_cant_por_unidad[]" value="${cantPorUnidad || ''}">`;
                        },
                    },
                    {
                        data: null,
                        render: (_data, _type, row) => {
                            const pesoPorUnidad = round4(toNum(row.peso_por_unidad ?? '0'));
                            const pesoUnidad = normalizeUnidad(row.peso_unidad || 'g', 'g');
                            return `${pesoPorUnidad || ''} ${pesoPorUnidad ? pesoUnidad : ''}<input type="hidden" name="detalle_peso_por_unidad[]" value="${pesoPorUnidad || ''}"><input type="hidden" name="detalle_peso_unidad[]" value="${pesoUnidad}">`;
                        },
                    },
                    {
                        data: 'costo',
                        render: (data) => {
                            const costo = round4(toNum(data));
                            return `${formatNumber(costo, 4)}<input type="hidden" name="detalle_costo[]" value="${costo}">`;
                        },
                    },
                    {
                        data: null,
                        render: (_data, _type, row) => {
                            const descPct = round4(toNum(row.desc_pct ?? row.descPct));
                            return `${formatNumber(descPct, 2)}<input type="hidden" name="detalle_desc_pct[]" value="${descPct}">`;
                        },
                    },
                    {
                        data: null,
                        render: (_data, _type, row) => {
                            const cantidad = round4(toNum(row.cantidad));
                            const costo = round4(toNum(row.costo));
                            const descPct = round4(toNum(row.desc_pct ?? row.descPct));
                            const impuestoPct = round4(toNum(row.impuesto_pct ?? row.impuestoPct));
                            const base = round4(cantidad * costo);
                            const descuento = round4(base * (descPct / 100));
                            const totalLinea = round4(base - descuento);
                            return `${formatNumber(totalLinea, 4)}<input type="hidden" name="detalle_impuesto_pct[]" value="${impuestoPct}"><input type="hidden" name="detalle_total[]" value="${totalLinea}">`;
                        },
                    },
                    {
                        data: null,
                        render: () => '<button type="button" class="btn btn-outline-danger btn-sm js-remove-detalle"><i class="bi bi-trash"></i></button>',
                    },
                ],
                createdRow: (row, data) => {
                    row.classList.add('js-detalle-row');
                    row.setAttribute('data-articulo-id', String(data.articuloId || data.articulo_id || 0));
                },
            });
            renderDetalle();
        }

        const initDT = (selector, searchPlaceholder, order = [1, 'asc']) => {
            const t = window.jQuery(selector);
            if (!t.length || window.jQuery.fn.dataTable.isDataTable(t)) return;
            t.DataTable({
                pageLength: 10,
                order: [order],
                autoWidth: false,
                deferRender: true,
                language: {
                    search: '',
                    searchPlaceholder,
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: { next: 'Sig', previous: 'Ant' },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                },
                columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
            });
        };
        initDT('#entradaPickerTable', 'Buscar entrada...', [0, 'desc']);
        initDT('#ocPickerTable', 'Buscar OC...', [0, 'desc']);
        initDT('#providerPickerTable', 'Buscar proveedor...');
        initDT('#articuloCompraPickerTable', 'Buscar articulo...');
    }

    formEl?.addEventListener('submit', (event) => {
        if (Number(proveedorIdEl.value || '0') <= 0) {
            event.preventDefault();
            showToast('Selecciona un proveedor antes de guardar.', 'warning');
            return;
        }
        if (detalleItems.length === 0) {
            event.preventDefault();
            showToast('Agrega al menos un articulo al detalle.', 'warning');
            return;
        }
        // Se permiten articulos adicionales fuera de la OC.
    });
})();
</script>
