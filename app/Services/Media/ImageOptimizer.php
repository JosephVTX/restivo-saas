<?php

namespace App\Services\Media;

use RuntimeException;

/**
 * Converts an uploaded image to a lightweight WebP using GD, capping its width.
 *
 * Keeps Cloudinary storage small: images are downscaled and re-encoded before
 * they ever leave the server, so every stored photo is a small WebP.
 */
class ImageOptimizer
{
    /**
     * @return array{data: string, width: int, height: int, bytes: int}
     */
    public function toWebp(string $binary, int $maxWidth = 1000, int $quality = 75): array
    {
        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            throw new RuntimeException('El archivo no es una imagen válida.');
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($maxWidth > 0 && $width > $maxWidth) {
            $targetHeight = (int) max(1, (int) round($height * $maxWidth / $width));
            $resized = imagescale($source, $maxWidth, $targetHeight);

            if ($resized !== false) {
                imagedestroy($source);
                $source = $resized;
                $width = $maxWidth;
                $height = $targetHeight;
            }
        }

        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);

        ob_start();
        imagewebp($source, null, max(1, min(100, $quality)));
        $data = (string) ob_get_clean();

        imagedestroy($source);

        return [
            'data' => $data,
            'width' => $width,
            'height' => $height,
            'bytes' => strlen($data),
        ];
    }
}
