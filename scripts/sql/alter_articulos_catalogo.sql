SET @schema := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='codigo')=0,
    'ALTER TABLE articulos ADD COLUMN codigo VARCHAR(40) NULL AFTER id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='nombre')=0,
    'ALTER TABLE articulos ADD COLUMN nombre VARCHAR(180) NULL AFTER codigo','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='tipo_articulo_id')=0,
    'ALTER TABLE articulos ADD COLUMN tipo_articulo_id BIGINT UNSIGNED NULL AFTER estado','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='categoria_id')=0,
    'ALTER TABLE articulos ADD COLUMN categoria_id BIGINT UNSIGNED NULL AFTER tipo_articulo_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='subcategoria_id')=0,
    'ALTER TABLE articulos ADD COLUMN subcategoria_id BIGINT UNSIGNED NULL AFTER categoria_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='marca_id')=0,
    'ALTER TABLE articulos ADD COLUMN marca_id BIGINT UNSIGNED NULL AFTER subcategoria_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='unidad_base_id')=0,
    'ALTER TABLE articulos ADD COLUMN unidad_base_id VARCHAR(5) NULL AFTER marca_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='presentacion_id')=0,
    'ALTER TABLE articulos ADD COLUMN presentacion_id BIGINT UNSIGNED NULL AFTER unidad_base_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='empaque_id')=0,
    'ALTER TABLE articulos ADD COLUMN empaque_id BIGINT UNSIGNED NULL AFTER presentacion_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='maneja_inventario')=0,
    'ALTER TABLE articulos ADD COLUMN maneja_inventario TINYINT(1) NOT NULL DEFAULT 1 AFTER empaque_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='stock_minimo')=0,
    'ALTER TABLE articulos ADD COLUMN stock_minimo DECIMAL(18,4) NULL AFTER maneja_inventario','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='stock_maximo')=0,
    'ALTER TABLE articulos ADD COLUMN stock_maximo DECIMAL(18,4) NULL AFTER stock_minimo','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='punto_reorden')=0,
    'ALTER TABLE articulos ADD COLUMN punto_reorden DECIMAL(18,4) NULL AFTER stock_maximo','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='ubicacion')=0,
    'ALTER TABLE articulos ADD COLUMN ubicacion VARCHAR(180) NULL AFTER punto_reorden','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='lote')=0,
    'ALTER TABLE articulos ADD COLUMN lote TINYINT(1) NOT NULL DEFAULT 0 AFTER ubicacion','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='vence')=0,
    'ALTER TABLE articulos ADD COLUMN vence TINYINT(1) NOT NULL DEFAULT 0 AFTER lote','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='es_comprable')=0,
    'ALTER TABLE articulos ADD COLUMN es_comprable TINYINT(1) NOT NULL DEFAULT 1 AFTER vence','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='costo_ultimo')=0,
    'ALTER TABLE articulos ADD COLUMN costo_ultimo DECIMAL(18,4) NULL AFTER es_comprable','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='costo_promedio')=0,
    'ALTER TABLE articulos ADD COLUMN costo_promedio DECIMAL(18,4) NULL AFTER costo_ultimo','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='proveedor_default_id')=0,
    'ALTER TABLE articulos ADD COLUMN proveedor_default_id BIGINT UNSIGNED NULL AFTER costo_promedio','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='es_fabricable')=0,
    'ALTER TABLE articulos ADD COLUMN es_fabricable TINYINT(1) NOT NULL DEFAULT 0 AFTER proveedor_default_id','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='tiene_receta')=0,
    'ALTER TABLE articulos ADD COLUMN tiene_receta TINYINT(1) NOT NULL DEFAULT 0 AFTER es_fabricable','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='insumo_receta')=0,
    'ALTER TABLE articulos ADD COLUMN insumo_receta TINYINT(1) NOT NULL DEFAULT 0 AFTER tiene_receta','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='rendimiento')=0,
    'ALTER TABLE articulos ADD COLUMN rendimiento DECIMAL(18,4) NULL AFTER insumo_receta','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='merma_pct')=0,
    'ALTER TABLE articulos ADD COLUMN merma_pct DECIMAL(10,4) NULL AFTER rendimiento','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='impuestos')=0,
    'ALTER TABLE articulos ADD COLUMN impuestos VARCHAR(120) NULL AFTER merma_pct','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='foto_path')=0,
    'ALTER TABLE articulos ADD COLUMN foto_path VARCHAR(255) NULL AFTER impuestos','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='created_by')=0,
    'ALTER TABLE articulos ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER foto_path','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='updated_by')=0,
    'ALTER TABLE articulos ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER created_by','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE articulos SET nombre = COALESCE(NULLIF(TRIM(nombre), ''), COALESCE(NULLIF(TRIM(descripcion), ''), CONCAT('ART-', id)));
UPDATE articulos SET codigo = COALESCE(NULLIF(TRIM(codigo), ''), CONCAT('AR', LPAD(id, 8, '0')));
UPDATE articulos SET unidad_base_id = COALESCE(NULLIF(TRIM(unidad_base_id), ''), 'u');
UPDATE articulos SET stock_minimo = COALESCE(stock_minimo, 0), costo_ultimo = COALESCE(costo_ultimo, 0);

ALTER TABLE articulos MODIFY nombre VARCHAR(180) NOT NULL;
ALTER TABLE articulos MODIFY codigo VARCHAR(40) NOT NULL;
ALTER TABLE articulos MODIFY unidad_base_id VARCHAR(5) NOT NULL;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND INDEX_NAME='uq_articulos_codigo')=0,
    'ALTER TABLE articulos ADD UNIQUE KEY uq_articulos_codigo (codigo)','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
