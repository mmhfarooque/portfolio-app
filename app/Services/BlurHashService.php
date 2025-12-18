<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;

class BlurHashService
{
    /**
     * Generate a simple color-based placeholder (fallback without blurhash package).
     * Returns dominant color and a simple SVG placeholder.
     */
    public function generatePlaceholder(string $imagePath): array
    {
        try {
            $image = Image::read($imagePath);

            // Resize to tiny size for color analysis
            $tiny = $image->resize(10, 10);

            // Get dominant color by sampling center pixel
            $color = $tiny->pickColor(5, 5);
            $hex = sprintf('#%02x%02x%02x', $color->red(), $color->green(), $color->blue());

            // Generate a simple gradient placeholder SVG
            $svg = $this->generateGradientSvg($imagePath, $image->width(), $image->height());

            return [
                'dominant_color' => $hex,
                'placeholder_svg' => $svg,
            ];
        } catch (\Exception $e) {
            Log::error('BlurHash generation failed', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            return [
                'dominant_color' => '#333333',
                'placeholder_svg' => null,
            ];
        }
    }

    /**
     * Generate a simple gradient SVG placeholder.
     */
    protected function generateGradientSvg(string $imagePath, int $width, int $height): string
    {
        try {
            $image = Image::read($imagePath);

            // Sample 4 corners for gradient
            $tiny = $image->resize(2, 2);

            $tl = $tiny->pickColor(0, 0);
            $tr = $tiny->pickColor(1, 0);
            $bl = $tiny->pickColor(0, 1);
            $br = $tiny->pickColor(1, 1);

            $colors = [
                sprintf('rgb(%d,%d,%d)', $tl->red(), $tl->green(), $tl->blue()),
                sprintf('rgb(%d,%d,%d)', $tr->red(), $tr->green(), $tr->blue()),
                sprintf('rgb(%d,%d,%d)', $bl->red(), $bl->green(), $bl->blue()),
                sprintf('rgb(%d,%d,%d)', $br->red(), $br->green(), $br->blue()),
            ];

            // Create simple gradient SVG
            $aspectRatio = $width / $height;
            $svgWidth = 20;
            $svgHeight = round($svgWidth / $aspectRatio);

            return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '"><defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:' . $colors[0] . '"/><stop offset="100%" style="stop-color:' . $colors[3] . '"/></linearGradient></defs><rect width="' . $svgWidth . '" height="' . $svgHeight . '" fill="url(#g)"/></svg>';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Generate data URI for placeholder.
     */
    public function getPlaceholderDataUri(string $svg): string
    {
        if (empty($svg)) {
            return '';
        }
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Get CSS for dominant color background.
     */
    public function getDominantColorCss(?string $color): string
    {
        return $color ? "background-color: {$color};" : 'background-color: #333;';
    }
}
