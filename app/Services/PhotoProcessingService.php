<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Interfaces\ImageInterface;
use App\Services\LoggingService;
use App\Services\AIImageService;
use App\Services\ImageHashService;

class PhotoProcessingService
{
    // Thumbnail configuration (fixed)
    protected int $thumbnailWidth = 400;
    protected int $thumbnailQuality = 80;

    /**
     * Get display settings from database.
     */
    protected function getDisplaySettings(): array
    {
        return [
            'max_dimension' => (int) Setting::get('image_max_resolution', 1920),
            'quality' => (int) Setting::get('image_quality', 92),
        ];
    }

    // Watermark settings
    protected array $watermarkSettings = [
        'text' => '© Photography Portfolio',
        'opacity' => 40,
        'position' => 'bottom-right',
        'padding' => 20,
        'fontSize' => 24,
    ];

    protected ImageHashService $hashService;

    public function __construct()
    {
        $this->hashService = new ImageHashService();
    }

    /**
     * Check if R2 cloud storage is configured and enabled.
     */
    protected function isR2Enabled(): bool
    {
        return !empty(config('filesystems.disks.r2.key')) &&
               !empty(config('filesystems.disks.r2.secret')) &&
               !empty(config('filesystems.disks.r2.endpoint'));
    }

    /**
     * Upload a file to R2 cloud storage.
     * Returns the R2 key (path) on success, null on failure.
     */
    protected function uploadToR2(string $localPath, string $r2Key): ?string
    {
        if (!$this->isR2Enabled()) {
            LoggingService::debug('r2.not_enabled', 'R2 is not configured, skipping cloud upload');
            return null;
        }

        try {
            $contents = file_get_contents($localPath);
            if ($contents === false) {
                LoggingService::error('r2.read_failed', "Failed to read local file: {$localPath}");
                return null;
            }

            Storage::disk('r2')->put($r2Key, $contents);

            LoggingService::debug('r2.uploaded', "Uploaded to R2: {$r2Key}");
            return $r2Key;
        } catch (\Exception $e) {
            LoggingService::error('r2.upload_failed', "R2 upload failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Download a file from R2 to a local temp path.
     * Returns the temp file path on success, null on failure.
     */
    protected function downloadFromR2(string $r2Key): ?string
    {
        if (!$this->isR2Enabled()) {
            return null;
        }

        try {
            if (!Storage::disk('r2')->exists($r2Key)) {
                LoggingService::debug('r2.not_found', "File not found in R2: {$r2Key}");
                return null;
            }

            $contents = Storage::disk('r2')->get($r2Key);
            if ($contents === null) {
                return null;
            }

            // Create temp file with appropriate extension
            $extension = pathinfo($r2Key, PATHINFO_EXTENSION);
            $tempPath = sys_get_temp_dir() . '/' . Str::uuid() . '.' . $extension;

            if (file_put_contents($tempPath, $contents) === false) {
                LoggingService::error('r2.write_failed', "Failed to write temp file: {$tempPath}");
                return null;
            }

            LoggingService::debug('r2.downloaded', "Downloaded from R2: {$r2Key} -> {$tempPath}");
            return $tempPath;
        } catch (\Exception $e) {
            LoggingService::error('r2.download_failed', "R2 download failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a file from R2 cloud storage.
     */
    protected function deleteFromR2(string $r2Key): bool
    {
        if (!$this->isR2Enabled()) {
            return false;
        }

        try {
            if (Storage::disk('r2')->exists($r2Key)) {
                Storage::disk('r2')->delete($r2Key);
                LoggingService::debug('r2.deleted', "Deleted from R2: {$r2Key}");
                return true;
            }
            return false;
        } catch (\Exception $e) {
            LoggingService::error('r2.delete_failed', "R2 delete failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a file exists in R2.
     */
    protected function existsInR2(string $r2Key): bool
    {
        if (!$this->isR2Enabled()) {
            return false;
        }

        try {
            return Storage::disk('r2')->exists($r2Key);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if an uploaded file is a duplicate.
     * Returns the existing photo if duplicate found, null otherwise.
     */
    public function checkForDuplicate(UploadedFile $file, int $threshold = 5): ?Photo
    {
        $filePath = $file->getRealPath();

        // Handle HEIC files - convert first for accurate hash
        $mimeType = $file->getMimeType();
        $isHeic = in_array($mimeType, ['image/heic', 'image/heif']) ||
                  in_array(strtolower($file->getClientOriginalExtension()), ['heic', 'heif']);

        $tempPath = null;
        if ($isHeic) {
            $tempPath = $this->convertHeicToJpeg($filePath);
            if ($tempPath) {
                $filePath = $tempPath;
            }
        }

        $duplicate = $this->hashService->findDuplicate($filePath, $threshold);

        // Clean up temp file
        if ($tempPath && file_exists($tempPath)) {
            @unlink($tempPath);
        }

        return $duplicate;
    }

    /**
     * Check multiple files for duplicates.
     * Returns array with 'duplicates' and 'valid' keys.
     */
    public function checkFilesForDuplicates(array $files, int $threshold = 5): array
    {
        $duplicates = [];
        $valid = [];

        foreach ($files as $index => $file) {
            $duplicate = $this->checkForDuplicate($file, $threshold);
            if ($duplicate) {
                $duplicates[] = [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'existing_photo' => [
                        'id' => $duplicate->id,
                        'title' => $duplicate->title,
                        'thumbnail' => $duplicate->thumbnail_path,
                    ],
                ];
            } else {
                $valid[] = $file;
            }
        }

        return [
            'duplicates' => $duplicates,
            'valid' => $valid,
        ];
    }

    /**
     * Quick upload - creates photo record and prepares for background processing.
     * Returns the photo and temp file path for the queue job.
     */
    public function quickUpload(UploadedFile $file, int $userId, ?int $categoryId = null): array
    {
        $originalFilename = $file->getClientOriginalName();

        try {
            // Generate unique slug (quick - no image processing)
            $baseTitle = pathinfo($originalFilename, PATHINFO_FILENAME);
            $baseSlug = Str::slug($baseTitle);
            $slug = $baseSlug;
            $counter = 1;
            while (Photo::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Create minimal photo record with 'processing' status
            // All heavy processing (EXIF, dimensions, thumbnails) happens in background job
            $photo = Photo::create([
                'title' => $baseTitle,
                'slug' => $slug,
                'original_filename' => $originalFilename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'user_id' => $userId,
                'category_id' => $categoryId,
                'status' => 'processing',
                'processing_stage' => 'queued',
            ]);

            // Save temp file for background processing
            $tempPath = storage_path('app/private/temp/' . Str::uuid() . '.tmp');
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            // Move file to temp location (faster than copy for large files)
            $file->move(dirname($tempPath), basename($tempPath));

            LoggingService::debug('photo.quick_upload', "Photo queued for processing: {$photo->id}");

            return [
                'photo' => $photo,
                'temp_path' => $tempPath,
                'original_filename' => $originalFilename,
            ];
        } catch (\Throwable $e) {
            LoggingService::photoUploadFailed($originalFilename, $e, ['file_size' => $file->getSize()]);
            throw $e;
        }
    }

    /**
     * Process an uploaded photo file (synchronous - full processing).
     */
    public function processUpload(UploadedFile $file, int $userId, ?int $categoryId = null): Photo
    {
        $startTime = microtime(true);
        $originalFilename = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        try {
            // Generate unique filename
            $extension = $this->getOutputExtension($file);
            $filename = Str::uuid() . '.' . $extension;

            // Handle HEIC/HEIF files - convert to JPEG first
            $filePath = $file->getRealPath();
            $mimeType = $file->getMimeType();
            $isHeic = in_array($mimeType, ['image/heic', 'image/heif']) ||
                      in_array(strtolower($file->getClientOriginalExtension()), ['heic', 'heif']);

            $tempJpegPath = null;
            if ($isHeic) {
                $tempJpegPath = $this->convertHeicToJpeg($filePath);
                if ($tempJpegPath) {
                    $filePath = $tempJpegPath;
                    LoggingService::debug('photo.heic_converted', "Converted HEIC to JPEG: {$originalFilename}");
                }
            }

            // NOTE: We do NOT save the original file to save server space.
            // Only optimized display, thumbnail, and watermarked versions are stored.

            // Get image dimensions and file info
            $image = Image::read($filePath);
            $width = $image->width();
            $height = $image->height();

            // Extract EXIF data
            $exifData = $this->extractExifData($file->getRealPath());

            // Extract GPS coordinates
            $gpsData = $this->extractGpsCoordinates($file->getRealPath());

            // Reverse geocode to get location name if GPS data is available
            $locationName = null;
            if ($gpsData['latitude'] && $gpsData['longitude']) {
                $locationName = $this->reverseGeocode($gpsData['latitude'], $gpsData['longitude']);
            }

            // Store original file for future re-optimization
            $originalUuid = Str::uuid();
            $originalExtension = $isHeic ? 'jpg' : strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            $originalStoragePath = 'originals/' . $originalUuid . '.' . $originalExtension;

            // Upload to R2 cloud storage (preferred) or fall back to local storage
            $sourceForOriginal = $tempJpegPath ?? $file->getRealPath();
            $r2Uploaded = false;

            if ($this->isR2Enabled()) {
                // Upload to R2 - no local copy needed
                $r2Key = $this->uploadToR2($sourceForOriginal, $originalStoragePath);
                if ($r2Key) {
                    $r2Uploaded = true;
                    // Prefix path with 'r2:' to indicate cloud storage
                    $originalStoragePath = 'r2:' . $originalStoragePath;
                    LoggingService::debug('photo.original_to_r2', "Original uploaded to R2: {$originalStoragePath}");
                }
            }

            // Fall back to local storage if R2 not available or failed
            if (!$r2Uploaded) {
                $localPath = 'photos/originals/' . $originalUuid . '.' . $originalExtension;
                Storage::disk('local')->put($localPath, file_get_contents($sourceForOriginal));
                $originalStoragePath = $localPath;
                LoggingService::debug('photo.original_to_local', "Original stored locally: {$originalStoragePath}");
            }

            // Create photo record with original path
            $baseTitle = pathinfo($originalFilename, PATHINFO_FILENAME);
            $baseSlug = Str::slug($baseTitle);

            // Generate unique slug by appending number if needed
            $slug = $baseSlug;
            $counter = 1;
            while (Photo::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $photo = Photo::create([
                'title' => $baseTitle,
                'slug' => $slug,
                'original_filename' => $originalFilename,
                'original_path' => $originalStoragePath,
                'original_width' => $width,
                'original_height' => $height,
                'width' => $width,
                'height' => $height,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'exif_data' => $exifData,
                'latitude' => $gpsData['latitude'],
                'longitude' => $gpsData['longitude'],
                'location_name' => $locationName,
                'user_id' => $userId,
                'category_id' => $categoryId,
                'status' => 'processing',
                'captured_at' => $this->getCaptureDate($exifData),
            ]);

            // Generate different sizes
            $this->generateImageVersions($photo, $image, $filename);

            // Generate and store image hashes for duplicate detection
            $hashes = $this->hashService->generateHashes($filePath);
            $photo->update([
                'file_hash' => $hashes['file_hash'],
                'image_hash' => $hashes['image_hash'],
            ]);

            // Clean up temp file if we converted HEIC
            if ($tempJpegPath && file_exists($tempJpegPath)) {
                @unlink($tempJpegPath);
            }

            // AI-powered title and description generation
            $this->applyAIAnalysis($photo);

            // Update status to draft
            $photo->update(['status' => 'draft']);

            // Log successful upload
            $duration = round((microtime(true) - $startTime) * 1000);
            LoggingService::photoUploaded($photo, $originalFilename, $fileSize, $duration);

            return $photo;
        } catch (\Throwable $e) {
            // Log failed upload
            LoggingService::photoUploadFailed($originalFilename, $fileSize, $e);
            throw $e;
        }
    }

    /**
     * Convert HEIC/HEIF file to JPEG using ImageMagick.
     */
    protected function convertHeicToJpeg(string $heicPath): ?string
    {
        $tempPath = sys_get_temp_dir() . '/' . Str::uuid() . '.jpg';

        // Use ImageMagick convert command
        $command = sprintf(
            'convert %s -quality 95 %s 2>&1',
            escapeshellarg($heicPath),
            escapeshellarg($tempPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($tempPath)) {
            return $tempPath;
        }

        // Log error if conversion failed
        \Log::warning('HEIC conversion failed', [
            'path' => $heicPath,
            'output' => implode("\n", $output),
            'return_code' => $returnCode,
        ]);

        return null;
    }

    /**
     * Generate all image versions (display, thumbnail, watermarked).
     * Display/Watermarked: AVIF format for best compression (30-50% smaller than WebP)
     * Thumbnails: WebP format for maximum compatibility (small files anyway)
     */
    protected function generateImageVersions(Photo $photo, ImageInterface $image, string $filename): void
    {
        // Get settings from database
        $displaySettings = $this->getDisplaySettings();

        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $avifFilename = $baseName . '.avif';
        $webpFilename = $baseName . '.webp';

        // AVIF quality mapping: WebP 85-92 ≈ AVIF 60-70
        $avifQuality = $this->mapWebpToAvifQuality($displaySettings['quality']);

        // Generate display version (AVIF - smaller files)
        $displayPath = $this->generateResizedImageAvif(
            $image,
            $avifFilename,
            'photos/display',
            $displaySettings['max_dimension'],
            $avifQuality
        );
        $photo->update(['display_path' => $displayPath]);

        // Generate thumbnail (WebP - maximum compatibility, already small)
        $thumbnailPath = $this->generateResizedImage(
            $image,
            $webpFilename,
            'photos/thumbnails',
            $this->thumbnailWidth,
            $this->thumbnailQuality
        );
        $photo->update(['thumbnail_path' => $thumbnailPath]);

        // Generate watermarked version (AVIF - smaller files)
        $watermarkedPath = $this->generateWatermarkedImageAvif(
            $image,
            $avifFilename,
            'photos/watermarked',
            $displaySettings['max_dimension'],
            $avifQuality
        );
        $photo->update(['watermarked_path' => $watermarkedPath]);
    }

    /**
     * Map WebP quality to AVIF quality.
     * AVIF is more efficient but needs higher quality numbers for photography.
     * WebP 85 ≈ AVIF 75, WebP 92 ≈ AVIF 80, WebP 100 ≈ AVIF 85
     */
    protected function mapWebpToAvifQuality(int $webpQuality): int
    {
        // Photography-optimized mapping: WebP 80-100 -> AVIF 70-85
        // Higher quality to preserve gradients and fine details
        $avifQuality = (int) (70 + (($webpQuality - 80) / 20) * 15);
        return max(65, min(85, $avifQuality)); // Clamp between 65-85
    }

    /**
     * Generate a resized image version (WebP format).
     * For landscape images: max width = maxDimension
     * For portrait images: max height = maxDimension
     */
    protected function generateResizedImage(
        ImageInterface $image,
        string $filename,
        string $directory,
        int $maxDimension,
        int $quality
    ): string {
        $resized = clone $image;
        $width = $resized->width();
        $height = $resized->height();

        // Determine if landscape or portrait and resize accordingly
        if ($width >= $height) {
            // Landscape or square: constrain by width
            if ($width > $maxDimension) {
                $resized->scale(width: $maxDimension);
            }
        } else {
            // Portrait: constrain by height
            if ($height > $maxDimension) {
                $resized->scale(height: $maxDimension);
            }
        }

        // Ensure directory exists
        $storagePath = storage_path('app/public/' . $directory);
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Save the image as WebP for better compression
        $path = $directory . '/' . $filename;
        $fullPath = storage_path('app/public/' . $path);
        $resized->toWebp($quality)->save($fullPath);

        return $path;
    }

    /**
     * Generate a resized image version (AVIF format - best compression).
     * For landscape images: max width = maxDimension
     * For portrait images: max height = maxDimension
     */
    protected function generateResizedImageAvif(
        ImageInterface $image,
        string $filename,
        string $directory,
        int $maxDimension,
        int $quality
    ): string {
        $resized = clone $image;
        $width = $resized->width();
        $height = $resized->height();

        // Determine if landscape or portrait and resize accordingly
        if ($width >= $height) {
            if ($width > $maxDimension) {
                $resized->scale(width: $maxDimension);
            }
        } else {
            if ($height > $maxDimension) {
                $resized->scale(height: $maxDimension);
            }
        }

        // Ensure directory exists
        $storagePath = storage_path('app/public/' . $directory);
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Save the image as AVIF for optimal compression (30-50% smaller than WebP)
        $path = $directory . '/' . $filename;
        $fullPath = storage_path('app/public/' . $path);
        $resized->toAvif($quality)->save($fullPath);

        return $path;
    }

    /**
     * Generate a watermarked image version (WebP format).
     * For landscape images: max width = maxDimension
     * For portrait images: max height = maxDimension
     */
    protected function generateWatermarkedImage(
        ImageInterface $image,
        string $filename,
        string $directory,
        int $maxDimension,
        int $quality
    ): string {
        $watermarked = clone $image;
        $width = $watermarked->width();
        $height = $watermarked->height();

        // Determine if landscape or portrait and resize accordingly
        if ($width >= $height) {
            // Landscape or square: constrain by width
            if ($width > $maxDimension) {
                $watermarked->scale(width: $maxDimension);
            }
        } else {
            // Portrait: constrain by height
            if ($height > $maxDimension) {
                $watermarked->scale(height: $maxDimension);
            }
        }

        // Get watermark settings from database or use defaults
        $watermarkEnabled = Setting::get('watermark_enabled', '1') === '1';
        $watermarkType = Setting::get('watermark_type', 'text');
        $watermarkText = Setting::get('watermark_text', $this->watermarkSettings['text']);
        $watermarkImage = Setting::get('watermark_image', '');
        $watermarkPosition = Setting::get('watermark_position', 'bottom-right');
        $watermarkOpacity = (int) Setting::get('watermark_opacity', '40');
        $watermarkSize = (int) Setting::get('watermark_size', '24');
        $watermarkImageSize = (int) Setting::get('watermark_image_size', '15');

        // Check if watermark should be applied
        $shouldApplyWatermark = $watermarkEnabled && (
            ($watermarkType === 'text' && !empty($watermarkText)) ||
            ($watermarkType === 'image' && !empty($watermarkImage))
        );

        if (!$shouldApplyWatermark) {
            // Just save without watermark (WebP)
            $storagePath = storage_path('app/public/' . $directory);
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }
            $path = $directory . '/' . $filename;
            $fullPath = storage_path('app/public/' . $path);
            $watermarked->toWebp($quality)->save($fullPath);
            return $path;
        }

        // Calculate position based on setting
        $padding = $this->watermarkSettings['padding'];
        list($x, $y, $align, $valign) = $this->getWatermarkPosition(
            $watermarked->width(),
            $watermarked->height(),
            $watermarkPosition,
            $padding
        );

        // Calculate opacity for rgba (0-100 to 0-1)
        $opacity = $watermarkOpacity / 100;

        if ($watermarkType === 'image' && !empty($watermarkImage)) {
            // Apply image watermark
            $watermarkPath = storage_path('app/public/' . $watermarkImage);
            if (file_exists($watermarkPath)) {
                $watermarkImg = Image::read($watermarkPath);

                // Calculate watermark size as percentage of main image width
                $targetWidth = (int) ($watermarked->width() * ($watermarkImageSize / 100));
                if ($watermarkImg->width() > $targetWidth) {
                    $watermarkImg->scale(width: $targetWidth);
                }

                // Apply opacity
                $watermarkImg->brightness((int) (($opacity - 1) * 100));

                // Place watermark
                $watermarked->place($watermarkImg, $align . '-' . $valign);
            }
        } else {
            // Apply text watermark with dual fonts
            // © symbol in regular font, name in handwriting font
            $regularFont = resource_path('fonts/arial.ttf');
            $scriptFont = resource_path('fonts/GreatVibes-Regular.ttf');

            // Parse the watermark text - split © from the rest
            $copyrightSymbol = '©';
            $nameText = $watermarkText;

            if (str_starts_with($watermarkText, '©')) {
                $nameText = trim(substr($watermarkText, strlen('©')));
            } elseif (str_starts_with($watermarkText, '(c)')) {
                $nameText = trim(substr($watermarkText, 3));
            }

            // Calculate positions based on alignment
            // Script font is typically larger, so adjust size
            $scriptSize = (int) ($watermarkSize * 1.3);

            // Estimate text widths for positioning
            $copyrightWidth = $watermarkSize * 0.8; // Approximate width of ©
            $spacing = $watermarkSize * 0.3; // Space between © and name

            if (file_exists($regularFont) && file_exists($scriptFont)) {
                // Render © symbol in regular font
                $copyrightX = $x;
                if ($align === 'right') {
                    // For right alignment, we need to calculate offset
                    // The script text will be drawn first, then ©
                    $watermarked->text(
                        $nameText,
                        $x,
                        $y,
                        function ($font) use ($scriptFont, $scriptSize, $opacity, $align, $valign) {
                            $font->filename($scriptFont);
                            $font->size($scriptSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align($align);
                            $font->valign($valign);
                        }
                    );

                    // Draw © to the left of the name
                    $watermarked->text(
                        $copyrightSymbol . ' ',
                        $x - ($this->estimateTextWidth($nameText, $scriptSize) + $spacing),
                        $y + ($scriptSize * 0.15), // Slight vertical adjustment
                        function ($font) use ($regularFont, $watermarkSize, $opacity, $valign) {
                            $font->filename($regularFont);
                            $font->size($watermarkSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align('right');
                            $font->valign($valign);
                        }
                    );
                } elseif ($align === 'center') {
                    // For center, draw combined but with different visual approach
                    // Draw the full text with script font for artistic look
                    $watermarked->text(
                        $copyrightSymbol . ' ' . $nameText,
                        $x,
                        $y,
                        function ($font) use ($scriptFont, $scriptSize, $opacity, $align, $valign) {
                            $font->filename($scriptFont);
                            $font->size($scriptSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align($align);
                            $font->valign($valign);
                        }
                    );
                } else {
                    // Left alignment - © first, then name
                    $watermarked->text(
                        $copyrightSymbol,
                        $x,
                        $y + ($scriptSize * 0.15),
                        function ($font) use ($regularFont, $watermarkSize, $opacity, $valign) {
                            $font->filename($regularFont);
                            $font->size($watermarkSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align('left');
                            $font->valign($valign);
                        }
                    );

                    $watermarked->text(
                        $nameText,
                        $x + $copyrightWidth + $spacing,
                        $y,
                        function ($font) use ($scriptFont, $scriptSize, $opacity, $valign) {
                            $font->filename($scriptFont);
                            $font->size($scriptSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align('left');
                            $font->valign($valign);
                        }
                    );
                }
            } else {
                // Fallback to single font if fonts not available
                $watermarked->text(
                    $watermarkText,
                    $x,
                    $y,
                    function ($font) use ($regularFont, $watermarkSize, $opacity, $align, $valign) {
                        if (file_exists($regularFont)) {
                            $font->filename($regularFont);
                        }
                        $font->size($watermarkSize);
                        $font->color("rgba(255, 255, 255, {$opacity})");
                        $font->align($align);
                        $font->valign($valign);
                    }
                );
            }
        }

        // Ensure directory exists
        $storagePath = storage_path('app/public/' . $directory);
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Save the image as WebP
        $path = $directory . '/' . $filename;
        $fullPath = storage_path('app/public/' . $path);
        $watermarked->toWebp($quality)->save($fullPath);

        return $path;
    }

    /**
     * Generate a watermarked image version (AVIF format - best compression).
     * For landscape images: max width = maxDimension
     * For portrait images: max height = maxDimension
     */
    protected function generateWatermarkedImageAvif(
        ImageInterface $image,
        string $filename,
        string $directory,
        int $maxDimension,
        int $quality
    ): string {
        $watermarked = clone $image;
        $width = $watermarked->width();
        $height = $watermarked->height();

        // Determine if landscape or portrait and resize accordingly
        if ($width >= $height) {
            if ($width > $maxDimension) {
                $watermarked->scale(width: $maxDimension);
            }
        } else {
            if ($height > $maxDimension) {
                $watermarked->scale(height: $maxDimension);
            }
        }

        // Get watermark settings from database or use defaults
        $watermarkEnabled = Setting::get('watermark_enabled', '1') === '1';
        $watermarkType = Setting::get('watermark_type', 'text');
        $watermarkText = Setting::get('watermark_text', $this->watermarkSettings['text']);
        $watermarkImage = Setting::get('watermark_image', '');
        $watermarkPosition = Setting::get('watermark_position', 'bottom-right');
        $watermarkOpacity = (int) Setting::get('watermark_opacity', '40');
        $watermarkSize = (int) Setting::get('watermark_size', '24');
        $watermarkImageSize = (int) Setting::get('watermark_image_size', '15');

        // Check if watermark should be applied
        $shouldApplyWatermark = $watermarkEnabled && (
            ($watermarkType === 'text' && !empty($watermarkText)) ||
            ($watermarkType === 'image' && !empty($watermarkImage))
        );

        if (!$shouldApplyWatermark) {
            // Just save without watermark (AVIF)
            $storagePath = storage_path('app/public/' . $directory);
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }
            $path = $directory . '/' . $filename;
            $fullPath = storage_path('app/public/' . $path);
            $watermarked->toAvif($quality)->save($fullPath);
            return $path;
        }

        // Calculate position based on setting
        $padding = $this->watermarkSettings['padding'];
        list($x, $y, $align, $valign) = $this->getWatermarkPosition(
            $watermarked->width(),
            $watermarked->height(),
            $watermarkPosition,
            $padding
        );

        // Calculate opacity for rgba (0-100 to 0-1)
        $opacity = $watermarkOpacity / 100;

        if ($watermarkType === 'image' && !empty($watermarkImage)) {
            // Apply image watermark
            $watermarkPath = storage_path('app/public/' . $watermarkImage);
            if (file_exists($watermarkPath)) {
                $watermarkImg = Image::read($watermarkPath);
                $targetWidth = (int) ($watermarked->width() * ($watermarkImageSize / 100));
                if ($watermarkImg->width() > $targetWidth) {
                    $watermarkImg->scale(width: $targetWidth);
                }
                $watermarkImg->brightness((int) (($opacity - 1) * 100));
                $watermarked->place($watermarkImg, $align . '-' . $valign);
            }
        } else {
            // Apply text watermark with dual fonts
            $regularFont = resource_path('fonts/arial.ttf');
            $scriptFont = resource_path('fonts/GreatVibes-Regular.ttf');

            $copyrightSymbol = '©';
            $nameText = $watermarkText;

            if (str_starts_with($watermarkText, '©')) {
                $nameText = trim(substr($watermarkText, strlen('©')));
            } elseif (str_starts_with($watermarkText, '(c)')) {
                $nameText = trim(substr($watermarkText, 3));
            }

            $scriptSize = (int) ($watermarkSize * 1.3);
            $copyrightWidth = $watermarkSize * 0.8;
            $spacing = $watermarkSize * 0.3;

            if (file_exists($regularFont) && file_exists($scriptFont)) {
                if ($align === 'right') {
                    $watermarked->text(
                        $nameText,
                        $x,
                        $y,
                        function ($font) use ($scriptFont, $scriptSize, $opacity, $align, $valign) {
                            $font->filename($scriptFont);
                            $font->size($scriptSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align($align);
                            $font->valign($valign);
                        }
                    );
                    $watermarked->text(
                        $copyrightSymbol . ' ',
                        $x - ($this->estimateTextWidth($nameText, $scriptSize) + $spacing),
                        $y + ($scriptSize * 0.15),
                        function ($font) use ($regularFont, $watermarkSize, $opacity, $valign) {
                            $font->filename($regularFont);
                            $font->size($watermarkSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align('right');
                            $font->valign($valign);
                        }
                    );
                } elseif ($align === 'center') {
                    $watermarked->text(
                        $copyrightSymbol . ' ' . $nameText,
                        $x,
                        $y,
                        function ($font) use ($scriptFont, $scriptSize, $opacity, $align, $valign) {
                            $font->filename($scriptFont);
                            $font->size($scriptSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align($align);
                            $font->valign($valign);
                        }
                    );
                } else {
                    $watermarked->text(
                        $copyrightSymbol,
                        $x,
                        $y + ($scriptSize * 0.15),
                        function ($font) use ($regularFont, $watermarkSize, $opacity, $valign) {
                            $font->filename($regularFont);
                            $font->size($watermarkSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align('left');
                            $font->valign($valign);
                        }
                    );
                    $watermarked->text(
                        $nameText,
                        $x + $copyrightWidth + $spacing,
                        $y,
                        function ($font) use ($scriptFont, $scriptSize, $opacity, $valign) {
                            $font->filename($scriptFont);
                            $font->size($scriptSize);
                            $font->color("rgba(255, 255, 255, {$opacity})");
                            $font->align('left');
                            $font->valign($valign);
                        }
                    );
                }
            } else {
                $watermarked->text(
                    $watermarkText,
                    $x,
                    $y,
                    function ($font) use ($regularFont, $watermarkSize, $opacity, $align, $valign) {
                        if (file_exists($regularFont)) {
                            $font->filename($regularFont);
                        }
                        $font->size($watermarkSize);
                        $font->color("rgba(255, 255, 255, {$opacity})");
                        $font->align($align);
                        $font->valign($valign);
                    }
                );
            }
        }

        // Ensure directory exists
        $storagePath = storage_path('app/public/' . $directory);
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Save the image as AVIF (30-50% smaller than WebP)
        $path = $directory . '/' . $filename;
        $fullPath = storage_path('app/public/' . $path);
        $watermarked->toAvif($quality)->save($fullPath);

        return $path;
    }

    /**
     * Calculate watermark position coordinates.
     * Uses 5% padding from edges for consistent positioning across image sizes.
     */
    protected function getWatermarkPosition(int $width, int $height, string $position, int $padding): array
    {
        // Use 5% of the dimension as padding for consistent look
        $paddingX = (int) ($width * 0.05);
        $paddingY = (int) ($height * 0.05);

        return match ($position) {
            'top-left' => [$paddingX, $paddingY, 'left', 'top'],
            'top-center' => [$width / 2, $paddingY, 'center', 'top'],
            'top-right' => [$width - $paddingX, $paddingY, 'right', 'top'],
            'middle-left' => [$paddingX, $height / 2, 'left', 'middle'],
            'center' => [$width / 2, $height / 2, 'center', 'middle'],
            'middle-right' => [$width - $paddingX, $height / 2, 'right', 'middle'],
            'bottom-left' => [$paddingX, $height - $paddingY, 'left', 'bottom'],
            'bottom-center' => [$width / 2, $height - $paddingY, 'center', 'bottom'],
            'bottom-right' => [$width - $paddingX, $height - $paddingY, 'right', 'bottom'],
            default => [$width - $paddingX, $height - $paddingY, 'right', 'bottom'],
        };
    }

    /**
     * Estimate text width based on character count and font size.
     * This is an approximation for script/handwriting fonts.
     */
    protected function estimateTextWidth(string $text, int $fontSize): int
    {
        // Script fonts are typically narrower, use 0.5 as multiplier
        // This is an approximation since we don't have access to actual font metrics
        $charWidth = $fontSize * 0.5;
        return (int) (strlen($text) * $charWidth);
    }

    /**
     * Extract EXIF data from an image file.
     */
    protected function extractExifData(string $filePath): ?array
    {
        try {
            $exif = @exif_read_data($filePath, 'ANY_TAG', true);

            if (!$exif) {
                return null;
            }

            // Extract relevant EXIF fields
            return [
                'Make' => $exif['IFD0']['Make'] ?? null,
                'Model' => $exif['IFD0']['Model'] ?? null,
                'ExposureTime' => $exif['EXIF']['ExposureTime'] ?? null,
                'FNumber' => isset($exif['EXIF']['FNumber']) ? $this->evaluateFraction($exif['EXIF']['FNumber']) : null,
                'ISOSpeedRatings' => $exif['EXIF']['ISOSpeedRatings'] ?? null,
                'FocalLength' => isset($exif['EXIF']['FocalLength']) ? $this->evaluateFraction($exif['EXIF']['FocalLength']) : null,
                'DateTimeOriginal' => $exif['EXIF']['DateTimeOriginal'] ?? null,
                'LensModel' => $exif['EXIF']['UndefinedTag:0xA434'] ?? $exif['EXIF']['LensModel'] ?? null,
                'GPSLatitude' => $exif['GPS']['GPSLatitude'] ?? null,
                'GPSLongitude' => $exif['GPS']['GPSLongitude'] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Reverse geocode coordinates to get a location name.
     * Uses OpenStreetMap Nominatim API (free, no API key required).
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        try {
            $url = sprintf(
                'https://nominatim.openstreetmap.org/reverse?format=json&lat=%s&lon=%s&zoom=10&addressdetails=1&accept-language=en',
                $latitude,
                $longitude
            );

            // Use shell curl command for better compatibility
            $command = sprintf(
                'curl -s -A "PhotographyPortfolio/1.0" "%s" 2>/dev/null',
                $url
            );
            $response = shell_exec($command);

            if (!$response) {
                return null;
            }

            $data = json_decode($response, true);

            if (!$data || isset($data['error'])) {
                return null;
            }

            // Build a readable location name from the address components
            $address = $data['address'] ?? [];

            // Priority order for location name construction
            $locationParts = [];

            // First, try to get the most specific location
            $specificLocation = $address['village'] ?? $address['town'] ?? $address['city'] ?? $address['municipality'] ?? $address['county'] ?? null;
            if ($specificLocation) {
                $locationParts[] = $specificLocation;
            }

            // Add district/state_district if different from specific location
            $district = $address['state_district'] ?? $address['district'] ?? null;
            if ($district && $district !== $specificLocation) {
                $locationParts[] = $district;
            }

            // Add state/region
            $state = $address['state'] ?? $address['region'] ?? null;
            if ($state && !in_array($state, $locationParts)) {
                $locationParts[] = $state;
            }

            // Add country
            $country = $address['country'] ?? null;
            if ($country && !in_array($country, $locationParts)) {
                $locationParts[] = $country;
            }

            if (empty($locationParts)) {
                // Fallback to display_name
                return $data['display_name'] ?? null;
            }

            return implode(', ', $locationParts);

        } catch (\Exception $e) {
            LoggingService::debug('photo.geocode_failed', "Reverse geocoding failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract GPS coordinates from EXIF data.
     */
    protected function extractGpsCoordinates(string $filePath): array
    {
        $result = ['latitude' => null, 'longitude' => null];

        try {
            $exif = @exif_read_data($filePath, 'GPS', true);

            if (!$exif || !isset($exif['GPS'])) {
                return $result;
            }

            $gps = $exif['GPS'];

            // Check if we have the required GPS data
            if (!isset($gps['GPSLatitude'], $gps['GPSLongitude'], $gps['GPSLatitudeRef'], $gps['GPSLongitudeRef'])) {
                return $result;
            }

            // Convert latitude
            $lat = $this->gpsToDecimal(
                $gps['GPSLatitude'],
                $gps['GPSLatitudeRef']
            );

            // Convert longitude
            $lng = $this->gpsToDecimal(
                $gps['GPSLongitude'],
                $gps['GPSLongitudeRef']
            );

            if ($lat !== null && $lng !== null) {
                $result['latitude'] = $lat;
                $result['longitude'] = $lng;
            }
        } catch (\Exception $e) {
            \Log::warning('GPS extraction failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Convert GPS coordinates from degrees/minutes/seconds to decimal.
     */
    protected function gpsToDecimal(array $coordinate, string $hemisphere): ?float
    {
        if (count($coordinate) !== 3) {
            return null;
        }

        // Convert each part from fraction to decimal
        $degrees = $this->fractionToDecimal($coordinate[0]);
        $minutes = $this->fractionToDecimal($coordinate[1]);
        $seconds = $this->fractionToDecimal($coordinate[2]);

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        // Calculate decimal degrees
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        // Apply hemisphere (S and W are negative)
        if (in_array(strtoupper($hemisphere), ['S', 'W'])) {
            $decimal *= -1;
        }

        return round($decimal, 8);
    }

    /**
     * Convert a fraction string or value to decimal.
     */
    protected function fractionToDecimal(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && str_contains($value, '/')) {
            $parts = explode('/', $value);
            if (count($parts) === 2 && $parts[1] != 0) {
                return (float) $parts[0] / (float) $parts[1];
            }
        }

        return null;
    }

    /**
     * Evaluate a fraction string like "1/500" to a decimal.
     */
    protected function evaluateFraction(string $fraction): ?float
    {
        if (str_contains($fraction, '/')) {
            $parts = explode('/', $fraction);
            if (count($parts) === 2 && $parts[1] != 0) {
                return round((float) $parts[0] / (float) $parts[1], 2);
            }
        }
        return (float) $fraction;
    }

    /**
     * Get capture date from EXIF data.
     */
    protected function getCaptureDate(?array $exifData): ?\DateTime
    {
        if (!$exifData || empty($exifData['DateTimeOriginal'])) {
            return null;
        }

        try {
            return \DateTime::createFromFormat('Y:m:d H:i:s', $exifData['DateTimeOriginal']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get output extension (convert HEIC to JPEG).
     */
    protected function getOutputExtension(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        // Convert HEIC/HEIF to JPEG
        if (in_array($mimeType, ['image/heic', 'image/heif'])) {
            return 'jpg';
        }

        $extension = strtolower($file->getClientOriginalExtension());

        // Standardize extensions
        return match ($extension) {
            'jpeg' => 'jpg',
            'tiff' => 'jpg',
            default => $extension,
        };
    }

    /**
     * Update location name for a photo using reverse geocoding.
     */
    public function updateLocationName(Photo $photo): ?string
    {
        if (!$photo->latitude || !$photo->longitude) {
            return null;
        }

        $locationName = $this->reverseGeocode($photo->latitude, $photo->longitude);

        if ($locationName) {
            $photo->update(['location_name' => $locationName]);
        }

        return $locationName;
    }

    /**
     * Update location names for all photos with GPS data but no location name.
     */
    public function geocodeAllPhotos(): int
    {
        $photos = Photo::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNull('location_name')
            ->get();

        $count = 0;
        foreach ($photos as $photo) {
            $locationName = $this->updateLocationName($photo);
            if ($locationName) {
                $count++;
                // Add a small delay to respect API rate limits
                usleep(500000); // 0.5 second delay
            }
        }

        LoggingService::info('photo.geocode_batch', "Geocoded {$count} photos");

        return $count;
    }

    /**
     * Reprocess an existing photo with a new image file.
     * Used for replacing images on the edit page.
     */
    public function reprocessPhoto(Photo $photo, $file): void
    {
        // Get current settings
        $maxWidth = (int) Setting::get('max_image_width', 1920);
        $quality = (int) Setting::get('image_quality', 92);
        $avifQuality = $this->mapWebpToAvifQuality($quality);

        // Generate unique filename
        $uuid = Str::uuid();
        $extension = 'avif';

        // Read image
        $image = Image::read($file);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Extract EXIF from new image
        $exifData = $this->extractExifData($file);

        // Resize if needed
        if ($image->width() > $maxWidth) {
            $image->scale(width: $maxWidth);
        }

        // Create directories
        Storage::disk('public')->makeDirectory('photos/display');
        Storage::disk('public')->makeDirectory('photos/thumbnails');
        Storage::disk('public')->makeDirectory('photos/watermarked');

        // Save display version
        $displayPath = "photos/display/{$uuid}.{$extension}";
        $image->toAvif($avifQuality)->save(
            storage_path('app/public/' . $displayPath)
        );

        // Generate thumbnail
        $thumbnailImage = clone $image;
        $thumbnailImage->cover(400, 300);
        $thumbnailPath = "photos/thumbnails/{$uuid}.webp";
        $thumbnailImage->toWebp(85)->save(
            storage_path('app/public/' . $thumbnailPath)
        );

        // Generate watermarked version
        $watermarkedPath = "photos/watermarked/{$uuid}.{$extension}";
        $this->applyWatermark($image);
        $image->toAvif($avifQuality)->save(
            storage_path('app/public/' . $watermarkedPath)
        );

        // Update photo record
        $photo->update([
            'display_path' => $displayPath,
            'thumbnail_path' => $thumbnailPath,
            'watermarked_path' => $watermarkedPath,
            'width' => $originalWidth,
            'height' => $originalHeight,
            'file_size' => $file->getSize(),
            'exif_data' => $exifData,
            'status' => 'published',
            'processing_stage' => null,
            'processing_error' => null,
        ]);

        // Update GPS if available in new image
        if (isset($exifData['GPSLatitude']) && isset($exifData['GPSLongitude'])) {
            $latitude = $this->convertGpsToDecimal($exifData['GPSLatitude'], $exifData['GPSLatitudeRef'] ?? 'N');
            $longitude = $this->convertGpsToDecimal($exifData['GPSLongitude'], $exifData['GPSLongitudeRef'] ?? 'E');
            $photo->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }

        LoggingService::activity(
            'photo.image_replaced',
            "Replaced image for photo: {$photo->title}",
            $photo
        );
    }

    /**
     * Delete all files associated with a photo.
     * Handles both local storage and R2 cloud storage.
     */
    public function deletePhotoFiles(Photo $photo, bool $preserveOriginal = false): void
    {
        // Delete display, thumbnail, and watermarked versions from public storage
        $publicPaths = [
            $photo->display_path,
            $photo->thumbnail_path,
            $photo->watermarked_path,
        ];

        $deletedCount = 0;
        foreach ($publicPaths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deletedCount++;
            }
        }

        // Optionally delete the original from storage (local or R2)
        if (!$preserveOriginal && $photo->original_path) {
            if (str_starts_with($photo->original_path, 'r2:')) {
                // Delete from R2 cloud storage
                $r2Key = substr($photo->original_path, 3);
                if ($this->deleteFromR2($r2Key)) {
                    $deletedCount++;
                }
            } else {
                // Delete from local private storage
                if (Storage::disk('local')->exists($photo->original_path)) {
                    Storage::disk('local')->delete($photo->original_path);
                    $deletedCount++;
                }
            }
        }

        LoggingService::activity(
            'photo.files_deleted',
            "Deleted {$deletedCount} file(s) for photo: {$photo->title}" . ($preserveOriginal ? ' (original preserved)' : ''),
            $photo,
            ['paths' => array_filter($publicPaths), 'preserved_original' => $preserveOriginal]
        );
    }

    /**
     * Update watermark settings.
     */
    public function setWatermarkSettings(array $settings): void
    {
        $this->watermarkSettings = array_merge($this->watermarkSettings, $settings);
    }

    /**
     * Regenerate watermarked version with new settings.
     * Uses the display version since we don't store originals.
     */
    public function regenerateWatermark(Photo $photo): void
    {
        // Use display version as source (we don't store originals)
        $displayPath = storage_path('app/public/' . $photo->display_path);

        if (!file_exists($displayPath)) {
            \Log::warning('Cannot regenerate watermark - display file not found', ['photo_id' => $photo->id]);
            return;
        }

        $displaySettings = $this->getDisplaySettings();
        $image = Image::read($displayPath);
        $filename = pathinfo($photo->display_path, PATHINFO_FILENAME) . '.webp';

        $watermarkedPath = $this->generateWatermarkedImage(
            $image,
            $filename,
            'photos/watermarked',
            $displaySettings['width'],
            $displaySettings['quality']
        );

        $photo->update(['watermarked_path' => $watermarkedPath]);
    }

    /**
     * Re-optimize a single photo with its effective settings (custom or global).
     * Prefers original file for best quality, falls back to display/watermarked.
     * Downloads from R2 if original is stored in cloud.
     */
    public function reoptimizePhoto(Photo $photo, ?int $customResolution = null, ?int $customQuality = null): bool
    {
        // Try to find a source file - prefer original for best quality
        $sourcePath = $this->findSourceFile($photo);

        // Check if using original (local or R2)
        $usingOriginal = $photo->hasOriginal();

        if (!$sourcePath) {
            \Log::warning('Cannot reoptimize - no source file found', [
                'photo_id' => $photo->id,
                'display_path' => $photo->display_path,
                'watermarked_path' => $photo->watermarked_path,
                'original_path' => $photo->original_path,
            ]);
            return false;
        }

        try {
            $image = Image::read($sourcePath);

            // Determine settings: custom params > per-photo settings > global settings
            if ($customResolution !== null) {
                $maxDimension = $customResolution;
                // Save custom settings to the photo
                $photo->custom_max_resolution = $customResolution;
            } else {
                $maxDimension = $photo->getEffectiveMaxResolution();
            }

            if ($customQuality !== null) {
                $quality = $customQuality;
                $photo->custom_quality = $customQuality;
            } else {
                $quality = $photo->getEffectiveQuality();
            }

            $avifQuality = $this->mapWebpToAvifQuality($quality);
            $webpFilename = Str::uuid() . '.webp';
            $avifFilename = pathinfo($webpFilename, PATHINFO_FILENAME) . '.avif';

            \Log::info('Reoptimizing photo', [
                'photo_id' => $photo->id,
                'using_original' => $usingOriginal,
                'from_r2' => str_starts_with($photo->original_path ?? '', 'r2:'),
                'max_dimension' => $maxDimension,
                'quality' => $quality,
                'source_dimensions' => $image->width() . 'x' . $image->height(),
            ]);

            // Delete old display/watermark/thumbnail files (but NOT original)
            $this->deletePhotoFiles($photo, preserveOriginal: true);

            // Generate new display version (AVIF for best compression)
            $newDisplayPath = $this->generateResizedImageAvif(
                $image,
                $avifFilename,
                'photos/display',
                $maxDimension,
                $avifQuality
            );
            $photo->display_path = $newDisplayPath;

            // Generate new thumbnail (WebP for compatibility)
            $newThumbnailPath = $this->generateResizedImage(
                $image,
                $webpFilename,
                'photos/thumbnails',
                $this->thumbnailWidth,
                $this->thumbnailQuality
            );
            $photo->thumbnail_path = $newThumbnailPath;

            // Generate new watermarked version (AVIF for best compression)
            $newWatermarkedPath = $this->generateWatermarkedImageAvif(
                $image,
                $avifFilename,
                'photos/watermarked',
                $maxDimension,
                $avifQuality
            );
            $photo->watermarked_path = $newWatermarkedPath;

            // Update width/height to match the new display dimensions
            $newImage = Image::read(storage_path('app/public/' . $newDisplayPath));
            $photo->width = $newImage->width();
            $photo->height = $newImage->height();

            $photo->save();

            // Clean up any temp files downloaded from R2
            $this->cleanupTempFiles();

            return true;
        } catch (\Exception $e) {
            // Clean up temp files even on failure
            $this->cleanupTempFiles();

            \Log::error('Failed to reoptimize photo', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Re-optimize all photos with current settings.
     */
    public function reoptimizeAllPhotos(): int
    {
        $count = 0;
        $failed = 0;
        $photos = Photo::all();
        $total = $photos->count();

        LoggingService::info('photo.reoptimize_started', "Starting re-optimization of {$total} photos");

        foreach ($photos as $photo) {
            if ($this->reoptimizePhoto($photo)) {
                $count++;
            } else {
                $failed++;
            }
        }

        LoggingService::info(
            'photo.reoptimize_completed',
            "Re-optimization completed: {$count} succeeded, {$failed} failed out of {$total} photos",
            null,
            ['total' => $total, 'success' => $count, 'failed' => $failed]
        );

        return $count;
    }

    /**
     * Find a source file for reoptimization.
     * PRIORITY: Original from R2 > Original local > Display > Watermarked
     * Returns array with 'path' and 'is_temp' (true if downloaded from R2)
     */
    protected function findSourceFile(Photo $photo): ?string
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];

        // 1. First, check for original in R2 cloud storage (prefixed with 'r2:')
        if (!empty($photo->original_path) && str_starts_with($photo->original_path, 'r2:')) {
            $r2Key = substr($photo->original_path, 3); // Remove 'r2:' prefix
            \Log::debug('Checking R2 for original', ['r2_key' => $r2Key]);

            $tempPath = $this->downloadFromR2($r2Key);
            if ($tempPath) {
                \Log::debug('Downloaded original from R2', ['r2_key' => $r2Key, 'temp_path' => $tempPath]);
                // Store temp path for cleanup later
                $this->tempFilesToCleanup[] = $tempPath;
                return $tempPath;
            }
        }

        // 2. Check for original file in local private storage
        if (!empty($photo->original_path) && !str_starts_with($photo->original_path, 'r2:')) {
            $originalPath = storage_path('app/private/' . $photo->original_path);
            if (file_exists($originalPath)) {
                \Log::debug('Found original source file locally', ['path' => $photo->original_path]);
                return $originalPath;
            }

            // Try with different extensions
            $pathWithoutExt = preg_replace('/\.[^.]+$/', '', $photo->original_path);
            foreach ($extensions as $ext) {
                $testPath = storage_path('app/private/' . $pathWithoutExt . '.' . $ext);
                if (file_exists($testPath)) {
                    \Log::debug('Found original with alternate extension', ['path' => $pathWithoutExt . '.' . $ext]);
                    return $testPath;
                }
            }
        }

        // 3. Fall back to public storage (display, watermarked)
        $basePath = storage_path('app/public/');
        $pathsToCheck = array_filter([
            $photo->display_path,
            $photo->watermarked_path,
        ]);

        foreach ($pathsToCheck as $path) {
            if (empty($path)) continue;

            $fullPath = $basePath . $path;
            if (file_exists($fullPath)) {
                \Log::debug('Found source file in public storage', ['path' => $path]);
                return $fullPath;
            }

            // Try with different extensions
            $pathWithoutExt = preg_replace('/\.[^.]+$/', '', $path);
            foreach ($extensions as $ext) {
                $testPath = $basePath . $pathWithoutExt . '.' . $ext;
                if (file_exists($testPath)) {
                    \Log::debug('Found source file with alternate extension', ['path' => $pathWithoutExt . '.' . $ext]);
                    return $testPath;
                }
            }
        }

        // 4. Last resort: scan directories for files matching UUID pattern
        $uuid = null;
        $allPaths = array_filter([$photo->original_path, $photo->display_path, $photo->watermarked_path]);
        foreach ($allPaths as $path) {
            // Remove r2: prefix if present for UUID extraction
            $cleanPath = str_starts_with($path, 'r2:') ? substr($path, 3) : $path;
            if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i', $cleanPath, $matches)) {
                $uuid = $matches[1];
                break;
            }
        }

        if ($uuid) {
            // Check private originals first
            $privateDir = storage_path('app/private/photos/originals');
            if (is_dir($privateDir)) {
                $files = glob($privateDir . '/' . $uuid . '.*');
                if (!empty($files)) {
                    \Log::debug('Found original by UUID scan', ['uuid' => $uuid, 'found' => $files[0]]);
                    return $files[0];
                }
            }

            // Then check public directories
            $directories = ['photos/display', 'photos/watermarked'];
            foreach ($directories as $dir) {
                $dirPath = $basePath . $dir;
                if (is_dir($dirPath)) {
                    $files = glob($dirPath . '/' . $uuid . '.*');
                    if (!empty($files)) {
                        \Log::debug('Found source file by UUID scan', ['uuid' => $uuid, 'found' => $files[0]]);
                        return $files[0];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Temp files downloaded from R2 that need cleanup after processing.
     */
    protected array $tempFilesToCleanup = [];

    /**
     * Clean up any temp files downloaded from R2.
     */
    protected function cleanupTempFiles(): void
    {
        foreach ($this->tempFilesToCleanup as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
                \Log::debug('Cleaned up temp file', ['path' => $tempFile]);
            }
        }
        $this->tempFilesToCleanup = [];
    }

    /**
     * Apply AI analysis to generate title and description.
     */
    protected function applyAIAnalysis(Photo $photo): void
    {
        try {
            $aiService = new AIImageService();

            // Check if AI is enabled
            if (!$aiService->isEnabled()) {
                return;
            }

            // Check settings for what to auto-generate
            $generateTitle = Setting::get('ai_auto_title', '1') === '1';
            $generateDescription = Setting::get('ai_auto_description', '1') === '1';

            if (!$generateTitle && !$generateDescription) {
                return;
            }

            // Get the display image path for analysis
            $imagePath = storage_path('app/public/' . $photo->display_path);

            if (!file_exists($imagePath)) {
                LoggingService::debug('ai.no_image', "AI: Image file not found for photo {$photo->id}");
                return;
            }

            // Analyze the image
            $result = $aiService->analyzeImage(
                $imagePath,
                $photo->exif_data,
                $photo->location_name
            );

            if (!$result) {
                LoggingService::debug('ai.no_result', "AI: No result returned for photo {$photo->id}");
                return;
            }

            // Update photo with AI-generated content
            $updates = [];

            if ($generateTitle && !empty($result['title'])) {
                $updates['title'] = $result['title'];
                // Update slug based on new title
                $updates['slug'] = Str::slug($result['title']) . '-' . Str::random(6);
            }

            if ($generateDescription) {
                if (!empty($result['description'])) {
                    $updates['description'] = $result['description'];
                }
                if (!empty($result['story'])) {
                    $updates['story'] = $result['story'];
                }
            }

            if (!empty($updates)) {
                $photo->update($updates);
                LoggingService::activity(
                    'ai.content_generated',
                    "AI generated content for photo: {$photo->title}",
                    $photo,
                    ['provider' => $aiService->getProvider(), 'fields' => array_keys($updates)]
                );
            }

        } catch (\Exception $e) {
            // Don't let AI failures break the upload process
            LoggingService::error('ai.analysis_failed', "AI analysis failed: " . $e->getMessage(), $photo);
        }
    }

    /**
     * Manually trigger AI analysis for a photo.
     */
    public function generateAIContent(Photo $photo): ?array
    {
        try {
            $aiService = new AIImageService();

            if (!$aiService->isEnabled()) {
                return ['error' => 'AI is not enabled or configured'];
            }

            $imagePath = storage_path('app/public/' . $photo->display_path);

            if (!file_exists($imagePath)) {
                return ['error' => 'Image file not found'];
            }

            $result = $aiService->analyzeImage(
                $imagePath,
                $photo->exif_data,
                $photo->location_name
            );

            if (!$result) {
                return ['error' => 'AI analysis returned no results'];
            }

            LoggingService::activity(
                'ai.manual_generation',
                "AI manually generated content for photo: {$photo->title}",
                $photo,
                ['provider' => $aiService->getProvider()]
            );

            return $result;

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
