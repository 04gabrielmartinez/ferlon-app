<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $vista, array $datos = []): void
    {
        View::render($vista, $datos);
    }

    protected function redirect(string $ruta): void
    {
        header('Location: ' . $ruta);
        exit;
    }
}
