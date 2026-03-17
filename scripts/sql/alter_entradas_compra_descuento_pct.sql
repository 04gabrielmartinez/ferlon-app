SET @schema := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='entradas_compra' AND COLUMN_NAME='descuento_general_pct')=0,
    'ALTER TABLE entradas_compra ADD COLUMN descuento_general_pct DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER total_descuento','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
