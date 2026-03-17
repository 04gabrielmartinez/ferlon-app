SET @schema := DATABASE();

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'departamento_id') = 0,
    'ALTER TABLE empleados ADD COLUMN departamento_id BIGINT UNSIGNED NULL AFTER email_empresa',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'subdepartamento_id') = 0,
    'ALTER TABLE empleados ADD COLUMN subdepartamento_id BIGINT UNSIGNED NULL AFTER departamento_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'puesto_id') = 0,
    'ALTER TABLE empleados ADD COLUMN puesto_id BIGINT UNSIGNED NULL AFTER subdepartamento_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'cargo') = 0,
    'ALTER TABLE empleados ADD COLUMN cargo VARCHAR(140) NULL AFTER puesto_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'supervisor_id') = 0,
    'ALTER TABLE empleados ADD COLUMN supervisor_id BIGINT UNSIGNED NULL AFTER cargo',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'tipo_contrato') = 0,
    'ALTER TABLE empleados ADD COLUMN tipo_contrato VARCHAR(30) NULL AFTER fecha_salida',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'jornada') = 0,
    'ALTER TABLE empleados ADD COLUMN jornada VARCHAR(30) NULL AFTER tipo_contrato',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'banco_id') = 0,
    'ALTER TABLE empleados ADD COLUMN banco_id BIGINT UNSIGNED NULL AFTER dieta',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'tipo_cuenta') = 0,
    'ALTER TABLE empleados ADD COLUMN tipo_cuenta VARCHAR(20) NULL AFTER banco_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'titular_cuenta') = 0,
    'ALTER TABLE empleados ADD COLUMN titular_cuenta VARCHAR(180) NULL AFTER tipo_cuenta',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'moneda_cuenta') = 0,
    'ALTER TABLE empleados ADD COLUMN moneda_cuenta VARCHAR(10) NULL AFTER titular_cuenta',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'telefono_personal') = 0,
    'ALTER TABLE empleados ADD COLUMN telefono_personal VARCHAR(25) NULL AFTER moneda_cuenta',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'contacto_emergencia_nombre') = 0,
    'ALTER TABLE empleados ADD COLUMN contacto_emergencia_nombre VARCHAR(140) NULL AFTER telefono_personal',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'contacto_emergencia_parentesco') = 0,
    'ALTER TABLE empleados ADD COLUMN contacto_emergencia_parentesco VARCHAR(30) NULL AFTER contacto_emergencia_nombre',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'contacto_emergencia_telefono') = 0,
    'ALTER TABLE empleados ADD COLUMN contacto_emergencia_telefono VARCHAR(25) NULL AFTER contacto_emergencia_parentesco',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'direccion_completa') = 0,
    'ALTER TABLE empleados ADD COLUMN direccion_completa TEXT NULL AFTER contacto_info',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'ciudad') = 0,
    'ALTER TABLE empleados ADD COLUMN ciudad VARCHAR(120) NULL AFTER direccion_completa',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'provincia') = 0,
    'ALTER TABLE empleados ADD COLUMN provincia VARCHAR(120) NULL AFTER ciudad',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'pais') = 0,
    'ALTER TABLE empleados ADD COLUMN pais VARCHAR(120) NULL AFTER provincia',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'codigo_postal') = 0,
    'ALTER TABLE empleados ADD COLUMN codigo_postal VARCHAR(20) NULL AFTER pais',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'salario_base') = 0,
    'ALTER TABLE empleados ADD COLUMN salario_base DECIMAL(12,2) NULL AFTER codigo_postal',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'frecuencia_pago') = 0,
    'ALTER TABLE empleados ADD COLUMN frecuencia_pago VARCHAR(20) NULL AFTER salario_base',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND COLUMN_NAME = 'fecha_ultimo_aumento') = 0,
    'ALTER TABLE empleados ADD COLUMN fecha_ultimo_aumento DATE NULL AFTER frecuencia_pago',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND INDEX_NAME = 'idx_empleados_departamento_id') = 0,
    'ALTER TABLE empleados ADD KEY idx_empleados_departamento_id (departamento_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND INDEX_NAME = 'idx_empleados_subdepartamento_id') = 0,
    'ALTER TABLE empleados ADD KEY idx_empleados_subdepartamento_id (subdepartamento_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND INDEX_NAME = 'idx_empleados_puesto_id') = 0,
    'ALTER TABLE empleados ADD KEY idx_empleados_puesto_id (puesto_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND INDEX_NAME = 'idx_empleados_supervisor_id') = 0,
    'ALTER TABLE empleados ADD KEY idx_empleados_supervisor_id (supervisor_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'empleados' AND INDEX_NAME = 'idx_empleados_banco_id') = 0,
    'ALTER TABLE empleados ADD KEY idx_empleados_banco_id (banco_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
