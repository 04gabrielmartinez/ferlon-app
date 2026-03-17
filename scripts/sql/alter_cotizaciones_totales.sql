ALTER TABLE cotizaciones
    ADD COLUMN descuento_lineas DECIMAL(18,4) NOT NULL DEFAULT 0 AFTER subtotal,
    ADD COLUMN descuento_general_pct DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER descuento_lineas,
    ADD COLUMN descuento_general_monto DECIMAL(18,4) NOT NULL DEFAULT 0 AFTER descuento_general_pct,
    ADD COLUMN impuesto_pct DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER descuento_general_monto,
    ADD COLUMN impuesto DECIMAL(18,4) NOT NULL DEFAULT 0 AFTER impuesto_pct;
