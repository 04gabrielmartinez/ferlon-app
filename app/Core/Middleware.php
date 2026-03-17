<?php

declare(strict_types=1);

namespace App\Core;

final class Middleware
{
    public static function ejecutar(array $middlewares): bool
    {
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        foreach ($middlewares as $middleware) {
            if (str_starts_with($middleware, 'role:')) {
                $roles = array_filter(array_map('trim', explode(',', substr($middleware, 5))));
                if (!$roles || !Auth::hasRole(...$roles)) {
                    AuditLog::write('authz.role.denied', [
                        'tipo_accion' => 'acceso_denegado_rol',
                        'apartado' => $requestPath,
                        'descripcion' => 'Acceso denegado por rol',
                        'middleware' => $middleware,
                    ]);
                    http_response_code(403);
                    echo '403 - Acceso denegado';
                    return false;
                }
                continue;
            }

            if (str_starts_with($middleware, 'perm_any:')) {
                $permissions = array_filter(array_map('trim', explode(',', substr($middleware, 9))));
                if ($permissions === [] || !Auth::hasAnyPermission(...$permissions)) {
                    AuditLog::write('authz.permission.denied', [
                        'tipo_accion' => 'acceso_denegado_permiso',
                        'apartado' => $requestPath,
                        'descripcion' => 'Acceso denegado por permisos',
                        'middleware' => $middleware,
                    ]);
                    http_response_code(403);
                    echo '403 - Acceso denegado';
                    return false;
                }
                continue;
            }

            if (str_starts_with($middleware, 'perm:')) {
                $permission = trim(substr($middleware, 5));
                if ($permission === '' || !Auth::hasPermission($permission)) {
                    AuditLog::write('authz.permission.denied', [
                        'tipo_accion' => 'acceso_denegado_permiso',
                        'apartado' => $requestPath,
                        'descripcion' => 'Acceso denegado por permiso',
                        'middleware' => $middleware,
                    ]);
                    http_response_code(403);
                    echo '403 - Acceso denegado';
                    return false;
                }
                continue;
            }

            if ($middleware === 'auth' && !Auth::check()) {
                AuditLog::write('authz.auth.required', [
                    'tipo_accion' => 'acceso_denegado_no_autenticado',
                    'apartado' => $requestPath,
                    'descripcion' => 'Acceso denegado por sesion no iniciada',
                ]);
                header('Location: /login');
                return false;
            }

            if ($middleware === 'guest' && Auth::check()) {
                header('Location: /dashboard');
                return false;
            }
        }

        return true;
    }
}
