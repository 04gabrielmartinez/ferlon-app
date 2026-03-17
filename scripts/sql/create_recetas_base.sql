CREATE TABLE IF NOT EXISTS recetas_base (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca_id BIGINT UNSIGNED NULL,
    familia_id BIGINT UNSIGNED NULL,
    producto_articulo_id BIGINT UNSIGNED NOT NULL,
    rendimiento DECIMAL(18,4) NOT NULL DEFAULT 1.0000,
    unidad_rendimiento VARCHAR(5) NOT NULL DEFAULT 'kg',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_recetas_base_producto (producto_articulo_id),
    KEY idx_recetas_base_marca (marca_id),
    KEY idx_recetas_base_familia (familia_id),
    CONSTRAINT fk_recetas_base_marca FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE SET NULL,
    CONSTRAINT fk_recetas_base_familia FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE SET NULL,
    CONSTRAINT fk_recetas_base_producto FOREIGN KEY (producto_articulo_id) REFERENCES articulos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_recetas_base_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_recetas_base_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recetas_base_detalles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receta_base_id BIGINT UNSIGNED NOT NULL,
    insumo_articulo_id BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(18,4) NOT NULL,
    unidad VARCHAR(5) NOT NULL DEFAULT 'u',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_receta_detalle_insumo (receta_base_id, insumo_articulo_id),
    KEY idx_receta_detalle_receta (receta_base_id),
    KEY idx_receta_detalle_insumo (insumo_articulo_id),
    CONSTRAINT fk_receta_detalle_receta FOREIGN KEY (receta_base_id) REFERENCES recetas_base(id) ON DELETE CASCADE,
    CONSTRAINT fk_receta_detalle_insumo FOREIGN KEY (insumo_articulo_id) REFERENCES articulos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
