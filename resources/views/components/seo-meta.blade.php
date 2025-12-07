@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'image' => null,
    'imageWidth' => 1200,
    'imageHeight' => 630,
    'type' => 'website',
    'url' => null,
    'article' => false,
    'publishedTime' => null,
    'modifiedTime' => null,
    'author' => null,
    'twitterCard' => 'summary_large_image',
    'noindex' => false,
    'schema' => null,
])

@php
    use App\Models\Setting;

    // Get site-wide SEO settings
    $siteName = Setting::get('site_name', config('app.name'));
    $siteDescription = Setting::get('seo_site_description', Setting::get('site_tagline', ''));
    $siteKeywords = Setting::get('seo_site_keywords', '');
    $siteImage = Setting::get('seo_og_image');
    $twitterHandle = Setting::get('seo_twitter_handle', '');
    $googleVerification = Setting::get('seo_google_verification', '');
    $bingVerification = Setting::get('seo_bing_verification', '');
    $googleAnalytics = Setting::get('seo_google_analytics', '');

    // Build full title
    $seoTitle = Setting::get('seo_site_title') ?: $siteName;
    $fullTitle = $title ? "{$title} - {$seoTitle}" : $seoTitle;

    // Use provided values or fall back to site defaults
    $metaDescription = $description ?: $siteDescription;
    $metaKeywords = $keywords ?: $siteKeywords;
    $ogImage = $image ?: ($siteImage ? asset('storage/' . $siteImage) : null);
    $canonicalUrl = $url ?: request()->url();

    // Clean description for meta (limit to 160 chars)
    $metaDescription = $metaDescription ? Str::limit(strip_tags($metaDescription), 160, '...') : '';
@endphp

{{-- Basic Meta Tags --}}
<meta name="description" content="{{ $metaDescription }}">
@if($metaKeywords)
<meta name="keywords" content="{{ $metaKeywords }}">
@endif
<meta name="author" content="{{ $author ?: Setting::get('photographer_name', $siteName) }}">
@if($noindex)
<meta name="robots" content="noindex, nofollow">
@else
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
@endif

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph Meta Tags --}}
<meta property="og:type" content="{{ $article ? 'article' : $type }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
@if($ogImage)
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="{{ $imageWidth }}">
<meta property="og:image:height" content="{{ $imageHeight }}">
<meta property="og:image:alt" content="{{ $title ?: $siteName }}">
@endif
@if($article && $publishedTime)
<meta property="article:published_time" content="{{ $publishedTime }}">
@endif
@if($article && $modifiedTime)
<meta property="article:modified_time" content="{{ $modifiedTime }}">
@endif

{{-- Twitter Card Meta Tags --}}
<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if($ogImage)
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $title ?: $siteName }}">
@endif
@if($twitterHandle)
<meta name="twitter:site" content="{{ Str::startsWith($twitterHandle, '@') ? $twitterHandle : '@' . $twitterHandle }}">
<meta name="twitter:creator" content="{{ Str::startsWith($twitterHandle, '@') ? $twitterHandle : '@' . $twitterHandle }}">
@endif

{{-- Pinterest --}}
@if($ogImage)
<meta name="pinterest:description" content="{{ $metaDescription }}">
@endif

{{-- Verification Tags --}}
@if($googleVerification)
<meta name="google-site-verification" content="{{ $googleVerification }}">
@endif
@if($bingVerification)
<meta name="msvalidate.01" content="{{ $bingVerification }}">
@endif

{{-- Google Analytics --}}
@if($googleAnalytics)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalytics }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $googleAnalytics }}');
</script>
@endif

{{-- JSON-LD Schema --}}
@if($schema)
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
