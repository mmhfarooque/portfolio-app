<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Category;
use Illuminate\Http\Request;

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
}
