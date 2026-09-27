<?php

namespace App\Models;

use App\Enums\TenantStatus;
use App\Models\Concerns\HasUuid;
use App\Services\Tenancy\TenantProvisioner;
use App\Support\Tenancy\TenantResolver;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'status', 'plan', 'locale', 'settings', 'trial_ends_at', 'suspended_at'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'locale' => 'es',
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            $tenant->slug ??= self::uniqueSlug($tenant->name);
        });

        static::created(function (Tenant $tenant): void {
            app(TenantProvisioner::class)->provision($tenant);
        });

        static::saved(function (Tenant $tenant): void {
            app(TenantResolver::class)->forget($tenant);
        });

        static::deleted(function (Tenant $tenant): void {
            app(TenantResolver::class)->forget($tenant);
        });
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasOne<TenantBillingSetting, $this>
     */
    public function billingSetting(): HasOne
    {
        return $this->hasOne(TenantBillingSetting::class);
    }

    public function isActive(): bool
    {
        return $this->status?->isUsable() ?? false;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function mergeSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings ?? [], $settings);
        $this->save();
    }

    public static function uniqueSlug(string $name, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $suffix = 1;

        while (self::slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    private static function slugExists(string $slug, int|string|null $ignoreId): bool
    {
        return self::query()
            ->withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
