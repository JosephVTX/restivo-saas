<?php

namespace App\Http\Requests\App;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in([
                PaymentMethod::Cash->value,
                PaymentMethod::Yape->value,
                PaymentMethod::Plin->value,
                PaymentMethod::Card->value,
                PaymentMethod::Transfer->value,
                PaymentMethod::Other->value,
            ])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tip' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'received_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
