<?php if (\App\Core\Auth::check()): ?>
            </section>
        </div>
        <?php require dirname(__DIR__) . '/components/employee-picker-modal.php'; ?>
        <?php require dirname(__DIR__) . '/components/client-picker-modal.php'; ?>
        <?php
        $usuarioSesion = \App\Core\Auth::user();
        $perfilNombre = trim((string) (($usuarioSesion['nombre'] ?? '') !== '' ? $usuarioSesion['nombre'] : ($usuarioSesion['username'] ?? 'Usuario')));
        $perfil2fa = (bool) ($usuarioSesion['two_factor_enabled'] ?? false);
        $prompt2faDisabled = (bool) ($usuarioSesion['two_factor_prompt_disabled'] ?? false);
        $setup2fa = !$perfil2fa && is_array($usuarioSesion) ? \App\Core\Auth::getOrCreatePendingTwoFactorSetup($usuarioSesion) : null;
        $setupSecret = trim((string) ($setup2fa['secret'] ?? ''));
        $setupQr = trim((string) ($setup2fa['qr_url'] ?? ''));
        $openTwoFactorModalByError = (string) ($_SESSION['open_two_factor_modal'] ?? '') === '1';
        $openTwoFactorModalByLogin = (string) ($_SESSION['open_two_factor_prompt'] ?? '') === '1' && !$perfil2fa;
        $openTwoFactorModal = $openTwoFactorModalByError || $openTwoFactorModalByLogin;
        unset($_SESSION['open_two_factor_modal']);
        unset($_SESSION['open_two_factor_prompt']);
        ?>
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-sm">
                    <form method="post" action="/mi-cuenta/password" id="changePasswordForm">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                        <div class="modal-header border-bottom-0 pb-0">
                            <div>
                                <h5 class="modal-title" id="changePasswordModalLabel">Cambiar contraseña</h5>
                                <small class="text-muted"><?= htmlspecialchars($perfilNombre) ?></small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body pt-3">
                            <div class="mb-3">
                                <label for="profilePasswordCurrent" class="form-label form-label-sm">Contraseña actual</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control" id="profilePasswordCurrent" name="password_actual" autocomplete="current-password" required>
                                    <button class="btn btn-outline-secondary js-toggle-pass" data-target="#profilePasswordCurrent" type="button" aria-label="Mostrar contraseña actual">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="profilePasswordNew" class="form-label form-label-sm">Nueva contraseña</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control" id="profilePasswordNew" name="password_nuevo" autocomplete="new-password" required>
                                    <button class="btn btn-outline-secondary js-toggle-pass" data-target="#profilePasswordNew" type="button" aria-label="Mostrar nueva contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="profilePasswordConfirm" class="form-label form-label-sm">Confirmar nueva contraseña</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control" id="profilePasswordConfirm" name="password_confirmacion" autocomplete="new-password" required>
                                    <button class="btn btn-outline-secondary js-toggle-pass" data-target="#profilePasswordConfirm" type="button" aria-label="Mostrar confirmación">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="password-rules-panel">
                                <div class="rule-item"><i id="profileRuleLen" class="bi bi-circle text-muted me-2"></i><span>8 caracteres minimo</span></div>
                                <div class="rule-item"><i id="profileRuleUpper" class="bi bi-circle text-muted me-2"></i><span>Una mayuscula</span></div>
                                <div class="rule-item"><i id="profileRuleNum" class="bi bi-circle text-muted me-2"></i><span>Un numero</span></div>
                                <div class="rule-item"><i id="profileRuleSpecial" class="bi bi-circle text-muted me-2"></i><span>Un caracter especial</span></div>
                                <div class="rule-item"><i id="profileRuleMatch" class="bi bi-circle text-muted me-2"></i><span>Ambas coinciden</span></div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="twoFactorModal" tabindex="-1" aria-labelledby="twoFactorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-sm">
                    <form method="post" action="/mi-cuenta/2fa">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                        <div class="modal-header border-bottom-0 pb-0">
                            <h5 class="modal-title" id="twoFactorModalLabel">Configurar 2FA</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <?php if ($perfil2fa): ?>
                                <p class="mb-2 text-muted small">2FA esta activo para esta cuenta.</p>
                                <p class="mb-0 text-muted small">En el login, luego de usuario y contraseña, se pedira el codigo del autenticador.</p>
                                <input type="hidden" name="action" value="disable">
                            <?php elseif ($setupSecret !== ''): ?>
                                <input type="hidden" name="action" value="enable">
                                <div class="d-flex justify-content-center mb-3">
                                    <div class="twofactor-qr-wrap">
                                        <img src="<?= htmlspecialchars($setupQr) ?>" alt="QR para 2FA" class="img-fluid">
                                    </div>
                                </div>
                                <p class="mb-2 text-muted small">Escanea este QR en Google Authenticator, Microsoft Authenticator o Authy.</p>
                                <div class="mb-3">
                                    <label class="form-label form-label-sm text-muted mb-1">Clave manual</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($setupSecret) ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary js-copy-text" data-copy-text="<?= htmlspecialchars($setupSecret) ?>">Copiar</button>
                                    </div>
                                </div>
                                <div>
                                    <label for="setup2faCode" class="form-label form-label-sm">Codigo de verificacion</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm text-center tracking-wide"
                                        id="setup2faCode"
                                        name="codigo_2fa"
                                        maxlength="6"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        placeholder="000000"
                                        required
                                    >
                                </div>
                            <?php else: ?>
                                <p class="mb-0 text-muted small">No se pudo preparar 2FA. Verifica que la base de datos tenga la columna <code>users.two_factor_secret</code>.</p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer border-top-0">
                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                            <?php if ($perfil2fa): ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">Desactivar 2FA</button>
                            <?php elseif ($setupSecret !== ''): ?>
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Activar 2FA</button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if (!$perfil2fa): ?>
                        <div class="px-3 pb-3">
                            <form method="post" action="/mi-cuenta/2fa/prompt" class="d-grid">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                                <input type="hidden" name="prompt_disabled" value="<?= $prompt2faDisabled ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-link btn-sm text-muted text-decoration-none">
                                    <?= $prompt2faDisabled ? 'Activar recordatorio al iniciar sesion' : 'Dejar de preguntar al iniciar sesion' ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($openTwoFactorModal): ?>
            <script>window.__openTwoFactorModal = true;</script>
        <?php endif; ?>
    </div>
