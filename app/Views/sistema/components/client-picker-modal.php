<?php

use App\Models\Cliente;

$clientesModal = Cliente::listarParaModal();
?>
<div class="modal fade employee-picker-modal" id="clientPickerModal" tabindex="-1" aria-labelledby="clientPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="clientPickerModalLabel">
                        <i class="bi bi-people"></i>
                        <span>Buscar cliente</span>
                    </h5>
                    <small class="text-muted">Click sobre una fila para seleccionar</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="clientPickerTable" class="table table-hover align-middle w-100 employee-picker-table">
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
                        <?php foreach ($clientesModal as $row): ?>
                            <tr class="js-client-row"
                                data-client-id="<?= (int) ($row['id'] ?? 0) ?>"
                                data-client-razon="<?= htmlspecialchars((string) ($row['razon_social'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-client-rnc="<?= htmlspecialchars((string) ($row['rnc'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-client-telefono="<?= htmlspecialchars((string) ($row['telefono_cliente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= (int) ($row['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($row['razon_social'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['rnc'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['telefono_cliente'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['estado'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
