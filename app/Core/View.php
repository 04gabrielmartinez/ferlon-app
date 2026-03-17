<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $vista, array $datos = []): void
    {
        $rutaVista = dirname(__DIR__) . '/Views/' . $vista . '.php';

        if (!is_file($rutaVista)) {
            http_response_code(500);
            echo 'Vista no encontrada';
            return;
        }

        extract($datos, EXTR_SKIP);

        require dirname(__DIR__) . '/Views/sistema/layout/header.php';
        require $rutaVista;
        require dirname(__DIR__) . '/Views/sistema/layout/footer.php';
    }
}
