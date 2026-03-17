SET @schema := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='stock_actual')=0,
    'ALTER TABLE articulos ADD COLUMN stock_actual DECIMAL(18,4) NOT NULL DEFAULT 0.0000 AFTER stock_maximo','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='articulos' AND COLUMN_NAME='stock_actual_kg')=0,
    'ALTER TABLE articulos ADD COLUMN stock_actual_kg DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER stock_actual','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
