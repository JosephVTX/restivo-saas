<?php

use App\Support\Tenancy\TenantContext;

if (! function_exists('tenant_context')) {
    function tenant_context(): TenantContext
    {
        return app(TenantContext::class);
    }
}

if (! function_exists('current_tenant_id')) {
    function current_tenant_id(): int|string|null
    {
        return app(TenantContext::class)->id();
    }
}

if (! function_exists('enum_options')) {
    /**
     * Map a backed enum to the `[{value, label}]` shape Inertia pages expect.
     * Uses a `label()` method when the enum provides one, else the case name.
     *
     * @param  class-string<UnitEnum>  $enum
     * @return array<int, array{value: string, label: string}>
     */
    function enum_options(string $enum): array
    {
        return array_map(
            fn (UnitEnum $case): array => [
                'value' => $case instanceof BackedEnum ? $case->value : $case->name,
                'label' => method_exists($case, 'label') ? $case->label() : $case->name,
            ],
            $enum::cases(),
        );
    }
}
