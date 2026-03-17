SET @schema := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND COLUMN_NAME='presentacion_id')=0,
    'ALTER TABLE recetas_producto_final ADD COLUMN presentacion_id BIGINT UNSIGNED NULL AFTER producto_articulo_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND COLUMN_NAME='empaque_id')=0,
    'ALTER TABLE recetas_producto_final ADD COLUMN empaque_id BIGINT UNSIGNED NULL AFTER presentacion_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE recetas_producto_final r
INNER JOIN articulos a ON a.id = r.producto_articulo_id
SET r.presentacion_id = a.presentacion_id
WHERE r.presentacion_id IS NULL
  AND a.presentacion_id IS NOT NULL
  AND a.presentacion_id > 0;

UPDATE recetas_producto_final r
INNER JOIN (
    SELECT articulo_id, MIN(presentacion_id) AS presentacion_id
    FROM articulos_presentaciones
    GROUP BY articulo_id
) ap ON ap.articulo_id = r.producto_articulo_id
SET r.presentacion_id = ap.presentacion_id
WHERE r.presentacion_id IS NULL;

UPDATE recetas_producto_final r
INNER JOIN articulos a ON a.id = r.producto_articulo_id
SET r.empaque_id = a.empaque_id
WHERE r.empaque_id IS NULL
  AND a.empaque_id IS NOT NULL
  AND a.empaque_id > 0;

UPDATE recetas_producto_final r
INNER JOIN (
    SELECT articulo_id, MIN(empaque_id) AS empaque_id
    FROM articulos_empaques
    GROUP BY articulo_id
) ae ON ae.articulo_id = r.producto_articulo_id
SET r.empaque_id = ae.empaque_id
WHERE r.empaque_id IS NULL;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND INDEX_NAME='uq_recetas_producto_final_producto')>0,
    'ALTER TABLE recetas_producto_final ADD KEY idx_recetas_producto_final_producto (producto_articulo_id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND INDEX_NAME='uq_recetas_producto_final_producto')>0,
    'ALTER TABLE recetas_producto_final DROP INDEX uq_recetas_producto_final_producto',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

DELETE r1 FROM recetas_producto_final r1
INNER JOIN recetas_producto_final r2
    ON r1.producto_articulo_id = r2.producto_articulo_id
   AND COALESCE(r1.presentacion_id, 0) = COALESCE(r2.presentacion_id, 0)
   AND COALESCE(r1.empaque_id, 0) = COALESCE(r2.empaque_id, 0)
   AND r1.id > r2.id
WHERE r1.presentacion_id IS NOT NULL
  AND r1.empaque_id IS NOT NULL
  AND r2.presentacion_id IS NOT NULL
  AND r2.empaque_id IS NOT NULL;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND COLUMN_NAME='presentacion_id' AND IS_NULLABLE='YES')=1,
    'ALTER TABLE recetas_producto_final MODIFY presentacion_id BIGINT UNSIGNED NOT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND COLUMN_NAME='empaque_id' AND IS_NULLABLE='YES')=1,
    'ALTER TABLE recetas_producto_final MODIFY empaque_id BIGINT UNSIGNED NOT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND INDEX_NAME='uq_recetas_producto_final_variante')=0,
    'ALTER TABLE recetas_producto_final ADD UNIQUE KEY uq_recetas_producto_final_variante (producto_articulo_id, presentacion_id, empaque_id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND INDEX_NAME='idx_recetas_producto_final_presentacion')=0,
    'ALTER TABLE recetas_producto_final ADD KEY idx_recetas_producto_final_presentacion (presentacion_id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND INDEX_NAME='idx_recetas_producto_final_empaque')=0,
    'ALTER TABLE recetas_producto_final ADD KEY idx_recetas_producto_final_empaque (empaque_id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND CONSTRAINT_NAME='fk_recetas_producto_final_presentacion')=0,
    'ALTER TABLE recetas_producto_final ADD CONSTRAINT fk_recetas_producto_final_presentacion FOREIGN KEY (presentacion_id) REFERENCES presentaciones(id) ON DELETE RESTRICT',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='recetas_producto_final' AND CONSTRAINT_NAME='fk_recetas_producto_final_empaque')=0,
    'ALTER TABLE recetas_producto_final ADD CONSTRAINT fk_recetas_producto_final_empaque FOREIGN KEY (empaque_id) REFERENCES empaques(id) ON DELETE RESTRICT',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
