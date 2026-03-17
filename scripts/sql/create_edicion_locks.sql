CREATE TABLE IF NOT EXISTS edicion_locks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recurso_tipo VARCHAR(50) NOT NULL,
    recurso_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en TIMESTAMP NOT NULL,
    UNIQUE KEY uq_edicion_recurso (recurso_tipo, recurso_id),
    KEY idx_edicion_usuario (usuario_id),
    KEY idx_edicion_expira (expira_en),
    CONSTRAINT fk_edicion_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
