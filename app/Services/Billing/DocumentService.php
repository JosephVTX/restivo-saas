<?php

namespace App\Services\Billing;

use App\Enums\BillingMode;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TenantBillingSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Single entry point for issuing and managing receipts (nota de venta, boleta,
 * factura): numbering, totals snapshot, optional SUNAT submission and PDF.
 */
class DocumentService
{
    public function __construct(
        private readonly SunatInvoiceBuilder $builder,
        private readonly GreenterGateway $gateway,
        private readonly PdfRenderer $pdf,
    ) {}

    public function settings(): TenantBillingSetting
    {
        return TenantBillingSetting::query()->firstOrCreate([], [
            'enabled' => false,
            'mode' => BillingMode::Beta,
            'boleta_series' => 'B001',
            'factura_series' => 'F001',
            'igv_rate' => config('restivo.igv_rate'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data): TenantBillingSetting
    {
        $settings = $this->settings();
        $settings->fill($data)->save();

        return $settings->refresh();
    }

    /**
     * @param  array{type: string, series?: string, customer_id?: ?int, customer_doc_type?: ?string, customer_doc_number?: ?string, customer_name?: ?string, customer_address?: ?string, notes?: ?string}  $data
     */
    public function emit(Order $order, array $data, ?Payment $payment = null): Document
    {
        return DB::transaction(function () use ($order, $data, $payment): Document {
            $settings = $this->settings();
            $type = DocumentType::from($data['type']);

            if ($type === DocumentType::NotaCredito) {
                throw new RuntimeException('La nota de crédito aún no está disponible.');
            }

            $series = $data['series'] ?? $this->defaultSeries($settings, $type);

            $document = Document::query()->create([
                'order_id' => $order->id,
                'payment_id' => $payment?->id,
                'customer_id' => $data['customer_id'] ?? null,
                'type' => $type,
                'series' => $series,
                'number' => $this->nextNumber($type, $series),
                'status' => DocumentStatus::Draft,
                'customer_doc_type' => $data['customer_doc_type'] ?? null,
                'customer_doc_number' => $data['customer_doc_number'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'currency' => config('restivo.currency'),
                'subtotal' => $order->subtotal,
                'tax_total' => $order->tax_total,
                'total' => $order->total,
                'tip' => $order->tip_total,
                'issue_date' => now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($type->isElectronic()) {
                $this->sendToSunat($document, $settings);
            } else {
                $document->status = DocumentStatus::Issued;
            }

            $document->pdf_path = $this->pdf->render($document, $settings);
            $document->save();

            return $document->refresh();
        });
    }

    public function sendToSunat(Document $document, ?TenantBillingSetting $settings = null): Document
    {
        $settings ??= $this->settings();

        if (! $settings->isElectronicConfigured()) {
            throw new RuntimeException('La facturación electrónica no está configurada para este restaurante.');
        }

        $document->loadMissing('order.items.modifiers');

        $invoice = $this->builder->build(
            $document->order,
            $settings,
            $document->type,
            $document->series,
            $document->number,
            [
                'doc_type' => $document->customer_doc_type?->value,
                'doc_number' => $document->customer_doc_number,
                'name' => $document->customer_name,
                'address' => $document->customer_address,
            ],
        );

        $pem = $this->gateway->buildPem(
            (string) $settings->certificate_path,
            (string) $settings->certificate_password,
        );

        $result = $this->gateway->send(
            $invoice,
            $pem,
            (string) $settings->sol_user,
            (string) $settings->sol_password,
            $settings->mode,
        );

        $document->status = $result['success'] ? DocumentStatus::Accepted : DocumentStatus::Rejected;
        $document->sunat_code = $result['code'] !== null ? (string) $result['code'] : null;
        $document->sunat_description = $result['description'];
        $document->sent_at = now();

        if ($result['xml'] !== null) {
            $path = $this->pathFor($document, 'xml');
            Storage::disk('local')->put($path, $result['xml']);
            $document->xml_path = $path;
        }

        if ($result['cdr'] !== null) {
            $path = $this->pathFor($document, 'zip');
            Storage::disk('local')->put($path, $result['cdr']);
            $document->cdr_path = $path;
        }

        $document->save();

        return $document->refresh();
    }

    public function annul(Document $document, ?string $reason = null): Document
    {
        if (! $document->isAnnulable()) {
            throw new RuntimeException('Este comprobante ya está anulado.');
        }

        if ($document->status === DocumentStatus::Accepted) {
            throw new RuntimeException('Un comprobante aceptado por SUNAT requiere una nota de crédito.');
        }

        $document->status = DocumentStatus::Annulled;
        $document->notes = $reason ?? $document->notes;
        $document->save();

        return $document->refresh();
    }

    public function regeneratePdf(Document $document): Document
    {
        $document->pdf_path = $this->pdf->render($document, $this->settings());
        $document->save();

        return $document->refresh();
    }

    public function defaultSeries(TenantBillingSetting $settings, DocumentType $type): string
    {
        return match ($type) {
            DocumentType::Factura => $settings->factura_series ?: 'F001',
            DocumentType::Boleta => $settings->boleta_series ?: 'B001',
            default => 'NV01',
        };
    }

    public function nextNumber(DocumentType $type, string $series): int
    {
        return ((int) Document::query()
            ->withTrashed()
            ->where('type', $type->value)
            ->where('series', $series)
            ->max('number')) + 1;
    }

    private function pathFor(Document $document, string $extension): string
    {
        return 'billing/'.$document->tenant_id.'/'.$document->uuid.'.'.$extension;
    }
}
