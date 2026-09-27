<?php

namespace App\Services\Billing;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\TenantBillingSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Renders the printable receipt. Notes of sale use an 80mm ticket layout; fiscal
 * documents render an A4 sheet suitable for filing.
 */
class PdfRenderer
{
    public function render(Document $document, TenantBillingSetting $settings): string
    {
        $document->loadMissing('order.items.modifiers');

        $isTicket = $document->type === DocumentType::NotaVenta;
        $view = $isTicket ? 'billing.ticket' : 'billing.a4';

        $pdf = Pdf::loadView($view, [
            'document' => $document,
            'order' => $document->order,
            'settings' => $settings,
        ]);

        $pdf->setPaper($isTicket ? [0, 0, 226.77, 900] : 'a4');

        $path = 'billing/'.$document->tenant_id.'/'.$document->uuid.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
