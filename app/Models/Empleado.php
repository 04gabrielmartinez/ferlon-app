<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDOException;
use RuntimeException;

final class Empleado
{
    public static function listarParaModal(): array
    {
        try {
            $sql = 'SELECT e.id,
                           e.cedula,
                           e.nombre,
                           e.apellido,
                           COALESCE(d.nombre, e.departamento, "") AS departamento,
                           e.estado
                    FROM empleados e
                    LEFT JOIN departamentos d ON d.id = e.departamento_id
                    ORDER BY e.id DESC';
            return Db::conexion()->query($sql)->fetchAll() ?: [];
        } catch (PDOException) {
            return [];
        }
    }

    public static function listarParaSupervisorSelect(?int $exceptId = null): array
    {
        $sql = "SELECT id, nombre, apellido
                FROM empleados
                WHERE estado = 'activo'" . ($exceptId !== null && $exceptId > 0 ? ' AND id <> :except_id' : '') . '
                ORDER BY nombre ASC, apellido ASC';
        $stmt = Db::conexion()->prepare($sql);
        $params = [];
        if ($exceptId !== null && $exceptId > 0) {
            $params['except_id'] = $exceptId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT * FROM empleados WHERE id = :id LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $empleado = $stmt->fetch();

        return $empleado ?: null;
    }

    public static function guardar(array $datos): int
    {
        $id = isset($datos['id']) ? (int) $datos['id'] : 0;

        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $apellido = trim((string) ($datos['apellido'] ?? ''));
        if ($nombre === '' || $apellido === '') {
            throw new RuntimeException('Nombre y apellido son obligatorios.');
        }

        $payload = [
            'cedula' => self::normalizarTextoOpcional($datos['cedula'] ?? null),
            'estado' => ($datos['estado'] ?? 'activo') === 'inactivo' ? 'inactivo' : 'activo',
            'nombre' => $nombre,
            'apellido' => $apellido,
            'genero' => trim((string) ($datos['genero'] ?? '')),
            'cumpleanos' => self::normalizarFecha($datos['cumpleanos'] ?? null),
            'email_personal' => self::normalizarTextoOpcional($datos['email_personal'] ?? null),
            'email_empresa' => self::normalizarTextoOpcional($datos['email_empresa'] ?? null),
            'departamento_id' => self::normalizarEnteroOpcional($datos['departamento_id'] ?? null),
            'subdepartamento_id' => self::normalizarEnteroOpcional($datos['subdepartamento_id'] ?? null),
            'puesto_id' => self::normalizarEnteroOpcional($datos['puesto_id'] ?? null),
            'cargo' => trim((string) ($datos['cargo'] ?? '')),
            'supervisor_id' => self::normalizarEnteroOpcional($datos['supervisor_id'] ?? null),
            'cuenta_banco' => trim((string) ($datos['cuenta_banco'] ?? '')),
            'afp_complementario' => trim((string) ($datos['afp_complementario'] ?? '')),
            'departamento' => trim((string) ($datos['departamento'] ?? '')),
            'subdepartamento' => trim((string) ($datos['subdepartamento'] ?? '')),
            'fecha_entrada' => self::normalizarFecha($datos['fecha_entrada'] ?? null),
            'fecha_salida' => self::normalizarFecha($datos['fecha_salida'] ?? null),
            'tipo_contrato' => trim((string) ($datos['tipo_contrato'] ?? '')),
            'jornada' => trim((string) ($datos['jornada'] ?? '')),
            'transporte' => trim((string) ($datos['transporte'] ?? '')),
            'dieta' => trim((string) ($datos['dieta'] ?? '')),
            'banco_id' => self::normalizarEnteroOpcional($datos['banco_id'] ?? null),
            'tipo_cuenta' => trim((string) ($datos['tipo_cuenta'] ?? '')),
            'titular_cuenta' => trim((string) ($datos['titular_cuenta'] ?? '')),
            'moneda_cuenta' => trim((string) ($datos['moneda_cuenta'] ?? '')),
            'telefono_personal' => trim((string) ($datos['telefono_personal'] ?? '')),
            'contacto_emergencia_nombre' => trim((string) ($datos['contacto_emergencia_nombre'] ?? '')),
            'contacto_emergencia_parentesco' => trim((string) ($datos['contacto_emergencia_parentesco'] ?? '')),
            'contacto_emergencia_telefono' => trim((string) ($datos['contacto_emergencia_telefono'] ?? '')),
            'direccion_completa' => trim((string) ($datos['direccion_completa'] ?? '')),
            'ciudad' => trim((string) ($datos['ciudad'] ?? '')),
            'provincia' => trim((string) ($datos['provincia'] ?? '')),
            'pais' => trim((string) ($datos['pais'] ?? '')),
            'codigo_postal' => trim((string) ($datos['codigo_postal'] ?? '')),
            'salario_base' => self::normalizarDecimalOpcional($datos['salario_base'] ?? null),
            'frecuencia_pago' => trim((string) ($datos['frecuencia_pago'] ?? '')),
            'fecha_ultimo_aumento' => self::normalizarFecha($datos['fecha_ultimo_aumento'] ?? null),
            'ubicacion' => trim((string) ($datos['ubicacion'] ?? '')),
            'contacto_info' => trim((string) ($datos['informacion_contacto'] ?? ($datos['contacto_info'] ?? ''))),
            'foto_path' => trim((string) ($datos['foto_path'] ?? '')),
        ];

        if ($id > 0) {
            $set = [];
            foreach (array_keys($payload) as $campo) {
                $set[] = $campo . ' = :' . $campo;
            }

            $sql = 'UPDATE empleados SET ' . implode(', ', $set) . ' WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute($payload + ['id' => $id]);
            return $id;
        }

        $campos = array_keys($payload);
        $sql = 'INSERT INTO empleados (' . implode(', ', $campos) . ') VALUES (:' . implode(', :', $campos) . ')';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute($payload);
        return (int) Db::conexion()->lastInsertId();
    }

    private static function normalizarFecha(mixed $valor): ?string
    {
        $fecha = trim((string) $valor);
        return $fecha === '' ? null : $fecha;
    }

    private static function normalizarTextoOpcional(mixed $valor): ?string
    {
        $texto = trim((string) $valor);
        return $texto === '' ? null : $texto;
    }

    private static function normalizarEnteroOpcional(mixed $valor): ?int
    {
        $numero = (int) $valor;
        return $numero > 0 ? $numero : null;
    }

    private static function normalizarDecimalOpcional(mixed $valor): ?float
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }
        return is_numeric($texto) ? (float) $texto : null;
    }
}
