<?php

namespace App\Console\Commands;

use App\Enums\ImageFormat;
use App\Enums\ImageVariant;
use App\Jobs\GeneratePhotoVariants;
use App\Models\Photo;
use App\Services\ImageVariantService;
use Illuminate\Console\Command;

/**
 * Idempotent variant warmer.
 *
 * Replaces the one-shot photos:convert-to-avif pattern: this can be re-run
 * forever, and a new width or format is a new enum case plus one more run.
 */
class GeneratePhotoVariantsCommand extends Command
{
    protected $signature = 'photos:variants
                            {--photo=* : Limit to these photo IDs or slugs}
                            {--variant= : Limit to one variant (thumb, display, watermarked)}
                            {--format= : Limit to one format (avif, webp, jpg)}
                            {--force : Rebuild variants that already exist}
                            {--queue : Dispatch to the queue instead of building inline}';

    protected $description = 'Build the responsive image variants every photo serves';

    public function handle(ImageVariantService $variants): int
    {
        $variant = $this->resolveVariant();
        $format = $this->resolveFormat();

        if ($variant === false || $format === false) {
            return self::FAILURE;
        }

        $photos = $this->photos();

        if ($photos->isEmpty()) {
            $this->warn('No photos matched.');

            return self::SUCCESS;
        }

        if ($this->option('queue')) {
            $photos->each(fn (Photo $photo) => GeneratePhotoVariants::dispatch($photo, $variant, $format));

            $this->info("Queued {$photos->count()} photos.");

            return self::SUCCESS;
        }

        $variantList = $variant ? [$variant] : ImageVariant::cases();
        $formats = $format ? [$format] : ImageFormat::ladder();
        $built = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($photos->count());
        $bar->start();

        foreach ($photos as $photo) {
            foreach ($variantList as $role) {
                foreach ($photo->image->for($role)->widths() as $width) {
                    foreach ($formats as $encoding) {
                        $target = $variants->absolutePath($photo, $role, $width, $encoding);

                        if ($this->option('force') && is_file($target)) {
                            unlink($target);
                        } elseif (is_file($target) && filesize($target) > 0) {
                            $skipped++;

                            continue;
                        }

                        if ($variants->ensure($photo, $role, $width, $encoding) !== null) {
                            $built++;
                        } else {
                            $failed++;
                        }
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Built {$built}, already present {$skipped}, unavailable {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, Photo> */
    private function photos()
    {
        $query = Photo::query()->where('status', 'published');

        if ($only = $this->option('photo')) {
            $query->where(function ($q) use ($only) {
                $q->whereIn('slug', $only)
                    ->orWhereIn('id', array_filter($only, 'is_numeric'));
            });
        }

        return $query->orderBy('id')->get();
    }

    private function resolveVariant(): ImageVariant|false|null
    {
        $value = $this->option('variant');

        if ($value === null) {
            return null;
        }

        $variant = ImageVariant::tryFrom($value);

        if ($variant === null) {
            $this->error('Unknown variant. Expected one of: ' . implode(', ', ImageVariant::values()));

            return false;
        }

        return $variant;
    }

    private function resolveFormat(): ImageFormat|false|null
    {
        $value = $this->option('format');

        if ($value === null) {
            return null;
        }

        $format = ImageFormat::tryFrom($value);

        if ($format === null) {
            $this->error('Unknown format. Expected one of: ' . implode(', ', ImageFormat::values()));

            return false;
        }

        return $format;
    }
}
