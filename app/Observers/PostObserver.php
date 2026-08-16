<?php

namespace App\Observers;

use App\Models\Post;
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

        if (SocialPost::where('post_id', $post->id)->where('platform', 'twitter')->exists()) {
            return;
        }

        app(SocialMediaService::class)->createBlogPost($post, 'twitter');
    }
}
