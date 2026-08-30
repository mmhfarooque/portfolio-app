<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Services\PhotoProcessingService;
use Illuminate\Console\Command;

/**
 * Restore the original_width / original_height record.
 *
 * Every photo predating those columns has them null, and until 2026-08-30
 * reoptimizePhoto() overwrote width/height with the DERIVATIVE dimensions
 * without preserving the master's first — so the app forgot how large its own
 * originals were. The masters themselves were never at risk; they sit in R2
 * untouched. This reads them back and records what it finds.
 *
 * Read-only against storage: nothing is re-encoded, replaced or deleted.
 */
class BackfillOriginalDimensions extends Command
{
    protected $signature = 'photos:backfill-dimensions
                            {--all : Re-measure every photo, not just the ones missing a record}
                            {--dry-run : Report what would change without saving}';

    protected $description = 'Record the true dimensions of each photo master, read from R2';

    public function handle(PhotoProcessingService $processor): int
    {
        $query = Photo::query();

        if (! $this->option('all')) {
            $query->whereNull('original_width');
        }

        $photos = $query->orderBy('id')->get();

        if ($photos->isEmpty()) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $dry = $this->option('dry-run');
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($photos as $photo) {
            $measured = $processor->measureOriginal($photo);

            if ($measured === null) {
                $this->line("  <fg=red>no source</> {$photo->slug}");
                $failed++;

                continue;
            }

            [$width, $height] = $measured;

            // A result no bigger than the derivative means findSourceFile fell
            // back to a compressed copy. Recording that as the original would
            // bake in the very loss this command exists to undo.
            if ($width <= (int) $photo->width) {
                $this->line("  <fg=yellow>derivative fallback</> {$photo->slug} ({$width}x{$height})");
                $skipped++;

                continue;
            }

            $this->line("  {$photo->slug}: {$photo->width}x{$photo->height} → original {$width}x{$height}");

            if (! $dry) {
                $photo->original_width = $width;
                $photo->original_height = $height;
                $photo->save();
            }

            $updated++;
        }

        $this->newLine();
        $this->info($dry
            ? "Would record {$updated}, skip {$skipped}, fail {$failed}."
            : "Recorded {$updated}, skipped {$skipped}, failed {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
