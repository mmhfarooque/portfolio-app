<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\LoggingService;
use App\Models\Setting;

class BackupPhotos extends Command
{
    protected $signature = 'backup:photos
                            {--database : Also backup the database}
                            {--dry-run : Show what would be backed up without actually uploading}';

    protected $description = 'Backup photos and optionally database to Backblaze B2';

    protected int $uploadedCount = 0;
    protected int $skippedCount = 0;
    protected int $errorCount = 0;

    public function handle(): int
    {
        // Check if B2 is configured
        if (!config('filesystems.disks.b2.key') || !config('filesystems.disks.b2.bucket')) {
            $this->error('Backblaze B2 is not configured. Please set B2_ACCESS_KEY_ID, B2_SECRET_ACCESS_KEY, and B2_BUCKET in .env');
            return Command::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $startTime = microtime(true);

        $this->info('Starting backup to Backblaze B2...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be uploaded');
        }

        try {
            // Test connection
            $this->info('Testing B2 connection...');
            if (!$dryRun) {
                Storage::disk('b2')->put('_connection_test.txt', 'test');
                Storage::disk('b2')->delete('_connection_test.txt');
            }
            $this->info('B2 connection successful.');

            // Backup photos
            $this->backupPhotos($dryRun);

            // Backup database if requested
            if ($this->option('database')) {
                $this->backupDatabase($dryRun);
            }

            $duration = round((microtime(true) - $startTime), 2);

            $this->newLine();
            $this->info("Backup completed in {$duration} seconds!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Uploaded', $this->uploadedCount],
                    ['Skipped (already exists)', $this->skippedCount],
                    ['Errors', $this->errorCount],
                ]
            );

            // Log the backup
            LoggingService::activity('backup.completed', "Backup completed: {$this->uploadedCount} uploaded, {$this->skippedCount} skipped, {$this->errorCount} errors");

            // Update last backup time
            if (!$dryRun) {
                Setting::set('last_backup_at', now()->toIso8601String());
                Setting::set('last_backup_stats', json_encode([
                    'uploaded' => $this->uploadedCount,
                    'skipped' => $this->skippedCount,
                    'errors' => $this->errorCount,
                    'duration' => $duration,
                ]));
            }

            return $this->errorCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            LoggingService::error('backup.failed', 'Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function backupPhotos(bool $dryRun): void
    {
        $this->info('Backing up photos...');

        $directories = ['photos/display', 'photos/thumbnails', 'photos/watermarked', 'categories', 'galleries', 'settings'];

        foreach ($directories as $directory) {
            $this->backupDirectory($directory, $dryRun);
        }
    }

    protected function backupDirectory(string $directory, bool $dryRun): void
    {
        $files = Storage::disk('public')->files($directory);

        if (empty($files)) {
            $this->line("  No files in {$directory}");
            return;
        }

        $this->line("  Backing up {$directory}...");
        $progressBar = $this->output->createProgressBar(count($files));

        foreach ($files as $file) {
            try {
                // Check if file already exists in B2
                $remotePath = "backup/{$file}";

                if (!$dryRun && Storage::disk('b2')->exists($remotePath)) {
                    // Check if local file is newer (by size comparison for simplicity)
                    $localSize = Storage::disk('public')->size($file);
                    $remoteSize = Storage::disk('b2')->size($remotePath);

                    if ($localSize === $remoteSize) {
                        $this->skippedCount++;
                        $progressBar->advance();
                        continue;
                    }
                }

                if (!$dryRun) {
                    // Stream upload for large files
                    $stream = Storage::disk('public')->readStream($file);
                    Storage::disk('b2')->writeStream($remotePath, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                $this->uploadedCount++;
                $progressBar->advance();

            } catch (\Exception $e) {
                $this->errorCount++;
                $progressBar->advance();
                $this->newLine();
                $this->error("    Error uploading {$file}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function backupDatabase(bool $dryRun): void
    {
        $this->info('Backing up database...');

        $databasePath = database_path('database.sqlite');

        if (!file_exists($databasePath)) {
            $this->warn('Database file not found. Skipping database backup.');
            return;
        }

        try {
            $backupName = 'backup/database/database_' . date('Y-m-d_H-i-s') . '.sqlite';

            if (!$dryRun) {
                // Create a copy of the database first to avoid locking issues
                $tempPath = storage_path('app/temp/database_backup.sqlite');
                copy($databasePath, $tempPath);

                // Upload to B2
                $stream = fopen($tempPath, 'r');
                Storage::disk('b2')->writeStream($backupName, $stream);
                fclose($stream);

                // Clean up temp file
                @unlink($tempPath);

                // Clean up old database backups (keep last 7)
                $this->cleanupOldDatabaseBackups();
            }

            $this->uploadedCount++;
            $this->info("  Database backed up as: {$backupName}");

        } catch (\Exception $e) {
            $this->errorCount++;
            $this->error('  Error backing up database: ' . $e->getMessage());
        }
    }

    protected function cleanupOldDatabaseBackups(): void
    {
        try {
            $files = Storage::disk('b2')->files('backup/database');

            // Sort by name (which includes timestamp)
            sort($files);

            // Keep only the last 7 backups
            $toDelete = array_slice($files, 0, max(0, count($files) - 7));

            foreach ($toDelete as $file) {
                Storage::disk('b2')->delete($file);
            }

            if (count($toDelete) > 0) {
                $this->line("  Cleaned up " . count($toDelete) . " old database backup(s)");
            }
        } catch (\Exception $e) {
            $this->warn("  Could not clean up old backups: " . $e->getMessage());
        }
    }
}
