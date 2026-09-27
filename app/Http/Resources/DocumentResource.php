<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_is_electronic' => $this->type->isElectronic(),
            'series' => $this->series,
            'number' => $this->number,
            'full_number' => $this->fullNumber(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'customer_doc_type' => $this->customer_doc_type?->value,
            'customer_doc_type_label' => $this->customer_doc_type?->label(),
            'customer_doc_number' => $this->customer_doc_number,
            'customer_name' => $this->customer_name,
            'customer_address' => $this->customer_address,
            'customer_uuid' => $this->whenLoaded('customer', fn (): ?string => $this->customer?->uuid),
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'total' => $this->total,
            'tip' => $this->tip,
            'issue_date' => $this->issue_date?->toDateString(),
            'sunat_code' => $this->sunat_code,
            'sunat_description' => $this->sunat_description,
            'has_xml' => $this->xml_path !== null,
            'has_cdr' => $this->cdr_path !== null,
            'has_pdf' => $this->pdf_path !== null,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order === null ? null : [
                'uuid' => $this->order->uuid,
                'number' => $this->order->number,
            ], null),
        ];
    }
}
