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
            'width' => (int) Setting::get('image_max_resolution', 2048),
            'quality' => (int) Setting::get('image_quality', 82),
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

    /**
     * Process an uploaded photo file.
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

            // Create photo record (no original_path - we don't store originals)
            $photo = Photo::create([
                'title' => pathinfo($originalFilename, PATHINFO_FILENAME),
                'slug' => Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '-' . Str::random(6),
                'original_filename' => $originalFilename,
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
     * All images are saved as WebP for optimal file size.
     */
    protected function generateImageVersions(Photo $photo, ImageInterface $image, string $filename): void
    {
        // Get settings from database
        $displaySettings = $this->getDisplaySettings();

        // Change extension to webp
        $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';

        // Generate display version (WebP)
        $displayPath = $this->generateResizedImage(
            $image,
            $webpFilename,
            'photos/display',
            $displaySettings['width'],
            $displaySettings['quality']
        );
        $photo->update(['display_path' => $displayPath]);

        // Generate thumbnail (WebP)
        $thumbnailPath = $this->generateResizedImage(
            $image,
            $webpFilename,
            'photos/thumbnails',
            $this->thumbnailWidth,
            $this->thumbnailQuality
        );
        $photo->update(['thumbnail_path' => $thumbnailPath]);

        // Generate watermarked version (WebP)
        $watermarkedPath = $this->generateWatermarkedImage(
            $image,
            $webpFilename,
            'photos/watermarked',
            $displaySettings['width'],
            $displaySettings['quality']
        );
        $photo->update(['watermarked_path' => $watermarkedPath]);
    }

    /**
     * Generate a resized image version (WebP format).
     */
    protected function generateResizedImage(
        ImageInterface $image,
        string $filename,
        string $directory,
        int $maxWidth,
        int $quality
    ): string {
        $resized = clone $image;

        // Only resize if image is larger than max width
        if ($resized->width() > $maxWidth) {
            $resized->scale(width: $maxWidth);
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
     * Generate a watermarked image version (WebP format).
     */
    protected function generateWatermarkedImage(
        ImageInterface $image,
        string $filename,
        string $directory,
        int $maxWidth,
        int $quality
    ): string {
        $watermarked = clone $image;

        // Resize if needed
        if ($watermarked->width() > $maxWidth) {
            $watermarked->scale(width: $maxWidth);
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
    protected function reverseGeocode(float $latitude, float $longitude): ?string
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
     * Delete all files associated with a photo.
     */
    public function deletePhotoFiles(Photo $photo): void
    {
        // Only delete display, thumbnail, and watermarked versions
        // (we no longer store originals to save space)
        $paths = [
            $photo->display_path,
            $photo->thumbnail_path,
            $photo->watermarked_path,
        ];

        $deletedCount = 0;
        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deletedCount++;
            }
        }

        LoggingService::activity(
            'photo.files_deleted',
            "Deleted {$deletedCount} file(s) for photo: {$photo->title}",
            $photo,
            ['paths' => array_filter($paths)]
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
     * Re-optimize a single photo with current settings.
     * Regenerates all versions from the display file.
     */
    public function reoptimizePhoto(Photo $photo): bool
    {
        $displayPath = storage_path('app/public/' . $photo->display_path);

        if (!file_exists($displayPath)) {
            \Log::warning('Cannot reoptimize - display file not found', ['photo_id' => $photo->id]);
            return false;
        }

        try {
            $image = Image::read($displayPath);
            $displaySettings = $this->getDisplaySettings();
            $webpFilename = Str::uuid() . '.webp';

            // Delete old files
            $this->deletePhotoFiles($photo);

            // Generate new display version
            $newDisplayPath = $this->generateResizedImage(
                $image,
                $webpFilename,
                'photos/display',
                $displaySettings['width'],
                $displaySettings['quality']
            );
            $photo->display_path = $newDisplayPath;

            // Generate new thumbnail
            $newThumbnailPath = $this->generateResizedImage(
                $image,
                $webpFilename,
                'photos/thumbnails',
                $this->thumbnailWidth,
                $this->thumbnailQuality
            );
            $photo->thumbnail_path = $newThumbnailPath;

            // Generate new watermarked version
            $newWatermarkedPath = $this->generateWatermarkedImage(
                $image,
                $webpFilename,
                'photos/watermarked',
                $displaySettings['width'],
                $displaySettings['quality']
            );
            $photo->watermarked_path = $newWatermarkedPath;

            $photo->save();

            return true;
        } catch (\Exception $e) {
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
