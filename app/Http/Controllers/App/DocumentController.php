<?php

namespace App\Http\Controllers\App;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\IdentityDocumentType;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Documents/Index', [
            'documentTypeOptions' => enum_options(DocumentType::class),
            'documentStatusOptions' => enum_options(DocumentStatus::class),
            'identityDocumentTypeOptions' => enum_options(IdentityDocumentType::class),
        ]);
    }
}
