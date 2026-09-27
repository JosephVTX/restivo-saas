<?php

namespace App\Http\Requests\App;

use App\Enums\BillingMode;
use App\Services\Billing\DocumentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', app(DocumentService::class)->settings()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'ruc', 'business_name', 'trade_name', 'address', 'ubigeo', 'email',
            'phone', 'sol_user', 'sol_password', 'mode', 'boleta_series',
            'factura_series', 'legend', 'igv_rate', 'certificate_password',
        ];

        foreach ($nullable as $field) {
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
            'enabled' => ['sometimes', 'boolean'],
            'ruc' => ['sometimes', 'nullable', 'digits:11'],
            'business_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trade_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ubigeo' => ['sometimes', 'nullable', 'digits:6'],
            'email' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'sol_user' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sol_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mode' => ['sometimes', 'nullable', Rule::in([
                BillingMode::Beta->value,
                BillingMode::Production->value,
            ])],
            'boleta_series' => ['sometimes', 'nullable', 'string', 'max:10'],
            'factura_series' => ['sometimes', 'nullable', 'string', 'max:10'],
            'legend' => ['sometimes', 'nullable', 'string', 'max:255'],
            'igv_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1'],
            'certificate_password' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
