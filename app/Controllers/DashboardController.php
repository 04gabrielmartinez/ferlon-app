<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $usuario = Auth::user();

        $this->render('sistema/dashboard/index', [
            'titulo' => 'Dashboard',
            'usuario' => $usuario,
            'kpis' => [
                ['titulo' => 'Ventas (demo)', 'valor' => '1,240'],
                ['titulo' => 'Usuarios activos', 'valor' => '328'],
                ['titulo' => 'Conversión', 'valor' => '4.8%'],
            ],
        ]);
    }
}
