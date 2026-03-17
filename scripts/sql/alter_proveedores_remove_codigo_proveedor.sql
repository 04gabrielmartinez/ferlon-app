SET @schema = DATABASE();

SET @sql := IF(
    (SELECT COUNT(*)
     FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @schema
       AND TABLE_NAME = 'proveedores'
       AND INDEX_NAME = 'uq_proveedores_codigo') > 0,
    'ALTER TABLE proveedores DROP INDEX uq_proveedores_codigo',
    'SELECT 1'
);
PREPARE stmt_drop_idx_codigo FROM @sql;
EXECUTE stmt_drop_idx_codigo;
DEALLOCATE PREPARE stmt_drop_idx_codigo;

SET @sql := IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @schema
       AND TABLE_NAME = 'proveedores'
       AND COLUMN_NAME = 'codigo_proveedor') > 0,
    'ALTER TABLE proveedores DROP COLUMN codigo_proveedor',
    'SELECT 1'
);
PREPARE stmt_drop_col_codigo FROM @sql;
EXECUTE stmt_drop_col_codigo;
DEALLOCATE PREPARE stmt_drop_col_codigo;
