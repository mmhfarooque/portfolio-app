<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    protected LoggingService $logger;

    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Download a photo in the specified format.
     */
    public function download(Request $request, Photo $photo, string $format = 'webp')
    {
        // Only allow published photos to be downloaded
        if ($photo->status !== 'published') {
            abort(404);
        }

        // Rate limiting: 10 downloads per hour per IP
        $ip = $request->ip();
        $cacheKey = "download_limit_{$ip}";
        $downloads = Cache::get($cacheKey, 0);

        if ($downloads >= 10) {
            return response()->json([
                'error' => 'Download limit exceeded. Please try again later.',
                'retry_after' => Cache::get("{$cacheKey}_expires", 3600)
            ], 429);
        }

        // Increment download count
        Cache::put($cacheKey, $downloads + 1, now()->addHour());
        Cache::put("{$cacheKey}_expires", 3600, now()->addHour());

        // Get the image path (use watermarked version for protection)
        $imagePath = $photo->watermarked_path ?? $photo->display_path;
        $fullPath = storage_path('app/public/' . $imagePath);

        if (!file_exists($fullPath)) {
            abort(404, 'Image not found');
        }

        // Generate filename
        $filename = $this->generateFilename($photo, $format);

        // Log the download
        $this->logger->logActivity('photo_downloaded', 'info', [
            'photo_id' => $photo->id,
            'photo_title' => $photo->title,
            'format' => $format,
            'ip' => $ip,
        ], $photo);

        // Handle different formats
        if ($format === 'webp') {
            return $this->downloadWebp($fullPath, $filename);
        } elseif ($format === 'jpeg' || $format === 'jpg') {
            return $this->downloadJpeg($fullPath, $filename);
        } else {
            abort(400, 'Invalid format');
        }
    }

    /**
     * Download as WebP (original format).
     */
    protected function downloadWebp(string $path, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($path) {
            readfile($path);
        }, $filename, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Download as JPEG (converted from WebP).
     */
    protected function downloadJpeg(string $path, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($path) {
            $image = Image::read($path);
            echo $image->toJpeg(90)->toString();
        }, $filename, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate a clean filename for download.
     */
    protected function generateFilename(Photo $photo, string $format): string
    {
        $slug = $photo->slug ?? str($photo->title)->slug();
        $extension = $format === 'jpeg' ? 'jpg' : $format;
        return "{$slug}.{$extension}";
    }

    /**
     * Show download options page.
     */
    public function options(Photo $photo): \Inertia\Response
    {
        if ($photo->status !== 'published') {
            abort(404);
        }

        return \Inertia\Inertia::render('Public/Download/Options', [
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
                'display_path' => $photo->display_path,
            ],
        ]);
    }
}
