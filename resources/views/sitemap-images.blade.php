<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    @foreach($photos as $photo)
    <url>
        <loc>{{ route('photos.show', $photo->slug) }}</loc>
        <image:image>
            <image:loc>{{ url('storage/' . $photo->display_path) }}</image:loc>
            <image:title>{{ htmlspecialchars($photo->title, ENT_XML1, 'UTF-8') }}</image:title>
            @if($photo->description)
            <image:caption>{{ htmlspecialchars(Str::limit($photo->description, 200), ENT_XML1, 'UTF-8') }}</image:caption>
            @endif
            @if($photo->location_name)
            <image:geo_location>{{ htmlspecialchars($photo->location_name, ENT_XML1, 'UTF-8') }}</image:geo_location>
            @endif
        </image:image>
    </url>
    @endforeach

</urlset>
