<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreCertificateRequest;
use App\Http\Requests\App\UpdateBillingSettingsRequest;
use App\Http\Resources\BillingSettingResource;
use App\Services\Billing\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BillingSettingController extends ApiController
{
    public function show(): JsonResponse
    {
        $settings = app(DocumentService::class)->settings();
        $this->authorize('view', $settings);

        return (new BillingSettingResource($settings))->response()->setStatusCode(200);
    }

    public function update(UpdateBillingSettingsRequest $request): BillingSettingResource
    {
        $settings = app(DocumentService::class)->updateSettings($request->validated());

        return new BillingSettingResource($settings);
    }

    public function storeCertificate(StoreCertificateRequest $request): BillingSettingResource
    {
        $settings = app(DocumentService::class)->settings();
        $this->authorize('update', $settings);

        /** @var UploadedFile $file */
        $file = $request->file('certificate');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pem');
        $name = $settings->tenant_id.'-'.$settings->uuid.'.'.$extension;

        File::ensureDirectoryExists((string) config('restivo.certificate_path'));

        $path = Storage::disk('local')->putFileAs('billing/certificates', $file, $name);

        $settings = app(DocumentService::class)->updateSettings([
            'certificate_path' => Storage::disk('local')->path($path),
            'certificate_password' => $request->validated('certificate_password'),
        ]);

        return new BillingSettingResource($settings);
    }
}
