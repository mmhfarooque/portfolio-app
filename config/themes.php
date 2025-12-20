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
            'name' => 'Darkroom',
            'description' => 'Cinematic dark theme inspired by the photographer\'s darkroom',
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
            'name' => 'Gallery',
            'description' => 'Elegant museum gallery aesthetic for fine art photography',
            'preview' => [
                'bg' => '#fafaf9',
                'accent' => '#1e3a5f',
                'text' => '#1c1917',
            ],
            'colors' => [
                // Backgrounds - Warm gallery whites
                'bg-primary' => '#fafaf9',
                'bg-secondary' => '#f5f5f4',
                'bg-tertiary' => '#e7e5e4',
                'bg-card' => '#ffffff',
                'bg-hover' => '#d6d3d1',
                'bg-input' => '#ffffff',

                // Text - Rich warm blacks
                'text-primary' => '#1c1917',
                'text-secondary' => '#57534e',
                'text-muted' => '#a8a29e',
                'text-inverse' => '#fafaf9',

                // Borders - Subtle warm grays
                'border' => '#d6d3d1',
                'border-light' => '#e7e5e4',

                // Accent - Deep sophisticated navy
                'accent' => '#1e3a5f',
                'accent-hover' => '#152a45',
                'accent-light' => 'rgba(30, 58, 95, 0.08)',

                // Status colors
                'success' => '#166534',
                'warning' => '#a16207',
                'error' => '#b91c1c',

                // Special
                'overlay' => 'rgba(28, 25, 23, 0.6)',
                'shadow' => 'rgba(28, 25, 23, 0.08)',
            ],
            'styles' => [
                'font-family' => "'Crimson Pro', 'Libre Baskerville', Georgia, serif",
                'font-heading' => "'Cormorant Garamond', 'Playfair Display', Georgia, serif",
                'border-radius' => '0.25rem',
                'border-radius-lg' => '0.5rem',
                'shadow-sm' => '0 1px 3px 0 rgba(28, 25, 23, 0.06)',
                'shadow' => '0 4px 12px -2px rgba(28, 25, 23, 0.08), 0 2px 4px -1px rgba(28, 25, 23, 0.04)',
                'shadow-lg' => '0 16px 32px -8px rgba(28, 25, 23, 0.12), 0 8px 16px -4px rgba(28, 25, 23, 0.06)',
                'transition' => '250ms ease-out',
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
