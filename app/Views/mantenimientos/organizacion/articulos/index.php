<?php
$articulo = is_array($articulo ?? null) ? $articulo : [];
$tiposArticulo = is_array($tiposArticulo ?? null) ? $tiposArticulo : [];
$categoriasArticulo = is_array($categoriasArticulo ?? null) ? $categoriasArticulo : [];
$subcategoriasArticulo = is_array($subcategoriasArticulo ?? null) ? $subcategoriasArticulo : [];
$presentaciones = is_array($presentaciones ?? null) ? $presentaciones : [];
$empaques = is_array($empaques ?? null) ? $empaques : [];
$marcasActivas = is_array($marcasActivas ?? null) ? $marcasActivas : [];
$familiasActivas = is_array($familiasActivas ?? null) ? $familiasActivas : [];
$proveedoresActivos = is_array($proveedoresActivos ?? null) ? $proveedoresActivos : [];
$articulos = is_array($articulos ?? null) ? $articulos : [];
$impuestosOpciones = is_array($impuestosOpciones ?? null) ? $impuestosOpciones : [];
$variantesRecetaProductoFinal = is_array($variantesRecetaProductoFinal ?? null) ? $variantesRecetaProductoFinal : [];

$estadoArticulo = strtolower((string) ($articulo['estado'] ?? 'activo')) === 'activo';
$impuestosArticulo = is_array($articulo['impuestos_array'] ?? null) ? $articulo['impuestos_array'] : [];
$fotoActual = trim((string) ($articulo['foto_path'] ?? ''));
$recetaProductoFinalActivo = ((int) ($articulo['tiene_receta'] ?? 0) === 1);
$presentacionesSel = array_map('intval', is_array($articulo['presentacion_ids'] ?? null) ? $articulo['presentacion_ids'] : []);
$empaquesSel = array_map('intval', is_array($articulo['empaque_ids'] ?? null) ? $articulo['empaque_ids'] : []);
$isEdit = (int) ($articulo['id'] ?? 0) > 0;

$help = static function (string $text): string {
    return '<button type="button" class="field-help" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-soft" data-bs-placement="top" title="'
        . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        . '" aria-label="Ayuda"><i class="bi bi-question-circle-fill"></i></button>';
};
?>
<style>
    .articulo-main-inputs .form-control,
    .articulo-main-inputs .form-select {
        min-height: 36px;
    }
    .employee-form .form-control,
    .employee-form .form-select {
        border-radius: 0.45rem;
        border-color: #d7dee6;
    }
    .employee-form .input-group > .form-control,
    .employee-form .input-group > .form-select {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .employee-form .select2-container {
        width: 100% !important;
    }
    .employee-form .select2-container--default .select2-selection--single {
        min-height: 36px;
        border: 1px solid #d6e1ec;
        border-radius: 0.45rem;
        background-color: #fff;
        display: flex;
        align-items: center;
        padding-right: 2rem;
    }
    .employee-form .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155;
        line-height: 1.5;
        padding-left: 0.7rem;
        padding-right: 0.2rem;
    }
    .employee-form .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8;
    }
    .employee-form .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        right: 0.45rem;
    }
    .employee-form .select2-container--default .select2-selection--single .select2-selection__clear {
        color: #64748b;
        margin-right: 0.35rem;
        font-size: 1rem;
    }
    .employee-form .select2-container--default.select2-container--focus .select2-selection--single,
    .employee-form .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #94bce5;
        box-shadow: 0 0 0 0.16rem rgba(59, 130, 246, 0.2);
    }
    .employee-form .select2-container--default.select2-container--disabled .select2-selection--single {
        background: #eef2f7;
        color: #8a98aa;
        border-color: #d6e1ec;
        cursor: not-allowed;
    }
    .select2-dropdown {
        border: 1px solid #d6e1ec;
        border-radius: 0.55rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }
    .select2-search--dropdown {
        padding: 0.45rem;
        background: #f8fbff;
        border-bottom: 1px solid #e2e8f0;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #d6e1ec;
        border-radius: 0.45rem;
        min-height: 32px;
        padding: 0.3rem 0.55rem;
    }
    .select2-search--dropdown .select2-search__field:focus {
        border-color: #94bce5;
        box-shadow: 0 0 0 0.16rem rgba(59, 130, 246, 0.2);
        outline: 0;
    }
    .select2-container--default .select2-results__option {
        padding: 0.45rem 0.65rem;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background: #e8f1fb;
        color: #1e293b;
    }
