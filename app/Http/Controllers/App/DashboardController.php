<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('app/Dashboard', [
            'rangeOptions' => [
                ['value' => 'today', 'label' => 'Hoy'],
                ['value' => 'week', 'label' => 'Esta semana'],
                ['value' => 'month', 'label' => 'Este mes'],
            ],
        ]);
    }
}
