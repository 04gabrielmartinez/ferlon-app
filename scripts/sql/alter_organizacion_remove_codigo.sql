SET @schema := DATABASE();

SET @has_dep_codigo := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'departamentos'
      AND COLUMN_NAME = 'codigo'
);
SET @drop_dep_uq_codigo := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'departamentos'
      AND INDEX_NAME = 'uq_departamentos_codigo'
);
SET @sql_drop_dep_uq := IF(@drop_dep_uq_codigo > 0, 'ALTER TABLE departamentos DROP INDEX uq_departamentos_codigo', 'SELECT 1');
PREPARE stmt_drop_dep_uq FROM @sql_drop_dep_uq;
EXECUTE stmt_drop_dep_uq;
DEALLOCATE PREPARE stmt_drop_dep_uq;

SET @sql_drop_dep_codigo := IF(@has_dep_codigo > 0, 'ALTER TABLE departamentos DROP COLUMN codigo', 'SELECT 1');
PREPARE stmt_drop_dep_codigo FROM @sql_drop_dep_codigo;
EXECUTE stmt_drop_dep_codigo;
DEALLOCATE PREPARE stmt_drop_dep_codigo;

SET @has_sub_codigo := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'subdepartamentos'
      AND COLUMN_NAME = 'codigo'
);
SET @drop_sub_uq_codigo := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'subdepartamentos'
      AND INDEX_NAME = 'uq_subdepartamentos_codigo'
);
SET @sql_drop_sub_uq := IF(@drop_sub_uq_codigo > 0, 'ALTER TABLE subdepartamentos DROP INDEX uq_subdepartamentos_codigo', 'SELECT 1');
PREPARE stmt_drop_sub_uq FROM @sql_drop_sub_uq;
EXECUTE stmt_drop_sub_uq;
DEALLOCATE PREPARE stmt_drop_sub_uq;

SET @sql_drop_sub_codigo := IF(@has_sub_codigo > 0, 'ALTER TABLE subdepartamentos DROP COLUMN codigo', 'SELECT 1');
PREPARE stmt_drop_sub_codigo FROM @sql_drop_sub_codigo;
EXECUTE stmt_drop_sub_codigo;
DEALLOCATE PREPARE stmt_drop_sub_codigo;
