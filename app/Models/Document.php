<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\IdentityDocumentType;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A sales receipt (nota de venta / boleta / factura). Electronic documents keep
 * the SUNAT response, the signed XML and the CDR alongside the printable PDF.
 */
#[Fillable([
    'order_id', 'payment_id', 'customer_id', 'type', 'series', 'number', 'status',
    'customer_doc_type', 'customer_doc_number', 'customer_name', 'customer_address',
    'currency', 'subtotal', 'tax_total', 'total', 'tip', 'issue_date',
    'sunat_code', 'sunat_description', 'hash', 'xml_path', 'cdr_path', 'pdf_path',
    'sent_at', 'notes',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'customer_doc_type' => IdentityDocumentType::class,
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'tip' => 'decimal:2',
            'issue_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fullNumber(): string
    {
        return $this->series.'-'.str_pad((string) $this->number, 8, '0', STR_PAD_LEFT);
    }

    public function isElectronic(): bool
    {
        return $this->type->isElectronic();
    }

    public function isAccepted(): bool
    {
        return $this->status === DocumentStatus::Accepted;
    }

    public function isAnnulable(): bool
    {
        return ! in_array($this->status, [DocumentStatus::Annulled], true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeElectronic(Builder $query): Builder
    {
        return $query->whereIn('type', array_map(
            fn (DocumentType $type): string => $type->value,
            DocumentType::electronic(),
        ));
    }
}
