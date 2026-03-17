<?php

declare(strict_types=1);

spl_autoload_register(function (string $clase): void {
    $prefijo = 'App\\';
    $directorioBase = dirname(__DIR__) . '/';

    if (strncmp($prefijo, $clase, strlen($prefijo)) !== 0) {
        return;
    }

    $relativo = substr($clase, strlen($prefijo));
    $archivo = $directorioBase . str_replace('\\', '/', $relativo) . '.php';

    if (is_file($archivo)) {
        require_once $archivo;
    }
});
