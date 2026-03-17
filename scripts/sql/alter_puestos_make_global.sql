SET @schema := DATABASE();

SET @has_fk_dep := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'puestos'
      AND CONSTRAINT_NAME = 'fk_puestos_departamento'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql_drop_fk_dep := IF(@has_fk_dep > 0, 'ALTER TABLE puestos DROP FOREIGN KEY fk_puestos_departamento', 'SELECT 1');
PREPARE stmt_drop_fk_dep FROM @sql_drop_fk_dep;
EXECUTE stmt_drop_fk_dep;
DEALLOCATE PREPARE stmt_drop_fk_dep;

SET @has_fk_subdep := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'puestos'
      AND CONSTRAINT_NAME = 'fk_puestos_subdepartamento'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql_drop_fk_subdep := IF(@has_fk_subdep > 0, 'ALTER TABLE puestos DROP FOREIGN KEY fk_puestos_subdepartamento', 'SELECT 1');
PREPARE stmt_drop_fk_subdep FROM @sql_drop_fk_subdep;
EXECUTE stmt_drop_fk_subdep;
DEALLOCATE PREPARE stmt_drop_fk_subdep;

SET @has_idx_combo := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'puestos'
      AND INDEX_NAME = 'uq_puestos_depto_subdepto_nombre'
);
SET @sql_drop_idx_combo := IF(@has_idx_combo > 0, 'ALTER TABLE puestos DROP INDEX uq_puestos_depto_subdepto_nombre', 'SELECT 1');
PREPARE stmt_drop_idx_combo FROM @sql_drop_idx_combo;
EXECUTE stmt_drop_idx_combo;
DEALLOCATE PREPARE stmt_drop_idx_combo;

SET @has_idx_dep := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'puestos'
      AND INDEX_NAME = 'idx_puestos_departamento'
);
SET @sql_drop_idx_dep := IF(@has_idx_dep > 0, 'ALTER TABLE puestos DROP INDEX idx_puestos_departamento', 'SELECT 1');
PREPARE stmt_drop_idx_dep FROM @sql_drop_idx_dep;
EXECUTE stmt_drop_idx_dep;
DEALLOCATE PREPARE stmt_drop_idx_dep;

SET @has_idx_subdep := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'puestos'
      AND INDEX_NAME = 'idx_puestos_subdepartamento'
);
SET @sql_drop_idx_subdep := IF(@has_idx_subdep > 0, 'ALTER TABLE puestos DROP INDEX idx_puestos_subdepartamento', 'SELECT 1');
PREPARE stmt_drop_idx_subdep FROM @sql_drop_idx_subdep;
EXECUTE stmt_drop_idx_subdep;
DEALLOCATE PREPARE stmt_drop_idx_subdep;

SET @has_col_dep := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'puestos'
      AND COLUMN_NAME = 'departamento_id'
);
SET @sql_drop_col_dep := IF(@has_col_dep > 0, 'ALTER TABLE puestos DROP COLUMN departamento_id', 'SELECT 1');
PREPARE stmt_drop_col_dep FROM @sql_drop_col_dep;
EXECUTE stmt_drop_col_dep;
DEALLOCATE PREPARE stmt_drop_col_dep;

SET @has_col_subdep := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'puestos'
      AND COLUMN_NAME = 'subdepartamento_id'
);
SET @sql_drop_col_subdep := IF(@has_col_subdep > 0, 'ALTER TABLE puestos DROP COLUMN subdepartamento_id', 'SELECT 1');
PREPARE stmt_drop_col_subdep FROM @sql_drop_col_subdep;
EXECUTE stmt_drop_col_subdep;
DEALLOCATE PREPARE stmt_drop_col_subdep;

SET @has_uq_nombre := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'puestos'
      AND INDEX_NAME = 'uq_puestos_nombre'
);
SET @sql_add_uq_nombre := IF(@has_uq_nombre = 0, 'ALTER TABLE puestos ADD UNIQUE KEY uq_puestos_nombre (nombre)', 'SELECT 1');
PREPARE stmt_add_uq_nombre FROM @sql_add_uq_nombre;
EXECUTE stmt_add_uq_nombre;
DEALLOCATE PREPARE stmt_add_uq_nombre;
