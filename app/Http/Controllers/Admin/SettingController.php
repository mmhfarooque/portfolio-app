<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Photo;
use App\Services\AIImageService;
use App\Services\ThemeService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected ThemeService $themeService
    ) {}

    /**
     * Display the settings page.
     */
    public function index(): Response
    {
        $settings = Setting::all()->groupBy('group');

        // Get theme data
        $currentTheme = $this->themeService->getCurrentTheme();
        $themes = config('themes.themes', []);

        // Transform settings for Vue
        $settingsData = [];
        foreach ($settings as $group => $groupSettings) {
            $settingsData[$group] = [];
            foreach ($groupSettings as $setting) {
                $settingsData[$group][$setting->key] = $setting->value;
            }
        }

        // Watermark settings
        $watermarkSettings = [
            'enabled' => Setting::get('watermark_enabled', '1') === '1',
            'type' => Setting::get('watermark_type', 'text'),
            'text' => Setting::get('watermark_text', '© Photography Portfolio'),
            'position' => Setting::get('watermark_position', 'bottom-right'),
            'opacity' => (int) Setting::get('watermark_opacity', '40'),
            'size' => (int) Setting::get('watermark_size', '24'),
            'imageSize' => (int) Setting::get('watermark_image_size', '15'),
            'image' => Setting::get('watermark_image'),
        ];

        // AI settings
        $aiSettings = [
            'enabled' => Setting::get('ai_enabled', '0') === '1',
            'provider' => Setting::get('ai_provider', 'google'),
            'googleKey' => Setting::get('google_ai_api_key', ''),
            'openaiKey' => Setting::get('openai_api_key', ''),
            'claudeKey' => Setting::get('claude_api_key', ''),
            'autoTitle' => Setting::get('ai_auto_title', '1') === '1',
            'autoDescription' => Setting::get('ai_auto_description', '1') === '1',
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settingsData,
            'currentTheme' => $currentTheme,
            'themes' => $themes,
            'watermarkSettings' => $watermarkSettings,
            'aiSettings' => $aiSettings,
            'photoCount' => Photo::count(),
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $settings = $request->except(['_token', '_method']);

        // Handle theme separately
        if (isset($settings['site_theme'])) {
            $this->themeService->setTheme($settings['site_theme']);

            // If this is an AJAX request just for theme, return JSON response
            if ($request->ajax() && count($settings) === 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'Theme applied successfully',
                    'theme' => $settings['site_theme']
                ]);
            }

            unset($settings['site_theme']);
        }

        foreach ($settings as $key => $value) {
            // Skip media picker hidden inputs - they're handled below
            if (str_ends_with($key, '_from_media')) {
                continue;
            }

            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                // Check if this is an image field with media selection
                $mediaKey = $key . '_from_media';
                if ($request->filled($mediaKey)) {
                    // Delete old file if exists and is a custom upload
                    if ($setting->value && !str_starts_with($setting->value, 'photos/')) {
                        Storage::disk('public')->delete($setting->value);
                    }
                    $value = $request->input($mediaKey);
                }
                // Handle file uploads
                elseif ($request->hasFile($key)) {
                    // Delete old file if exists and is a custom upload
                    if ($setting->value && !str_starts_with($setting->value, 'photos/')) {
                        Storage::disk('public')->delete($setting->value);
                    }

                    $file = $request->file($key);

                    // Check if it's a PDF file
                    if ($file->getClientOriginalExtension() === 'pdf' || $file->getMimeType() === 'application/pdf') {
                        $filename = Str::uuid() . '.pdf';
                        $path = 'settings/' . $filename;

                        $storagePath = storage_path('app/public/settings');
                        if (!is_dir($storagePath)) {
                            mkdir($storagePath, 0755, true);
                        }

                        $file->move(storage_path('app/public/settings'), $filename);
                        $value = $path;
                    } else {
                        // Handle image files
                        $image = Image::read($file->getRealPath());

                        // Resize logo/images to reasonable size
                        if ($image->width() > 500) {
                            $image->scale(width: 500);
                        }

                        $filename = Str::uuid() . '.png';
                        $path = 'settings/' . $filename;

                        $storagePath = storage_path('app/public/settings');
                        if (!is_dir($storagePath)) {
                            mkdir($storagePath, 0755, true);
                        }

                        $image->toPng()->save(storage_path('app/public/' . $path));
                        $value = $path;
                    }
                }

                // Handle checkboxes (boolean)
                if ($setting->type === 'boolean') {
                    $value = $request->has($key) ? '1' : '0';
                }

                $setting->update(['value' => $value]);
            }
        }

        Setting::clearCache();
        Cache::forget('site_theme');

        LoggingService::settingsUpdated(array_keys($request->except(['_token', '_method'])));

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully.'
            ]);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Update theme via AJAX (dedicated POST endpoint).
     */
    public function updateTheme(Request $request)
    {
        $theme = $request->input('site_theme');

        if (!$theme) {
            return response()->json([
                'success' => false,
                'message' => 'No theme specified'
            ], 400);
        }

        $this->themeService->setTheme($theme);
        Setting::clearCache();
        Cache::forget('site_theme');

        LoggingService::settingsUpdated(['site_theme']);

        return response()->json([
            'success' => true,
            'message' => 'Theme applied successfully',
            'theme' => $theme
        ]);
    }

    /**
     * Validate AI API key for any provider.
     */
    public function validateAiApiKey(Request $request)
    {
        $apiKey = $request->input('api_key');
        $provider = $request->input('provider', 'google');

        if (empty($apiKey)) {
            return response()->json([
                'valid' => false,
                'message' => 'Please enter an API key',
            ]);
        }

        $aiService = new AIImageService();
        $result = $aiService->validateApiKey($apiKey, $provider);

        return response()->json($result);
    }

    /**
     * Regenerate watermarks for all photos.
     */
    public function regenerateWatermarks(Request $request)
    {
        $service = new \App\Services\PhotoProcessingService();
        $photos = \App\Models\Photo::all();
        $count = 0;

        foreach ($photos as $photo) {
            $service->regenerateWatermark($photo);
            $count++;
        }

        LoggingService::info('watermarks.regenerated', "Regenerated watermarks for {$count} photos");

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "Regenerated watermarks for {$count} photos"
        ]);
    }
}
