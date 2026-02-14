<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'subject',
        'body_html',
        'available_variables',
        'category',
        'is_active',
        'last_edited_by',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public static function findBySlug(string $slug): ?self
    {
        return Cache::remember("email_template:{$slug}", 3600, function () use ($slug) {
            return self::where('slug', $slug)->first();
        });
    }

    public static function clearSlugCache(string $slug): void
    {
        Cache::forget("email_template:{$slug}");
    }

    public function renderSubject(array $variables = []): string
    {
        return $this->replaceVariables($this->subject, $variables);
    }

    public function renderBody(array $variables = []): string
    {
        return $this->replaceVariables($this->body_html, $variables);
    }

    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        return $content;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    protected static function booted(): void
    {
        static::updated(function (EmailTemplate $template) {
            self::clearSlugCache($template->slug);
        });

        static::deleted(function (EmailTemplate $template) {
            self::clearSlugCache($template->slug);
        });
    }
}
