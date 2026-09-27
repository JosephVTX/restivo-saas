<?php

namespace App\Http\Controllers\App;

use App\Enums\IdentityDocumentType;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Customers/Index', [
            'identityDocumentTypeOptions' => enum_options(IdentityDocumentType::class),
        ]);
    }
}
