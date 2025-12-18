<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title>{{ $siteName }} - Blog</title>
        <description>{{ $siteDescription }}</description>
        <link>{{ url('/blog') }}</link>
        <atom:link href="{{ route('feed.rss') }}" rel="self" type="application/rss+xml"/>
        <language>en-us</language>
        <lastBuildDate>{{ $posts->first()?->published_at?->toRfc2822String() ?? now()->toRfc2822String() }}</lastBuildDate>
        <generator>Laravel</generator>
        @foreach($posts as $post)
        <item>
            <title><![CDATA[{{ $post->title }}]]></title>
            <link>{{ route('blog.show', $post->slug) }}</link>
            <guid isPermaLink="true">{{ route('blog.show', $post->slug) }}</guid>
            <pubDate>{{ $post->published_at?->toRfc2822String() ?? $post->created_at->toRfc2822String() }}</pubDate>
            @if($post->user)
            <author>{{ $post->user->email }} ({{ $post->user->name }})</author>
            @endif
            @if($post->category)
            <category><![CDATA[{{ $post->category->name }}]]></category>
            @endif
            <description><![CDATA[{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 300) }}]]></description>
            <content:encoded><![CDATA[{!! $post->content !!}]]></content:encoded>
            @if($post->featured_image)
            <media:content url="{{ asset('storage/' . $post->featured_image) }}" medium="image"/>
            <enclosure url="{{ asset('storage/' . $post->featured_image) }}" type="image/jpeg" length="0"/>
            @endif
        </item>
        @endforeach
    </channel>
</rss>