</style>

<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Organizacion / Articulos</h2>
            <small class="text-muted">Registro y control de articulos</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <div class="section-card mb-0">
                <div class="section-title">Formulario de Articulos</div>
                <form method="post" action="/mantenimientos/organizacion/articulos" class="employee-form" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                    <input type="hidden" name="id" value="<?= (int) ($articulo['id'] ?? 0) ?>">
                    <input type="hidden" name="codigo" value="<?= htmlspecialchars((string) ($articulo['codigo'] ?? '')) ?>">
                    <input type="hidden" name="foto_actual" value="<?= htmlspecialchars($fotoActual) ?>">

                    <div class="row g-3">
                        <div class="col-12 col-lg-8">
                            <div class="row g-3 articulo-main-inputs">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>ID</span><?= $help('Identificador interno del articulo.') ?></label>
                                    <div class="input-group input-group-sm">
                                        <input class="form-control" value="<?= !empty($articulo['id']) ? (int) $articulo['id'] : '' ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary js-open-article-picker" data-article-redirect="/mantenimientos/organizacion/articulos?id={id}" aria-label="Buscar articulo">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Codigo</span><?= $help('Se genera automaticamente por secuencia segun el tipo de articulo.') ?></label>
                                    <input class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['codigo'] ?? '')) ?>" readonly placeholder="Se genera al guardar">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Descripcion</span><?= $help('Detalle opcional del articulo.') ?></label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['descripcion'] ?? '')) ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Tipo de articulo</span><?= $help('Clasificacion general del articulo.') ?></label>
                                    <select name="tipo_articulo_id" class="form-select form-select-sm js-articulo-select2" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($tiposArticulo as $t): $tid = (int) ($t['id'] ?? 0); if ($tid <= 0) continue; ?>
                                            <option value="<?= $tid ?>" <?= ((int) ($articulo['tipo_articulo_id'] ?? 0) === $tid) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($t['descripcion'] ?? '')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Categoria</span><?= $help('Categoria principal del articulo.') ?></label>
                                    <select name="categoria_id" id="articulo_categoria_id" class="form-select form-select-sm js-articulo-select2">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($categoriasArticulo as $c): $cid = (int) ($c['id'] ?? 0); if ($cid <= 0) continue; ?>
                                            <option value="<?= $cid ?>" <?= ((int) ($articulo['categoria_id'] ?? 0) === $cid) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($c['descripcion'] ?? '')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Subcategoria</span><?= $help('Subcategoria dependiente de la categoria seleccionada.') ?></label>
                                    <select name="subcategoria_id" id="articulo_subcategoria_id" class="form-select form-select-sm js-articulo-select2">
                                        <option value="">Seleccione</option>
                                        <?php foreach ($subcategoriasArticulo as $s): $sid = (int) ($s['id'] ?? 0); $scid = (int) ($s['categoria_id'] ?? 0); if ($sid <= 0) continue; ?>
                                            <option value="<?= $sid ?>" data-categoria-id="<?= $scid ?>" <?= ((int) ($articulo['subcategoria_id'] ?? 0) === $sid) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($s['descripcion'] ?? '')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Marca</span><?= $help('Solo disponible al activar "Receta producto final".') ?></label>
                                    <select name="marca_id" id="articulo_marca_id" class="form-select form-select-sm js-articulo-select2" <?= $recetaProductoFinalActivo ? '' : 'disabled' ?> <?= $recetaProductoFinalActivo ? 'required' : '' ?>>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($marcasActivas as $m): $mid = (int) ($m['id'] ?? 0); if ($mid <= 0) continue; ?>
                                            <option value="<?= $mid ?>" <?= ((int) ($articulo['marca_id'] ?? 0) === $mid) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($m['descripcion'] ?? '')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Familia</span><?= $help('Solo disponible al activar "Receta producto final".') ?></label>
                                    <select name="familia_id" id="articulo_familia_id" class="form-select form-select-sm js-articulo-select2" <?= $recetaProductoFinalActivo ? 'required' : '' ?>>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($familiasActivas as $f): $fid = (int) ($f['id'] ?? 0); $fmid = (int) ($f['marca_id'] ?? 0); if ($fid <= 0) continue; ?>
                                            <option value="<?= $fid ?>" data-marca-id="<?= $fmid ?>" <?= ((int) ($articulo['familia_id'] ?? 0) === $fid) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($f['descripcion'] ?? '')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Unidad base</span><?= $help('Unidad principal para inventario y conversiones.') ?></label>
                                    <?php $ub = (string) ($articulo['unidad_base_id'] ?? 'u'); ?>
                                    <select name="unidad_base_id" id="articulo_unidad_base_id" class="form-select form-select-sm js-articulo-select2">
                                        <option value="kg" <?= $ub === 'kg' ? 'selected' : '' ?>>kg</option>
                                        <option value="u" <?= $ub === 'u' ? 'selected' : '' ?>>u</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Ubicacion</span><?= $help('Ubicacion fisica en almacen o bodega.') ?></label>
                                    <input name="ubicacion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['ubicacion'] ?? '')) ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Stock</span><?= $help('Stock actual para visualizar inventario.') ?></label>
                                    <?php
                                        $stockActual = (string) ($articulo['stock_actual'] ?? '0');
                                        $stockActualKg = (string) ($articulo['stock_actual_kg'] ?? '0');
                                        $unidadBase = (string) ($articulo['unidad_base_id'] ?? 'u');
                                    ?>
                                    <input type="number" step="0.0001" class="form-control form-control-sm mb-1" value="<?= htmlspecialchars($stockActual) ?>" readonly>
                                    <?php if ($unidadBase !== 'u'): ?>
                                        <input type="number" step="0.0001" class="form-control form-control-sm" value="<?= htmlspecialchars($stockActualKg) ?>" readonly>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Stock minimo</span><?= $help('Nivel minimo para alertas.') ?></label>
                                    <input type="number" step="0.0001" min="0" name="stock_minimo" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['stock_minimo'] ?? '0')) ?>" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Punto de reorden</span><?= $help('Cantidad objetivo para reabastecer.') ?></label>
                                    <input type="number" step="0.0001" name="punto_reorden" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['punto_reorden'] ?? '')) ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Costo ultimo</span><?= $help('Ultimo costo registrado de compra.') ?></label>
                                    <input type="number" step="0.0001" name="costo_ultimo" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['costo_ultimo'] ?? '0')) ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Costo promedio</span><?= $help('Costo promedio historico (opcional).') ?></label>
                                    <input type="number" step="0.0001" name="costo_promedio" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['costo_promedio'] ?? '')) ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Rendimiento</span><?= $help('Rendimiento esperado si es fabricable.') ?></label>
                                    <input type="number" step="0.0001" name="rendimiento" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['rendimiento'] ?? '')) ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Merma %</span><?= $help('Porcentaje estimado de merma.') ?></label>
                                    <input type="number" step="0.0001" name="merma_pct" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($articulo['merma_pct'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Estado</span><?= $help('Define si el articulo esta disponible para uso en el sistema.') ?></label>
                                    <input type="hidden" name="estado" id="articulo_estado_hidden" value="<?= $estadoArticulo ? 'activo' : 'inactivo' ?>">
                                    <div class="d-flex align-items-center gap-2 pt-1">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input js-catalogo-switch" data-hidden="#articulo_estado_hidden" data-badge="#articulo_estado_badge" type="checkbox" <?= $estadoArticulo ? 'checked' : '' ?>>
                                        </div>
                                        <span id="articulo_estado_badge" class="badge rounded-pill <?= $estadoArticulo ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $estadoArticulo ? 'Activo' : 'Inactivo' ?></span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Foto</span><?= $help('Sube imagen JPG, PNG o WEBP. Maximo 1MB.') ?></label>
                                    <input id="fotoArticuloInput" type="file" name="foto" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Vista previa</span><?= $help('Previsualizacion de la imagen actual del articulo.') ?></label>
                                    <div class="border rounded-3 bg-light d-flex align-items-center justify-content-center" style="height:86px;">
                                        <img id="articuloFotoPreview" src="<?= htmlspecialchars($fotoActual) ?>" alt="Vista previa" class="<?= $fotoActual !== '' ? '' : 'd-none' ?>" style="max-width:100%;max-height:82px;object-fit:contain;">
                                        <span id="articuloFotoPlaceholder" class="small text-muted <?= $fotoActual !== '' ? 'd-none' : '' ?>">Sin imagen</span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Impuestos</span><?= $help('Selecciona uno o varios impuestos.') ?></label>
                                    <div class="border rounded-3 p-2 bg-light-subtle articulo-impuestos-list">
                                        <div class="row g-1">
                                            <?php foreach ($impuestosOpciones as $imp): $checkedImp = in_array((string) $imp, $impuestosArticulo, true); ?>
                                                <div class="col-12">
                                                    <div class="form-check m-0">
                                                        <input class="form-check-input" type="checkbox" id="imp_<?= htmlspecialchars(strtolower((string) $imp)) ?>" name="impuestos[]" value="<?= htmlspecialchars((string) $imp) ?>" <?= $checkedImp ? 'checked' : '' ?>>
                                                        <label class="form-check-label small" for="imp_<?= htmlspecialchars(strtolower((string) $imp)) ?>"><?= htmlspecialchars((string) $imp) ?></label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                $switches = [
                                    ['es_comprable', 'Es comprable', 'Habilita el articulo para compras.'],
                                    ['insumo_receta', 'Insumo receta', 'Marca el articulo como insumo para recetas.'],
                                    ['es_fabricable', 'Receta base', 'Marca el articulo como receta base.'],
                                    ['tiene_receta', 'Receta producto final', 'Marca el articulo como receta de producto final.'],
                                ];
                                foreach ($switches as [$field, $label, $helpText]):
                                    $checked = ((int) ($articulo[$field] ?? 0) === 1);
                                ?>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label small mb-1 d-flex align-items-center gap-1"><span><?= htmlspecialchars($label) ?></span><?= $help($helpText) ?></label>
                                        <div class="form-check form-switch m-0 pt-1">
                                            <input class="form-check-input" type="checkbox" name="<?= htmlspecialchars($field) ?>" <?= $checked ? 'checked' : '' ?> <?= $isEdit ? 'disabled' : '' ?>>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="col-12" id="articuloPresentacionesBox">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Presentacion</span><?= $help('Solo aplica con "Receta producto final". Si no esta activo, las opciones estaran deshabilitadas.') ?></label>
                                    <div class="border rounded-3 p-2 bg-light-subtle articulo-impuestos-list">
                                        <div class="row g-1">
                                            <?php foreach ($presentaciones as $p): $pid = (int) ($p['id'] ?? 0); if ($pid <= 0) continue; ?>
                                                <?php $checkedPresentacion = in_array($pid, $presentacionesSel, true); ?>
                                                <div class="col-12 col-md-6">
                                                    <div class="form-check m-0">
                                                        <input class="form-check-input js-presentacion-check" type="checkbox" id="presentacion_<?= $pid ?>" name="presentacion_ids[]" value="<?= $pid ?>" <?= $checkedPresentacion ? 'checked' : '' ?> <?= $recetaProductoFinalActivo ? '' : 'disabled' ?>>
                                                        <label class="form-check-label small" for="presentacion_<?= $pid ?>"><?= htmlspecialchars((string) ($p['descripcion'] ?? '')) ?></label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12" id="articuloEmpaquesBox">
                                    <label class="form-label small mb-1 d-flex align-items-center gap-1"><span>Empaque</span><?= $help('Solo aplica con "Receta producto final". Si no esta activo, las opciones estaran deshabilitadas.') ?></label>
                                    <div class="border rounded-3 p-2 bg-light-subtle articulo-impuestos-list">
                                        <div class="row g-1">
                                            <?php foreach ($empaques as $e): $eid = (int) ($e['id'] ?? 0); if ($eid <= 0) continue; ?>
                                                <?php $checkedEmpaque = in_array($eid, $empaquesSel, true); ?>
                                                <div class="col-12 col-md-6">
                                                    <div class="form-check m-0">
                                                        <input class="form-check-input js-empaque-check" type="checkbox" id="empaque_<?= $eid ?>" name="empaque_ids[]" value="<?= $eid ?>" <?= $checkedEmpaque ? 'checked' : '' ?> <?= $recetaProductoFinalActivo ? '' : 'disabled' ?>>
                                                        <label class="form-check-label small" for="empaque_<?= $eid ?>"><?= htmlspecialchars((string) ($e['descripcion'] ?? '')) ?></label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                        <a href="/mantenimientos/organizacion/articulos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade employee-picker-modal" id="articlePickerModal" tabindex="-1" aria-labelledby="articlePickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="articlePickerModalLabel">
                        <i class="bi bi-box-seam"></i>
                        <span>Inventario de articulos</span>
                    </h5>
                    <small class="text-muted">Click sobre una fila para editar</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="articlePickerTipoFilter" class="form-label small mb-1">Filtrar por tipo de articulo</label>
                        <select id="articlePickerTipoFilter" class="form-select form-select-sm js-articulo-select2">
                            <option value="">Todos</option>
                            <?php foreach ($tiposArticulo as $t): $tdesc = trim((string) ($t['descripcion'] ?? '')); if ($tdesc === '') continue; ?>
                                <option value="<?= htmlspecialchars($tdesc) ?>"><?= htmlspecialchars($tdesc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <table id="articlePickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Stock</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articulos as $row): ?>
                            <?php $id = (int) ($row['id'] ?? 0); if ($id <= 0) { continue; } ?>
                            <?php $tieneReceta = (int) ($row['tiene_receta'] ?? 0) === 1; ?>
                            <tr class="js-article-row" data-article-id="<?= $id ?>" data-has-variants="<?= $tieneReceta ? '1' : '0' ?>">
                                <td>
                                    <?php if ($tieneReceta): ?>
                                        <i class="bi bi-caret-right-fill text-secondary me-1 js-variant-toggle-icon"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars((string) ($row['codigo'] ?? '')) ?>
                                </td>
                                <td><?= htmlspecialchars((string) ($row['nombre'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['tipo_articulo_descripcion'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['categoria_descripcion'] ?? '')) ?></td>
                                <?php
                                    $unidadBaseRow = (string) ($row['unidad_base_id'] ?? 'u');
                                    $stockNum = $tieneReceta
                                        ? (float) ($row['stock_variantes_total'] ?? 0)
                                        : ($unidadBaseRow === 'u'
                                            ? (float) ($row['stock_actual'] ?? 0)
                                            : (float) ($row['stock_actual_kg'] ?? 0));
                                    $stockFormatted = number_format($stockNum, 2, '.', '');
                                    $stockLabel = $tieneReceta ? ($stockFormatted . ' u') : ($unidadBaseRow === 'u' ? ($stockFormatted . ' u') : ($stockFormatted . ' kg'));
                                ?>
                                <td><?= htmlspecialchars($stockLabel) ?></td>
                                <td><?= htmlspecialchars((string) ($row['estado'] ?? '')) ?></td>
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
    const variantesRaw = <?= json_encode($variantesRecetaProductoFinal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const variantesMap = {};
    (variantesRaw || []).forEach((v) => {
        const id = Number(v.articulo_id || 0);
        if (!id) return;
        if (!variantesMap[id]) variantesMap[id] = [];
        variantesMap[id].push({
            articulo_id: id,
            articulo_codigo: v.articulo_codigo || '',
            articulo_descripcion: v.articulo_descripcion || '',
            presentacion_id: Number(v.presentacion_id || 0),
            presentacion_descripcion: v.presentacion_descripcion || '',
            empaque_id: Number(v.empaque_id || 0),
            empaque_descripcion: v.empaque_descripcion || '',
            stock_actual: Number(v.stock_actual || 0),
        });
    });
    window.ARTICULO_VARIANTES = variantesMap;
})();

(() => {
    document.querySelectorAll('.js-catalogo-switch').forEach((sw) => {
        const hidden = document.querySelector(sw.getAttribute('data-hidden') || '');
        const badge = document.querySelector(sw.getAttribute('data-badge') || '');
        if (!hidden || !badge) return;
        const sync = () => {
            const activo = sw.checked;
            hidden.value = activo ? 'activo' : 'inactivo';
            badge.textContent = activo ? 'Activo' : 'Inactivo';
            badge.classList.toggle('text-bg-success', activo);
            badge.classList.toggle('text-bg-secondary', !activo);
        };
        sw.addEventListener('change', sync);
        sync();
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            bootstrap.Tooltip.getOrCreateInstance(el, { container: 'body', trigger: 'hover focus click' });
        }
    });

    const catSel = document.getElementById('articulo_categoria_id');
    const subSel = document.getElementById('articulo_subcategoria_id');
    if (catSel && subSel) {
        const syncSubcats = () => {
            const catId = catSel.value || '';
            let selectedValid = false;
            Array.from(subSel.options).forEach((opt, idx) => {
                if (idx === 0) {
                    opt.hidden = false;
                    return;
                }
                const optCat = opt.getAttribute('data-categoria-id') || '';
                const show = catId === '' || optCat === catId;
                opt.hidden = !show;
                opt.disabled = !show;
                if (!show && opt.selected) {
                    opt.selected = false;
                }
                if (show && opt.selected) {
                    selectedValid = true;
                }
            });
            if (!selectedValid && subSel.value !== '') {
                subSel.value = '';
            }
            refreshSelect2(subSel);
        };
        catSel.addEventListener('change', syncSubcats);
        syncSubcats();
    }

    const fotoInput = document.getElementById('fotoArticuloInput');
    const fotoPreview = document.getElementById('articuloFotoPreview');
    const fotoPlaceholder = document.getElementById('articuloFotoPlaceholder');
    if (fotoInput && fotoPreview && fotoPlaceholder) {
        fotoInput.addEventListener('change', () => {
            const [file] = fotoInput.files || [];
            if (!file) return;
            const url = URL.createObjectURL(file);
            fotoPreview.src = url;
            fotoPreview.classList.remove('d-none');
            fotoPlaceholder.classList.add('d-none');
        });
    }

    const recetaBase = document.querySelector('input[name="es_fabricable"]');
    const recetaProductoFinal = document.querySelector('input[name="tiene_receta"]');
    const marcaSel = document.getElementById('articulo_marca_id');
    const familiaSel = document.getElementById('articulo_familia_id');
    const unidadBaseSel = document.getElementById('articulo_unidad_base_id');
    const presentacionChecks = Array.from(document.querySelectorAll('.js-presentacion-check'));
    const empaqueChecks = Array.from(document.querySelectorAll('.js-empaque-check'));
    const bindNativeChange = (el, handler) => {
        if (!(el instanceof HTMLSelectElement) || typeof handler !== 'function') return;
        el.addEventListener('change', handler);
    };
    function refreshSelect2(el) {
        if (!(el instanceof HTMLSelectElement)) return;
        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
            window.jQuery(el).trigger('change.select2');
        }
    }
    const setSelectDisabled = (el, disabled) => {
        if (!(el instanceof HTMLSelectElement)) return;
        el.disabled = !!disabled;
        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
            window.jQuery(el).prop('disabled', !!disabled).trigger('change.select2');
        }
    };

    const initArticuloSelect2 = () => {
        if (!(window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function')) {
            return;
        }
        const $ = window.jQuery;
        $('.js-articulo-select2').each(function initSelect2() {
            const $select = $(this);
            if ($select.data('select2')) return;
            const $modal = $select.closest('.modal');
            $select.select2({
                placeholder: 'Buscar...',
                allowClear: true,
                width: '100%',
                dropdownParent: $modal.length ? $modal : $(document.body),
                templateResult: (data) => {
                    if (data && data.element && data.element.hidden) return null;
                    return data.text;
                },
            });
        });
        if (marcaSel) {
            $(marcaSel)
                .off('change.select2familia select2:select.select2familia select2:clear.select2familia')
                .on('change.select2familia select2:select.select2familia select2:clear.select2familia', () => {
                    syncFamiliasPorMarca();
                });
        }
        syncFamiliasPorMarca();
    };

    const syncFamiliasPorMarca = () => {
        if (!marcaSel || !familiaSel) return;
        const recetaActiva = !!(recetaProductoFinal && recetaProductoFinal.checked);
        const marcaId = recetaActiva ? (marcaSel.value || '') : '';
        setSelectDisabled(familiaSel, !recetaActiva || marcaId === '');
        let selectedValid = false;
        Array.from(familiaSel.options).forEach((opt, idx) => {
            if (idx === 0) {
                opt.hidden = false;
                return;
            }
            const optMarcaId = opt.getAttribute('data-marca-id') || '';
            const show = recetaActiva && marcaId !== '' && optMarcaId === marcaId;
            opt.hidden = !show;
            opt.disabled = !show;
            if (!show && opt.selected) {
                opt.selected = false;
            }
            if (show && opt.selected) {
                selectedValid = true;
            }
        });
        if (!selectedValid && familiaSel.value !== '') {
            familiaSel.value = '';
        }
        refreshSelect2(familiaSel);
    };

    const syncMarcaFamiliaByRecetaProductoFinal = () => {
        if (!marcaSel || !familiaSel || !recetaProductoFinal) return;
        const recetaActiva = recetaProductoFinal.checked;
        setSelectDisabled(marcaSel, !recetaActiva);
        marcaSel.required = recetaActiva;
        familiaSel.required = recetaActiva;
        if (!recetaActiva) {
            marcaSel.value = '';
            familiaSel.value = '';
        }
        refreshSelect2(marcaSel);
        syncFamiliasPorMarca();
        presentacionChecks.forEach((chk) => {
            chk.disabled = !recetaActiva;
            if (!recetaActiva) {
                chk.checked = false;
            }
        });
        empaqueChecks.forEach((chk) => {
            chk.disabled = !recetaActiva;
            if (!recetaActiva) {
                chk.checked = false;
            }
        });
    };

    const syncUnidadBaseByRecetas = () => {
        if (!unidadBaseSel) return;
        const recetaBaseActiva = !!(recetaBase && recetaBase.checked);
        const recetaFinalActiva = !!(recetaProductoFinal && recetaProductoFinal.checked);
        if (recetaBaseActiva) {
            unidadBaseSel.value = 'kg';
            setSelectDisabled(unidadBaseSel, true);
        } else if (recetaFinalActiva) {
            unidadBaseSel.value = 'u';
            setSelectDisabled(unidadBaseSel, true);
        } else {
            setSelectDisabled(unidadBaseSel, false);
        }
        refreshSelect2(unidadBaseSel);
    };

    if (marcaSel && familiaSel) {
        bindNativeChange(marcaSel, syncFamiliasPorMarca);
        syncFamiliasPorMarca();
    }
    if (recetaBase && recetaProductoFinal) {
        recetaBase.addEventListener('change', () => {
            if (recetaBase.checked) {
                recetaProductoFinal.checked = false;
            }
            syncMarcaFamiliaByRecetaProductoFinal();
            syncUnidadBaseByRecetas();
        });
        recetaProductoFinal.addEventListener('change', () => {
            if (recetaProductoFinal.checked) {
                recetaBase.checked = false;
            }
            syncUnidadBaseByRecetas();
        });
    }
    if (recetaProductoFinal) {
        recetaProductoFinal.addEventListener('change', syncMarcaFamiliaByRecetaProductoFinal);
        syncMarcaFamiliaByRecetaProductoFinal();
    }
    syncUnidadBaseByRecetas();

    const form = document.querySelector('form.employee-form');
    if (form) {
        form.addEventListener('submit', (event) => {
            const flags = ['es_comprable', 'insumo_receta', 'es_fabricable', 'tiene_receta'];
            const anyChecked = flags.some((name) => {
                const input = form.querySelector(`input[name="${name}"]`);
                return input ? input.checked : false;
            });
            if (!anyChecked) {
                event.preventDefault();
                alert('Debes activar al menos una opcion: Es comprable, Insumo receta, Receta base o Receta producto final.');
            }
        });
    }
    window.addEventListener('load', initArticuloSelect2);
})();
</script>
