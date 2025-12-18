<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\Tag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LightroomSyncService
{
    /**
     * Parse XMP sidecar file content.
     */
    public function parseXmp(string $content): array
    {
        try {
            $xml = simplexml_load_string($content);

            if (!$xml) {
                return ['error' => 'Invalid XML content'];
            }

            // Register namespaces
            $namespaces = $xml->getDocNamespaces(true);
            foreach ($namespaces as $prefix => $uri) {
                $xml->registerXPathNamespace($prefix ?: 'rdf', $uri);
            }

            // Extract data
            return [
                'title' => $this->extractValue($xml, '//dc:title//rdf:li'),
                'description' => $this->extractValue($xml, '//dc:description//rdf:li'),
                'keywords' => $this->extractArray($xml, '//dc:subject//rdf:li'),
                'rating' => (int) ($this->extractAttribute($xml, '//@xmp:Rating') ?? 0),
                'label' => $this->extractAttribute($xml, '//@xmp:Label'),
                'creator' => $this->extractValue($xml, '//dc:creator//rdf:li'),
                'date_created' => $this->extractAttribute($xml, '//@photoshop:DateCreated'),
                'city' => $this->extractAttribute($xml, '//@photoshop:City'),
                'state' => $this->extractAttribute($xml, '//@photoshop:State'),
                'country' => $this->extractAttribute($xml, '//@photoshop:Country'),
                'headline' => $this->extractAttribute($xml, '//@photoshop:Headline'),
                'caption' => $this->extractAttribute($xml, '//@photoshop:Caption'),
            ];
        } catch (\Exception $e) {
            Log::error('XMP parsing failed', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Extract single value from XPath.
     */
    protected function extractValue(\SimpleXMLElement $xml, string $xpath): ?string
    {
        $result = $xml->xpath($xpath);
        return $result ? trim((string) $result[0]) : null;
    }

    /**
     * Extract attribute value from XPath.
     */
    protected function extractAttribute(\SimpleXMLElement $xml, string $xpath): ?string
    {
        $result = $xml->xpath($xpath);
        return $result ? trim((string) $result[0]) : null;
    }

    /**
     * Extract array of values from XPath.
     */
    protected function extractArray(\SimpleXMLElement $xml, string $xpath): array
    {
        $result = $xml->xpath($xpath);
        if (!$result) {
            return [];
        }

        $values = [];
        foreach ($result as $item) {
            $value = trim((string) $item);
            if ($value) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * Match XMP data to a photo by filename.
     */
    public function findPhotoByFilename(string $filename): ?Photo
    {
        // Remove .xmp extension if present
        $filename = preg_replace('/\.xmp$/i', '', $filename);

        return Photo::where('original_filename', 'LIKE', "{$filename}%")->first();
    }

    /**
     * Apply XMP data to a photo.
     */
    public function applyToPhoto(Photo $photo, array $xmpData, array $options = []): array
    {
        $applied = [];
        $skipped = [];

        // Title
        if (!empty($xmpData['title']) && ($options['overwrite'] ?? empty($photo->title))) {
            $photo->title = $xmpData['title'];
            $applied[] = 'title';
        } elseif (!empty($xmpData['title'])) {
            $skipped[] = 'title (already set)';
        }

        // Description
        if (!empty($xmpData['description']) && ($options['overwrite'] ?? empty($photo->description))) {
            $photo->description = $xmpData['description'];
            $applied[] = 'description';
        } elseif (!empty($xmpData['description'])) {
            $skipped[] = 'description (already set)';
        }

        // Location from IPTC
        $locationParts = array_filter([
            $xmpData['city'] ?? null,
            $xmpData['state'] ?? null,
            $xmpData['country'] ?? null,
        ]);
        if (!empty($locationParts) && ($options['overwrite'] ?? empty($photo->location_name))) {
            $photo->location_name = implode(', ', $locationParts);
            $applied[] = 'location_name';
        }

        // Keywords as tags
        if (!empty($xmpData['keywords']) && ($options['sync_tags'] ?? true)) {
            $tagIds = [];
            foreach ($xmpData['keywords'] as $keyword) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($keyword)],
                    ['name' => $keyword]
                );
                $tagIds[] = $tag->id;
            }
            $photo->tags()->syncWithoutDetaching($tagIds);
            $applied[] = 'tags (' . count($xmpData['keywords']) . ')';
        }

        $photo->save();

        return [
            'applied' => $applied,
            'skipped' => $skipped,
        ];
    }

    /**
     * Process uploaded XMP files.
     */
    public function processXmpFiles(array $files, array $options = []): array
    {
        $results = [];

        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $content = file_get_contents($file->getRealPath());

            $xmpData = $this->parseXmp($content);

            if (isset($xmpData['error'])) {
                $results[] = [
                    'filename' => $filename,
                    'status' => 'error',
                    'message' => $xmpData['error'],
                ];
                continue;
            }

            // Find matching photo
            $photo = $this->findPhotoByFilename($filename);

            if (!$photo) {
                $results[] = [
                    'filename' => $filename,
                    'status' => 'not_found',
                    'message' => 'No matching photo found',
                    'xmp_data' => $xmpData,
                ];
                continue;
            }

            // Apply data
            $applyResult = $this->applyToPhoto($photo, $xmpData, $options);

            $results[] = [
                'filename' => $filename,
                'status' => 'success',
                'photo_id' => $photo->id,
                'photo_title' => $photo->title,
                'applied' => $applyResult['applied'],
                'skipped' => $applyResult['skipped'],
            ];
        }

        return $results;
    }
}
