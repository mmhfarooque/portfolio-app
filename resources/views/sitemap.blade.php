<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<?php
    // Force HTTPS for all URLs in sitemap
    $secure = function($url) {
        return str_replace('http://', 'https://', $url);
    };
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    {{-- Homepage --}}
    <url>
        <loc>{{ $secure(route('home')) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Photos Index --}}
    <url>
        <loc>{{ $secure(route('photos.index')) }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    {{-- About Page --}}
    <url>
        <loc>{{ $secure(route('about')) }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    {{-- Contact Page --}}
    <url>
        <loc>{{ $secure(route('contact')) }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>

    {{-- Photos Map --}}
    <url>
        <loc>{{ $secure(route('photos.map')) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>

    {{-- Individual Photos --}}
    @foreach($photos as $photo)
    <url>
        <loc>{{ $secure(route('photos.show', $photo->slug)) }}</loc>
        <lastmod>{{ $photo->updated_at->toW3cString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
        <image:image>
            <image:loc>{{ $secure(url('storage/' . $photo->display_path)) }}</image:loc>
        </image:image>
    </url>
    @endforeach

    {{-- Categories --}}
    @foreach($categories as $category)
    <url>
        <loc>{{ $secure(route('category.show', $category->slug)) }}</loc>
        <lastmod>{{ $category->updated_at->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

    {{-- Galleries --}}
    @foreach($galleries as $gallery)
    <url>
        <loc>{{ $secure(route('gallery.show', $gallery->slug)) }}</loc>
        <lastmod>{{ $gallery->updated_at->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

    {{-- Tags --}}
    @foreach($tags as $tag)
    <url>
        <loc>{{ $secure(route('tag.show', $tag->slug)) }}</loc>
        <lastmod>{{ $tag->updated_at->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
    @endforeach

    {{-- Blog Index --}}
    <url>
        <loc>{{ $secure(route('blog.index')) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    {{-- Blog Posts --}}
    @if(isset($posts))
    @foreach($posts as $post)
    <url>
        <loc>{{ $secure(route('blog.show', $post->slug)) }}</loc>
        <lastmod>{{ $post->updated_at->toW3cString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
    @endif

</urlset>
