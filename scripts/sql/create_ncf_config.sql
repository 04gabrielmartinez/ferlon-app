CREATE TABLE IF NOT EXISTS ncf_config (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_ncf VARCHAR(120) NOT NULL,
    prefijo VARCHAR(20) NOT NULL,
    autorizacion VARCHAR(30) NOT NULL,
    contador_inicial BIGINT NOT NULL DEFAULT 0,
    contador_actual BIGINT NOT NULL DEFAULT 0,
    final_numero BIGINT NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    alerta_faltan INT NOT NULL DEFAULT 10,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ncf_created_by (created_by),
    CONSTRAINT fk_ncf_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
