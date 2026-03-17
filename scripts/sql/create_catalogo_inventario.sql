CREATE TABLE IF NOT EXISTS presentaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(160) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_presentaciones_descripcion (descripcion),
    KEY idx_presentaciones_estado (estado),
    CONSTRAINT fk_presentaciones_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_presentaciones_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empaques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(160) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_empaques_descripcion (descripcion),
    KEY idx_empaques_estado (estado),
    CONSTRAINT fk_empaques_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_empaques_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tipos_articulo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(160) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tipos_articulo_descripcion (descripcion),
    KEY idx_tipos_articulo_estado (estado),
    CONSTRAINT fk_tipos_articulo_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_tipos_articulo_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias_articulo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(160) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categorias_articulo_descripcion (descripcion),
    KEY idx_categorias_articulo_estado (estado),
    CONSTRAINT fk_categorias_articulo_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_categorias_articulo_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subcategorias_articulo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id BIGINT UNSIGNED NOT NULL,
    descripcion VARCHAR(160) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subcategorias_articulo_categoria_descripcion (categoria_id, descripcion),
    KEY idx_subcategorias_articulo_estado (estado),
    KEY idx_subcategorias_articulo_categoria (categoria_id),
    CONSTRAINT fk_subcategorias_articulo_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_articulo(id) ON DELETE RESTRICT,
    CONSTRAINT fk_subcategorias_articulo_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_subcategorias_articulo_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
