ALTER TABLE pedido_detalles
    ADD COLUMN descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER precio,
    ADD COLUMN descuento_monto DECIMAL(18,4) NOT NULL DEFAULT 0 AFTER descuento_pct;
