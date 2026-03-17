<?php

use App\Models\Empleado;

$empleadosModal = Empleado::listarParaModal();
?>
<div class="modal fade employee-picker-modal" id="employeePickerModal" tabindex="-1" aria-labelledby="employeePickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2" id="employeePickerModalLabel">
                        <i class="bi bi-people-fill"></i>
                        <span>Buscar empleado</span>
                    </h5>
                    <small class="text-muted">Haz click sobre una fila para seleccionar</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <table id="employeePickerTable" class="table table-hover align-middle w-100 employee-picker-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cedula</th>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleadosModal as $emp): ?>
                            <?php
                            $id = (int) ($emp['id'] ?? 0);
                            $nombre = trim((string) (($emp['nombre'] ?? '') . ' ' . ($emp['apellido'] ?? '')));
                            $estado = strtolower((string) ($emp['estado'] ?? ''));
                            ?>
                            <tr class="js-employee-row" data-employee-id="<?= $id ?>" data-employee-name="<?= htmlspecialchars($nombre) ?>">
                                <td><?= $id ?></td>
                                <td><?= htmlspecialchars((string) ($emp['cedula'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($nombre) ?></td>
                                <td><?= htmlspecialchars((string) ($emp['departamento'] ?? '')) ?></td>
                                <td>
                                    <span class="badge <?= $estado === 'activo' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= htmlspecialchars((string) ($emp['estado'] ?? '')) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
