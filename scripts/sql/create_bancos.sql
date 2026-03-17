CREATE TABLE IF NOT EXISTS bancos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_banco VARCHAR(160) NOT NULL,
    codigo_banco VARCHAR(20) NOT NULL,
    estado VARCHAR(10) NOT NULL DEFAULT 'activo',
    rnc VARCHAR(20) NULL,
    telefono VARCHAR(40) NULL,
    correo_contacto VARCHAR(150) NULL,
    sitio_web VARCHAR(180) NULL,
    direccion VARCHAR(250) NULL,
    pais VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bancos_nombre_banco (nombre_banco),
    UNIQUE KEY uq_bancos_codigo_banco (codigo_banco)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO bancos (nombre_banco, codigo_banco, estado, pais) VALUES
('Banco Popular Dominicano', 'POP', 'activo', 'Republica Dominicana'),
('Banreservas', 'RES', 'activo', 'Republica Dominicana'),
('Banco BHD', 'BHD', 'activo', 'Republica Dominicana'),
('Scotiabank Republica Dominicana', 'SCO', 'activo', 'Republica Dominicana'),
('Banco Santa Cruz', 'SCZ', 'activo', 'Republica Dominicana'),
('Banco Caribe', 'CAR', 'activo', 'Republica Dominicana'),
('Banco Ademi', 'ADE', 'activo', 'Republica Dominicana'),
('Asociacion Popular de Ahorros y Prestamos', 'APAP', 'activo', 'Republica Dominicana');
