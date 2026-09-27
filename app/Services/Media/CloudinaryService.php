<?php

namespace App\Services\Media;

use App\Models\PlatformSetting;
use Cloudinary\Api;
use Cloudinary\Uploader;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around the Cloudinary SDK using the platform-wide credentials
 * configured by the super admin. Images are optimized to WebP before upload.
 */
class CloudinaryService
{
    public function __construct(private readonly ImageOptimizer $optimizer) {}

    public function settings(): PlatformSetting
    {
        return PlatformSetting::current();
    }

    public function configured(): bool
    {
        return $this->settings()->cloudinaryConfigured();
    }

    /**
     * Verify the configured credentials against Cloudinary.
     *
     * @return array{ok: bool, message: string}
     */
    public function ping(): array
    {
        $settings = $this->settings();

        if (! $settings->cloudinaryConfigured()) {
            return ['ok' => false, 'message' => 'Cloudinary no está configurado.'];
        }

        $this->configure($settings);

        try {
            $this->quietly(fn () => (new Api)->ping());

            return ['ok' => true, 'message' => 'Conexión correcta con Cloudinary.'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => 'No se pudo conectar: '.$exception->getMessage()];
        }
    }

    /**
     * Downscale + convert the binary to WebP and upload it to Cloudinary.
     *
     * @return array{url: string, public_id: ?string, format: ?string, width: int, height: int, bytes: int}
     */
    public function uploadImage(string $binary): array
    {
        $settings = $this->settings();

        if (! $settings->cloudinaryConfigured()) {
            throw new RuntimeException('Cloudinary no está configurado. Pídele al administrador que agregue las credenciales.');
        }

        $optimized = $this->optimizer->toWebp(
            $binary,
            $settings->image_max_width,
            $settings->webp_quality,
        );

        $this->configure($settings);

        $path = tempnam(sys_get_temp_dir(), 'restivo').'.webp';
        file_put_contents($path, $optimized['data']);

        try {
            $result = $this->quietly(fn (): array => Uploader::upload($path, [
                'folder' => $settings->cloudinary_folder,
                'resource_type' => 'image',
                'format' => 'webp',
                'unique_filename' => true,
                'overwrite' => false,
            ]));
        } finally {
            @unlink($path);
        }

        if (isset($result['error'])) {
            throw new RuntimeException('Cloudinary rechazó la imagen: '.($result['error']['message'] ?? 'error desconocido'));
        }

        return [
            'url' => (string) ($result['secure_url'] ?? $result['url'] ?? ''),
            'public_id' => $result['public_id'] ?? null,
            'format' => $result['format'] ?? 'webp',
            'width' => (int) ($result['width'] ?? $optimized['width']),
            'height' => (int) ($result['height'] ?? $optimized['height']),
            'bytes' => (int) ($result['bytes'] ?? $optimized['bytes']),
        ];
    }

    public function destroy(?string $publicId): void
    {
        if (blank($publicId)) {
            return;
        }

        $settings = $this->settings();

        if (! $settings->cloudinaryConfigured()) {
            return;
        }

        $this->configure($settings);

        try {
            $this->quietly(fn () => Uploader::destroy($publicId, ['resource_type' => 'image', 'invalidate' => true]));
        } catch (Throwable) {
            // Best effort: the record is removed even if Cloudinary is unreachable.
        }
    }

    private function configure(PlatformSetting $settings): void
    {
        \Cloudinary::config([
            'cloud_name' => $settings->cloudinary_cloud_name,
            'api_key' => $settings->cloudinary_api_key,
            'api_secret' => $settings->cloudinary_api_secret,
        ]);
    }

    /**
     * Run the Cloudinary SDK without its PHP 8.1+ deprecation notices (the v1
     * SDK passes nulls internally). Scoped to the SDK call only.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function quietly(callable $callback): mixed
    {
        set_error_handler(
            fn (int $severity): bool => $severity === E_DEPRECATED || $severity === E_USER_DEPRECATED,
            E_DEPRECATED | E_USER_DEPRECATED,
        );

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
