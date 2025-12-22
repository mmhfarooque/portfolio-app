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
    | Available Themes - Darkroom, Gallery, Vintage
    |--------------------------------------------------------------------------
    | Each theme provides comprehensive styling inspired by photography.
    */
    'themes' => [
        'dark' => [
            'name' => 'Dark',
            'description' => 'Cinematic dark theme with warm amber accents',
            'preview' => [
                'bg' => '#0c0c0c',
                'accent' => '#d4a574',
                'text' => '#f5f5f4',
            ],
            'colors' => [
                // Backgrounds - Deep cinematic blacks with warm undertones
                'bg-primary' => '#0c0c0c',
                'bg-secondary' => '#141414',
                'bg-tertiary' => '#1c1c1c',
                'bg-card' => '#121212',
                'bg-hover' => '#242424',
                'bg-input' => '#181818',

                // Text - Warm whites, not stark
                'text-primary' => '#f5f5f4',
                'text-secondary' => '#a8a29e',
                'text-muted' => '#78716c',
                'text-inverse' => '#0c0c0c',

                // Borders - Subtle warm grays
                'border' => '#292524',
                'border-light' => '#3d3835',

                // Accent - Golden hour amber (photography-inspired)
                'accent' => '#d4a574',
                'accent-hover' => '#c4956a',
                'accent-light' => 'rgba(212, 165, 116, 0.12)',

                // Status colors
                'success' => '#86efac',
                'warning' => '#fcd34d',
                'error' => '#fca5a5',

                // Special
                'overlay' => 'rgba(12, 12, 12, 0.85)',
                'shadow' => 'rgba(0, 0, 0, 0.6)',
            ],
            'styles' => [
                'font-family' => "'Plus Jakarta Sans', 'Inter', system-ui, sans-serif",
                'font-heading' => "'Sora', 'Plus Jakarta Sans', system-ui, sans-serif",
                'border-radius' => '0.375rem',
                'border-radius-lg' => '0.75rem',
                'shadow-sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.4)',
                'shadow' => '0 4px 16px -2px rgba(0, 0, 0, 0.5), 0 2px 4px -2px rgba(0, 0, 0, 0.4)',
                'shadow-lg' => '0 20px 40px -8px rgba(0, 0, 0, 0.6), 0 8px 16px -4px rgba(0, 0, 0, 0.4)',
                'transition' => '200ms cubic-bezier(0.4, 0, 0.2, 1)',
            ],
            'is_dark' => true,
        ],

        'light' => [
            'name' => 'Light',
            'description' => 'Clean, bright and modern with blue accents',
            'preview' => [
                'bg' => '#ffffff',
                'accent' => '#2563eb',
                'text' => '#111827',
            ],
            'colors' => [
                // Backgrounds - Pure clean whites
                'bg-primary' => '#ffffff',
                'bg-secondary' => '#f9fafb',
                'bg-tertiary' => '#f3f4f6',
                'bg-card' => '#ffffff',
                'bg-hover' => '#e5e7eb',
                'bg-input' => '#ffffff',

                // Text - Crisp dark grays
                'text-primary' => '#111827',
                'text-secondary' => '#4b5563',
                'text-muted' => '#9ca3af',
                'text-inverse' => '#ffffff',

                // Borders - Light cool grays
                'border' => '#e5e7eb',
                'border-light' => '#f3f4f6',

                // Accent - Vibrant blue
                'accent' => '#2563eb',
                'accent-hover' => '#1d4ed8',
                'accent-light' => 'rgba(37, 99, 235, 0.1)',

                // Status colors
                'success' => '#059669',
                'warning' => '#d97706',
                'error' => '#dc2626',

                // Special
                'overlay' => 'rgba(17, 24, 39, 0.5)',
                'shadow' => 'rgba(0, 0, 0, 0.1)',
            ],
            'styles' => [
                'font-family' => "'Inter', 'Segoe UI', system-ui, sans-serif",
                'font-heading' => "'Inter', 'Segoe UI', system-ui, sans-serif",
                'border-radius' => '0.5rem',
                'border-radius-lg' => '0.75rem',
                'shadow-sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'shadow' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                'shadow-lg' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                'transition' => '150ms ease-out',
            ],
            'is_dark' => false,
        ],

        'retro' => [
            'name' => 'Vintage',
            'description' => 'Nostalgic analog film photography aesthetic',
            'preview' => [
                'bg' => '#f5f0e8',
                'accent' => '#8b2942',
                'text' => '#2d2520',
            ],
            'colors' => [
                // Backgrounds - Aged paper and sepia tones
                'bg-primary' => '#f5f0e8',
                'bg-secondary' => '#ebe4d9',
                'bg-tertiary' => '#ddd5c8',
                'bg-card' => '#faf7f2',
                'bg-hover' => '#d4cbbf',
                'bg-input' => '#faf7f2',

                // Text - Deep warm browns
                'text-primary' => '#2d2520',
                'text-secondary' => '#5c4f45',
                'text-muted' => '#8b7b6e',
                'text-inverse' => '#f5f0e8',

                // Borders - Warm sepia
                'border' => '#c9bfb0',
                'border-light' => '#ddd5c8',

                // Accent - Deep burgundy (like darkroom safe light)
                'accent' => '#8b2942',
                'accent-hover' => '#6d1f33',
                'accent-light' => 'rgba(139, 41, 66, 0.1)',

                // Status colors
                'success' => '#2d5a27',
                'warning' => '#926c15',
                'error' => '#9a2c2c',

                // Special
                'overlay' => 'rgba(45, 37, 32, 0.75)',
                'shadow' => 'rgba(45, 37, 32, 0.15)',
            ],
            'styles' => [
                'font-family' => "'Libre Baskerville', 'Lora', Georgia, serif",
                'font-heading' => "'Playfair Display', 'Libre Baskerville', Georgia, serif",
                'border-radius' => '0.125rem',
                'border-radius-lg' => '0.25rem',
                'shadow-sm' => '1px 1px 0 0 rgba(139, 41, 66, 0.08), 2px 2px 4px 0 rgba(45, 37, 32, 0.1)',
                'shadow' => '2px 2px 0 0 rgba(139, 41, 66, 0.1), 4px 4px 12px 0 rgba(45, 37, 32, 0.12)',
                'shadow-lg' => '3px 3px 0 0 rgba(139, 41, 66, 0.12), 8px 8px 24px 0 rgba(45, 37, 32, 0.15)',
                'transition' => '300ms ease-in-out',
            ],
            'is_dark' => false,
        ],
    ],
];
