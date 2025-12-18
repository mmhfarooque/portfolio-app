<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>{{ $siteName }} - Blog</title>
    <subtitle>{{ $siteDescription }}</subtitle>
    <link href="{{ route('feed.atom') }}" rel="self" type="application/atom+xml"/>
    <link href="{{ url('/blog') }}" rel="alternate" type="text/html"/>
    <id>{{ url('/blog') }}</id>
    <updated>{{ $posts->first()?->published_at?->toAtomString() ?? now()->toAtomString() }}</updated>
    <generator uri="https://laravel.com">Laravel</generator>
    @foreach($posts as $post)
    <entry>
        <title><![CDATA[{{ $post->title }}]]></title>
        <link href="{{ route('blog.show', $post->slug) }}" rel="alternate" type="text/html"/>
        <id>{{ route('blog.show', $post->slug) }}</id>
        <published>{{ $post->published_at?->toAtomString() ?? $post->created_at->toAtomString() }}</published>
        <updated>{{ $post->updated_at->toAtomString() }}</updated>
        @if($post->user)
        <author>
            <name>{{ $post->user->name }}</name>
            @if($post->user->email)
            <email>{{ $post->user->email }}</email>
            @endif
        </author>
        @endif
        @if($post->category)
        <category term="{{ $post->category->slug }}" label="{{ $post->category->name }}"/>
        @endif
        <summary type="html"><![CDATA[{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 300) }}]]></summary>
        <content type="html"><![CDATA[{!! $post->content !!}]]></content>
        @if($post->featured_image)
        <link href="{{ asset('storage/' . $post->featured_image) }}" rel="enclosure" type="image/jpeg"/>
        @endif
    </entry>
    @endforeach
</feed>
