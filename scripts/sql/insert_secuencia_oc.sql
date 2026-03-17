INSERT INTO secuencias (clave, aplica_a, prefijo, longitud, valor_actual, incremento, activo, uso_total)
VALUES ('oc', 'Orden Compra', 'OC', 5, 0, 1, 1, 0)
ON DUPLICATE KEY UPDATE
    aplica_a = VALUES(aplica_a),
    prefijo = VALUES(prefijo),
    longitud = VALUES(longitud),
    incremento = VALUES(incremento),
    activo = 1;
