ALTER TABLE pedidos
    ADD COLUMN localidad_id BIGINT UNSIGNED NULL AFTER cliente_rnc,
    ADD COLUMN localidad_nombre VARCHAR(150) NULL AFTER localidad_id;
