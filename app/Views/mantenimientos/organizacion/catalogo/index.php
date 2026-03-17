<?php
$tab = (string) ($tab ?? 'presentaciones');
$presentacion = is_array($presentacion ?? null) ? $presentacion : [];
$empaque = is_array($empaque ?? null) ? $empaque : [];
$tipoArticulo = is_array($tipoArticulo ?? null) ? $tipoArticulo : [];
$categoriaArticulo = is_array($categoriaArticulo ?? null) ? $categoriaArticulo : [];
$subcategoriaArticulo = is_array($subcategoriaArticulo ?? null) ? $subcategoriaArticulo : [];
$presentaciones = is_array($presentaciones ?? null) ? $presentaciones : [];
$empaques = is_array($empaques ?? null) ? $empaques : [];
$tiposArticulo = is_array($tiposArticulo ?? null) ? $tiposArticulo : [];
$categoriasArticulo = is_array($categoriasArticulo ?? null) ? $categoriasArticulo : [];
$subcategoriasArticulo = is_array($subcategoriasArticulo ?? null) ? $subcategoriasArticulo : [];
$categoriasActivas = is_array($categoriasActivas ?? null) ? $categoriasActivas : [];

$estadoPresentacion = strtolower((string) ($presentacion['estado'] ?? 'activo')) === 'activo';
$estadoEmpaque = strtolower((string) ($empaque['estado'] ?? 'activo')) === 'activo';
$estadoTipo = strtolower((string) ($tipoArticulo['estado'] ?? 'activo')) === 'activo';
$estadoCategoria = strtolower((string) ($categoriaArticulo['estado'] ?? 'activo')) === 'activo';
$estadoSubcategoria = strtolower((string) ($subcategoriaArticulo['estado'] ?? 'activo')) === 'activo';
?>
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Mantenimientos / Organizacion / Catalogo</h2>
            <small class="text-muted">Presentaciones, empaques, tipos y unidades de medida</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
            <ul class="nav nav-tabs flex-nowrap overflow-auto mb-3" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link <?= $tab === 'presentaciones' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#cat-tab-presentaciones" type="button" role="tab">Presentaciones</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link <?= $tab === 'empaques' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#cat-tab-empaques" type="button" role="tab">Empaques</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link <?= $tab === 'tipos' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#cat-tab-tipos" type="button" role="tab">Tipos de Articulo</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link <?= $tab === 'categorias' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#cat-tab-categorias" type="button" role="tab">Categorias</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link <?= $tab === 'subcategorias' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#cat-tab-subcategorias" type="button" role="tab">Subcategorias de Art.</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link <?= $tab === 'unidades' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#cat-tab-unidades" type="button" role="tab">Unidades de Medida</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade <?= $tab === 'presentaciones' ? 'show active' : '' ?>" id="cat-tab-presentaciones" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Formulario de Presentaciones</div>
                        <form method="post" action="/mantenimientos/organizacion/catalogo" class="employee-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="accion" value="guardar_presentacion">
                            <input type="hidden" name="id" value="<?= (int) ($presentacion['id'] ?? 0) ?>">
                            <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($presentacion['descripcion'] ?? '')) ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <input type="hidden" name="estado" id="presentacion_estado_hidden" value="<?= $estadoPresentacion ? 'activo' : 'inactivo' ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input js-catalogo-switch" data-hidden="#presentacion_estado_hidden" data-badge="#presentacion_estado_badge" type="checkbox" <?= $estadoPresentacion ? 'checked' : '' ?>>
                                        </div>
                                        <span id="presentacion_estado_badge" class="badge rounded-pill <?= $estadoPresentacion ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $estadoPresentacion ? 'Activo' : 'Inactivo' ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                                <a href="/mantenimientos/organizacion/catalogo?tab=presentaciones" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    <div class="section-card">
                        <div class="section-title">Listado</div>
                        <div class="table-responsive">
                            <table id="catalogoPresentacionesTable" class="table table-sm align-middle" data-page-length="10">
                                <thead><tr><th>ID</th><th>Descripcion</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php foreach ($presentaciones as $row): ?>
                                        <?php $id = (int) ($row['id'] ?? 0); ?>
                                        <tr>
                                            <td><?= $id ?></td>
                                            <td><?= htmlspecialchars((string) ($row['descripcion'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['estado'] ?? '')) ?></td>
                                            <td><a class="btn btn-outline-secondary btn-sm" href="/mantenimientos/organizacion/catalogo?tab=presentaciones&presentacion_id=<?= $id ?>"><i class="bi bi-pencil-square"></i></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $tab === 'empaques' ? 'show active' : '' ?>" id="cat-tab-empaques" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Formulario de Empaques</div>
                        <form method="post" action="/mantenimientos/organizacion/catalogo" class="employee-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="accion" value="guardar_empaque">
                            <input type="hidden" name="id" value="<?= (int) ($empaque['id'] ?? 0) ?>">
                            <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($empaque['descripcion'] ?? '')) ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <input type="hidden" name="estado" id="empaque_estado_hidden" value="<?= $estadoEmpaque ? 'activo' : 'inactivo' ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input js-catalogo-switch" data-hidden="#empaque_estado_hidden" data-badge="#empaque_estado_badge" type="checkbox" <?= $estadoEmpaque ? 'checked' : '' ?>>
                                        </div>
                                        <span id="empaque_estado_badge" class="badge rounded-pill <?= $estadoEmpaque ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $estadoEmpaque ? 'Activo' : 'Inactivo' ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                                <a href="/mantenimientos/organizacion/catalogo?tab=empaques" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    <div class="section-card">
                        <div class="section-title">Listado</div>
                        <div class="table-responsive">
                            <table id="catalogoEmpaquesTable" class="table table-sm align-middle" data-page-length="10">
                                <thead><tr><th>ID</th><th>Descripcion</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php foreach ($empaques as $row): ?>
                                        <?php $id = (int) ($row['id'] ?? 0); ?>
                                        <tr>
                                            <td><?= $id ?></td>
                                            <td><?= htmlspecialchars((string) ($row['descripcion'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['estado'] ?? '')) ?></td>
                                            <td><a class="btn btn-outline-secondary btn-sm" href="/mantenimientos/organizacion/catalogo?tab=empaques&empaque_id=<?= $id ?>"><i class="bi bi-pencil-square"></i></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $tab === 'tipos' ? 'show active' : '' ?>" id="cat-tab-tipos" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Formulario de Tipos de Articulo</div>
                        <form method="post" action="/mantenimientos/organizacion/catalogo" class="employee-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="accion" value="guardar_tipo">
                            <input type="hidden" name="id" value="<?= (int) ($tipoArticulo['id'] ?? 0) ?>">
                            <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($tipoArticulo['descripcion'] ?? '')) ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <input type="hidden" name="estado" id="tipo_estado_hidden" value="<?= $estadoTipo ? 'activo' : 'inactivo' ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input js-catalogo-switch" data-hidden="#tipo_estado_hidden" data-badge="#tipo_estado_badge" type="checkbox" <?= $estadoTipo ? 'checked' : '' ?>>
                                        </div>
                                        <span id="tipo_estado_badge" class="badge rounded-pill <?= $estadoTipo ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $estadoTipo ? 'Activo' : 'Inactivo' ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                                <a href="/mantenimientos/organizacion/catalogo?tab=tipos" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    <div class="section-card">
                        <div class="section-title">Listado</div>
                        <div class="table-responsive">
                            <table id="catalogoTiposTable" class="table table-sm align-middle" data-page-length="10">
                                <thead><tr><th>ID</th><th>Descripcion</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php foreach ($tiposArticulo as $row): ?>
                                        <?php $id = (int) ($row['id'] ?? 0); ?>
                                        <tr>
                                            <td><?= $id ?></td>
                                            <td><?= htmlspecialchars((string) ($row['descripcion'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['estado'] ?? '')) ?></td>
                                            <td><a class="btn btn-outline-secondary btn-sm" href="/mantenimientos/organizacion/catalogo?tab=tipos&tipo_id=<?= $id ?>"><i class="bi bi-pencil-square"></i></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $tab === 'categorias' ? 'show active' : '' ?>" id="cat-tab-categorias" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Formulario de Categorias</div>
                        <form method="post" action="/mantenimientos/organizacion/catalogo" class="employee-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="accion" value="guardar_categoria">
                            <input type="hidden" name="id" value="<?= (int) ($categoriaArticulo['id'] ?? 0) ?>">
                            <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($categoriaArticulo['descripcion'] ?? '')) ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <input type="hidden" name="estado" id="categoria_estado_hidden" value="<?= $estadoCategoria ? 'activo' : 'inactivo' ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input js-catalogo-switch" data-hidden="#categoria_estado_hidden" data-badge="#categoria_estado_badge" type="checkbox" <?= $estadoCategoria ? 'checked' : '' ?>>
                                        </div>
                                        <span id="categoria_estado_badge" class="badge rounded-pill <?= $estadoCategoria ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $estadoCategoria ? 'Activo' : 'Inactivo' ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                                <a href="/mantenimientos/organizacion/catalogo?tab=categorias" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    <div class="section-card">
                        <div class="section-title">Listado</div>
                        <div class="table-responsive">
                            <table id="catalogoCategoriasTable" class="table table-sm align-middle" data-page-length="10">
                                <thead><tr><th>ID</th><th>Descripcion</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php foreach ($categoriasArticulo as $row): ?>
                                        <?php $id = (int) ($row['id'] ?? 0); ?>
                                        <tr>
                                            <td><?= $id ?></td>
                                            <td><?= htmlspecialchars((string) ($row['descripcion'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['estado'] ?? '')) ?></td>
                                            <td><a class="btn btn-outline-secondary btn-sm" href="/mantenimientos/organizacion/catalogo?tab=categorias&categoria_id=<?= $id ?>"><i class="bi bi-pencil-square"></i></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $tab === 'subcategorias' ? 'show active' : '' ?>" id="cat-tab-subcategorias" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Formulario de Subcategorias de Articulo</div>
                        <form method="post" action="/mantenimientos/organizacion/catalogo" class="employee-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                            <input type="hidden" name="accion" value="guardar_subcategoria">
                            <input type="hidden" name="id" value="<?= (int) ($subcategoriaArticulo['id'] ?? 0) ?>">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Categoria</label>
                                    <select name="categoria_id" class="form-select form-select-sm" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($categoriasActivas as $cat): ?>
                                            <?php $catId = (int) ($cat['id'] ?? 0); ?>
                                            <?php $catDesc = (string) ($cat['descripcion'] ?? ''); ?>
                                            <?php if ($catId <= 0 || $catDesc === '') { continue; } ?>
                                            <option value="<?= $catId ?>" <?= ((int) ($subcategoriaArticulo['categoria_id'] ?? 0) === $catId) ? 'selected' : '' ?>><?= htmlspecialchars($catDesc) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Descripcion</label>
                                    <input name="descripcion" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($subcategoriaArticulo['descripcion'] ?? '')) ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small mb-1">Estado</label>
                                    <input type="hidden" name="estado" id="subcategoria_estado_hidden" value="<?= $estadoSubcategoria ? 'activo' : 'inactivo' ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input js-catalogo-switch" data-hidden="#subcategoria_estado_hidden" data-badge="#subcategoria_estado_badge" type="checkbox" <?= $estadoSubcategoria ? 'checked' : '' ?>>
                                        </div>
                                        <span id="subcategoria_estado_badge" class="badge rounded-pill <?= $estadoSubcategoria ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $estadoSubcategoria ? 'Activo' : 'Inactivo' ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                                <a href="/mantenimientos/organizacion/catalogo?tab=subcategorias" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    <div class="section-card">
                        <div class="section-title">Listado</div>
                        <div class="table-responsive">
                            <table id="catalogoSubcategoriasTable" class="table table-sm align-middle" data-page-length="10">
                                <thead><tr><th>ID</th><th>Categoria</th><th>Descripcion</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php foreach ($subcategoriasArticulo as $row): ?>
                                        <?php $id = (int) ($row['id'] ?? 0); ?>
                                        <tr>
                                            <td><?= $id ?></td>
                                            <td><?= htmlspecialchars((string) ($row['categoria_descripcion'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['descripcion'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($row['estado'] ?? '')) ?></td>
                                            <td><a class="btn btn-outline-secondary btn-sm" href="/mantenimientos/organizacion/catalogo?tab=subcategorias&subcategoria_id=<?= $id ?>"><i class="bi bi-pencil-square"></i></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <div class="tab-pane fade <?= $tab === 'unidades' ? 'show active' : '' ?>" id="cat-tab-unidades" role="tabpanel">
                    <div class="section-card mb-3">
                        <div class="section-title">Unidades de Medida</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label small mb-1">Seleccione unidad</label>
                                <select id="unidadBaseSelect" class="form-select form-select-sm">
                                    <option value="g">Gramo (g)</option>
                                    <option value="kg">Kilogramo (kg)</option>
                                    <option value="lb">Libra (lb)</option>
                                    <option value="oz">Onza (oz)</option>
                                    <option value="u">Unidad (u)</option>
                                </select>
                            </div>
                        </div>
                        <div id="unidadMensaje" class="small text-muted mt-3 d-none">Esta unidad no convierte a unidades de peso.</div>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle mb-0" id="unidadConversionTable">
                                <thead>
                                    <tr>
                                        <th>Convierte a</th>
                                        <th>Factor</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
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

    const select = document.getElementById('unidadBaseSelect');
    const tbody = document.querySelector('#unidadConversionTable tbody');
    const msg = document.getElementById('unidadMensaje');
    if (!select || !tbody || !msg) return;

    const units = {
        g: { name: 'gramo', symbol: 'g' },
        kg: { name: 'kilogramo', symbol: 'kg' },
        lb: { name: 'libra', symbol: 'lb' },
        oz: { name: 'onza', symbol: 'oz' },
        u: { name: 'unidad', symbol: 'u' },
    };

    const factors = {
        g: { kg: 0.001000, lb: 0.002205, oz: 0.035274 },
        kg: { g: 1000.000000, lb: 2.204623, oz: 35.273962 },
        lb: { g: 453.592370, kg: 0.453592, oz: 16.000000 },
        oz: { g: 28.349523, kg: 0.028350, lb: 0.062500 },
    };

    const destroyTooltips = () => {
        document.querySelectorAll('#unidadConversionTable [data-bs-toggle="tooltip"]').forEach((el) => {
            const inst = bootstrap.Tooltip.getInstance(el);
            if (inst) inst.dispose();
        });
    };

    const initTooltips = () => {
        document.querySelectorAll('#unidadConversionTable [data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover focus click', container: 'body' });
        });
    };

    const render = () => {
        const base = select.value;
        destroyTooltips();
        tbody.innerHTML = '';

        if (base === 'u') {
            msg.classList.remove('d-none');
            return;
        }
        msg.classList.add('d-none');

        const map = factors[base] || {};
        Object.entries(map).forEach(([to, factor]) => {
            const baseUnit = units[base];
            const toUnit = units[to];
            const factorText = Number(factor).toFixed(6);
            const infoText = `1 ${baseUnit.name} (${baseUnit.symbol}) equivale a ${factorText} ${toUnit.name}s (${toUnit.symbol})`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${toUnit.name} (${toUnit.symbol})</td>
                <td>
                    <div class="d-inline-flex align-items-center gap-2">
                        <span>${factorText}</span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary" data-bs-toggle="tooltip" title="${infoText}" aria-label="Info conversion">
                            <i class="bi bi-question-circle-fill"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        initTooltips();
    };

    select.addEventListener('change', render);
    render();
})();
</script>
