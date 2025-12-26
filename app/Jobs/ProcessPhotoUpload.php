<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Services\PhotoProcessingService;
use App\Services\BlurHashService;
use App\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessPhotoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Photo $photo,
        public string $tempFilePath,
        public string $originalFilename
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PhotoProcessingService $photoService): void
    {
        $startTime = microtime(true);
        $filePath = $this->tempFilePath;

        try {
            // Check if images already exist (from a previous partial attempt)
            $this->photo->refresh();
            if ($this->photo->display_path && $this->photo->thumbnail_path) {
                // Images already generated - just mark as complete
                $this->photo->update([
                    'status' => 'draft',
                    'processing_stage' => null,
                    'processing_error' => null,
                ]);

                // Clean up temp file if it exists
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }

                LoggingService::debug('photo.queue_processing', "Photo {$this->photo->id} already processed, marking complete");
                return;
            }

            // Verify temp file still exists
            if (!file_exists($filePath)) {
                throw new \Exception("Temp file not found: {$filePath}");
            }

            LoggingService::debug('photo.queue_processing', "Processing queued photo: {$this->photo->id}");

            // Handle HEIC/HEIF conversion first
            $this->photo->update(['processing_stage' => 'converting']);
            $mimeType = $this->photo->mime_type;
            $isHeic = in_array($mimeType, ['image/heic', 'image/heif']) ||
                      in_array(strtolower(pathinfo($this->originalFilename, PATHINFO_EXTENSION)), ['heic', 'heif']);

            if ($isHeic) {
                $convertedPath = $this->convertHeicToJpeg($filePath);
                if ($convertedPath) {
                    @unlink($filePath);
                    $filePath = $convertedPath;
                    LoggingService::debug('photo.heic_converted', "Converted HEIC to JPEG: {$this->originalFilename}");
                }
            }

            // Extract EXIF data
            $this->photo->update(['processing_stage' => 'extracting_metadata']);
            $exifData = $this->extractExifData($filePath);
            $gpsData = $this->extractGpsCoordinates($filePath);

            // Reverse geocode location
            $locationName = null;
            if ($gpsData['latitude'] && $gpsData['longitude']) {
                $locationName = $photoService->reverseGeocode($gpsData['latitude'], $gpsData['longitude']);
            }

            // Read image for dimensions
            $this->photo->update(['processing_stage' => 'reading_image']);
            $image = \Intervention\Image\Laravel\Facades\Image::read($filePath);
            $width = $image->width();
            $height = $image->height();

            // Generate blur placeholder for LQIP
            $this->photo->update(['processing_stage' => 'generating_placeholder']);
            $this->generatePlaceholder($filePath);

            // Generate and store image hashes for duplicate detection BEFORE any file operations
            $this->photo->update(['processing_stage' => 'generating_hashes']);
            if (file_exists($filePath)) {
                $this->generateHashes($filePath);
            }

            // Update photo with extracted metadata
            $this->photo->update([
                'width' => $width,
                'height' => $height,
                'exif_data' => $exifData,
                'latitude' => $gpsData['latitude'],
                'longitude' => $gpsData['longitude'],
                'location_name' => $locationName,
                'captured_at' => $this->getCaptureDate($exifData),
            ]);

            // Generate image versions
            $this->photo->update(['processing_stage' => 'generating_versions']);
            $this->generateImageVersions($photoService, $image);

            // Apply AI analysis (only if AI is enabled)
            $aiService = new \App\Services\AIImageService();
            if ($aiService->isEnabled()) {
                $this->photo->update(['processing_stage' => 'ai_analysis']);
                $this->applyAIAnalysis($photoService);
            }

            // Clean up temp file
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            // Update status to draft (processing complete)
            $this->photo->update([
                'status' => 'draft',
                'processing_stage' => null,
            ]);

            // Log successful processing
            $duration = round((microtime(true) - $startTime) * 1000);
            LoggingService::photoUploaded(
                $this->photo,
                $this->originalFilename,
                $this->photo->file_size,
                $duration
            );

        } catch (\Throwable $e) {
            // Update processing error but DON'T mark as failed yet (allow retries)
            // DON'T delete temp file - retries need it
            $this->photo->update([
                'processing_stage' => 'error',
                'processing_error' => $e->getMessage(),
            ]);

            LoggingService::photoUploadFailed(
                $this->originalFilename,
                $e,
                ['file_size' => $this->photo->file_size ?? 0, 'attempt' => $this->attempts()]
            );

            throw $e;
        }
    }

    /**
     * Convert HEIC/HEIF to JPEG.
     */
    protected function convertHeicToJpeg(string $filePath): ?string
    {
        $outputPath = $filePath . '.jpg';
        $command = sprintf(
            'magick %s -quality 95 %s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($outputPath)
        );
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputPath)) {
            return $outputPath;
        }
        return null;
    }

    /**
     * Extract EXIF data from image.
     */
    protected function extractExifData(string $filePath): ?array
    {
        try {
            $exif = @exif_read_data($filePath, 'ANY_TAG', true);
            if (!$exif) return null;

            return [
                'Make' => $exif['IFD0']['Make'] ?? null,
                'Model' => $exif['IFD0']['Model'] ?? null,
                'ExposureTime' => $exif['EXIF']['ExposureTime'] ?? null,
                'FNumber' => isset($exif['EXIF']['FNumber']) ? $this->evalFraction($exif['EXIF']['FNumber']) : null,
                'ISO' => $exif['EXIF']['ISOSpeedRatings'] ?? null,
                'FocalLength' => isset($exif['EXIF']['FocalLength']) ? $this->evalFraction($exif['EXIF']['FocalLength']) : null,
                'DateTimeOriginal' => $exif['EXIF']['DateTimeOriginal'] ?? null,
                'LensModel' => $exif['EXIF']['UndefinedTag:0xA434'] ?? $exif['EXIF']['LensModel'] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract GPS coordinates from image.
     */
    protected function extractGpsCoordinates(string $filePath): array
    {
        $result = ['latitude' => null, 'longitude' => null];
        try {
            $exif = @exif_read_data($filePath, 'GPS', true);
            if (!$exif || !isset($exif['GPS'])) return $result;

            $gps = $exif['GPS'];
            if (isset($gps['GPSLatitude'], $gps['GPSLatitudeRef'], $gps['GPSLongitude'], $gps['GPSLongitudeRef'])) {
                $result['latitude'] = $this->gpsToDecimal($gps['GPSLatitude'], $gps['GPSLatitudeRef']);
                $result['longitude'] = $this->gpsToDecimal($gps['GPSLongitude'], $gps['GPSLongitudeRef']);
            }
        } catch (\Exception $e) {}
        return $result;
    }

    protected function gpsToDecimal(array $coordinate, string $hemisphere): float
    {
        $degrees = $this->evalFraction($coordinate[0]);
        $minutes = $this->evalFraction($coordinate[1]);
        $seconds = $this->evalFraction($coordinate[2]);
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        return ($hemisphere === 'S' || $hemisphere === 'W') ? -$decimal : $decimal;
    }

    protected function evalFraction($value): float
    {
        if (is_numeric($value)) return (float) $value;
        if (is_string($value) && str_contains($value, '/')) {
            $parts = explode('/', $value);
            return count($parts) === 2 && $parts[1] != 0 ? $parts[0] / $parts[1] : 0;
        }
        return 0;
    }

    protected function getCaptureDate(?array $exifData): ?\DateTime
    {
        if (!$exifData || empty($exifData['DateTimeOriginal'])) return null;
        try {
            return new \DateTime($exifData['DateTimeOriginal']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate all image versions.
     */
    protected function generateImageVersions(PhotoProcessingService $photoService, $image): void
    {
        $filename = \Illuminate\Support\Str::uuid() . '.webp';

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($photoService);
        $method = $reflection->getMethod('generateImageVersions');
        $method->setAccessible(true);
        $method->invoke($photoService, $this->photo, $image, $filename);
    }

    /**
     * Generate image hashes for duplicate detection.
     */
    protected function generateHashes(string $filePath): void
    {
        $hashService = new \App\Services\ImageHashService();
        $hashes = $hashService->generateHashes($filePath);

        $this->photo->update([
            'file_hash' => $hashes['file_hash'],
            'image_hash' => $hashes['image_hash'],
        ]);
    }

    /**
     * Generate blur placeholder for LQIP (Low Quality Image Placeholder).
     */
    protected function generatePlaceholder(string $filePath): void
    {
        $blurHashService = new BlurHashService();
        $placeholder = $blurHashService->generatePlaceholder($filePath);

        $this->photo->update([
            'dominant_color' => $placeholder['dominant_color'],
        ]);
    }

    /**
     * Apply AI analysis.
     */
    protected function applyAIAnalysis(PhotoProcessingService $photoService): void
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($photoService);
        $method = $reflection->getMethod('applyAIAnalysis');
        $method->setAccessible(true);
        $method->invoke($photoService, $this->photo);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Mark photo as failed
        $this->photo->update([
            'status' => 'failed',
            'processing_stage' => 'error',
            'processing_error' => $exception->getMessage(),
        ]);

        // Clean up temp file
        if (file_exists($this->tempFilePath)) {
            @unlink($this->tempFilePath);
        }

        LoggingService::error(
            'photo.queue_failed',
            "Photo processing job failed: {$exception->getMessage()}",
            $this->photo
        );
    }
}
