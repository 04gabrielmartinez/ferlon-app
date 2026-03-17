CREATE TABLE IF NOT EXISTS marcas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(160) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_marcas_descripcion (descripcion),
    KEY idx_marcas_estado (estado),
    CONSTRAINT fk_marcas_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_marcas_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS familias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca_id BIGINT UNSIGNED NOT NULL,
    descripcion VARCHAR(160) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_familias_marca_descripcion (marca_id, descripcion),
    KEY idx_familias_estado (estado),
    KEY idx_familias_marca (marca_id),
    CONSTRAINT fk_familias_marca FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_familias_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_familias_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articulos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(180) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_articulos_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
