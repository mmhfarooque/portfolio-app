<?php

namespace App\Observers;

use App\Models\Photo;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\SocialMediaService;

class PhotoObserver
{
    public function created(Photo $photo): void
    {
        $this->queueSocialDraft($photo);
    }

    public function updated(Photo $photo): void
    {
        if ($photo->wasChanged('status')) {
            $this->queueSocialDraft($photo);
        }
    }

    /**
     * Confirm-before-post queue: publishing a photo drafts a pending social
     * post. Nothing is ever sent automatically — it waits in Admin → Social
     * for an explicit publish click.
     */
    protected function queueSocialDraft(Photo $photo): void
    {
        if ($photo->status !== 'published') {
            return;
        }

        // One draft per connected platform; twitter as a fallback so the
        // queue is never empty before any account is wired up.
        $platforms = SocialAccount::query()->active()->pluck('platform')->unique()->values();
        if ($platforms->isEmpty()) {
            $platforms = collect(['twitter']);
        }

        $service = app(SocialMediaService::class);
        foreach ($platforms as $platform) {
            if (SocialPost::where('photo_id', $photo->id)->where('platform', $platform)->exists()) {
                continue;
            }
            $service->createPhotoPost($photo, $platform);
        }
    }
}
