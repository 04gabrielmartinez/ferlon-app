CREATE TABLE IF NOT EXISTS articulos_variantes_stock (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    articulo_id BIGINT UNSIGNED NOT NULL,
    presentacion_id BIGINT UNSIGNED NOT NULL,
    empaque_id BIGINT UNSIGNED NOT NULL,
    stock_actual DECIMAL(18,4) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(18,4) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_articulos_variantes (articulo_id, presentacion_id, empaque_id),
    KEY idx_articulos_variantes_articulo (articulo_id),
    CONSTRAINT fk_art_var_articulo FOREIGN KEY (articulo_id) REFERENCES articulos(id),
    CONSTRAINT fk_art_var_presentacion FOREIGN KEY (presentacion_id) REFERENCES presentaciones(id),
    CONSTRAINT fk_art_var_empaque FOREIGN KEY (empaque_id) REFERENCES empaques(id)
);
