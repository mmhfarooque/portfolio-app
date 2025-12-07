<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\Tag;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        // Photo statistics
        $totalPhotos = Photo::count();
        $publishedPhotos = Photo::published()->count();
        $draftPhotos = Photo::draft()->count();

        // Photos by category
        $photosByCategory = Category::withCount('photos')
            ->orderByDesc('photos_count')
            ->take(5)
            ->get();

        // Total categories, galleries, tags
        $totalCategories = Category::count();
        $totalGalleries = Gallery::count();
        $totalTags = Tag::count();

        // Storage usage
        $storageUsed = $this->calculateStorageUsage();

        // Recent photos (last 5)
        $recentPhotos = Photo::with('category')
            ->latest()
            ->take(5)
            ->get();

        // Recent activity from logs
        $recentActivity = ActivityLog::latest()
            ->take(10)
            ->get();

        // Photos uploaded this month
        $photosThisMonth = Photo::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Photos uploaded last month
        $photosLastMonth = Photo::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        // Calculate growth percentage
        $growthPercent = $photosLastMonth > 0
            ? round((($photosThisMonth - $photosLastMonth) / $photosLastMonth) * 100, 1)
            : ($photosThisMonth > 0 ? 100 : 0);

        return view('dashboard', compact(
            'totalPhotos',
            'publishedPhotos',
            'draftPhotos',
            'photosByCategory',
            'totalCategories',
            'totalGalleries',
            'totalTags',
            'storageUsed',
            'recentPhotos',
            'recentActivity',
            'photosThisMonth',
            'photosLastMonth',
            'growthPercent'
        ));
    }

    /**
     * Calculate total storage used by photos
     */
    protected function calculateStorageUsage(): array
    {
        $photosPath = storage_path('app/public/photos');
        $totalBytes = 0;

        if (is_dir($photosPath)) {
            $totalBytes = $this->getDirectorySize($photosPath);
        }

        return [
            'bytes' => $totalBytes,
            'formatted' => $this->formatBytes($totalBytes),
            'mb' => round($totalBytes / 1024 / 1024, 2),
            'gb' => round($totalBytes / 1024 / 1024 / 1024, 2),
        ];
    }

    /**
     * Get directory size recursively
     */
    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Format bytes to human readable string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
