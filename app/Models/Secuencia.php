<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;
use RuntimeException;

final class Secuencia
{
    public const APLICA_A = [
        'Orden Compra',
        'Entradas Compras',
        'Produccion',
        'Fabricacion',
        'Pedido',
        'Cotizacion',
    ];

    public static function opcionesAplicaA(): array
    {
        $result = [];
        foreach (self::APLICA_A as $nombre) {
            if (!in_array($nombre, $result, true)) {
                $result[] = $nombre;
            }
        }
        $stmtTipos = Db::conexion()->query("SELECT DISTINCT TRIM(descripcion) AS descripcion FROM tipos_articulo WHERE TRIM(descripcion) <> '' ORDER BY descripcion ASC");
        if ($stmtTipos !== false) {
            foreach (($stmtTipos->fetchAll() ?: []) as $row) {
                $nombre = trim((string) ($row['descripcion'] ?? ''));
                if ($nombre !== '') {
                    $label = $nombre . ' (articulos)';
                    if (!in_array($label, $result, true)) {
                        $result[] = $label;
                    }
                }
            }
        }

        return $result;
    }

    public static function listar(): array
    {
        $sql = 'SELECT id, clave, aplica_a, prefijo, longitud, valor_actual, incremento, activo, uso_total, updated_at
                FROM secuencias
                ORDER BY clave ASC';

        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT id, clave, aplica_a, prefijo, longitud, valor_actual, incremento, activo, uso_total, updated_at
                FROM secuencias
                WHERE id = :id
                LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function guardar(
        ?int $id,
        string $clave,
        string $aplicaA,
        string $prefijo,
        int $longitud,
        int $valorActual,
        int $incremento,
        bool $activo
    ): int {
        $clave = trim(strtolower($clave));
        $aplicaA = trim($aplicaA);
        $prefijo = trim($prefijo);

        if ($clave === '') {
            throw new RuntimeException('La clave es obligatoria.');
        }
        if (!preg_match('/^[a-z0-9_-]+$/', $clave)) {
            throw new RuntimeException('La clave solo permite letras, numeros, guion y guion bajo.');
        }
        if (!in_array($aplicaA, self::opcionesAplicaA(), true)) {
            throw new RuntimeException('Selecciona un valor valido en "Aplica a".');
        }
        if ($longitud <= 1) {
            throw new RuntimeException('La longitud debe ser mayor que 1.');
        }
        if ($incremento <= 0) {
            throw new RuntimeException('El incremento debe ser mayor que 0.');
        }
        if ($valorActual < 0) {
            throw new RuntimeException('Valor actual no puede ser negativo.');
        }

        if (self::claveExiste($clave, $id)) {
            throw new RuntimeException('La clave ya existe en otra secuencia.');
        }
        if (self::aplicaAExiste($aplicaA, $id)) {
            throw new RuntimeException('Ya existe una secuencia para ese "Aplica a".');
        }

        if ($id !== null && $id > 0) {
            $actual = self::buscarPorId($id);
            if (!$actual) {
                throw new RuntimeException('Secuencia no encontrada.');
            }
            if (!$activo && (int) ($actual['uso_total'] ?? 0) > 0) {
                throw new RuntimeException('No se puede descartar una secuencia que ya fue utilizada.');
            }

            $sql = 'UPDATE secuencias
                    SET clave = :clave,
                        aplica_a = :aplica_a,
                        prefijo = :prefijo,
                        longitud = :longitud,
                        valor_actual = :valor_actual,
                        incremento = :incremento,
                        activo = :activo
                    WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'clave' => $clave,
                'aplica_a' => $aplicaA,
                'prefijo' => $prefijo,
                'longitud' => $longitud,
                'valor_actual' => $valorActual,
                'incremento' => $incremento,
                'activo' => $activo ? 1 : 0,
            ]);

            return $id;
        }

