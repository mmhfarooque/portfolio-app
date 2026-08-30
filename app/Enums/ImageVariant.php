<?php

namespace App\Enums;

/**
 * The kinds of derivative the site publishes, and the responsive width ladder
 * each one offers.
 *
 * A variant is a role, not a file. The storage key stays an opaque UUID; the
 * public URL is built from the photo slug plus this role, so the same indexed
 * URL survives a format change, a re-encode, or a move to another origin.
 */
enum ImageVariant: string
{
    /** Grid and list tiles. */
    case Thumb = 'thumb';

    /** The clean, un-watermarked frame (admin, social cards, previews). */
    case Display = 'display';

    /** The public hero on a photo page — watermark baked in. */
    case Watermarked = 'watermarked';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Candidate widths, small to large. Widths above the master are dropped at
     * render time — this never upscales.
     *
     * @return list<int>
     */
    public function widths(): array
    {
        return match ($this) {
            self::Thumb => [200, 400, 800],
            self::Display, self::Watermarked => [480, 960, 1440, 1920],
        };
    }

    /** The width used for the canonical single URL (og:image, JSON-LD, sitemap). */
    public function canonicalWidth(): int
    {
        return match ($this) {
            self::Thumb => 400,
            self::Display, self::Watermarked => 1920,
        };
    }

    /** A sensible default sizes attribute when a caller does not pass one. */
    public function defaultSizes(): string
    {
        return match ($this) {
            self::Thumb => '(max-width: 640px) 50vw, 400px',
            self::Display, self::Watermarked => '100vw',
        };
    }

    /**
     * The legacy photos-table column holding the 1920 master this variant is
     * derived from. Downscaling the existing master keeps the baked-in
     * watermark intact and avoids re-running the watermark pipeline.
     */
    public function masterColumn(): string
    {
        return match ($this) {
            self::Watermarked => 'watermarked_path',
            self::Thumb, self::Display => 'display_path',
        };
    }

    /** Storage prefix for generated variants, under the public disk. */
    public function storagePrefix(): string
    {
        return 'photos/variants/' . $this->value;
    }
}
