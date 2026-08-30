<?php

namespace App\Support\Images;

use App\Enums\ImageFormat;
use App\Enums\ImageVariant;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * One variant of one photo, in every width and format the site offers.
 *
 * This is the single place a public image URL is built. Blade, Inertia props,
 * the sitemap and the JSON-LD all read from here, so they cannot drift apart.
 *
 * @implements Arrayable<string, mixed>
 */
final class VariantSet implements Arrayable, JsonSerializable
{
    /**
     * @param  list<int>  $widths  offered widths, ascending, never above the master
     */
    public function __construct(
        private readonly string $slug,
        private readonly ImageVariant $variant,
        private readonly array $widths,
        private readonly int $masterWidth,
        private readonly int $masterHeight,
        private readonly ?string $alt = null,
        private readonly ?string $placeholder = null,
        private readonly ?string $blurhash = null,
    ) {
    }

    public function url(?int $width = null, ?ImageFormat $format = null): string
    {
        return route('photo.image', [
            'photo' => $this->slug,
            'variant' => $this->variant->value,
            'width' => $width ?? $this->canonicalWidth(),
            'format' => ($format ?? ImageFormat::canonical())->value,
        ]);
    }

    /** The widest offered width, capped by the master. */
    public function canonicalWidth(): int
    {
        return empty($this->widths)
            ? $this->masterWidth
            : min($this->variant->canonicalWidth(), max($this->widths));
    }

    /**
     * The widths this variant actually offers for this photo — never above
     * the master, so nothing is ever upscaled.
     *
     * @return list<int>
     */
    public function widths(): array
    {
        return $this->widths;
    }

    public function srcset(ImageFormat $format): string
    {
        return implode(', ', array_map(
            fn (int $w): string => $this->url($w, $format) . ' ' . $w . 'w',
            $this->widths,
        ));
    }

    /**
     * <source> entries for every format above the fallback, best first.
     *
     * @return list<array{type: string, srcset: string}>
     */
    public function sources(): array
    {
        $sources = [];

        foreach (ImageFormat::ladder() as $format) {
            if ($format === ImageFormat::fallback()) {
                continue;
            }

            $sources[] = [
                'type' => $format->mimeType(),
                'srcset' => $this->srcset($format),
            ];
        }

        return $sources;
    }

    public function height(?int $width = null): int
    {
        $width ??= $this->canonicalWidth();

        return $this->masterWidth > 0
            ? (int) round($this->masterHeight * $width / $this->masterWidth)
            : 0;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $width = $this->canonicalWidth();
        $fallback = ImageFormat::fallback();

        return [
            'variant' => $this->variant->value,
            'alt' => $this->alt,
            'width' => $width,
            'height' => $this->height($width),
            'sizes' => $this->variant->defaultSizes(),
            'src' => $this->url($width, $fallback),
            'srcset' => $this->srcset($fallback),
            'sources' => $this->sources(),
            'url' => $this->url($width, ImageFormat::canonical()),
            'placeholder' => $this->placeholder,
            'blurhash' => $this->blurhash,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
