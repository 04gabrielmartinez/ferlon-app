CREATE TABLE IF NOT EXISTS subdepartamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    departamento_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(140) NOT NULL,
    descripcion VARCHAR(255) NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subdepartamentos_departamento_nombre (departamento_id, nombre),
    KEY idx_subdepartamentos_departamento (departamento_id),
    KEY idx_subdepartamentos_estado (estado),
    CONSTRAINT fk_subdepartamentos_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_subdepartamentos_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_subdepartamentos_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
