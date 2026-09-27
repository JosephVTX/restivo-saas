<?php

use App\Models\Tenant;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant model
    |--------------------------------------------------------------------------
    */

    'model' => Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Resolution
    |--------------------------------------------------------------------------
    |
    | Restivo runs on a SINGLE domain (no wildcard subdomains). The
    | active tenant is resolved from the authenticated user's memberships and
    | persisted in the session. Super admins may impersonate any tenant.
    |
    */

    'session_key' => 'tenant_id',

    'header' => 'X-Tenant',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Tenant lookups are cached to avoid a database round-trip on every
    | request. Set ttl to 0 to disable caching (useful in tests).
    |
    */

    'cache' => [
        'store' => env('TENANCY_CACHE_STORE', 'redis'),
        'ttl' => (int) env('TENANCY_CACHE_TTL', 3600),
        'prefix' => env('CACHE_PREFIX', 'restivo'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | Requests per minute. Authenticated tenant traffic gets a larger budget.
    |
    */

    'rate_limit' => [
        'tenant' => (int) env('TENANCY_RATE_LIMIT_TENANT', 300),
        'guest' => (int) env('TENANCY_RATE_LIMIT_GUEST', 60),
    ],

];
