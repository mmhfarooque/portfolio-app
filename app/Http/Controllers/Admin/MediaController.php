<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class MediaController extends Controller
{
    /**
     * Get photos for the media picker.
     */
    public function photos(Request $request)
    {
        $query = Photo::query()
            ->select(['id', 'title', 'thumbnail_path', 'display_path', 'category_id'])
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $photos = $query->limit(100)->get()->map(function ($photo) {
            return [
                'id' => $photo->id,
                'title' => $photo->title,
                'thumbnail_path' => $photo->thumbnail_path,
                'display_path' => $photo->display_path,
                'thumbnail_url' => asset('storage/' . $photo->thumbnail_path),
                'display_url' => asset('storage/' . $photo->display_path),
            ];
        });

        $categories = Category::select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'photos' => $photos,
            'categories' => $categories,
        ]);
    }

    /**
     * Upload an image for the Editor.js image tool.
     *
     * resources/js/Pages/Admin/About/EditorJs.vue has always pointed its
     * ImageTool byFile endpoint at route('admin.media.upload'), but neither the
     * route nor this method existed, so Ziggy threw on page init and the About
     * editor's image upload was dead.
     *
     * Editor.js sends the file as the `image` field and expects
     * {success: 1, file: {url: ...}} back.
     *
     * The file is re-encoded through Intervention rather than moved as-is, which
     * normalises the output to WebP and discards anything smuggled in the
     * original container. The filename is a UUID, so nothing user-supplied
     * reaches the filesystem path. The route sits inside the admin middleware
     * group, so this is not publicly reachable.
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp,avif', 'max:15360'],
        ]);

        try {
            $image = Image::decode($validated['image']->getRealPath());

            if ($image->width() > 1920) {
                $image->scale(width: 1920);
            }

            $filename = Str::uuid() . '.webp';
            $path = 'editorjs/' . $filename;

            $dir = storage_path('app/public/editorjs');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $image->encode(new WebpEncoder(quality: 85))->save(storage_path('app/public/' . $path));

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => asset('storage/' . $path),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            // Editor.js shows its own error state when success is 0.
            return response()->json([
                'success' => 0,
                'message' => 'Upload failed.',
            ], 500);
        }
    }
}
