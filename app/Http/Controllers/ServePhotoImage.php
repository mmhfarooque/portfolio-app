<?php

namespace App\Http\Controllers;

use App\Enums\ImageFormat;
use App\Enums\ImageVariant;
use App\Models\Photo;
use App\Services\ImageVariantService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves every public photo derivative.
 *
 * The URL carries the photo SLUG, the role, the width and the format —
 * /img/begnas-lake-pokhara-nepal/watermarked-1920.avif — while the bytes stay
 * under an opaque storage key. That separation is what lets the format ladder,
 * the encoder settings or the storage origin change without invalidating a
 * single URL Google has already indexed.
 */
class ServePhotoImage extends Controller
{
    public function __construct(private readonly ImageVariantService $variants)
    {
    }

    public function __invoke(
        Request $request,
        Photo $photo,
        string $variant,
        int $width,
        string $format,
    ): BinaryFileResponse {
        if ($photo->status !== 'published' && ! $request->user()) {
            abort(404);
        }

        $role = ImageVariant::tryFrom($variant);
        $encoding = ImageFormat::tryFrom($format);

        if ($role === null || $encoding === null) {
            abort(404);
        }

        // Only widths this variant actually advertises are servable. Without
        // the allowlist the route would be an open image-resizing endpoint.
        if (! in_array($width, $role->widths(), true)) {
            abort(404);
        }

        $file = $this->variants->ensure($photo, $role, $width, $encoding);

        if ($file === null) {
            abort(404);
        }

        $response = response()->file($file, [
            'Content-Type' => $encoding->mimeType(),
            'Cache-Control' => 'public, max-age=31536000, stale-while-revalidate=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // A strong validator built from the file itself — BinaryFileResponse
        // has no body to hash, so setAutoEtag() would give every image the
        // same tag.
        $response->setEtag(sprintf('%x-%x', filemtime($file), filesize($file)));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
