<?php

namespace App\Http\Requests\App;

use App\Enums\IdentityDocumentType;
use App\Models\Customer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->customer()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['doc_number', 'email', 'phone', 'address', 'notes'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'doc_type' => ['sometimes', 'required', Rule::enum(IdentityDocumentType::class)],
            'doc_number' => [
                'sometimes', 'nullable', 'string', 'max:20',
                Rule::unique('customers', 'doc_number')
                    ->where('tenant_id', current_tenant_id())
                    ->ignore($this->customer()->getKey()),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $number = $this->input('doc_number');

            if (! is_string($number) || $number === '') {
                return;
            }

            $docType = $this->input('doc_type', $this->customer()->doc_type->value);

            if ($docType === IdentityDocumentType::Ruc->value && ! preg_match('/^\d{11}$/', $number)) {
                $validator->errors()->add('doc_number', 'El RUC debe tener 11 dígitos.');
            }

            if ($docType === IdentityDocumentType::Dni->value && ! preg_match('/^\d{8}$/', $number)) {
                $validator->errors()->add('doc_number', 'El DNI debe tener 8 dígitos.');
            }
        });
    }

    private function customer(): Customer
    {
        return Customer::query()->where('uuid', $this->route('customer'))->firstOrFail();
    }
}
