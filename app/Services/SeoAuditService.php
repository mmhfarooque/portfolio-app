<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SeoAuditService
{
    /**
     * Audit a single photo for SEO.
     */
    public function auditPhoto(Photo $photo): array
    {
        $issues = [];
        $score = 100;

        // Title
        if (empty($photo->seo_title) && empty($photo->title)) {
            $issues[] = ['type' => 'error', 'field' => 'title', 'message' => 'Missing title'];
            $score -= 20;
        } elseif (strlen($photo->seo_title ?? $photo->title) < 10) {
            $issues[] = ['type' => 'warning', 'field' => 'title', 'message' => 'Title is too short (< 10 chars)'];
            $score -= 10;
        } elseif (strlen($photo->seo_title ?? $photo->title) > 60) {
            $issues[] = ['type' => 'warning', 'field' => 'title', 'message' => 'Title is too long (> 60 chars)'];
            $score -= 5;
        }

        // Meta description
        if (empty($photo->meta_description) && empty($photo->description)) {
            $issues[] = ['type' => 'error', 'field' => 'meta_description', 'message' => 'Missing meta description'];
            $score -= 15;
        } elseif (strlen($photo->meta_description ?? $photo->description ?? '') < 50) {
            $issues[] = ['type' => 'warning', 'field' => 'meta_description', 'message' => 'Meta description is too short (< 50 chars)'];
            $score -= 10;
        } elseif (strlen($photo->meta_description ?? $photo->description ?? '') > 160) {
            $issues[] = ['type' => 'info', 'field' => 'meta_description', 'message' => 'Meta description may be truncated (> 160 chars)'];
            $score -= 5;
        }

        // Tags
        if ($photo->tags->count() === 0) {
            $issues[] = ['type' => 'warning', 'field' => 'tags', 'message' => 'No tags assigned'];
            $score -= 10;
        } elseif ($photo->tags->count() < 3) {
            $issues[] = ['type' => 'info', 'field' => 'tags', 'message' => 'Consider adding more tags (< 3)'];
            $score -= 5;
        }

        // Category
        if (!$photo->category_id) {
            $issues[] = ['type' => 'warning', 'field' => 'category', 'message' => 'No category assigned'];
            $score -= 10;
        }

        // Location
        if (!$photo->hasLocation() && !$photo->location_name) {
            $issues[] = ['type' => 'info', 'field' => 'location', 'message' => 'No location data'];
            $score -= 5;
        }

        // File size
        if ($photo->file_size > 5 * 1024 * 1024) { // > 5MB
            $issues[] = ['type' => 'warning', 'field' => 'file_size', 'message' => 'Image file is large (> 5MB)'];
            $score -= 5;
        }

        return [
            'id' => $photo->id,
            'title' => $photo->title,
            'slug' => $photo->slug,
            'score' => max(0, $score),
            'issues' => $issues,
            'grade' => $this->getGrade($score),
        ];
    }

    /**
     * Audit a single post for SEO.
     */
    public function auditPost(Post $post): array
    {
        $issues = [];
        $score = 100;

        // Title
        if (empty($post->seo_title) && empty($post->title)) {
            $issues[] = ['type' => 'error', 'field' => 'title', 'message' => 'Missing title'];
            $score -= 20;
        } elseif (strlen($post->seo_title ?? $post->title) > 60) {
            $issues[] = ['type' => 'warning', 'field' => 'title', 'message' => 'Title may be truncated (> 60 chars)'];
            $score -= 5;
        }

        // Meta description
        if (empty($post->meta_description) && empty($post->excerpt)) {
            $issues[] = ['type' => 'error', 'field' => 'meta_description', 'message' => 'Missing meta description'];
            $score -= 15;
        }

        // Featured image
        if (!$post->hasFeaturedImage()) {
            $issues[] = ['type' => 'warning', 'field' => 'featured_image', 'message' => 'No featured image'];
            $score -= 10;
        }

        // Content length
        $wordCount = str_word_count(strip_tags($post->content ?? ''));
        if ($wordCount < 300) {
            $issues[] = ['type' => 'warning', 'field' => 'content', 'message' => 'Content is short (< 300 words)'];
            $score -= 10;
        }

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'score' => max(0, $score),
            'issues' => $issues,
            'grade' => $this->getGrade($score),
        ];
    }

    /**
     * Run full site audit.
     */
    public function auditSite(): array
    {
        $photos = Photo::published()->with('tags', 'category')->get();
        $posts = Post::published()->get();

        $photoAudits = $photos->map(fn($p) => $this->auditPhoto($p));
        $postAudits = $posts->map(fn($p) => $this->auditPost($p));

        $avgPhotoScore = $photoAudits->avg('score') ?? 0;
        $avgPostScore = $postAudits->avg('score') ?? 0;

        $overallScore = $photos->count() + $posts->count() > 0
            ? ($avgPhotoScore * $photos->count() + $avgPostScore * $posts->count()) / ($photos->count() + $posts->count())
            : 0;

        return [
            'overall_score' => round($overallScore),
            'overall_grade' => $this->getGrade($overallScore),
            'photos' => [
                'count' => $photos->count(),
                'average_score' => round($avgPhotoScore),
                'issues_by_type' => $this->countIssuesByType($photoAudits),
                'audits' => $photoAudits->sortBy('score')->take(20)->values(),
            ],
            'posts' => [
                'count' => $posts->count(),
                'average_score' => round($avgPostScore),
                'issues_by_type' => $this->countIssuesByType($postAudits),
                'audits' => $postAudits->sortBy('score')->values(),
            ],
        ];
    }

    /**
     * Count issues by type across audits.
     */
    protected function countIssuesByType($audits): array
    {
        $counts = ['error' => 0, 'warning' => 0, 'info' => 0];

        foreach ($audits as $audit) {
            foreach ($audit['issues'] as $issue) {
                $counts[$issue['type']]++;
            }
        }

        return $counts;
    }

    /**
     * Get letter grade from score.
     */
    protected function getGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }
}
