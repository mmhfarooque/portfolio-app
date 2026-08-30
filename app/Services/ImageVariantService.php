<?php

namespace App\Services;

use App\Enums\ImageFormat;
use App\Enums\ImageVariant;
use App\Models\Photo;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Materialises a (photo, variant, width, format) triple on disk.
 *
 * Variants are derived by downscaling the master this variant already owns —
 * so a watermarked variant keeps its baked-in watermark and the expensive
 * watermark pipeline never runs again. Only when a master has gone missing do
 * we fall back to PhotoProcessingService to rebuild it from the R2 original.
 */
class ImageVariantService
{
    public function __construct(
        private readonly PhotoProcessingService $processor = new PhotoProcessingService(),
    ) {
    }

    /**
     * Absolute path to the generated file, building it if it is not there yet.
     * Null when no source exists for this photo at all.
     */
    public function ensure(Photo $photo, ImageVariant $variant, int $width, ImageFormat $format): ?string
    {
        $target = $this->absolutePath($photo, $variant, $width, $format);

        if (is_file($target) && filesize($target) > 0) {
            return $target;
        }

        // One generator per target; everyone else waits for it rather than
        // burning CPU on the same encode.
        $lock = Cache::lock('image-variant:' . md5($target), 30);

        try {
            $lock->block(20);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
            return is_file($target) ? $target : null;
        }

        try {
            if (is_file($target) && filesize($target) > 0) {
                return $target;
            }

            return $this->generate($photo, $variant, $width, $format, $target);
        } finally {
            optional($lock)->release();
        }
    }

    /** Storage-relative path (on the public disk) for a generated variant. */
    public function relativePath(Photo $photo, ImageVariant $variant, int $width, ImageFormat $format): string
    {
        // The master path is folded into the filename so that re-optimising a
        // photo produces a new file while the PUBLIC URL stays exactly the
        // same — which is the whole point of routing images.
        $fingerprint = substr(md5((string) $photo->getAttribute($variant->masterColumn())), 0, 8);

        return sprintf(
            '%s/%d/%d-%s.%s',
            $variant->storagePrefix(),
            $width,
            $photo->id,
            $fingerprint,
            $format->value,
        );
    }

    public function absolutePath(Photo $photo, ImageVariant $variant, int $width, ImageFormat $format): string
    {
        return storage_path('app/public/' . $this->relativePath($photo, $variant, $width, $format));
    }

    private function generate(Photo $photo, ImageVariant $variant, int $width, ImageFormat $format, string $target): ?string
    {
        $source = $this->sourceFor($photo, $variant);

        if ($source === null) {
            return null;
        }

        $directory = dirname($target);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return null;
        }

        $image = Image::decode($source);

        // Never upscale: a request for a width above the master is served the
        // master's own size, so the srcset descriptor stays honest.
        if ($image->width() > $width) {
            $image->scale(width: $width);
        }

        $quality = (int) ($photo->custom_quality ?: Setting::get('image_quality', 92));

        $image->encode($format->encoder($quality))->save($target);

        return is_file($target) ? $target : null;
    }

    /**
     * The master this variant downscales from. Rebuilds it through the
     * existing pipeline if the file has gone missing.
     */
    private function sourceFor(Photo $photo, ImageVariant $variant): ?string
    {
        $existing = $this->masterFile($photo, $variant);

        if ($existing !== null) {
            return $existing;
        }

        // Cold path: the master is gone. Rebuild it the way the rest of the
        // app already does, then look again.
        try {
            if ($variant === ImageVariant::Watermarked) {
                $this->processor->regenerateWatermark($photo);
            } else {
                $this->processor->reoptimizePhoto($photo);
            }
        } catch (\Throwable $e) {
            LoggingService::error(
                'image.variant_source_missing',
                "Could not rebuild the {$variant->value} master for photo {$photo->id}",
                $e,
                $photo,
            );

            return null;
        }

        return $this->masterFile($photo->fresh(), $variant);
    }

    private function masterFile(?Photo $photo, ImageVariant $variant): ?string
    {
        $path = $photo?->getAttribute($variant->masterColumn());

        if (empty($path)) {
            return null;
        }

        $full = storage_path('app/public/' . $path);

        return is_file($full) && filesize($full) > 0 ? $full : null;
    }
}
