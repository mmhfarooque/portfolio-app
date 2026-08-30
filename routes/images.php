<?php

use App\Enums\ImageFormat;
use App\Enums\ImageVariant;
use App\Http\Controllers\ServePhotoImage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Image delivery
|--------------------------------------------------------------------------
|
| Deliberately outside the web group. An image needs no session, no CSRF
| token, no referral tracking and no Inertia negotiation — and inheriting them
| is not merely wasteful: the session and CSRF middleware attach Set-Cookie
| and Vary: X-Inertia to every response, which makes Cloudflare refuse to
| cache any of it and sends every image through PHP.
|
| bootstrap/app.php gives this file route model binding and nothing else, so
| the stack cannot drift when the framework renames its middleware.
|
| The slug is the identity; role, width and format are delivery details, so a
| future format or a new width ladder adds URLs rather than breaking indexed
| ones.
|
*/

Route::get('/img/{photo:slug}/{variant}-{width}.{format}', ServePhotoImage::class)
    ->where('variant', implode('|', ImageVariant::values()))
    ->where('width', '[0-9]+')
    ->where('format', implode('|', ImageFormat::values()))
    ->name('photo.image');
