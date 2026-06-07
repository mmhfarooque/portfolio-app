<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configure if and how Inertia uses Server Side Rendering
    | to pre-render the initial visits made to the application's pages.
    |
    | Bot-visible <head> metadata (title, Open Graph, Twitter cards, JSON-LD)
    | from SeoHead.vue depends on this being enabled in production.
    |
    | https://inertiajs.com/server-side-rendering
    |
    */

    'ssr' => [
        'enabled' => true,
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
    ],

    /*
    |--------------------------------------------------------------------------
    | History Encryption
    |--------------------------------------------------------------------------
    */

    'history' => [
        'encrypt' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    */

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/Pages'),
        ],
        'page_extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

];
