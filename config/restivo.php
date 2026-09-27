<?php

use Greenter\Ws\Services\SunatEndpoints;

return [
    /*
    |--------------------------------------------------------------------------
    | IGV (Perú)
    |--------------------------------------------------------------------------
    |
    | Products are stored with gross (IGV-included) prices. This rate is used
    | to extract the taxable base and IGV amount from each order line.
    |
    */
    'igv_rate' => (float) env('RESTIVO_IGV_RATE', 0.18),

    'currency' => env('RESTIVO_CURRENCY', 'PEN'),

    /*
    |--------------------------------------------------------------------------
    | Electronic invoicing (SUNAT / Greenter)
    |--------------------------------------------------------------------------
    |
    | Where uploaded digital certificates live, and the SUNAT service endpoints
    | for each billing mode. Invoicing stays disabled per tenant until its own
    | settings are configured.
    |
    */
    'certificate_path' => storage_path('app/private/billing/certificates'),

    'sunat' => [
        'beta' => SunatEndpoints::FE_BETA,
        'production' => SunatEndpoints::FE_PRODUCCION,
    ],
];
