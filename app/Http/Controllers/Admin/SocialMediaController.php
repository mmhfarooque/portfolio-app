<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\SocialMediaService;
use Illuminate\Http\Request;

class SocialMediaController extends Controller
{
    protected SocialMediaService $socialService;

    public function __construct(SocialMediaService $socialService)
    {
        $this->socialService = $socialService;
    }

    /**
     * Display social media dashboard.
     */
    public function index()
    {
        $platforms = $this->socialService->getAvailablePlatforms();

        $recentPosts = SocialPost::with(['photo', 'post'])
            ->latest()
            ->take(20)
            ->get();

        $stats = [
            'total_posts' => SocialPost::count(),
            'published' => SocialPost::published()->count(),
            'scheduled' => SocialPost::scheduled()->count(),
            'failed' => SocialPost::failed()->count(),
        ];

        return view('admin.social.index', compact('platforms', 'recentPosts', 'stats'));
    }

    /**
     * Show form to create a new social post.
     */
    public function create(Request $request)
    {
        $platforms = $this->socialService->getAvailablePlatforms();
        $photos = Photo::published()->latest()->take(50)->get();
        $selectedPhoto = $request->photo_id ? Photo::find($request->photo_id) : null;

        return view('admin.social.create', compact('platforms', 'photos', 'selectedPhoto'));
    }

    /**
     * Store a new social post.
     */
    public function store(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|exists:photos,id',
            'platforms' => 'required|array',
            'platforms.*' => 'in:twitter,facebook,instagram',
            'caption' => 'nullable|string|max:2200',
            'hashtags' => 'nullable|string',
            'schedule_at' => 'nullable|date|after:now',
        ]);

        $photo = Photo::findOrFail($request->photo_id);
        $hashtags = $request->hashtags
            ? array_map('trim', explode(',', $request->hashtags))
            : null;

        $options = [
            'caption' => $request->caption,
            'hashtags' => $hashtags,
            'schedule_at' => $request->schedule_at,
        ];

        $created = 0;
        foreach ($request->platforms as $platform) {
            $this->socialService->createPhotoPost($photo, $platform, $options);
            $created++;
        }

        return redirect()->route('admin.social.index')
            ->with('success', "{$created} social post(s) created successfully.");
    }

    /**
     * View a single social post.
     */
    public function show(SocialPost $socialPost)
    {
        $socialPost->load(['photo', 'post']);

        return view('admin.social.show', compact('socialPost'));
    }

    /**
     * Publish a social post immediately.
     */
    public function publish(SocialPost $socialPost)
    {
        if ($socialPost->isPublished()) {
            return back()->with('error', 'Post is already published.');
        }

        $success = $this->socialService->publish($socialPost);

        if ($success) {
            return back()->with('success', 'Post published successfully!');
        }

        return back()->with('error', 'Failed to publish: ' . $socialPost->error_message);
    }

    /**
     * Delete a social post.
     */
    public function destroy(SocialPost $socialPost)
    {
        $socialPost->delete();

        return redirect()->route('admin.social.index')
            ->with('success', 'Social post deleted.');
    }

    /**
     * Show connected accounts.
     */
    public function accounts()
    {
        $accounts = SocialAccount::with('user')->get();
        $platforms = $this->socialService->getAvailablePlatforms();

        return view('admin.social.accounts', compact('accounts', 'platforms'));
    }

    /**
     * Disconnect a social account.
     */
    public function disconnect(SocialAccount $account)
    {
        $account->update(['is_active' => false]);

        return back()->with('success', 'Account disconnected.');
    }
}
