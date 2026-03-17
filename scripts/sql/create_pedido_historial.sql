CREATE TABLE IF NOT EXISTS pedido_historial (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    usuario_nombre VARCHAR(150) NOT NULL,
    accion_realizada VARCHAR(120) NOT NULL,
    detalle TEXT NULL,
    comentario TEXT NULL,
    datos_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pedido_historial_pedido (pedido_id),
    KEY idx_pedido_historial_usuario (usuario_id),
    CONSTRAINT fk_pedido_historial_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
