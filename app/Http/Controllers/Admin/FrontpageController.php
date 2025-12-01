<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class FrontpageController extends Controller
{
    /**
     * Display the frontpage settings.
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');

        return view('admin.frontpage.index', compact('settings'));
    }

    /**
     * Update frontpage settings.
     */
    public function update(Request $request)
    {
        $settings = $request->except(['_token', '_method']);

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

                        // Resize profile images to reasonable size
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

                $setting->update(['value' => $value]);
            }
        }

        Setting::clearCache();

        LoggingService::settingsUpdated(array_keys($request->except(['_token', '_method'])));

        return redirect()
            ->route('admin.frontpage.index')
            ->with('success', 'Front page settings updated successfully.');
    }
}
