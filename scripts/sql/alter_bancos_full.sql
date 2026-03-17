SET @schema := DATABASE();

SET @has_nombre_banco := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'nombre_banco'
);
SET @sql_add_nombre_banco := IF(
    @has_nombre_banco = 0,
    'ALTER TABLE bancos ADD COLUMN nombre_banco VARCHAR(160) NULL AFTER id',
    'SELECT 1'
);
PREPARE stmt_add_nombre_banco FROM @sql_add_nombre_banco;
EXECUTE stmt_add_nombre_banco;
DEALLOCATE PREPARE stmt_add_nombre_banco;

SET @has_codigo_banco := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'codigo_banco'
);
SET @sql_add_codigo_banco := IF(
    @has_codigo_banco = 0,
    'ALTER TABLE bancos ADD COLUMN codigo_banco VARCHAR(20) NULL AFTER nombre_banco',
    'SELECT 1'
);
PREPARE stmt_add_codigo_banco FROM @sql_add_codigo_banco;
EXECUTE stmt_add_codigo_banco;
DEALLOCATE PREPARE stmt_add_codigo_banco;

SET @has_estado := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'estado'
);
SET @sql_add_estado := IF(
    @has_estado = 0,
    'ALTER TABLE bancos ADD COLUMN estado VARCHAR(10) NULL AFTER codigo_banco',
    'SELECT 1'
);
PREPARE stmt_add_estado FROM @sql_add_estado;
EXECUTE stmt_add_estado;
DEALLOCATE PREPARE stmt_add_estado;

SET @has_rnc := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'rnc'
);
SET @sql_add_rnc := IF(@has_rnc = 0, 'ALTER TABLE bancos ADD COLUMN rnc VARCHAR(20) NULL AFTER estado', 'SELECT 1');
PREPARE stmt_add_rnc FROM @sql_add_rnc;
EXECUTE stmt_add_rnc;
DEALLOCATE PREPARE stmt_add_rnc;

SET @has_telefono := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'telefono'
);
SET @sql_add_telefono := IF(@has_telefono = 0, 'ALTER TABLE bancos ADD COLUMN telefono VARCHAR(40) NULL AFTER rnc', 'SELECT 1');
PREPARE stmt_add_telefono FROM @sql_add_telefono;
EXECUTE stmt_add_telefono;
DEALLOCATE PREPARE stmt_add_telefono;

SET @has_correo_contacto := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'correo_contacto'
);
SET @sql_add_correo_contacto := IF(@has_correo_contacto = 0, 'ALTER TABLE bancos ADD COLUMN correo_contacto VARCHAR(150) NULL AFTER telefono', 'SELECT 1');
PREPARE stmt_add_correo_contacto FROM @sql_add_correo_contacto;
EXECUTE stmt_add_correo_contacto;
DEALLOCATE PREPARE stmt_add_correo_contacto;

SET @has_sitio_web := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'sitio_web'
);
SET @sql_add_sitio_web := IF(@has_sitio_web = 0, 'ALTER TABLE bancos ADD COLUMN sitio_web VARCHAR(180) NULL AFTER correo_contacto', 'SELECT 1');
PREPARE stmt_add_sitio_web FROM @sql_add_sitio_web;
EXECUTE stmt_add_sitio_web;
DEALLOCATE PREPARE stmt_add_sitio_web;

SET @has_direccion := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'direccion'
);
SET @sql_add_direccion := IF(@has_direccion = 0, 'ALTER TABLE bancos ADD COLUMN direccion VARCHAR(250) NULL AFTER sitio_web', 'SELECT 1');
PREPARE stmt_add_direccion FROM @sql_add_direccion;
EXECUTE stmt_add_direccion;
DEALLOCATE PREPARE stmt_add_direccion;

SET @has_pais := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema
      AND TABLE_NAME = 'bancos'
      AND COLUMN_NAME = 'pais'
);
SET @sql_add_pais := IF(@has_pais = 0, 'ALTER TABLE bancos ADD COLUMN pais VARCHAR(100) NULL AFTER direccion', 'SELECT 1');
PREPARE stmt_add_pais FROM @sql_add_pais;
EXECUTE stmt_add_pais;
DEALLOCATE PREPARE stmt_add_pais;

UPDATE bancos
SET nombre_banco = COALESCE(NULLIF(nombre_banco, ''), NULLIF(nombre, ''), CONCAT('Banco ', id))
WHERE nombre_banco IS NULL OR TRIM(nombre_banco) = '';

UPDATE bancos
SET codigo_banco = UPPER(
    LEFT(
        REPLACE(REPLACE(REPLACE(COALESCE(nombre_banco, ''), ' ', ''), '.', ''), '-', ''),
        4
    )
)
WHERE codigo_banco IS NULL OR TRIM(codigo_banco) = '';

UPDATE bancos
SET codigo_banco = CONCAT(codigo_banco, LPAD(id, 2, '0'))
WHERE codigo_banco IN (
    SELECT c.codigo_banco
    FROM (
        SELECT codigo_banco
        FROM bancos
        GROUP BY codigo_banco
        HAVING COUNT(*) > 1
    ) c
);

UPDATE bancos
SET estado = CASE
    WHEN estado IS NULL OR TRIM(estado) = '' THEN IF(COALESCE(activo, 1) = 1, 'activo', 'inactivo')
    WHEN LOWER(estado) NOT IN ('activo', 'inactivo') THEN IF(COALESCE(activo, 1) = 1, 'activo', 'inactivo')
    ELSE LOWER(estado)
END;

UPDATE bancos
SET pais = 'Republica Dominicana'
WHERE pais IS NULL OR TRIM(pais) = '';

ALTER TABLE bancos MODIFY nombre_banco VARCHAR(160) NOT NULL;
ALTER TABLE bancos MODIFY codigo_banco VARCHAR(20) NOT NULL;
ALTER TABLE bancos MODIFY estado VARCHAR(10) NOT NULL;

SET @has_uq_nombre := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'bancos' AND INDEX_NAME = 'uq_bancos_nombre_banco'
);
SET @sql_add_uq_nombre := IF(@has_uq_nombre = 0, 'ALTER TABLE bancos ADD UNIQUE KEY uq_bancos_nombre_banco (nombre_banco)', 'SELECT 1');
PREPARE stmt_add_uq_nombre FROM @sql_add_uq_nombre;
EXECUTE stmt_add_uq_nombre;
DEALLOCATE PREPARE stmt_add_uq_nombre;

SET @has_uq_codigo := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'bancos' AND INDEX_NAME = 'uq_bancos_codigo_banco'
);
SET @sql_add_uq_codigo := IF(@has_uq_codigo = 0, 'ALTER TABLE bancos ADD UNIQUE KEY uq_bancos_codigo_banco (codigo_banco)', 'SELECT 1');
PREPARE stmt_add_uq_codigo FROM @sql_add_uq_codigo;
EXECUTE stmt_add_uq_codigo;
DEALLOCATE PREPARE stmt_add_uq_codigo;
