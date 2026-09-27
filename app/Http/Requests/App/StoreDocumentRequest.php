<?php

namespace App\Http\Requests\App;

use App\Enums\DocumentType;
use App\Enums\IdentityDocumentType;
use App\Models\Customer;
use App\Models\Document;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'series', 'customer_id', 'customer_doc_type', 'customer_doc_number',
            'customer_name', 'customer_address', 'notes',
        ];

        foreach ($nullable as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        $customer = $this->input('customer_id');

        if (is_string($customer) && ! ctype_digit($customer)) {
            $this->merge([
                'customer_id' => Customer::query()->where('uuid', $customer)->value('id'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                DocumentType::NotaVenta->value,
                DocumentType::Boleta->value,
                DocumentType::Factura->value,
            ])],
            'series' => ['sometimes', 'nullable', 'string', 'max:10'],
            'customer_id' => ['sometimes', 'nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', current_tenant_id())],
            'customer_doc_type' => ['sometimes', 'nullable', Rule::in(array_map(
                fn (IdentityDocumentType $type): string => $type->value,
                IdentityDocumentType::cases(),
            ))],
            'customer_doc_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $docType = $this->input('customer_doc_type');
            $docNumber = $this->input('customer_doc_number');

            if ($type === DocumentType::Factura->value) {
                if ($docType !== IdentityDocumentType::Ruc->value) {
                    $validator->errors()->add('customer_doc_type', 'La factura requiere un RUC.');
                }

                if (! is_string($docNumber) || ! preg_match('/^\d{11}$/', $docNumber)) {
                    $validator->errors()->add('customer_doc_number', 'El RUC debe tener 11 dígitos.');
                }
            }

            if ($type === DocumentType::Boleta->value && $docNumber !== null) {
                if (! is_string($docNumber) || ! preg_match('/^(\d{8}|\d{11})$/', $docNumber)) {
                    $validator->errors()->add('customer_doc_number', 'El documento debe tener 8 u 11 dígitos.');
                }
            }
        });
    }
}
