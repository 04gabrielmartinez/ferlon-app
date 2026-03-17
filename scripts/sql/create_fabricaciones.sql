CREATE TABLE IF NOT EXISTS fabricaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_fabricacion VARCHAR(50) NOT NULL,
    fecha DATE NOT NULL,
    producto_articulo_id BIGINT UNSIGNED NOT NULL,
    presentacion_id BIGINT UNSIGNED NULL,
    empaque_id BIGINT UNSIGNED NULL,
    receta_producto_final_id BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(18,4) NOT NULL,
    unidad VARCHAR(5) NOT NULL,
    comentario TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_fabricaciones_codigo (codigo_fabricacion),
    KEY idx_fabricaciones_producto (producto_articulo_id),
    KEY idx_fabricaciones_receta (receta_producto_final_id),
    CONSTRAINT fk_fabricaciones_producto FOREIGN KEY (producto_articulo_id) REFERENCES articulos(id),
    CONSTRAINT fk_fabricaciones_presentacion FOREIGN KEY (presentacion_id) REFERENCES presentaciones(id) ON DELETE SET NULL,
    CONSTRAINT fk_fabricaciones_empaque FOREIGN KEY (empaque_id) REFERENCES empaques(id) ON DELETE SET NULL,
    CONSTRAINT fk_fabricaciones_receta FOREIGN KEY (receta_producto_final_id) REFERENCES recetas_producto_final(id),
    CONSTRAINT fk_fabricaciones_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_fabricaciones_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS fabricacion_detalles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fabricacion_id BIGINT UNSIGNED NOT NULL,
    insumo_articulo_id BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(18,4) NOT NULL,
    unidad VARCHAR(5) NOT NULL,
    cantidad_kg DECIMAL(18,6) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fabricacion_detalles_fab (fabricacion_id),
    KEY idx_fabricacion_detalles_insumo (insumo_articulo_id),
    CONSTRAINT fk_fabricacion_detalles_fabricacion FOREIGN KEY (fabricacion_id) REFERENCES fabricaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_fabricacion_detalles_insumo FOREIGN KEY (insumo_articulo_id) REFERENCES articulos(id)
);
