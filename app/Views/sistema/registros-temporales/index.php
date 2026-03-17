<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3 px-3 px-md-4">
            <h2 class="h6 mb-0 text-secondary">Sistema / Registros temporales</h2>
            <small class="text-muted">Bloqueos de edicion activos y limpieza manual.</small>
        </div>
        <div class="card-body px-3 px-md-4 pb-4">
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
                    <table class="table table-sm align-middle mb-0" id="temporalesTable">
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
                            <?php $locks = is_array($locks ?? null) ? $locks : []; ?>
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
        </div>
    </div>
</div>