<?php else: ?>
    </main>
<?php endif; ?>
<?php
$toast = $_SESSION['flash_toast'] ?? null;
unset($_SESSION['flash_toast']);
$requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
$isArticulosPage = str_starts_with($requestPath, '/mantenimientos/organizacion/articulos');
?>
<?php if (is_array($toast) && !empty($toast['message'])): ?>
    <?php
    $toastRawType = strtolower((string) ($toast['type'] ?? 'info'));
    $toastType = in_array($toastRawType, ['success', 'danger', 'warning', 'info'], true) ? $toastRawType : 'info';
    $toastTitle = htmlspecialchars((string) ($toast['title'] ?? 'Notificacion'));
    $toastMessage = htmlspecialchars((string) ($toast['message'] ?? ''));
    ?>
    <div class="toast-overlay-container position-fixed top-0 end-0 p-3">
        <div id="globalToast" class="toast show app-toast app-toast-<?= htmlspecialchars($toastType) ?> border-0 shadow-sm" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="5000">
            <div class="toast-header">
                <strong class="me-auto"><?= $toastTitle ?></strong>
                <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body"><?= $toastMessage ?></div>
        </div>
    </div>
<?php endif; ?>
<div id="globalLoadingOverlay" class="app-loading-overlay" aria-hidden="true">
    <div class="app-loading-card" role="status" aria-live="polite">
        <div class="spinner-border spinner-border-sm text-secondary me-2" aria-hidden="true"></div>
        <span>Procesando...</span>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<?php if ($isArticulosPage): ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php endif; ?>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
