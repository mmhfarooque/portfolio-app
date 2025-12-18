<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Map Style
    |--------------------------------------------------------------------------
    |
    | The default map tile style to use. Options: osm, carto-dark, carto-light,
    | stamen-terrain, stamen-toner
    |
    */
    'default_style' => env('MAP_DEFAULT_STYLE', 'carto-dark'),

    /*
    |--------------------------------------------------------------------------
    | Map Tile Providers
    |--------------------------------------------------------------------------
    */
    'styles' => [
        'osm' => [
            'name' => 'OpenStreetMap',
            'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            'dark' => false,
        ],
        'carto-dark' => [
            'name' => 'CartoDB Dark',
            'url' => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
            'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            'dark' => true,
        ],
        'carto-light' => [
            'name' => 'CartoDB Light',
            'url' => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
            'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            'dark' => false,
        ],
        'carto-voyager' => [
            'name' => 'CartoDB Voyager',
            'url' => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
            'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            'dark' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Clustering Options
    |--------------------------------------------------------------------------
    */
    'clustering' => [
        'enabled' => true,
        'max_cluster_radius' => 50,
        'spiderfy_on_max_zoom' => true,
        'show_coverage_on_hover' => true,
        'animate' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Map View
    |--------------------------------------------------------------------------
    */
    'default_center' => [
        'lat' => env('MAP_DEFAULT_LAT', 40.7128),
        'lng' => env('MAP_DEFAULT_LNG', -74.0060),
    ],

    'default_zoom' => env('MAP_DEFAULT_ZOOM', 4),
    'max_zoom' => 18,
    'min_zoom' => 2,
];
