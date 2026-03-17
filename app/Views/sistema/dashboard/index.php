<?php $usuario = $usuario ?? []; ?>
<section>
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <h2 class="h4 mb-1">Bienvenido, <?= htmlspecialchars((string) ($usuario['nombre'] ?? 'Usuario')) ?></h2>
            <p class="text-muted mb-0">Correo: <?= htmlspecialchars((string) ($usuario['email'] ?? '')) ?></p>
            <p class="small text-secondary mb-0">Rol: <?= htmlspecialchars((string) ($usuario['role'] ?? 'sin-rol')) ?></p>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach (($kpis ?? []) as $kpi): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-2"><?= htmlspecialchars((string) $kpi['titulo']) ?></p>
                        <p class="display-6 fw-semibold mb-0 text-dark"><?= htmlspecialchars((string) $kpi['valor']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
