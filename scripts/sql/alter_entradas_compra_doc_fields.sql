SET @schema := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='entradas_compra' AND COLUMN_NAME='ncf')=0,
    'ALTER TABLE entradas_compra ADD COLUMN ncf VARCHAR(30) NULL AFTER condicion_pago','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='entradas_compra' AND COLUMN_NAME='orden_no')=0,
    'ALTER TABLE entradas_compra ADD COLUMN orden_no VARCHAR(40) NULL AFTER ncf','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='entradas_compra' AND COLUMN_NAME='factura_no')=0,
    'ALTER TABLE entradas_compra ADD COLUMN factura_no VARCHAR(40) NULL AFTER orden_no','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='entradas_compra' AND COLUMN_NAME='pedido_no')=0,
    'ALTER TABLE entradas_compra ADD COLUMN pedido_no VARCHAR(40) NULL AFTER factura_no','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
