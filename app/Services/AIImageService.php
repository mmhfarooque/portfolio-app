<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class AIImageService
{
    protected ?string $provider;
    protected array $apiKeys = [];
    protected array $models = [
        'google' => 'gemini-1.5-flash',
        'openai' => 'gpt-4o-mini',
        'claude' => 'claude-3-5-sonnet-20241022',
    ];
    protected array $baseUrls = [
        'google' => 'https://generativelanguage.googleapis.com/v1beta/models',
        'openai' => 'https://api.openai.com/v1/chat/completions',
        'claude' => 'https://api.anthropic.com/v1/messages',
    ];

    public function __construct()
    {
        $this->provider = Setting::get('ai_provider', 'google');
        $this->apiKeys = [
            'google' => Setting::get('google_ai_api_key'),
            'openai' => Setting::get('openai_api_key'),
            'claude' => Setting::get('claude_api_key'),
        ];
    }

    /**
     * Check if AI is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return Setting::get('ai_enabled', '0') === '1' && !empty($this->apiKeys[$this->provider]);
    }

    /**
     * Get the current provider.
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Validate the API key by making a test request.
     */
    public function validateApiKey(?string $apiKey = null, string $provider = 'google'): array
    {
        $key = $apiKey ?? $this->apiKeys[$provider] ?? null;

        if (empty($key)) {
            return [
                'valid' => false,
                'message' => 'API key is empty',
            ];
        }

        try {
            switch ($provider) {
                case 'google':
                    return $this->validateGoogleKey($key);
                case 'openai':
                    return $this->validateOpenAIKey($key);
                case 'claude':
                    return $this->validateClaudeKey($key);
                default:
                    return ['valid' => false, 'message' => 'Unknown provider'];
            }
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate Google AI API key.
     */
    protected function validateGoogleKey(string $key): array
    {
        $url = "{$this->baseUrls['google']}/{$this->models['google']}:generateContent?key={$key}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Say "API key is valid" in exactly those words.']
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 20,
            ]
        ];

        $command = sprintf(
            'curl -s -X POST "%s" -H "Content-Type: application/json" -d %s 2>/dev/null',
            $url,
            escapeshellarg(json_encode($payload))
        );

        $response = shell_exec($command);

        if (!$response) {
            return ['valid' => false, 'message' => 'No response from API'];
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            return ['valid' => false, 'message' => $data['error']['message'] ?? 'API error'];
        }

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return ['valid' => true, 'message' => 'API key is valid and working'];
        }

        return ['valid' => false, 'message' => 'Unexpected API response'];
    }

    /**
     * Validate OpenAI API key.
     */
    protected function validateOpenAIKey(string $key): array
    {
        $url = $this->baseUrls['openai'];

        $payload = [
            'model' => $this->models['openai'],
            'messages' => [
                ['role' => 'user', 'content' => 'Say "API key is valid" in exactly those words.']
            ],
            'max_tokens' => 20,
        ];

        $command = sprintf(
            'curl -s -X POST "%s" -H "Content-Type: application/json" -H "Authorization: Bearer %s" -d %s 2>/dev/null',
            $url,
            $key,
            escapeshellarg(json_encode($payload))
        );

        $response = shell_exec($command);

        if (!$response) {
            return ['valid' => false, 'message' => 'No response from API'];
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            return ['valid' => false, 'message' => $data['error']['message'] ?? 'API error'];
        }

        if (isset($data['choices'][0]['message']['content'])) {
            return ['valid' => true, 'message' => 'API key is valid and working'];
        }

        return ['valid' => false, 'message' => 'Unexpected API response'];
    }

    /**
     * Validate Claude API key.
     */
    protected function validateClaudeKey(string $key): array
    {
        $url = $this->baseUrls['claude'];

        $payload = [
            'model' => $this->models['claude'],
            'max_tokens' => 20,
            'messages' => [
                ['role' => 'user', 'content' => 'Say "API key is valid" in exactly those words.']
            ],
        ];

        $command = sprintf(
            'curl -s -X POST "%s" -H "Content-Type: application/json" -H "x-api-key: %s" -H "anthropic-version: 2023-06-01" -d %s 2>/dev/null',
            $url,
            $key,
            escapeshellarg(json_encode($payload))
        );

        $response = shell_exec($command);

        if (!$response) {
            return ['valid' => false, 'message' => 'No response from API'];
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            return ['valid' => false, 'message' => $data['error']['message'] ?? 'API error'];
        }

        if (isset($data['content'][0]['text'])) {
            return ['valid' => true, 'message' => 'API key is valid and working'];
        }

        return ['valid' => false, 'message' => 'Unexpected API response'];
    }

    /**
     * Analyze an image and generate title and description.
     */
    public function analyzeImage(string $imagePath, ?array $exifData = null, ?string $locationName = null): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            if (!file_exists($imagePath)) {
                Log::warning('AI: Image file not found', ['path' => $imagePath]);
                return null;
            }

            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';

            $context = $this->buildContext($exifData, $locationName);
            $prompt = $this->buildPrompt($context);

            switch ($this->provider) {
                case 'google':
                    return $this->analyzeWithGoogle($base64Image, $mimeType, $prompt);
                case 'openai':
                    return $this->analyzeWithOpenAI($base64Image, $mimeType, $prompt);
                case 'claude':
                    return $this->analyzeWithClaude($base64Image, $mimeType, $prompt);
                default:
                    return null;
            }

        } catch (\Exception $e) {
            Log::error('AI: Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Analyze image with Google Gemini.
     */
    protected function analyzeWithGoogle(string $base64Image, string $mimeType, string $prompt): ?array
    {
        $apiKey = $this->apiKeys['google'];
        $url = "{$this->baseUrls['google']}/{$this->models['google']}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Image,
                            ]
                        ],
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500,
            ]
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'ai_payload_');
        file_put_contents($tempFile, json_encode($payload));

        $command = sprintf(
            'curl -s -X POST "%s" -H "Content-Type: application/json" -d @%s 2>/dev/null',
            $url,
            escapeshellarg($tempFile)
        );

        $response = shell_exec($command);
        @unlink($tempFile);

        if (!$response) {
            Log::warning('AI: No response from Google API');
            return null;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            Log::warning('AI: Google API error', ['error' => $data['error']]);
            return null;
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            Log::warning('AI: No text in Google response');
            return null;
        }

        return $this->parseResponse($text);
    }

    /**
     * Analyze image with OpenAI GPT-4 Vision.
     */
    protected function analyzeWithOpenAI(string $base64Image, string $mimeType, string $prompt): ?array
    {
        $apiKey = $this->apiKeys['openai'];
        $url = $this->baseUrls['openai'];

        $payload = [
            'model' => $this->models['openai'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$base64Image}",
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'max_tokens' => 500,
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'ai_payload_');
        file_put_contents($tempFile, json_encode($payload));

        $command = sprintf(
            'curl -s -X POST "%s" -H "Content-Type: application/json" -H "Authorization: Bearer %s" -d @%s 2>/dev/null',
            $url,
            $apiKey,
            escapeshellarg($tempFile)
        );

        $response = shell_exec($command);
        @unlink($tempFile);

        if (!$response) {
            Log::warning('AI: No response from OpenAI API');
            return null;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            Log::warning('AI: OpenAI API error', ['error' => $data['error']]);
            return null;
        }

        $text = $data['choices'][0]['message']['content'] ?? null;

        if (!$text) {
            Log::warning('AI: No text in OpenAI response');
            return null;
        }

        return $this->parseResponse($text);
    }

    /**
     * Analyze image with Claude.
     */
    protected function analyzeWithClaude(string $base64Image, string $mimeType, string $prompt): ?array
    {
        $apiKey = $this->apiKeys['claude'];
        $url = $this->baseUrls['claude'];

        // Claude uses specific media types
        $mediaType = $mimeType;
        if ($mimeType === 'image/jpg') {
            $mediaType = 'image/jpeg';
        }

        $payload = [
            'model' => $this->models['claude'],
            'max_tokens' => 500,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mediaType,
                                'data' => $base64Image,
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'ai_payload_');
        file_put_contents($tempFile, json_encode($payload));

        $command = sprintf(
            'curl -s -X POST "%s" -H "Content-Type: application/json" -H "x-api-key: %s" -H "anthropic-version: 2023-06-01" -d @%s 2>/dev/null',
            $url,
            $apiKey,
            escapeshellarg($tempFile)
        );

        $response = shell_exec($command);
        @unlink($tempFile);

        if (!$response) {
            Log::warning('AI: No response from Claude API');
            return null;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            Log::warning('AI: Claude API error', ['error' => $data['error']]);
            return null;
        }

        $text = $data['content'][0]['text'] ?? null;

        if (!$text) {
            Log::warning('AI: No text in Claude response');
            return null;
        }

        return $this->parseResponse($text);
    }

    /**
     * Build context string from EXIF and location data.
     */
    protected function buildContext(?array $exifData, ?string $locationName): string
    {
        $context = [];

        if ($locationName) {
            $context[] = "Location: {$locationName}";
        }

        if ($exifData) {
            if (!empty($exifData['Make']) && !empty($exifData['Model'])) {
                $context[] = "Camera: {$exifData['Make']} {$exifData['Model']}";
            }

            if (!empty($exifData['FocalLength'])) {
                $context[] = "Focal Length: {$exifData['FocalLength']}mm";
            }

            if (!empty($exifData['FNumber'])) {
                $context[] = "Aperture: f/{$exifData['FNumber']}";
            }

            if (!empty($exifData['ExposureTime'])) {
                $context[] = "Shutter: {$exifData['ExposureTime']}s";
            }

            if (!empty($exifData['DateTimeOriginal'])) {
                $context[] = "Taken: {$exifData['DateTimeOriginal']}";
            }
        }

        return implode("\n", $context);
    }

    /**
     * Build the AI prompt.
     */
    protected function buildPrompt(string $context): string
    {
        $prompt = "You are a professional photography curator. Analyze this photograph and provide:

1. A compelling, creative TITLE (max 60 characters) - should be evocative and artistic, not just descriptive
2. A SHORT DESCRIPTION (max 150 characters) - brief summary for galleries and search
3. A STORY (2-3 sentences) - describe the mood, atmosphere, what makes this photo special

";

        if ($context) {
            $prompt .= "Additional context about this photo:\n{$context}\n\n";
        }

        $prompt .= "Respond in this exact JSON format (no markdown, just JSON):
{
  \"title\": \"Your creative title here\",
  \"description\": \"Brief description for galleries\",
  \"story\": \"A longer narrative about the photo's mood and what makes it special.\"
}";

        return $prompt;
    }

    /**
     * Parse the AI response to extract title, description, and story.
     */
    protected function parseResponse(string $text): ?array
    {
        $text = trim($text);

        // Remove markdown code blocks if present
        $text = preg_replace('/^```json?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);

        $data = json_decode($text, true);

        if ($data && isset($data['title'])) {
            return [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'story' => $data['story'] ?? null,
            ];
        }

        // Fallback: try to extract with regex
        preg_match('/"title"\s*:\s*"([^"]+)"/', $text, $titleMatch);
        preg_match('/"description"\s*:\s*"([^"]+)"/', $text, $descMatch);
        preg_match('/"story"\s*:\s*"([^"]+)"/', $text, $storyMatch);

        if ($titleMatch) {
            return [
                'title' => $titleMatch[1] ?? null,
                'description' => $descMatch[1] ?? null,
                'story' => $storyMatch[1] ?? null,
            ];
        }

        Log::warning('AI: Could not parse response', ['text' => $text]);
        return null;
    }

    /**
     * Generate content for a single photo (can be called manually).
     */
    public function generateForPhoto(\App\Models\Photo $photo): ?array
    {
        $imagePath = storage_path('app/public/' . $photo->display_path);

        return $this->analyzeImage(
            $imagePath,
            $photo->exif_data,
            $photo->location_name
        );
    }
}
