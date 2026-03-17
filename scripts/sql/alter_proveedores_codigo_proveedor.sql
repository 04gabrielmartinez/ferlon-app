SET @schema := DATABASE();

SET @has_codigo := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'proveedores'
      AND COLUMN_NAME = 'codigo_proveedor'
);

SET @sql_add_codigo := IF(
    @has_codigo = 0,
    'ALTER TABLE proveedores ADD COLUMN codigo_proveedor VARCHAR(40) NULL AFTER id',
    'SELECT 1'
);
PREPARE stmt_add_codigo FROM @sql_add_codigo;
EXECUTE stmt_add_codigo;
DEALLOCATE PREPARE stmt_add_codigo;

UPDATE proveedores
SET codigo_proveedor = CONCAT('PV', LPAD(id, 8, '0'))
WHERE codigo_proveedor IS NULL OR TRIM(codigo_proveedor) = '';

ALTER TABLE proveedores
MODIFY codigo_proveedor VARCHAR(40) NOT NULL;

SET @has_uq := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'proveedores'
      AND INDEX_NAME = 'uq_proveedores_codigo'
);

SET @sql_add_uq := IF(
    @has_uq = 0,
    'ALTER TABLE proveedores ADD UNIQUE KEY uq_proveedores_codigo (codigo_proveedor)',
    'SELECT 1'
);
PREPARE stmt_add_uq FROM @sql_add_uq;
EXECUTE stmt_add_uq;
DEALLOCATE PREPARE stmt_add_uq;
