<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    */
    'default' => 'dark',

    /*
    |--------------------------------------------------------------------------
    | Available Themes - Dark, Light, Retro
    |--------------------------------------------------------------------------
    | Each theme provides comprehensive styling for the entire site.
    */
    'themes' => [
        'dark' => [
            'name' => 'Dark',
            'description' => 'Modern dark theme with clean aesthetics',
            'preview' => [
                'bg' => '#0f0f0f',
                'accent' => '#3b82f6',
                'text' => '#ffffff',
            ],
            'colors' => [
                // Backgrounds
                'bg-primary' => '#0f0f0f',
                'bg-secondary' => '#1a1a1a',
                'bg-tertiary' => '#262626',
                'bg-card' => '#171717',
                'bg-hover' => '#2a2a2a',
                'bg-input' => '#1f1f1f',

                // Text
                'text-primary' => '#ffffff',
                'text-secondary' => '#a1a1aa',
                'text-muted' => '#71717a',
                'text-inverse' => '#0f0f0f',

                // Borders
                'border' => '#2e2e2e',
                'border-light' => '#3f3f3f',

                // Accent
                'accent' => '#3b82f6',
                'accent-hover' => '#2563eb',
                'accent-light' => 'rgba(59, 130, 246, 0.1)',

                // Status colors
                'success' => '#22c55e',
                'warning' => '#f59e0b',
                'error' => '#ef4444',

                // Special
                'overlay' => 'rgba(0, 0, 0, 0.8)',
                'shadow' => 'rgba(0, 0, 0, 0.5)',
            ],
            'styles' => [
                'font-family' => "'Inter', 'Figtree', system-ui, sans-serif",
                'font-heading' => "'Inter', 'Figtree', system-ui, sans-serif",
                'border-radius' => '0.5rem',
                'border-radius-lg' => '1rem',
                'shadow-sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.3)',
                'shadow' => '0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3)',
                'shadow-lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -4px rgba(0, 0, 0, 0.4)',
                'transition' => '150ms ease-in-out',
            ],
            'is_dark' => true,
        ],

        'light' => [
            'name' => 'Light',
            'description' => 'Clean and bright theme with soft colors',
            'preview' => [
                'bg' => '#ffffff',
                'accent' => '#2563eb',
                'text' => '#111827',
            ],
            'colors' => [
                // Backgrounds
                'bg-primary' => '#ffffff',
                'bg-secondary' => '#f8fafc',
                'bg-tertiary' => '#f1f5f9',
                'bg-card' => '#ffffff',
                'bg-hover' => '#e2e8f0',
                'bg-input' => '#ffffff',

                // Text
                'text-primary' => '#111827',
                'text-secondary' => '#4b5563',
                'text-muted' => '#9ca3af',
                'text-inverse' => '#ffffff',

                // Borders
                'border' => '#e5e7eb',
                'border-light' => '#f3f4f6',

                // Accent
                'accent' => '#2563eb',
                'accent-hover' => '#1d4ed8',
                'accent-light' => 'rgba(37, 99, 235, 0.1)',

                // Status colors
                'success' => '#16a34a',
                'warning' => '#d97706',
                'error' => '#dc2626',

                // Special
                'overlay' => 'rgba(0, 0, 0, 0.5)',
                'shadow' => 'rgba(0, 0, 0, 0.1)',
            ],
            'styles' => [
                'font-family' => "'Inter', 'Figtree', system-ui, sans-serif",
                'font-heading' => "'Inter', 'Figtree', system-ui, sans-serif",
                'border-radius' => '0.5rem',
                'border-radius-lg' => '1rem',
                'shadow-sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'shadow' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
                'shadow-lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)',
                'transition' => '150ms ease-in-out',
            ],
            'is_dark' => false,
        ],

        'retro' => [
            'name' => 'Retro',
            'description' => 'Vintage-inspired warm tones with character',
            'preview' => [
                'bg' => '#fef3c7',
                'accent' => '#b45309',
                'text' => '#451a03',
            ],
            'colors' => [
                // Backgrounds
                'bg-primary' => '#fefce8',
                'bg-secondary' => '#fef9c3',
                'bg-tertiary' => '#fef08a',
                'bg-card' => '#fffbeb',
                'bg-hover' => '#fde68a',
                'bg-input' => '#fffbeb',

                // Text
                'text-primary' => '#451a03',
                'text-secondary' => '#78350f',
                'text-muted' => '#a16207',
                'text-inverse' => '#fefce8',

                // Borders
                'border' => '#fcd34d',
                'border-light' => '#fde68a',

                // Accent
                'accent' => '#b45309',
                'accent-hover' => '#92400e',
                'accent-light' => 'rgba(180, 83, 9, 0.15)',

                // Status colors
                'success' => '#15803d',
                'warning' => '#a16207',
                'error' => '#b91c1c',

                // Special
                'overlay' => 'rgba(69, 26, 3, 0.7)',
                'shadow' => 'rgba(120, 53, 15, 0.2)',
            ],
            'styles' => [
                'font-family' => "'Georgia', 'Cambria', 'Times New Roman', serif",
                'font-heading' => "'Georgia', 'Cambria', 'Times New Roman', serif",
                'border-radius' => '0.25rem',
                'border-radius-lg' => '0.5rem',
                'shadow-sm' => '2px 2px 0 0 rgba(180, 83, 9, 0.2)',
                'shadow' => '4px 4px 0 0 rgba(180, 83, 9, 0.2)',
                'shadow-lg' => '6px 6px 0 0 rgba(180, 83, 9, 0.25)',
                'transition' => '200ms ease-out',
            ],
            'is_dark' => false,
        ],
    ],
];
