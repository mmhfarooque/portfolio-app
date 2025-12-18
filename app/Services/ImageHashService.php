<?php

namespace App\Services;

use App\Models\Photo;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Log;

class ImageHashService
{
    /**
     * Generate both file hash and perceptual hash for an image.
     */
    public function generateHashes(string $filePath): array
    {
        return [
            'file_hash' => $this->generateFileHash($filePath),
            'image_hash' => $this->generatePerceptualHash($filePath),
        ];
    }

    /**
     * Generate MD5 hash of the file content.
     * This detects exact duplicates.
     */
    public function generateFileHash(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        return md5_file($filePath);
    }

    /**
     * Generate a perceptual hash (pHash) of the image.
     * This detects visually similar images even if resized/compressed.
     */
    public function generatePerceptualHash(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        try {
            // Load image and resize to 8x8 for hash calculation
            $image = Image::read($filePath);

            // Convert to grayscale and resize to 9x8 (for DCT-like comparison)
            $image->greyscale()->resize(9, 8);

            // Get pixel values
            $pixels = [];
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 9; $x++) {
                    $color = $image->pickColor($x, $y);
                    // Get the red channel (grayscale so all channels are same)
                    $pixels[] = $color->red();
                }
            }

            // Calculate differences between adjacent pixels (difference hash)
            $hash = '';
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 8; $x++) {
                    $idx = $y * 9 + $x;
                    // Compare pixel with its right neighbor
                    $hash .= ($pixels[$idx] < $pixels[$idx + 1]) ? '1' : '0';
                }
            }

            // Convert binary string to hexadecimal (64 bits = 16 hex chars)
            return $this->binaryToHex($hash);

        } catch (\Exception $e) {
            Log::warning('Failed to generate perceptual hash', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Convert binary string to hexadecimal.
     */
    protected function binaryToHex(string $binary): string
    {
        $hex = '';
        for ($i = 0; $i < strlen($binary); $i += 4) {
            $hex .= dechex(bindec(substr($binary, $i, 4)));
        }
        return $hex;
    }

    /**
     * Calculate Hamming distance between two hashes.
     * Lower distance = more similar images.
     */
    public function hammingDistance(string $hash1, string $hash2): int
    {
        if (strlen($hash1) !== strlen($hash2)) {
            return PHP_INT_MAX;
        }

        $distance = 0;
        $bin1 = $this->hexToBinary($hash1);
        $bin2 = $this->hexToBinary($hash2);

        for ($i = 0; $i < strlen($bin1); $i++) {
            if ($bin1[$i] !== $bin2[$i]) {
                $distance++;
            }
        }

        return $distance;
    }

    /**
     * Convert hexadecimal to binary string.
     */
    protected function hexToBinary(string $hex): string
    {
        $binary = '';
        for ($i = 0; $i < strlen($hex); $i++) {
            $binary .= str_pad(decbin(hexdec($hex[$i])), 4, '0', STR_PAD_LEFT);
        }
        return $binary;
    }

    /**
     * Check if an image is a duplicate based on file hash or perceptual hash.
     * Returns the duplicate photo if found, null otherwise.
     */
    public function findDuplicate(string $filePath, int $threshold = 5): ?Photo
    {
        $hashes = $this->generateHashes($filePath);

        // First check for exact file match
        if ($hashes['file_hash']) {
            $exactMatch = Photo::where('file_hash', $hashes['file_hash'])->first();
            if ($exactMatch) {
                return $exactMatch;
            }
        }

        // Then check for perceptual similarity
        if ($hashes['image_hash']) {
            // Get all photos with image hashes
            $photos = Photo::whereNotNull('image_hash')->get(['id', 'title', 'image_hash', 'thumbnail_path']);

            foreach ($photos as $photo) {
                $distance = $this->hammingDistance($hashes['image_hash'], $photo->image_hash);
                if ($distance <= $threshold) {
                    return $photo;
                }
            }
        }

        return null;
    }

    /**
     * Check multiple files for duplicates.
     * Returns array of duplicates found.
     */
    public function findDuplicates(array $filePaths, int $threshold = 5): array
    {
        $duplicates = [];

        foreach ($filePaths as $index => $filePath) {
            $duplicate = $this->findDuplicate($filePath, $threshold);
            if ($duplicate) {
                $duplicates[$index] = [
                    'file' => basename($filePath),
                    'existing_photo' => $duplicate,
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Generate hashes for existing photos that don't have them.
     */
    public function backfillHashes(): int
    {
        $photos = Photo::whereNull('image_hash')
            ->orWhereNull('file_hash')
            ->get();

        $count = 0;
        foreach ($photos as $photo) {
            $originalPath = storage_path('app/public/' . $photo->original_path);

            if (file_exists($originalPath)) {
                $hashes = $this->generateHashes($originalPath);
                $photo->update([
                    'file_hash' => $hashes['file_hash'],
                    'image_hash' => $hashes['image_hash'],
                ]);
                $count++;
            }
        }

        return $count;
    }
}
