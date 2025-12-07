<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel 12.8+ Automatic Eager Loading
        // Prevents N+1 query problems by automatically loading relationships
        // when they are accessed, without needing to specify with() every time
        Model::automaticallyEagerLoadRelationships();

        // Prevent lazy loading in development to catch N+1 issues early
        // Model::preventLazyLoading(!app()->isProduction());
    }
}
