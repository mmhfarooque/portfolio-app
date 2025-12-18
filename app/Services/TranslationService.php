<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    /**
     * Supported locales.
     */
    protected array $locales = [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'ja' => '日本語',
    ];

    /**
     * Get supported locales.
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * Get current locale.
     */
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Set locale.
     */
    public function setLocale(string $locale): void
    {
        if (array_key_exists($locale, $this->locales)) {
            App::setLocale($locale);
            session(['locale' => $locale]);
        }
    }

    /**
     * Get translated field for a model.
     */
    public function getTranslation(Model $model, string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? $this->getCurrentLocale();

        // Return original if default locale
        if ($locale === config('app.fallback_locale', 'en')) {
            return $model->{$field};
        }

        // Check cache first
        $cacheKey = "translation.{$model->getTable()}.{$model->id}.{$field}.{$locale}";

        return Cache::remember($cacheKey, 3600, function () use ($model, $field, $locale) {
            $translation = Translation::getFor($model, $field, $locale);

            // Fallback to original if no translation
            return $translation ?? $model->{$field};
        });
    }

    /**
     * Set translation for a model field.
     */
    public function setTranslation(Model $model, string $field, string $locale, string $value): void
    {
        Translation::setFor($model, $field, $locale, $value);

        // Clear cache
        $cacheKey = "translation.{$model->getTable()}.{$model->id}.{$field}.{$locale}";
        Cache::forget($cacheKey);
    }

    /**
     * Get all translations for a model.
     */
    public function getAllTranslations(Model $model): array
    {
        return Translation::allFor($model);
    }

    /**
     * Set multiple translations at once.
     */
    public function setTranslations(Model $model, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            foreach ($fields as $field => $value) {
                if (!empty($value)) {
                    $this->setTranslation($model, $field, $locale, $value);
                }
            }
        }
    }

    /**
     * Delete all translations for a model.
     */
    public function deleteTranslations(Model $model): void
    {
        Translation::deleteFor($model);

        // Clear all related cache
        foreach ($this->locales as $locale => $name) {
            Cache::forget("translation.{$model->getTable()}.{$model->id}.*.{$locale}");
        }
    }

    /**
     * Get locale from URL or preference.
     */
    public function detectLocale(): string
    {
        // Check URL parameter
        if (request()->has('lang')) {
            $locale = request()->get('lang');
            if (array_key_exists($locale, $this->locales)) {
                return $locale;
            }
        }

        // Check session
        if (session()->has('locale')) {
            return session('locale');
        }

        // Check user preference
        if (auth()->check() && auth()->user()->locale) {
            return auth()->user()->locale;
        }

        // Check browser language
        $browserLocale = substr(request()->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
        if (array_key_exists($browserLocale, $this->locales)) {
            return $browserLocale;
        }

        return config('app.fallback_locale', 'en');
    }

    /**
     * Check if locale is supported.
     */
    public function isValidLocale(string $locale): bool
    {
        return array_key_exists($locale, $this->locales);
    }

    /**
     * Get locale name.
     */
    public function getLocaleName(string $locale): string
    {
        return $this->locales[$locale] ?? $locale;
    }

    /**
     * Get translation completion percentage for a model.
     */
    public function getCompletionPercentage(Model $model, array $fields): array
    {
        $translations = $this->getAllTranslations($model);
        $percentages = [];

        foreach ($this->locales as $locale => $name) {
            if ($locale === config('app.fallback_locale', 'en')) {
                $percentages[$locale] = 100;
                continue;
            }

            $translated = 0;
            foreach ($fields as $field) {
                if (!empty($translations[$locale][$field] ?? null)) {
                    $translated++;
                }
            }

            $percentages[$locale] = count($fields) > 0
                ? round(($translated / count($fields)) * 100)
                : 0;
        }

        return $percentages;
    }
}
