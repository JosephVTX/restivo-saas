<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
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
            'certificate' => ['required', 'file', 'mimes:pem,p12,pfx,crt,cer,txt', 'max:2048'],
            'certificate_password' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
