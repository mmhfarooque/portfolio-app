<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaService
{
    /**
     * Create a social post for a photo.
     */
    public function createPhotoPost(Photo $photo, string $platform, array $options = []): SocialPost
    {
        return SocialPost::create([
            'photo_id' => $photo->id,
            'platform' => $platform,
            'status' => $options['schedule_at'] ? 'scheduled' : 'pending',
            'caption' => $options['caption'] ?? $this->generatePhotoCaption($photo),
            'hashtags' => $options['hashtags'] ?? $this->generateHashtags($photo),
            'scheduled_at' => $options['schedule_at'] ?? null,
        ]);
    }

    /**
     * Create a social post for a blog post.
     */
    public function createBlogPost(Post $post, string $platform, array $options = []): SocialPost
    {
        return SocialPost::create([
            'post_id' => $post->id,
            'platform' => $platform,
            'status' => $options['schedule_at'] ? 'scheduled' : 'pending',
            'caption' => $options['caption'] ?? $this->generateBlogCaption($post),
            'hashtags' => $options['hashtags'] ?? [],
            'scheduled_at' => $options['schedule_at'] ?? null,
        ]);
    }

    /**
     * Generate caption for a photo.
     */
    protected function generatePhotoCaption(Photo $photo): string
    {
        $parts = [];

        if ($photo->title) {
            $parts[] = $photo->title;
        }

        if ($photo->description) {
            $parts[] = $photo->description;
        }

        if ($photo->location_name) {
            $parts[] = "\u{1F4CD} " . $photo->location_name;
        }

        if ($photo->camera_make || $photo->camera_model) {
            $camera = trim(($photo->camera_make ?? '') . ' ' . ($photo->camera_model ?? ''));
            $parts[] = "\u{1F4F7} Shot on " . $camera;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Generate caption for a blog post.
     */
    protected function generateBlogCaption(Post $post): string
    {
        $caption = $post->title;

        if ($post->excerpt) {
            $caption .= "\n\n" . $post->excerpt;
        }

        $caption .= "\n\n" . route('blog.show', $post);

        return $caption;
    }

    /**
     * Generate hashtags from photo tags.
     */
    protected function generateHashtags(Photo $photo): array
    {
        $hashtags = [];

        foreach ($photo->tags as $tag) {
            $hashtag = preg_replace('/[^a-zA-Z0-9]/', '', $tag->name);
            if ($hashtag) {
                $hashtags[] = '#' . $hashtag;
            }
        }

        // Add category hashtag
        if ($photo->category) {
            $categoryTag = preg_replace('/[^a-zA-Z0-9]/', '', $photo->category->name);
            if ($categoryTag) {
                $hashtags[] = '#' . $categoryTag;
            }
        }

        // Add photography-related hashtags
        $hashtags = array_merge($hashtags, ['#photography', '#photooftheday']);

        return array_unique($hashtags);
    }

    /**
     * Publish a social post.
     */
    public function publish(SocialPost $socialPost): bool
    {
        $account = SocialAccount::where('platform', $socialPost->platform)
            ->active()
            ->first();

        if (!$account) {
            $socialPost->markAsFailed('No active account for platform');
            return false;
        }

        try {
            $result = match ($socialPost->platform) {
                'twitter' => $this->publishToTwitter($socialPost, $account),
                'facebook' => $this->publishToFacebook($socialPost, $account),
                'instagram' => $this->publishToInstagram($socialPost, $account),
                default => throw new \Exception("Unsupported platform: {$socialPost->platform}"),
            };

            if ($result['success']) {
                $socialPost->markAsPublished(
                    $result['external_id'] ?? null,
                    $result['external_url'] ?? null
                );
                return true;
            }

            $socialPost->markAsFailed($result['error'] ?? 'Unknown error');
            return false;

        } catch (\Exception $e) {
            Log::error('Social media publish failed', [
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'error' => $e->getMessage(),
            ]);

            $socialPost->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Publish to Twitter/X.
     */
    protected function publishToTwitter(SocialPost $post, SocialAccount $account): array
    {
        $text = $post->caption;

        // Add hashtags
        if ($post->hashtags) {
            $text .= "\n\n" . implode(' ', array_slice($post->hashtags, 0, 5));
        }

        // Twitter API v2 - This is a placeholder implementation
        // In production, use proper OAuth and the Twitter API
        $response = Http::withToken($account->access_token)
            ->post('https://api.twitter.com/2/tweets', [
                'text' => mb_substr($text, 0, 280),
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'external_id' => $data['data']['id'] ?? null,
                'external_url' => isset($data['data']['id'])
                    ? "https://twitter.com/{$account->username}/status/{$data['data']['id']}"
                    : null,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('detail') ?? 'Twitter API error',
        ];
    }

    /**
     * Publish to Facebook.
     */
    protected function publishToFacebook(SocialPost $post, SocialAccount $account): array
    {
        $text = $post->caption;

        if ($post->hashtags) {
            $text .= "\n\n" . implode(' ', $post->hashtags);
        }

        $params = [
            'message' => $text,
            'access_token' => $account->access_token,
        ];

        // Add photo if available
        $imageUrl = $post->getImageUrl();
        if ($imageUrl) {
            $params['url'] = $imageUrl;
            $endpoint = "https://graph.facebook.com/v18.0/{$account->platform_user_id}/photos";
        } else {
            $endpoint = "https://graph.facebook.com/v18.0/{$account->platform_user_id}/feed";
        }

        $response = Http::post($endpoint, $params);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'external_id' => $data['id'] ?? $data['post_id'] ?? null,
                'external_url' => isset($data['id'])
                    ? "https://facebook.com/{$data['id']}"
                    : null,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message') ?? 'Facebook API error',
        ];
    }

    /**
     * Publish to Instagram (via Facebook Graph API).
     */
    protected function publishToInstagram(SocialPost $post, SocialAccount $account): array
    {
        $imageUrl = $post->getImageUrl();

        if (!$imageUrl) {
            return [
                'success' => false,
                'error' => 'Instagram requires an image',
            ];
        }

        $caption = $post->caption;
        if ($post->hashtags) {
            $caption .= "\n\n" . implode(' ', $post->hashtags);
        }

        // Step 1: Create media container
        $response = Http::post("https://graph.facebook.com/v18.0/{$account->platform_user_id}/media", [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $account->access_token,
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('error.message') ?? 'Failed to create media container',
            ];
        }

        $containerId = $response->json('id');

        // Step 2: Publish media
        $publishResponse = Http::post("https://graph.facebook.com/v18.0/{$account->platform_user_id}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $account->access_token,
        ]);

        if ($publishResponse->successful()) {
            $data = $publishResponse->json();
            return [
                'success' => true,
                'external_id' => $data['id'] ?? null,
                'external_url' => "https://instagram.com/p/{$data['id']}",
            ];
        }

        return [
            'success' => false,
            'error' => $publishResponse->json('error.message') ?? 'Failed to publish media',
        ];
    }

    /**
     * Process scheduled posts that are ready.
     */
    public function processScheduledPosts(): int
    {
        $posts = SocialPost::readyToPublish()->get();
        $published = 0;

        foreach ($posts as $post) {
            if ($this->publish($post)) {
                $published++;
            }
        }

        return $published;
    }

    /**
     * Update engagement metrics for published posts.
     */
    public function updateEngagement(SocialPost $post): void
    {
        if (!$post->isPublished() || !$post->external_id) {
            return;
        }

        $account = SocialAccount::where('platform', $post->platform)
            ->active()
            ->first();

        if (!$account) {
            return;
        }

        try {
            $engagement = match ($post->platform) {
                'twitter' => $this->getTwitterEngagement($post, $account),
                'facebook' => $this->getFacebookEngagement($post, $account),
                'instagram' => $this->getInstagramEngagement($post, $account),
                default => null,
            };

            if ($engagement) {
                $post->update(['engagement' => $engagement]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update engagement', [
                'social_post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getTwitterEngagement(SocialPost $post, SocialAccount $account): ?array
    {
        $response = Http::withToken($account->access_token)
            ->get("https://api.twitter.com/2/tweets/{$post->external_id}", [
                'tweet.fields' => 'public_metrics',
            ]);

        if ($response->successful()) {
            $metrics = $response->json('data.public_metrics');
            return [
                'likes' => $metrics['like_count'] ?? 0,
                'retweets' => $metrics['retweet_count'] ?? 0,
                'replies' => $metrics['reply_count'] ?? 0,
                'impressions' => $metrics['impression_count'] ?? 0,
            ];
        }

        return null;
    }

    protected function getFacebookEngagement(SocialPost $post, SocialAccount $account): ?array
    {
        $response = Http::get("https://graph.facebook.com/v18.0/{$post->external_id}", [
            'fields' => 'likes.summary(true),comments.summary(true),shares',
            'access_token' => $account->access_token,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'likes' => $data['likes']['summary']['total_count'] ?? 0,
                'comments' => $data['comments']['summary']['total_count'] ?? 0,
                'shares' => $data['shares']['count'] ?? 0,
            ];
        }

        return null;
    }

    protected function getInstagramEngagement(SocialPost $post, SocialAccount $account): ?array
    {
        $response = Http::get("https://graph.facebook.com/v18.0/{$post->external_id}", [
            'fields' => 'like_count,comments_count',
            'access_token' => $account->access_token,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'likes' => $data['like_count'] ?? 0,
                'comments' => $data['comments_count'] ?? 0,
            ];
        }

        return null;
    }

    /**
     * Get available platforms.
     */
    public function getAvailablePlatforms(): array
    {
        return [
            'twitter' => [
                'name' => 'Twitter / X',
                'icon' => 'x-twitter',
                'connected' => SocialAccount::forPlatform('twitter')->active()->exists(),
            ],
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'facebook',
                'connected' => SocialAccount::forPlatform('facebook')->active()->exists(),
            ],
            'instagram' => [
                'name' => 'Instagram',
                'icon' => 'instagram',
                'connected' => SocialAccount::forPlatform('instagram')->active()->exists(),
            ],
        ];
    }
}
