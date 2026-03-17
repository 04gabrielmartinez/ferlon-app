SET @schema := DATABASE();

SET @has_tipo_banco := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'proveedores'
      AND COLUMN_NAME = 'tipo_banco'
);

SET @sql_add_tipo_banco := IF(
    @has_tipo_banco = 0,
    'ALTER TABLE proveedores ADD COLUMN tipo_banco VARCHAR(40) NULL AFTER banco',
    'SELECT 1'
);
PREPARE stmt_add_tipo_banco FROM @sql_add_tipo_banco;
EXECUTE stmt_add_tipo_banco;
DEALLOCATE PREPARE stmt_add_tipo_banco;
