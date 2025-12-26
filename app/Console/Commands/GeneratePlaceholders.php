<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;
use App\Services\BlurHashService;

class GeneratePlaceholders extends Command
{
    protected $signature = 'photos:generate-placeholders
                            {--dry-run : Show what would be processed without actually generating}
                            {--photo= : Process a specific photo by ID}
                            {--limit= : Limit the number of photos to process}
                            {--force : Regenerate even if placeholder already exists}';

    protected $description = 'Generate blur placeholders (LQIP) for existing photos';

    protected int $processedCount = 0;
    protected int $skippedCount = 0;
    protected int $errorCount = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $photoId = $this->option('photo');
        $limit = $this->option('limit');
        $force = $this->option('force');
        $startTime = microtime(true);

        $this->info('Starting placeholder generation...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get photos to process
        $query = Photo::query()
            ->whereNotNull('thumbnail_path');

        // Filter by photo ID if specified
        if ($photoId) {
            $query->where('id', $photoId);
        } elseif (!$force) {
            // Only get photos without dominant_color
            $query->whereNull('dominant_color');
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $photos = $query->get();

        if ($photos->isEmpty()) {
            $this->info('No photos found that need placeholder generation.');
            return Command::SUCCESS;
        }

        $this->info("Found {$photos->count()} photo(s) to process");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($photos->count());
        $progressBar->start();

        $blurHashService = new BlurHashService();

        foreach ($photos as $photo) {
            try {
                $this->processPhoto($photo, $blurHashService, $dryRun);
            } catch (\Exception $e) {
                $this->errorCount++;
                $this->newLine();
                $this->error("Error processing photo {$photo->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $duration = round(microtime(true) - $startTime, 2);
        $this->info("Placeholder generation complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $this->processedCount],
                ['Skipped', $this->skippedCount],
                ['Errors', $this->errorCount],
                ['Duration', "{$duration}s"],
            ]
        );

        return Command::SUCCESS;
    }

    protected function processPhoto(Photo $photo, BlurHashService $blurHashService, bool $dryRun): void
    {
        // Check if thumbnail exists
        $thumbnailPath = storage_path('app/public/' . $photo->thumbnail_path);

        if (!file_exists($thumbnailPath)) {
            // Try display path instead
            if ($photo->display_path) {
                $thumbnailPath = storage_path('app/public/' . $photo->display_path);
            }

            if (!file_exists($thumbnailPath)) {
                $this->skippedCount++;
                return;
            }
        }

        if ($dryRun) {
            $this->processedCount++;
            return;
        }

        // Generate placeholder
        $placeholder = $blurHashService->generatePlaceholder($thumbnailPath);

        // Update photo with dominant color only
        $photo->update([
            'dominant_color' => $placeholder['dominant_color'],
        ]);

        $this->processedCount++;
    }
}
