CREATE TABLE IF NOT EXISTS producciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_produccion VARCHAR(50) NOT NULL,
    fecha DATE NOT NULL,
    producto_articulo_id BIGINT UNSIGNED NOT NULL,
    receta_base_id BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(18,4) NOT NULL,
    unidad VARCHAR(5) NOT NULL,
    comentario TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_producciones_codigo (codigo_produccion),
    KEY idx_producciones_producto (producto_articulo_id),
    KEY idx_producciones_receta (receta_base_id),
    CONSTRAINT fk_producciones_producto FOREIGN KEY (producto_articulo_id) REFERENCES articulos(id),
    CONSTRAINT fk_producciones_receta FOREIGN KEY (receta_base_id) REFERENCES recetas_base(id),
    CONSTRAINT fk_producciones_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_producciones_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS produccion_detalles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produccion_id BIGINT UNSIGNED NOT NULL,
    insumo_articulo_id BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(18,4) NOT NULL,
    unidad VARCHAR(5) NOT NULL,
    cantidad_kg DECIMAL(18,6) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_produccion_detalles_prod (produccion_id),
    KEY idx_produccion_detalles_insumo (insumo_articulo_id),
    CONSTRAINT fk_produccion_detalles_produccion FOREIGN KEY (produccion_id) REFERENCES producciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_produccion_detalles_insumo FOREIGN KEY (insumo_articulo_id) REFERENCES articulos(id)
);
