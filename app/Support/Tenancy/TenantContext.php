<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use RuntimeException;

/**
 * Holds the resolved tenant for the current request.
 *
 * Registered as a scoped binding so every request/job gets a fresh instance
 * and no tenant state leaks between requests under Octane.
 */
final class TenantContext
{
    private ?Tenant $tenant = null;

    private bool $resolved = false;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->resolved = true;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->resolved = false;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): int|string|null
    {
        return $this->tenant?->getKey();
    }

    public function require(): Tenant
    {
        return $this->tenant ?? throw new RuntimeException('No tenant has been resolved for the current request.');
    }

    public function cacheKey(string $key): string
    {
        $prefix = (string) config('tenancy.cache.prefix', 'tenant');

        return sprintf('%s:%s:%s', $prefix, $this->id() ?? 'central', $key);
    }
}
