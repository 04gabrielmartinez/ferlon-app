<?php
$articulos = is_array($articulos ?? null) ? $articulos : [];
$proveedores = is_array($proveedores ?? null) ? $proveedores : [];
$filters = is_array($filters ?? null) ? $filters : [];
$desde = (string) ($filters['desde'] ?? '');
$hasta = (string) ($filters['hasta'] ?? '');
$proveedorId = (int) ($filters['proveedor_id'] ?? 0);
$articuloId = (int) ($filters['articulo_id'] ?? 0);
$moneda = (string) ($filters['moneda'] ?? '');
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Reportes / Historial de compras</h2>
            <small class="text-muted">Resumen de precios y cantidades compradas por articulo y proveedor.</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <form method="get" action="/reportes/procesos/historial-compras" class="row g-3" style="max-width: 520px;">
                <div class="col-12">
                    <label class="form-label small mb-1">Desde</label>
                    <input type="date" name="desde" class="form-control form-control-sm" value="<?= htmlspecialchars($desde) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Hasta</label>
                    <input type="date" name="hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($hasta) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Proveedor</label>
                    <select name="proveedor_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($proveedores as $p): ?>
                            <?php $pid = (int) ($p['id'] ?? 0); ?>
                            <option value="<?= $pid ?>" <?= $pid === $proveedorId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($p['razon_social'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Articulo</label>
                    <select name="articulo_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($articulos as $a): ?>
                            <?php $aid = (int) ($a['id'] ?? 0); ?>
                            <option value="<?= $aid ?>" <?= $aid === $articuloId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($a['codigo'] ?? '')) ?> - <?= htmlspecialchars((string) ($a['nombre'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Solo articulos comprables activos.</small>
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Moneda</label>
                    <select name="moneda" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach (['DOP', 'USD', 'EUR', 'MXN'] as $m): ?>
                            <option value="<?= $m ?>" <?= $m === $moneda ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2 justify-content-start pt-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3">Filtrar</button>
                        <a href="/reportes/procesos/historial-compras" class="btn btn-outline-secondary btn-sm rounded-2 px-3">Limpiar</a>
                        <button type="submit" name="export" value="excel" class="btn btn-outline-success btn-sm rounded-2 px-3">Excel</button>
                        <button type="submit" name="export" value="pdf" formtarget="_blank" class="btn btn-outline-danger btn-sm rounded-2 px-3">PDF</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
