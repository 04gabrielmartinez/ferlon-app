<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $rutas = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $ruta, array|callable $accion, array $middlewares = []): void
    {
        $this->registrar('GET', $ruta, $accion, $middlewares);
    }

    public function post(string $ruta, array|callable $accion, array $middlewares = []): void
    {
        $this->registrar('POST', $ruta, $accion, $middlewares);
    }

    private function registrar(string $metodo, string $ruta, array|callable $accion, array $middlewares = []): void
    {
        $rutaNormalizada = $this->normalizarRuta($ruta);
        $this->rutas[$metodo][$rutaNormalizada] = [
            'accion' => $accion,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $metodo, string $uri): void
    {
        $ruta = $this->normalizarRuta(parse_url($uri, PHP_URL_PATH) ?: '/');
        $definicion = $this->rutas[$metodo][$ruta] ?? null;

        if ($definicion === null) {
            AuditLog::write('router.not_found', [
                'tipo_accion' => 'ruta_no_encontrada',
                'apartado' => $ruta,
                'descripcion' => 'Ruta no encontrada',
            ]);
            http_response_code(404);
            echo '404 - Página no encontrada';
            return;
        }

        if (!Middleware::ejecutar($definicion['middlewares'])) {
            return;
        }

        if (($metodo === 'GET') && Auth::check()) {
            AuditLog::write('view.open', [
                'tipo_accion' => 'vista_abrir',
                'apartado' => $ruta,
                'descripcion' => 'Apertura de vista',
            ]);
        }

        $accion = $definicion['accion'];

        if (is_array($accion)) {
            [$controlador, $metodoControlador] = $accion;
            (new $controlador())->{$metodoControlador}();
            return;
        }

        $accion();
    }

    private function normalizarRuta(string $ruta): string
    {
        if ($ruta === '') {
            return '/';
        }

        $rutaNormalizada = '/' . trim($ruta, '/');
        return $rutaNormalizada === '//' ? '/' : $rutaNormalizada;
    }
}
