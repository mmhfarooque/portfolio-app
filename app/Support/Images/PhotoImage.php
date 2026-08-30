<?php

namespace App\Support\Images;

use App\Enums\ImageVariant;
use App\Models\Photo;
use App\Models\Setting;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * How a Photo becomes a URL.
 *
 * Every template, controller, sitemap and structured-data block asks this
 * object instead of concatenating a storage path. Change the origin, the
 * format ladder or the width ladder here and the whole site follows.
 *
 * @implements Arrayable<string, mixed>
 */
final class PhotoImage implements Arrayable, JsonSerializable
{
    /** @var array<string, VariantSet> */
    private array $sets = [];

    public function __construct(private readonly Photo $photo)
    {
    }

    public function for(ImageVariant $variant): VariantSet
    {
        return $this->sets[$variant->value] ??= $this->build($variant);
    }

    public function thumb(): VariantSet
    {
        return $this->for(ImageVariant::Thumb);
    }

    public function display(): VariantSet
    {
        return $this->for(ImageVariant::Display);
    }

    /** The public hero — watermarked when one exists, otherwise the clean frame. */
    public function primary(): VariantSet
    {
        return $this->photo->watermarked_path
            ? $this->for(ImageVariant::Watermarked)
            : $this->for(ImageVariant::Display);
    }

    /**
     * Dimensions of the stored master after upload-time downscaling.
     *
     * The photos table keeps the ORIGINAL dimensions, while the derivatives on
     * disk are capped at image_max_resolution. Responsive widths and the
     * height attribute have to follow the derivative, not the original.
     *
     * @return array{0: int, 1: int}
     */
    public function masterDimensions(): array
    {
        $width = (int) ($this->photo->original_width ?: $this->photo->width);
        $height = (int) ($this->photo->original_height ?: $this->photo->height);

        if ($width <= 0 || $height <= 0) {
            return [0, 0];
        }

        $max = (int) ($this->photo->custom_max_resolution
            ?: Setting::get('image_max_resolution', 1920));

        if ($max > 0) {
            if ($width >= $height && $width > $max) {
                $height = (int) round($height * $max / $width);
                $width = $max;
            } elseif ($height > $width && $height > $max) {
                $width = (int) round($width * $max / $height);
                $height = $max;
            }
        }

        return [$width, $height];
    }

    private function build(ImageVariant $variant): VariantSet
    {
        [$masterWidth, $masterHeight] = $this->masterDimensions();

        return new VariantSet(
            slug: $this->photo->slug,
            variant: $variant,
            widths: $this->widthsFor($variant, $masterWidth),
            masterWidth: $masterWidth,
            masterHeight: $masterHeight,
            alt: $this->photo->title,
            placeholder: $this->photo->dominant_color,
            blurhash: $this->photo->blurhash,
        );
    }

    /**
     * Never offer a width the master cannot fill. The first width at or above
     * the master is kept so the largest option is still a real size.
     *
     * @return list<int>
     */
    private function widthsFor(ImageVariant $variant, int $masterWidth): array
    {
        $widths = $variant->widths();

        if ($masterWidth <= 0) {
            return $widths;
        }

        $usable = array_values(array_filter($widths, fn (int $w): bool => $w < $masterWidth));
        $usable[] = min($masterWidth, max($widths));

        return array_values(array_unique($usable));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'thumb' => $this->thumb()->toArray(),
            'primary' => $this->primary()->toArray(),
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
