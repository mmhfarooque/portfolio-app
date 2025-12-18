<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Services\PhotoProcessingService;
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

        try {
            // Verify temp file still exists
            if (!file_exists($this->tempFilePath)) {
                throw new \Exception("Temp file not found: {$this->tempFilePath}");
            }

            LoggingService::debug('photo.queue_processing', "Processing queued photo: {$this->photo->id}");

            // Update status to show processing is active
            $this->photo->update(['processing_stage' => 'reading_image']);

            // Read the image (may have been converted from HEIC)
            $image = \Intervention\Image\Laravel\Facades\Image::read($this->tempFilePath);

            // Generate image versions
            $this->photo->update(['processing_stage' => 'generating_versions']);
            $this->generateImageVersions($photoService, $image);

            // Generate and store image hashes for duplicate detection
            $this->photo->update(['processing_stage' => 'generating_hashes']);
            $this->generateHashes($photoService);

            // Apply AI analysis
            $this->photo->update(['processing_stage' => 'ai_analysis']);
            $this->applyAIAnalysis($photoService);

            // Clean up temp file
            if (file_exists($this->tempFilePath)) {
                @unlink($this->tempFilePath);
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
            // Mark photo as failed
            $this->photo->update([
                'status' => 'failed',
                'processing_stage' => 'error',
                'processing_error' => $e->getMessage(),
            ]);

            // Clean up temp file
            if (file_exists($this->tempFilePath)) {
                @unlink($this->tempFilePath);
            }

            LoggingService::photoUploadFailed(
                $this->originalFilename,
                $this->photo->file_size ?? 0,
                $e
            );

            throw $e;
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
    protected function generateHashes(PhotoProcessingService $photoService): void
    {
        $hashService = new \App\Services\ImageHashService();
        $hashes = $hashService->generateHashes($this->tempFilePath);

        $this->photo->update([
            'file_hash' => $hashes['file_hash'],
            'image_hash' => $hashes['image_hash'],
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