        $sql = 'INSERT INTO secuencias (clave, aplica_a, prefijo, longitud, valor_actual, incremento, activo, uso_total)
                VALUES (:clave, :aplica_a, :prefijo, :longitud, :valor_actual, :incremento, :activo, 0)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'clave' => $clave,
            'aplica_a' => $aplicaA,
            'prefijo' => $prefijo,
            'longitud' => $longitud,
            'valor_actual' => $valorActual,
            'incremento' => $incremento,
            'activo' => $activo ? 1 : 0,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function getNextNumber(string $clave): string
    {
        $clave = trim(strtolower($clave));
        if ($clave === '') {
            throw new RuntimeException('Clave de secuencia requerida.');
        }

        $pdo = Db::conexion();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id, prefijo, longitud, valor_actual, incremento, activo
                                   FROM secuencias
                                   WHERE clave = :clave
                                   LIMIT 1
                                   FOR UPDATE');
            $stmt->execute(['clave' => $clave]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new RuntimeException('Secuencia no encontrada: ' . $clave);
            }
            if ((int) ($row['activo'] ?? 0) !== 1) {
                throw new RuntimeException('La secuencia "' . $clave . '" esta descartada/inactiva.');
            }

            $next = (int) $row['valor_actual'] + (int) $row['incremento'];
            $up = $pdo->prepare('UPDATE secuencias
                                 SET valor_actual = :valor_actual,
                                     uso_total = uso_total + 1
                                 WHERE id = :id');
            $up->execute([
                'id' => (int) $row['id'],
                'valor_actual' => $next,
            ]);

            $pdo->commit();

            return (string) $row['prefijo'] . str_pad((string) $next, (int) $row['longitud'], '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getNextNumberInTransaction(PDO $pdo, string $clave): string
    {
        $clave = trim(strtolower($clave));
        if ($clave === '') {
            throw new RuntimeException('Clave de secuencia requerida.');
        }

        $stmt = $pdo->prepare('SELECT id, prefijo, longitud, valor_actual, incremento, activo
                               FROM secuencias
                               WHERE clave = :clave
                               LIMIT 1
                               FOR UPDATE');
        $stmt->execute(['clave' => $clave]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Secuencia no encontrada: ' . $clave);
        }
        if ((int) ($row['activo'] ?? 0) !== 1) {
            throw new RuntimeException('La secuencia "' . $clave . '" esta descartada/inactiva.');
        }

        $next = (int) $row['valor_actual'] + (int) $row['incremento'];
        $up = $pdo->prepare('UPDATE secuencias
                             SET valor_actual = :valor_actual,
                                 uso_total = uso_total + 1
                             WHERE id = :id');
        $up->execute([
            'id' => (int) $row['id'],
            'valor_actual' => $next,
        ]);

        return (string) $row['prefijo'] . str_pad((string) $next, (int) $row['longitud'], '0', STR_PAD_LEFT);
    }

    public static function ensureExists(
        string $clave,
        string $aplicaA = 'Otros',
        string $prefijo = '',
        int $longitud = 8,
        int $incremento = 1
    ): void {
        $clave = trim(strtolower($clave));
        if ($clave === '') {
            return;
        }

        $stmt = Db::conexion()->prepare('SELECT id FROM secuencias WHERE clave = :clave LIMIT 1');
        $stmt->execute(['clave' => $clave]);
        if ($stmt->fetch()) {
            return;
        }

        $longitud = $longitud > 1 ? $longitud : 8;
        $incremento = $incremento > 0 ? $incremento : 1;
        if (!in_array($aplicaA, self::opcionesAplicaA(), true)) {
            $aplicaA = 'Otros';
        }

        $insert = Db::conexion()->prepare(
            'INSERT INTO secuencias (clave, aplica_a, prefijo, longitud, valor_actual, incremento, activo, uso_total)
             VALUES (:clave, :aplica_a, :prefijo, :longitud, 0, :incremento, 1, 0)'
        );

        try {
            $insert->execute([
                'clave' => $clave,
                'aplica_a' => $aplicaA,
                'prefijo' => $prefijo,
                'longitud' => $longitud,
                'incremento' => $incremento,
            ]);
        } catch (\Throwable $e) {
            // Otro proceso pudo crearla al mismo tiempo.
        }
    }

    public static function reset(string $clave, int $valorActual = 0, int $usoTotal = 0): void
    {
        $clave = trim(strtolower($clave));
        if ($clave === '') {
            return;
        }
        $valorActual = max(0, $valorActual);
        $usoTotal = max(0, $usoTotal);

        $stmt = Db::conexion()->prepare('UPDATE secuencias
                                         SET valor_actual = :valor_actual,
                                             uso_total = :uso_total
                                         WHERE clave = :clave');
        $stmt->execute([
            'clave' => $clave,
            'valor_actual' => $valorActual,
            'uso_total' => $usoTotal,
        ]);
    }

    private static function claveExiste(string $clave, ?int $exceptId): bool
    {
        $sql = 'SELECT id FROM secuencias WHERE clave = :clave LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['clave' => $clave]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return false;
        }

        return true;
    }

    private static function aplicaAExiste(string $aplicaA, ?int $exceptId): bool
    {
        $sql = 'SELECT id FROM secuencias WHERE LOWER(TRIM(aplica_a)) = LOWER(TRIM(:aplica_a)) LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['aplica_a' => $aplicaA]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return false;
        }

        return true;
    }
}
