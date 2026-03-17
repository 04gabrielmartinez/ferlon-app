<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Settings;
use RuntimeException;

final class Acceso
{
    public static function sincronizarPermisosBase(): void
    {
        $base = self::permisosBase();
        if ($base === []) {
            return;
        }

        $pdo = Db::conexion();
        $existentes = $pdo->query('SELECT id, clave, descripcion, modulo FROM permisos')->fetchAll() ?: [];
        $porClave = [];
        $aExcluir = [
            'mi_cuenta.password',
            'mi_cuenta.2fa',
        ];
        foreach ($existentes as $permiso) {
            $clave = strtolower(trim((string) ($permiso['clave'] ?? '')));
            if ($clave !== '') {
                $porClave[$clave] = $permiso;
            }
        }

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO permisos (clave, descripcion, modulo) VALUES (:clave, :descripcion, :modulo)');
            $upd = $pdo->prepare('UPDATE permisos SET descripcion = :descripcion, modulo = :modulo WHERE id = :id');
            $delNivelPermiso = $pdo->prepare('DELETE FROM nivel_permiso WHERE permiso_id = :permiso_id');
            $delPermiso = $pdo->prepare('DELETE FROM permisos WHERE id = :id');

            foreach ($aExcluir as $claveExcluir) {
                if (!isset($porClave[$claveExcluir])) {
                    continue;
                }

                $permisoId = (int) ($porClave[$claveExcluir]['id'] ?? 0);
                if ($permisoId > 0) {
                    $delNivelPermiso->execute(['permiso_id' => $permisoId]);
                    $delPermiso->execute(['id' => $permisoId]);
                }
            }

            foreach ($base as $item) {
                $clave = trim((string) ($item['clave'] ?? ''));
                if ($clave === '') {
                    continue;
                }

                $descripcion = trim((string) ($item['descripcion'] ?? ''));
                $modulo = trim((string) ($item['modulo'] ?? 'General'));
                $key = strtolower($clave);

                if (!isset($porClave[$key])) {
                    $ins->execute([
                        'clave' => $clave,
                        'descripcion' => $descripcion,
                        'modulo' => $modulo,
                    ]);
                    continue;
                }

                $row = $porClave[$key];
                $descActual = trim((string) ($row['descripcion'] ?? ''));
                $modActual = trim((string) ($row['modulo'] ?? ''));
                if ($descActual !== $descripcion || $modActual !== $modulo) {
                    $upd->execute([
                        'id' => (int) ($row['id'] ?? 0),
                        'descripcion' => $descripcion,
                        'modulo' => $modulo,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function permisosBase(): array
    {
        return [
            ['clave' => 'dashboard.ver', 'descripcion' => 'Ver dashboard', 'modulo' => 'Dashboard'],
            ['clave' => 'empleados.ver', 'descripcion' => 'Ver empleados', 'modulo' => 'Terceros'],
            ['clave' => 'empleados.crear', 'descripcion' => 'Crear empleados', 'modulo' => 'Terceros'],
            ['clave' => 'empleados.editar', 'descripcion' => 'Editar empleados', 'modulo' => 'Terceros'],
            ['clave' => 'proveedores.ver', 'descripcion' => 'Ver proveedores', 'modulo' => 'Terceros'],
            ['clave' => 'proveedores.crear', 'descripcion' => 'Crear proveedores', 'modulo' => 'Terceros'],
            ['clave' => 'proveedores.editar', 'descripcion' => 'Editar proveedores', 'modulo' => 'Terceros'],
            ['clave' => 'clientes.ver', 'descripcion' => 'Ver clientes', 'modulo' => 'Terceros'],
            ['clave' => 'clientes.crear', 'descripcion' => 'Crear clientes', 'modulo' => 'Terceros'],
            ['clave' => 'clientes.editar', 'descripcion' => 'Editar clientes', 'modulo' => 'Terceros'],
            ['clave' => 'localidades.ver', 'descripcion' => 'Ver localidades', 'modulo' => 'Terceros'],
            ['clave' => 'localidades.crear', 'descripcion' => 'Crear localidades', 'modulo' => 'Terceros'],
            ['clave' => 'localidades.editar', 'descripcion' => 'Editar localidades', 'modulo' => 'Terceros'],
            ['clave' => 'bancos.ver', 'descripcion' => 'Ver bancos', 'modulo' => 'Terceros'],
            ['clave' => 'bancos.crear', 'descripcion' => 'Crear bancos', 'modulo' => 'Terceros'],
            ['clave' => 'bancos.editar', 'descripcion' => 'Editar bancos', 'modulo' => 'Terceros'],
            ['clave' => 'puestos.ver', 'descripcion' => 'Ver puestos', 'modulo' => 'Organizacion'],
            ['clave' => 'puestos.crear', 'descripcion' => 'Crear puestos', 'modulo' => 'Organizacion'],
            ['clave' => 'puestos.editar', 'descripcion' => 'Editar puestos', 'modulo' => 'Organizacion'],
            ['clave' => 'catalogo.ver', 'descripcion' => 'Ver catalogo', 'modulo' => 'Organizacion'],
            ['clave' => 'catalogo.crear', 'descripcion' => 'Crear catalogo', 'modulo' => 'Organizacion'],
            ['clave' => 'catalogo.editar', 'descripcion' => 'Editar catalogo', 'modulo' => 'Organizacion'],
            ['clave' => 'articulos.ver', 'descripcion' => 'Ver articulos', 'modulo' => 'Organizacion'],
            ['clave' => 'articulos.crear', 'descripcion' => 'Crear articulos', 'modulo' => 'Organizacion'],
            ['clave' => 'articulos.editar', 'descripcion' => 'Editar articulos', 'modulo' => 'Organizacion'],
            ['clave' => 'marcas.ver', 'descripcion' => 'Ver marcas', 'modulo' => 'Organizacion'],
            ['clave' => 'marcas.crear', 'descripcion' => 'Crear marcas', 'modulo' => 'Organizacion'],
            ['clave' => 'marcas.editar', 'descripcion' => 'Editar marcas', 'modulo' => 'Organizacion'],
            ['clave' => 'familias.ver', 'descripcion' => 'Ver familias', 'modulo' => 'Organizacion'],
            ['clave' => 'familias.crear', 'descripcion' => 'Crear familias', 'modulo' => 'Organizacion'],
            ['clave' => 'familias.editar', 'descripcion' => 'Editar familias', 'modulo' => 'Organizacion'],
            ['clave' => 'recetas_base.ver', 'descripcion' => 'Ver recetas base', 'modulo' => 'Organizacion'],
            ['clave' => 'recetas_base.crear', 'descripcion' => 'Crear recetas base', 'modulo' => 'Organizacion'],
            ['clave' => 'recetas_base.editar', 'descripcion' => 'Editar recetas base', 'modulo' => 'Organizacion'],
            ['clave' => 'recetas_producto_final.ver', 'descripcion' => 'Ver recetas producto final', 'modulo' => 'Organizacion'],
            ['clave' => 'recetas_producto_final.crear', 'descripcion' => 'Crear recetas producto final', 'modulo' => 'Organizacion'],
            ['clave' => 'recetas_producto_final.editar', 'descripcion' => 'Editar recetas producto final', 'modulo' => 'Organizacion'],
            ['clave' => 'produccion.ver', 'descripcion' => 'Ver produccion', 'modulo' => 'Procesos'],
            ['clave' => 'produccion.crear', 'descripcion' => 'Crear produccion', 'modulo' => 'Procesos'],
            ['clave' => 'produccion.editar', 'descripcion' => 'Editar produccion', 'modulo' => 'Procesos'],
            ['clave' => 'fabricacion.ver', 'descripcion' => 'Ver fabricacion', 'modulo' => 'Procesos'],
            ['clave' => 'fabricacion.crear', 'descripcion' => 'Crear fabricacion', 'modulo' => 'Procesos'],
            ['clave' => 'fabricacion.editar', 'descripcion' => 'Editar fabricacion', 'modulo' => 'Procesos'],
            ['clave' => 'pedidos.ver', 'descripcion' => 'Ver pedidos', 'modulo' => 'Procesos'],
            ['clave' => 'pedidos.crear', 'descripcion' => 'Crear pedidos', 'modulo' => 'Procesos'],
            ['clave' => 'pedidos.editar', 'descripcion' => 'Editar pedidos', 'modulo' => 'Procesos'],
            ['clave' => 'cotizaciones.ver', 'descripcion' => 'Ver cotizaciones', 'modulo' => 'Procesos'],
            ['clave' => 'cotizaciones.crear', 'descripcion' => 'Crear cotizaciones', 'modulo' => 'Procesos'],
            ['clave' => 'cotizaciones.editar', 'descripcion' => 'Editar cotizaciones', 'modulo' => 'Procesos'],
            ['clave' => 'ncf.ver', 'descripcion' => 'Ver NCF', 'modulo' => 'Fiscal'],
            ['clave' => 'ncf.crear', 'descripcion' => 'Crear NCF', 'modulo' => 'Fiscal'],
            ['clave' => 'ncf.editar', 'descripcion' => 'Editar NCF', 'modulo' => 'Fiscal'],
            ['clave' => 'configuracion.ver', 'descripcion' => 'Ver configuracion', 'modulo' => 'Sistema'],
            ['clave' => 'configuracion.editar', 'descripcion' => 'Editar configuracion', 'modulo' => 'Sistema'],
            ['clave' => 'niveles.ver', 'descripcion' => 'Ver niveles de acceso', 'modulo' => 'Seguridad'],
            ['clave' => 'niveles.crear', 'descripcion' => 'Crear niveles de acceso', 'modulo' => 'Seguridad'],
            ['clave' => 'niveles.permisos', 'descripcion' => 'Asignar permisos a niveles', 'modulo' => 'Seguridad'],
            ['clave' => 'cuentas_acceso.ver', 'descripcion' => 'Ver cuentas de acceso', 'modulo' => 'Seguridad'],
            ['clave' => 'cuentas_acceso.crear', 'descripcion' => 'Crear cuentas de acceso', 'modulo' => 'Seguridad'],
            ['clave' => 'cuentas_acceso.editar', 'descripcion' => 'Editar cuentas de acceso', 'modulo' => 'Seguridad'],
        ];
    }

    public static function niveles(): array
    {
        $sql = 'SELECT id, nombre, descripcion, activo, created_at FROM niveles_acceso ORDER BY nombre ASC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function permisos(): array
    {
        $sql = 'SELECT id, clave, descripcion, modulo FROM permisos ORDER BY modulo ASC, clave ASC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function permisosPorNivel(int $nivelId): array
    {
        $sql = 'SELECT permiso_id FROM nivel_permiso WHERE nivel_id = :nivel_id';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['nivel_id' => $nivelId]);
        $rows = $stmt->fetchAll() ?: [];

        return array_map(static fn (array $row): int => (int) $row['permiso_id'], $rows);
    }

    public static function crearNivel(string $nombre, string $descripcion, bool $activo): int
    {
        $sql = 'INSERT INTO niveles_acceso (nombre, descripcion, activo) VALUES (:nombre, :descripcion, :activo)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'nombre' => trim($nombre),
            'descripcion' => trim($descripcion) !== '' ? trim($descripcion) : null,
            'activo' => $activo ? 1 : 0,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function obtenerNivelPorId(int $nivelId): ?array
    {
        if ($nivelId <= 0) {
            return null;
        }

        $sql = 'SELECT id, nombre, descripcion, activo FROM niveles_acceso WHERE id = :id LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $nivelId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function actualizarNivel(int $nivelId, string $nombre, string $descripcion, bool $activo): void
    {
        if ($nivelId <= 0) {
            throw new RuntimeException('Nivel de acceso invalido.');
        }

        $sql = 'UPDATE niveles_acceso
                SET nombre = :nombre,
                    descripcion = :descripcion,
                    activo = :activo
                WHERE id = :id';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'id' => $nivelId,
            'nombre' => trim($nombre),
            'descripcion' => trim($descripcion) !== '' ? trim($descripcion) : null,
            'activo' => $activo ? 1 : 0,
        ]);
    }

    public static function empleadosPorNivel(int $nivelId): array
    {
        if ($nivelId <= 0) {
            return [];
        }

        $sql = "SELECT u.id AS user_id,
                       u.username,
                       u.email,
                       u.is_active,
                       e.id AS empleado_id,
                       e.nombre,
                       e.apellido
                FROM users u
                INNER JOIN empleados e ON e.id = u.empleado_id
                WHERE u.nivel_acceso_id = :nivel_id
                ORDER BY e.nombre ASC, e.apellido ASC";
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['nivel_id' => $nivelId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function cantidadUsuariosPorNivel(int $nivelId): int
    {
        if ($nivelId <= 0) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM users WHERE nivel_acceso_id = :nivel_id';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['nivel_id' => $nivelId]);
        return (int) $stmt->fetchColumn();
    }

    public static function eliminarNivel(int $nivelId): void
    {
        if ($nivelId <= 0) {
            throw new RuntimeException('Nivel de acceso invalido.');
        }

        if (self::cantidadUsuariosPorNivel($nivelId) > 0) {
            throw new RuntimeException('No se puede eliminar este nivel porque tiene empleados asignados.');
        }

        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $delNivelPermiso = $pdo->prepare('DELETE FROM nivel_permiso WHERE nivel_id = :nivel_id');
            $delNivelPermiso->execute(['nivel_id' => $nivelId]);

            $delNivel = $pdo->prepare('DELETE FROM niveles_acceso WHERE id = :id');
            $delNivel->execute(['id' => $nivelId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function asignarPermisosNivel(int $nivelId, array $permisosIds): void
    {
        $pdo = Db::conexion();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM nivel_permiso WHERE nivel_id = :nivel_id');
            $del->execute(['nivel_id' => $nivelId]);

            $ins = $pdo->prepare('INSERT INTO nivel_permiso (nivel_id, permiso_id) VALUES (:nivel_id, :permiso_id)');
            foreach ($permisosIds as $permisoId) {
                $permisoId = (int) $permisoId;
                if ($permisoId > 0) {
                    $ins->execute([
                        'nivel_id' => $nivelId,
                        'permiso_id' => $permisoId,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function obtenerCuentaPorUsuarioId(int $userId): ?array
    {
        $sql = 'SELECT u.id, u.username, u.email, u.is_active, u.empleado_id, u.nivel_acceso_id,
                       e.nombre AS emp_nombre, e.apellido AS emp_apellido,
                       n.nombre AS nivel_nombre
                FROM users u
                LEFT JOIN empleados e ON e.id = u.empleado_id
                LEFT JOIN niveles_acceso n ON n.id = u.nivel_acceso_id
                WHERE u.id = :id
                LIMIT 1';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function crearOActualizarCuentaEmpleado(
        ?int $userId,
        int $empleadoId,
        int $nivelId,
        string $username,
        string $password
    ): int {
        $username = trim($username);
        if ($username === '') {
            throw new RuntimeException('Debes indicar un usuario.');
        }

        $userId = $userId !== null && $userId > 0 ? $userId : null;

        $empleado = Empleado::buscarPorId($empleadoId);
        if (!$empleado) {
            throw new RuntimeException('Empleado no encontrado.');
        }
        if (strtolower((string) ($empleado['estado'] ?? '')) !== 'activo') {
            throw new RuntimeException('Solo se pueden asignar accesos a empleados activos.');
        }

        $dominio = trim((string) Settings::get('dominio', ''));
        $email = $dominio !== '' ? ($username . '@' . $dominio) : $username;
        $nombreCompleto = trim((string) (($empleado['nombre'] ?? '') . ' ' . ($empleado['apellido'] ?? '')));
        $hash = password_hash(trim($password), PASSWORD_DEFAULT);

        if ($userId !== null) {
            $sql = 'UPDATE users
                    SET nombre = :nombre,
                        email = :email,
                        username = :username,
                        empleado_id = :empleado_id,
                        nivel_acceso_id = :nivel_acceso_id,
                        password_hash = :password_hash,
                        is_active = 1
                    WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute([
                'id' => $userId,
                'nombre' => $nombreCompleto,
                'email' => $email,
                'username' => $username,
                'empleado_id' => $empleadoId,
                'nivel_acceso_id' => $nivelId,
                'password_hash' => $hash,
            ]);
            return $userId;
        }

        $sql = 'INSERT INTO users (nombre, email, username, empleado_id, nivel_acceso_id, role, password_hash, is_active)
                VALUES (:nombre, :email, :username, :empleado_id, :nivel_acceso_id, :role, :password_hash, 1)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'nombre' => $nombreCompleto,
            'email' => $email,
            'username' => $username,
            'empleado_id' => $empleadoId,
            'nivel_acceso_id' => $nivelId,
            'role' => 'admin',
            'password_hash' => $hash,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function cuentasAcceso(): array
    {
        $sql = 'SELECT u.id, u.username, u.email, u.is_active, u.empleado_id, u.nivel_acceso_id,
                       e.nombre AS emp_nombre, e.apellido AS emp_apellido,
                       n.nombre AS nivel_nombre
                FROM users u
                LEFT JOIN empleados e ON e.id = u.empleado_id
                LEFT JOIN niveles_acceso n ON n.id = u.nivel_acceso_id
                WHERE u.empleado_id IS NOT NULL
                ORDER BY u.id DESC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function cuentasParaPicker(): array
    {
        $sql = 'SELECT id, nombre, username
                FROM users
                WHERE username IS NOT NULL AND username <> ""
                ORDER BY id DESC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }
}
