ALTER TABLE recetas_producto_final
    ADD COLUMN precio_venta DECIMAL(18,4) NOT NULL DEFAULT 0 AFTER empaque_id;
