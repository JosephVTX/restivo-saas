<?php

namespace Tests\Unit;

use App\Services\Media\ImageOptimizer;
use PHPUnit\Framework\TestCase;

class ImageOptimizerTest extends TestCase
{
    public function test_downscales_and_converts_a_png_to_webp(): void
    {
        $source = imagecreatetruecolor(2000, 1500);

        ob_start();
        imagepng($source);
        $png = (string) ob_get_clean();
        imagedestroy($source);

        $result = (new ImageOptimizer)->toWebp($png, 1000, 75);

        $this->assertSame('RIFF', substr($result['data'], 0, 4));
        $this->assertSame('WEBP', substr($result['data'], 8, 4));
        $this->assertSame(1000, $result['width']);
        $this->assertSame(750, $result['height']);
        $this->assertGreaterThan(0, $result['bytes']);
    }
}
