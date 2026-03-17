<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;

final class Ncf
{
    public const TIPOS = [
        'Factura Consumidor Final (Contado)',
        'Factura Crédito Fiscal (Contado)',
        'Gastos Menores',
        'Gubernamental (Contado)',
        'Nota Crédito (Contado)',
        'Nota Débito (Contado)',
        'Pagos al Exterior',
        'Para Exportación',
        'Regímenes Especiales (Contado)',
        'Registro Único Ingresos',
        'Suplidores Informales',
    ];

    public static function listar(): array
    {
        $sql = 'SELECT n.id, n.tipo_ncf, n.prefijo, n.autorizacion, n.contador_inicial, n.contador_actual,
                       n.final_numero, n.fecha_vencimiento, n.alerta_faltan, n.created_at, n.created_by,
                       u.nombre AS creador_nombre
                FROM ncf_config n
                LEFT JOIN users u ON u.id = n.created_by
                ORDER BY n.id DESC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT id, tipo_ncf, prefijo, autorizacion, contador_inicial, contador_actual,
                       final_numero, fecha_vencimiento, alerta_faltan, created_at, created_by
                FROM ncf_config
                WHERE id = :id
                LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function guardar(?int $id, array $data, int $usuarioId): int
    {
        $tipo = trim((string) ($data['tipo_ncf'] ?? ''));
        $prefijo = trim((string) ($data['prefijo'] ?? ''));
        $autorizacion = trim((string) ($data['autorizacion'] ?? ''));
        $contadorInicial = (int) ($data['contador_inicial'] ?? 0);
        $finalNumero = (int) ($data['final_numero'] ?? 0);
        $fechaVencimiento = trim((string) ($data['fecha_vencimiento'] ?? ''));
        $alertaFaltan = (int) ($data['alerta_faltan'] ?? 0);

        if (!in_array($tipo, self::TIPOS, true)) {
            throw new RuntimeException('Selecciona un tipo de NCF válido.');
        }
        if ($prefijo === '' || strlen($prefijo) > 20) {
            throw new RuntimeException('Prefijo es obligatorio y debe ser corto.');
        }
        if ($autorizacion === '' || !preg_match('/^[0-9]{5,30}$/', $autorizacion)) {
            throw new RuntimeException('Autorización debe contener solo números (5 a 30 dígitos).');
        }
        if ($contadorInicial < 0) {
            throw new RuntimeException('Contador inicial no puede ser negativo.');
        }
        if ($finalNumero <= 0 || $finalNumero < $contadorInicial) {
            throw new RuntimeException('Final debe ser mayor o igual al contador inicial y mayor que 0.');
        }
        if ($alertaFaltan < 0) {
            throw new RuntimeException('Alerta cuando falten no puede ser negativa.');
        }
        if (!$thisDate = \DateTime::createFromFormat('Y-m-d', $fechaVencimiento)) {
            throw new RuntimeException('Fecha de vencimiento inválida.');
        }
        if ($thisDate->format('Y-m-d') !== $fechaVencimiento) {
            throw new RuntimeException('Fecha de vencimiento inválida.');
        }

        $id = $id !== null && $id > 0 ? $id : null;
        $contadorActual = $contadorInicial;

        if ($id !== null) {
            $actual = self::buscarPorId($id);
            if (!$actual) {
                throw new RuntimeException('Registro NCF no encontrado.');
            }
            $contadorActual = max((int) ($actual['contador_actual'] ?? 0), $contadorInicial);

            $sql = 'UPDATE ncf_config
                    SET tipo_ncf = :tipo_ncf,
                        prefijo = :prefijo,
                        autorizacion = :autorizacion,
                        contador_inicial = :contador_inicial,
                        contador_actual = :contador_actual,
                        final_numero = :final_numero,
                        fecha_vencimiento = :fecha_vencimiento,
                        alerta_faltan = :alerta_faltan
                    WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'tipo_ncf' => $tipo,
                'prefijo' => $prefijo,
                'autorizacion' => $autorizacion,
                'contador_inicial' => $contadorInicial,
                'contador_actual' => $contadorActual,
                'final_numero' => $finalNumero,
                'fecha_vencimiento' => $fechaVencimiento,
                'alerta_faltan' => $alertaFaltan,
            ]);

            return $id;
        }

        $sql = 'INSERT INTO ncf_config (
                    tipo_ncf, prefijo, autorizacion, contador_inicial, contador_actual, final_numero,
                    fecha_vencimiento, alerta_faltan, created_by
                ) VALUES (
                    :tipo_ncf, :prefijo, :autorizacion, :contador_inicial, :contador_actual, :final_numero,
                    :fecha_vencimiento, :alerta_faltan, :created_by
                )';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'tipo_ncf' => $tipo,
            'prefijo' => $prefijo,
            'autorizacion' => $autorizacion,
            'contador_inicial' => $contadorInicial,
            'contador_actual' => $contadorActual,
            'final_numero' => $finalNumero,
            'fecha_vencimiento' => $fechaVencimiento,
            'alerta_faltan' => $alertaFaltan,
            'created_by' => $usuarioId > 0 ? $usuarioId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function eliminar(int $id): void
    {
        $stmt = Db::conexion()->prepare('DELETE FROM ncf_config WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
