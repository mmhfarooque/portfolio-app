<?php

namespace App\Jobs;

use App\Enums\ImageFormat;
use App\Enums\ImageVariant;
use App\Models\Photo;
use App\Services\ImageVariantService;
use App\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Warms every derivative a photo can serve.
 *
 * The route generates on demand anyway, so this job is purely about who pays
 * the encode cost — a worker at upload time rather than the first visitor.
 * Adding a width or a format means re-running it; nothing else changes.
 */
class GeneratePhotoVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public Photo $photo,
        public ?ImageVariant $only = null,
        public ?ImageFormat $format = null,
    ) {
    }

    public function handle(ImageVariantService $variants): void
    {
        $variantList = $this->only ? [$this->only] : ImageVariant::cases();
        $formats = $this->format ? [$this->format] : ImageFormat::ladder();
        $built = 0;
        $failed = 0;

        foreach ($variantList as $variant) {
            foreach ($this->photo->image->for($variant)->widths() as $width) {
                foreach ($formats as $format) {
                    if ($variants->ensure($this->photo, $variant, $width, $format) !== null) {
                        $built++;
                    } else {
                        $failed++;
                    }
                }
            }
        }

        LoggingService::debug(
            'image.variants_built',
            "Photo {$this->photo->id}: {$built} variants ready, {$failed} unavailable",
            $this->photo,
        );
    }
}
