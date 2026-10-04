<?php

namespace App\Providers;

use App\Models\Photo;
use App\Models\Post;
use App\Observers\PhotoObserver;
use App\Observers\PostObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Publishing a photo or blog post drafts a pending social post
        // (confirm-before-post queue in Admin → Social; nothing auto-sends)
        Photo::observe(PhotoObserver::class);
        Post::observe(PostObserver::class);

        // /resume password attempts: 5 a minute per IP, answered with a
        // friendly form error rather than a bare 429 page.
        RateLimiter::for('resume-unlock', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn () => back()->withErrors([
                    'password' => 'Too many attempts. Please wait a minute and try again.',
                ]));
        });
    }
}
