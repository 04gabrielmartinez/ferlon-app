ALTER TABLE pedidos
    ADD COLUMN departamento ENUM('almacen', 'facturacion') NOT NULL DEFAULT 'almacen' AFTER comentario,
    ADD COLUMN visto TINYINT(1) NOT NULL DEFAULT 0 AFTER departamento;
