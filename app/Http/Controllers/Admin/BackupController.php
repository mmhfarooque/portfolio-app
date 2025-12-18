<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Services\LoggingService;

class BackupController extends Controller
{
    /**
     * Display backup settings and status.
     */
    public function index()
    {
        $isConfigured = config('filesystems.disks.b2.key') && config('filesystems.disks.b2.bucket');
        $lastBackupAt = Setting::get('last_backup_at');
        $lastBackupStats = Setting::get('last_backup_stats');

        if ($lastBackupStats) {
            $lastBackupStats = json_decode($lastBackupStats, true);
        }

        // Get storage usage
        $storageStats = $this->getStorageStats();

        return view('admin.settings.backup', compact(
            'isConfigured',
            'lastBackupAt',
            'lastBackupStats',
            'storageStats'
        ));
    }

    /**
     * Run backup manually.
     */
    public function runBackup(Request $request)
    {
        // Check if B2 is configured
        if (!config('filesystems.disks.b2.key') || !config('filesystems.disks.b2.bucket')) {
            return response()->json([
                'success' => false,
                'message' => 'Backblaze B2 is not configured. Please add B2 credentials to your .env file.',
            ], 400);
        }

        $includeDatabase = $request->boolean('include_database', true);

        try {
            // Run backup in background using job queue would be better for large backups
            // For now, run synchronously with timeout
            set_time_limit(600);

            $exitCode = Artisan::call('backup:photos', [
                '--database' => $includeDatabase,
            ]);

            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup completed successfully!',
                    'output' => $output,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup completed with errors.',
                    'output' => $output,
                ], 500);
            }

        } catch (\Exception $e) {
            LoggingService::error('backup.manual_failed', 'Manual backup failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test B2 connection.
     */
    public function testConnection()
    {
        if (!config('filesystems.disks.b2.key') || !config('filesystems.disks.b2.bucket')) {
            return response()->json([
                'success' => false,
                'message' => 'Backblaze B2 is not configured.',
            ], 400);
        }

        try {
            // Test by listing files (or creating a test file)
            Storage::disk('b2')->put('_connection_test.txt', 'test-' . time());
            Storage::disk('b2')->delete('_connection_test.txt');

            return response()->json([
                'success' => true,
                'message' => 'Connection to Backblaze B2 successful!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of remote backups.
     */
    public function listBackups()
    {
        if (!config('filesystems.disks.b2.key') || !config('filesystems.disks.b2.bucket')) {
            return response()->json([
                'success' => false,
                'message' => 'Backblaze B2 is not configured.',
            ], 400);
        }

        try {
            $backups = [];

            // List database backups
            $databaseBackups = Storage::disk('b2')->files('backup/database');
            foreach ($databaseBackups as $file) {
                $backups[] = [
                    'type' => 'database',
                    'path' => $file,
                    'name' => basename($file),
                    'size' => Storage::disk('b2')->size($file),
                    'last_modified' => Storage::disk('b2')->lastModified($file),
                ];
            }

            // Count photo backups
            $photoCount = count(Storage::disk('b2')->files('backup/photos/display'));
            $thumbnailCount = count(Storage::disk('b2')->files('backup/photos/thumbnails'));

            return response()->json([
                'success' => true,
                'database_backups' => $backups,
                'photo_stats' => [
                    'display_count' => $photoCount,
                    'thumbnail_count' => $thumbnailCount,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list backups: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get local storage statistics.
     */
    protected function getStorageStats(): array
    {
        $stats = [
            'photos' => 0,
            'thumbnails' => 0,
            'watermarked' => 0,
            'total_size' => 0,
            'file_count' => 0,
        ];

        try {
            $directories = [
                'photos' => 'photos/display',
                'thumbnails' => 'photos/thumbnails',
                'watermarked' => 'photos/watermarked',
            ];

            foreach ($directories as $key => $dir) {
                $files = Storage::disk('public')->files($dir);
                $size = 0;
                foreach ($files as $file) {
                    $size += Storage::disk('public')->size($file);
                }
                $stats[$key] = $size;
                $stats['total_size'] += $size;
                $stats['file_count'] += count($files);
            }

            // Format sizes
            $stats['photos_formatted'] = $this->formatBytes($stats['photos']);
            $stats['thumbnails_formatted'] = $this->formatBytes($stats['thumbnails']);
            $stats['watermarked_formatted'] = $this->formatBytes($stats['watermarked']);
            $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);

        } catch (\Exception $e) {
            // Silently fail
        }

        return $stats;
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
