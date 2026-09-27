<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Contracts\Cache\Repository;

/**
 * Resolves tenants by id, uuid or slug with a cached, detached model.
 *
 * Caching the raw attributes (instead of the model) keeps the stored payload
 * small and avoids hydrating Eloquent state on cache hits.
 */
final class TenantResolver
{
    private const CACHE_VERSION = 1;

    public function __construct(private readonly Repository $cache) {}

    public function find(string $key): ?Tenant
    {
        if (ctype_digit($key)) {
            return $this->findById((int) $key);
        }

        if (preg_match('/^[0-9a-fA-F-]{36}$/', $key) === 1) {
            return $this->findByUuid($key);
        }

        return $this->findBySlug($key);
    }

    public function findById(int|string $id): ?Tenant
    {
        return $this->remember($this->cacheKey('id', $id), fn () => Tenant::query()->find($id));
    }

    public function findByUuid(string $uuid): ?Tenant
    {
        return $this->remember($this->cacheKey('uuid', $uuid), fn () => Tenant::query()->where('uuid', $uuid)->first());
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return $this->remember($this->cacheKey('slug', $slug), fn () => Tenant::query()->where('slug', $slug)->first());
    }

    public function forget(Tenant $tenant): void
    {
        $this->cache->forget($this->cacheKey('id', $tenant->getKey()));
        $this->cache->forget($this->cacheKey('uuid', $tenant->uuid));
        $this->cache->forget($this->cacheKey('slug', $tenant->slug));
    }

    /**
     * @param  callable(): ?Tenant  $callback
     */
    private function remember(string $key, callable $callback): ?Tenant
    {
        $ttl = (int) config('tenancy.cache.ttl', 3600);

        if ($ttl <= 0) {
            $tenant = $callback();

            return $tenant?->fresh();
        }

        /** @var array{found: bool, attributes: array<string, mixed>|null} $payload */
        $payload = $this->cache->remember($key, now()->addSeconds($ttl), function () use ($callback): array {
            $tenant = $callback();

            return [
                'found' => $tenant !== null,
                'attributes' => $tenant?->getRawOriginal(),
            ];
        });

        if (! $payload['found'] || $payload['attributes'] === null) {
            return null;
        }

        $tenant = new Tenant;
        $tenant->setRawAttributes($payload['attributes'], true);
        $tenant->exists = true;

        return $tenant;
    }

    private function cacheKey(string $type, int|string $value): string
    {
        $prefix = (string) config('tenancy.cache.prefix', 'tenant');

        return sprintf('%s:resolve:v%d:%s:%s', $prefix, self::CACHE_VERSION, $type, $value);
    }
}
