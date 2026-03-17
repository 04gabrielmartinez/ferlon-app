SET @schema := DATABASE();

CREATE TABLE IF NOT EXISTS articulos_presentaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    articulo_id BIGINT UNSIGNED NOT NULL,
    presentacion_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_articulo_presentacion (articulo_id, presentacion_id),
    KEY idx_ap_articulo (articulo_id),
    KEY idx_ap_presentacion (presentacion_id),
    CONSTRAINT fk_ap_articulo FOREIGN KEY (articulo_id) REFERENCES articulos(id) ON DELETE CASCADE,
    CONSTRAINT fk_ap_presentacion FOREIGN KEY (presentacion_id) REFERENCES presentaciones(id) ON DELETE RESTRICT,
    CONSTRAINT fk_ap_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ap_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articulos_empaques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    articulo_id BIGINT UNSIGNED NOT NULL,
    empaque_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_articulo_empaque (articulo_id, empaque_id),
    KEY idx_ae_articulo (articulo_id),
    KEY idx_ae_empaque (empaque_id),
    CONSTRAINT fk_ae_articulo FOREIGN KEY (articulo_id) REFERENCES articulos(id) ON DELETE CASCADE,
    CONSTRAINT fk_ae_empaque FOREIGN KEY (empaque_id) REFERENCES empaques(id) ON DELETE RESTRICT,
    CONSTRAINT fk_ae_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ae_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO articulos_presentaciones (articulo_id, presentacion_id)
SELECT a.id, a.presentacion_id
FROM articulos a
WHERE a.presentacion_id IS NOT NULL
  AND a.presentacion_id > 0;

INSERT IGNORE INTO articulos_empaques (articulo_id, empaque_id)
SELECT a.id, a.empaque_id
FROM articulos a
WHERE a.empaque_id IS NOT NULL
  AND a.empaque_id > 0;
