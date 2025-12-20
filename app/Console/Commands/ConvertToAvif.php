<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;
use App\Models\Setting;
use App\Services\LoggingService;
use Intervention\Image\Laravel\Facades\Image;

class ConvertToAvif extends Command
{
    protected $signature = 'photos:convert-to-avif
                            {--dry-run : Show what would be converted without actually converting}
                            {--keep-webp : Keep old WebP files after conversion}
                            {--photo= : Convert a specific photo by ID}
                            {--limit= : Limit the number of photos to convert}';

    protected $description = 'Convert existing WebP photos to AVIF format for better compression';

    protected int $convertedCount = 0;
    protected int $skippedCount = 0;
    protected int $errorCount = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $keepWebp = $this->option('keep-webp');
        $photoId = $this->option('photo');
        $limit = $this->option('limit');
        $startTime = microtime(true);

        $this->info('Starting AVIF conversion...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be converted');
        }
        if ($keepWebp) {
            $this->info('Old WebP files will be kept after conversion');
        }

        // Get photos to convert
        $query = Photo::query();

        // Filter by photo ID if specified
        if ($photoId) {
            $query->where('id', $photoId);
        } else {
            // Only get photos with WebP display paths
            $query->where(function ($q) {
                $q->where('display_path', 'like', '%.webp')
                  ->orWhere('watermarked_path', 'like', '%.webp');
            });
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $photos = $query->get();

        if ($photos->isEmpty()) {
            $this->info('No photos found that need conversion.');
            return Command::SUCCESS;
        }

        $this->info("Found {$photos->count()} photo(s) to convert");
        $this->newLine();

        // Get quality settings
        $webpQuality = (int) Setting::get('image_quality', 92);
        $avifQuality = $this->mapWebpToAvifQuality($webpQuality);
        $this->info("Using AVIF quality: {$avifQuality} (mapped from WebP quality: {$webpQuality})");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($photos->count());
        $progressBar->start();

        foreach ($photos as $photo) {
            try {
                $converted = $this->convertPhoto($photo, $avifQuality, $dryRun, $keepWebp);
                if ($converted) {
                    $this->convertedCount++;
                } else {
                    $this->skippedCount++;
                }
            } catch (\Exception $e) {
                $this->errorCount++;
                LoggingService::error('avif.conversion_failed', "Failed to convert photo {$photo->id}: " . $e->getMessage(), $photo);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $duration = round((microtime(true) - $startTime), 2);

        $this->info("Conversion completed in {$duration} seconds!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Converted', $this->convertedCount],
                ['Skipped (already AVIF)', $this->skippedCount],
                ['Errors', $this->errorCount],
            ]
        );

        if (!$dryRun && $this->convertedCount > 0) {
            LoggingService::activity(
                'avif.batch_conversion',
                "Batch AVIF conversion completed: {$this->convertedCount} converted, {$this->skippedCount} skipped, {$this->errorCount} errors"
            );
        }

        return $this->errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Convert a single photo to AVIF format.
     */
    protected function convertPhoto(Photo $photo, int $avifQuality, bool $dryRun, bool $keepWebp): bool
    {
        $displayConverted = false;
        $watermarkedConverted = false;

        // Convert display path
        if ($photo->display_path && str_ends_with($photo->display_path, '.webp')) {
            $newDisplayPath = $this->convertImageToAvif(
                $photo->display_path,
                $avifQuality,
                $dryRun,
                $keepWebp
            );

            if ($newDisplayPath && !$dryRun) {
                $photo->display_path = $newDisplayPath;
                $displayConverted = true;
            } elseif ($newDisplayPath) {
                $displayConverted = true; // Count dry-run as converted
            }
        }

        // Convert watermarked path
        if ($photo->watermarked_path && str_ends_with($photo->watermarked_path, '.webp')) {
            $newWatermarkedPath = $this->convertImageToAvif(
                $photo->watermarked_path,
                $avifQuality,
                $dryRun,
                $keepWebp
            );

            if ($newWatermarkedPath && !$dryRun) {
                $photo->watermarked_path = $newWatermarkedPath;
                $watermarkedConverted = true;
            } elseif ($newWatermarkedPath) {
                $watermarkedConverted = true;
            }
        }

        // Save photo if any paths were updated
        if (!$dryRun && ($displayConverted || $watermarkedConverted)) {
            $photo->save();
        }

        return $displayConverted || $watermarkedConverted;
    }

    /**
     * Convert a single WebP image to AVIF.
     */
    protected function convertImageToAvif(string $webpPath, int $quality, bool $dryRun, bool $keepWebp): ?string
    {
        $fullPath = storage_path('app/public/' . $webpPath);

        if (!file_exists($fullPath)) {
            return null;
        }

        // Generate new path with .avif extension
        $avifPath = preg_replace('/\.webp$/', '.avif', $webpPath);
        $avifFullPath = storage_path('app/public/' . $avifPath);

        // Check if AVIF already exists
        if (file_exists($avifFullPath)) {
            return null; // Already converted
        }

        if ($dryRun) {
            $webpSize = filesize($fullPath);
            $this->line("  Would convert: {$webpPath} (" . $this->formatBytes($webpSize) . ")");
            return $avifPath;
        }

        // Read the WebP image
        $image = Image::read($fullPath);

        // Save as AVIF
        $image->toAvif($quality)->save($avifFullPath);

        // Log size reduction
        $webpSize = filesize($fullPath);
        $avifSize = filesize($avifFullPath);
        $reduction = round((1 - ($avifSize / $webpSize)) * 100, 1);

        LoggingService::debug(
            'avif.converted',
            "Converted {$webpPath}: {$this->formatBytes($webpSize)} -> {$this->formatBytes($avifSize)} ({$reduction}% reduction)"
        );

        // Delete old WebP if not keeping
        if (!$keepWebp) {
            @unlink($fullPath);
        }

        return $avifPath;
    }

    /**
     * Map WebP quality to AVIF quality.
     * Photography-optimized: preserves gradients and fine details.
     */
    protected function mapWebpToAvifQuality(int $webpQuality): int
    {
        // WebP 80-100 -> AVIF 70-85 (higher quality for photography)
        $avifQuality = (int) (70 + (($webpQuality - 80) / 20) * 15);
        return max(65, min(85, $avifQuality));
    }

    /**
     * Format bytes to human readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
