<?php

namespace App\Enums;

use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\EncoderInterface;

/**
 * Every delivery format the site can serve, best-first.
 *
 * Adding a future format (JPEG XL, whatever follows it) is one case here plus
 * one encoder arm. Public URLs carry the format as an extension, so a new
 * format adds URLs instead of rewriting the ones search engines already hold.
 */
enum ImageFormat: string
{
    case Avif = 'avif';
    case Webp = 'webp';
    case Jpeg = 'jpg';

    /**
     * Preference order offered to <picture>: best compression first, the
     * universally decodable one last. The last entry is the <img> fallback.
     */
    public static function ladder(): array
    {
        return [self::Avif, self::Webp, self::Jpeg];
    }

    /** The format every browser and every crawler can decode. */
    public static function fallback(): self
    {
        return self::Jpeg;
    }

    /** The format used for canonical SEO URLs (og:image, JSON-LD, sitemap). */
    public static function canonical(): self
    {
        return self::Jpeg;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Avif => 'image/avif',
            self::Webp => 'image/webp',
            self::Jpeg => 'image/jpeg',
        };
    }

    /**
     * Quality is expressed on the WebP scale throughout this app (the
     * image_quality setting). AVIF reaches the same perceived quality at a
     * lower number; JPEG needs a touch more.
     *
     * The AVIF arm reproduces PhotoProcessingService::mapWebpToAvifQuality()
     * so on-demand variants match the masters generated at upload time.
     */
    public function qualityFor(int $webpQuality): int
    {
        return match ($this) {
            self::Webp => $webpQuality,
            self::Avif => max(65, min(85, (int) (70 + (($webpQuality - 80) / 20) * 15))),
            self::Jpeg => max(70, min(95, $webpQuality)),
        };
    }

    public function encoder(int $webpQuality): EncoderInterface
    {
        $quality = $this->qualityFor($webpQuality);

        return match ($this) {
            self::Avif => new AvifEncoder(quality: $quality),
            self::Webp => new WebpEncoder(quality: $quality),
            self::Jpeg => new JpegEncoder(quality: $quality, progressive: true),
        };
    }
}
