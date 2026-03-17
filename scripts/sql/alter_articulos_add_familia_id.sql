SET @schema := DATABASE();

SET @has_familia_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'articulos'
      AND COLUMN_NAME = 'familia_id'
);

SET @sql_add_familia_id := IF(
    @has_familia_id = 0,
    'ALTER TABLE articulos ADD COLUMN familia_id BIGINT UNSIGNED NULL AFTER marca_id',
    'SELECT 1'
);
PREPARE stmt_add_familia_id FROM @sql_add_familia_id;
EXECUTE stmt_add_familia_id;
DEALLOCATE PREPARE stmt_add_familia_id;

SET @has_idx_familia := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'articulos'
      AND INDEX_NAME = 'idx_articulos_familia'
);

SET @sql_add_idx_familia := IF(
    @has_idx_familia = 0,
    'ALTER TABLE articulos ADD KEY idx_articulos_familia (familia_id)',
    'SELECT 1'
);
PREPARE stmt_add_idx_familia FROM @sql_add_idx_familia;
EXECUTE stmt_add_idx_familia;
DEALLOCATE PREPARE stmt_add_idx_familia;

SET @has_fk_familia := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = @schema
      AND TABLE_NAME = 'articulos'
      AND CONSTRAINT_NAME = 'fk_articulos_familia'
);

SET @sql_add_fk_familia := IF(
    @has_fk_familia = 0,
    'ALTER TABLE articulos ADD CONSTRAINT fk_articulos_familia FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_add_fk_familia FROM @sql_add_fk_familia;
EXECUTE stmt_add_fk_familia;
DEALLOCATE PREPARE stmt_add_fk_familia;
