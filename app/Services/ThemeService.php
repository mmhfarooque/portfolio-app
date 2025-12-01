<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class ThemeService
{
    /**
     * Get the current active theme.
     */
    public function getCurrentTheme(): string
    {
        return Cache::remember('site_theme', 3600, function () {
            return Setting::get('site_theme', config('themes.default', 'dark'));
        });
    }

    /**
     * Get all available themes.
     */
    public function getThemes(): array
    {
        return config('themes.themes', []);
    }

    /**
     * Get a specific theme's configuration.
     */
    public function getTheme(string $name): ?array
    {
        return config("themes.themes.{$name}");
    }

    /**
     * Get CSS variables for a theme (colors + styles).
     */
    public function getCssVariables(?string $themeName = null): string
    {
        $themeName = $themeName ?? $this->getCurrentTheme();
        $theme = $this->getTheme($themeName);

        if (!$theme) {
            $theme = $this->getTheme(config('themes.default', 'dark'));
        }

        $css = ":root {\n";

        // Add color variables
        foreach ($theme['colors'] ?? [] as $key => $value) {
            $css .= "    --{$key}: {$value};\n";
        }

        // Add RGB versions for opacity support (only for hex colors)
        foreach ($theme['colors'] ?? [] as $key => $value) {
            if (strpos($value, '#') === 0) {
                $rgb = $this->hexToRgb($value);
                if ($rgb) {
                    $css .= "    --{$key}-rgb: {$rgb['r']}, {$rgb['g']}, {$rgb['b']};\n";
                }
            }
        }

        // Add style variables
        foreach ($theme['styles'] ?? [] as $key => $value) {
            $css .= "    --{$key}: {$value};\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Check if current theme is dark.
     */
    public function isDarkTheme(?string $themeName = null): bool
    {
        $themeName = $themeName ?? $this->getCurrentTheme();
        $theme = $this->getTheme($themeName);

        if (!$theme) {
            return false;
        }

        // Use is_dark property if set
        if (isset($theme['is_dark'])) {
            return $theme['is_dark'];
        }

        // Fallback: check luminance of background color
        $bgColor = $theme['colors']['bg-primary'] ?? '#ffffff';
        return $this->isColorDark($bgColor);
    }

    /**
     * Convert hex color to RGB array.
     */
    protected function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Check if a color is dark based on luminance.
     */
    protected function isColorDark(string $hex): bool
    {
        $rgb = $this->hexToRgb($hex);

        if (!$rgb) {
            return false;
        }

        $luminance = (0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b']) / 255;

        return $luminance < 0.5;
    }

    /**
     * Set the current theme.
     */
    public function setTheme(string $themeName): bool
    {
        if (!$this->getTheme($themeName)) {
            return false;
        }

        Setting::set('site_theme', $themeName);
        Cache::forget('site_theme');

        return true;
    }

    /**
     * Get theme preview data.
     */
    public function getThemePreview(string $themeName): array
    {
        $theme = $this->getTheme($themeName);

        if (!$theme) {
            return [];
        }

        return [
            'name' => $theme['name'] ?? $themeName,
            'description' => $theme['description'] ?? '',
            'preview' => $theme['preview'] ?? [
                'bg' => $theme['colors']['bg-primary'] ?? '#ffffff',
                'accent' => $theme['colors']['accent'] ?? '#3b82f6',
                'text' => $theme['colors']['text-primary'] ?? '#000000',
            ],
            'isDark' => $this->isDarkTheme($themeName),
        ];
    }

    /**
     * Get all themes with preview data for settings page.
     */
    public function getThemesForSettings(): array
    {
        $themes = $this->getThemes();
        $result = [];

        foreach ($themes as $key => $theme) {
            $result[$key] = [
                'key' => $key,
                'name' => $theme['name'] ?? $key,
                'description' => $theme['description'] ?? '',
                'preview' => $theme['preview'] ?? [
                    'bg' => $theme['colors']['bg-primary'] ?? '#ffffff',
                    'accent' => $theme['colors']['accent'] ?? '#3b82f6',
                    'text' => $theme['colors']['text-primary'] ?? '#000000',
                ],
                'isDark' => $theme['is_dark'] ?? false,
            ];
        }

        return $result;
    }
}
