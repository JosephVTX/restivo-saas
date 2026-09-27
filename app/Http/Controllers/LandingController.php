<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            return redirect()->route($user->isSuperAdmin() ? 'admin.dashboard' : 'app.dashboard');
        }

        return Inertia::render('auth/Login');
    }
}
