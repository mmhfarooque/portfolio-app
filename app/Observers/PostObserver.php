<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\SocialMediaService;

class PostObserver
{
    public function created(Post $post): void
    {
        $this->queueSocialDraft($post);
    }

    public function updated(Post $post): void
    {
        if ($post->wasChanged('status')) {
            $this->queueSocialDraft($post);
        }
    }

    /**
     * Confirm-before-post queue: publishing a blog post drafts a pending
     * social post. Nothing is ever sent automatically — it waits in
     * Admin → Social for an explicit publish click.
     */
    protected function queueSocialDraft(Post $post): void
    {
        if ($post->status !== 'published') {
            return;
        }

        // One draft per ACTIVE platform only — no fallback; an unconnected
        // or deactivated platform gets no drafts.
        $platforms = SocialAccount::query()->active()->pluck('platform')->unique()->values();

        $service = app(SocialMediaService::class);
        foreach ($platforms as $platform) {
            if (SocialPost::where('post_id', $post->id)->where('platform', $platform)->exists()) {
                continue;
            }
            $service->createBlogPost($post, $platform);
        }
    }
}
