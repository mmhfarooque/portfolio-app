<x-layouts.public>
    <x-slot name="title">{{ $post->seo_title ?? $post->title }} - {{ config('app.name', 'Photography Portfolio') }}</x-slot>

    @if ($post->meta_description)
        <x-slot name="description">{{ $post->meta_description }}</x-slot>
    @endif

    <article class="min-h-screen py-20 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <header class="mb-8">
                @if ($post->category)
                    <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}"
                       class="inline-block text-sm font-medium px-3 py-1 bg-theme-accent/10 text-theme-accent rounded-full mb-4 hover:bg-theme-accent hover:text-white transition">
                        {{ $post->category->name }}
                    </a>
                @endif

                <h1 class="text-4xl md:text-5xl font-bold text-theme-primary mb-4 leading-tight">
                    {{ $post->title }}
                </h1>

                <div class="flex items-center gap-4 text-theme-muted">
                    <span>{{ $post->published_at->format('F d, Y') }}</span>
                    <span>&bull;</span>
                    <span>{{ $post->reading_time }} min read</span>
                    @if ($post->user)
                        <span>&bull;</span>
                        <span>By {{ $post->user->name }}</span>
                    @endif
                </div>
            </header>

            <!-- Featured Image -->
            @if ($post->featured_image)
                <div class="mb-12 rounded-xl overflow-hidden">
                    <img src="{{ asset('storage/' . $post->featured_image) }}"
                         alt="{{ $post->title }}"
                         class="w-full h-auto max-h-[500px] object-cover">
                </div>
            @endif

            <!-- Content -->
            <div class="prose prose-lg max-w-none text-theme-secondary
                        prose-headings:text-theme-primary prose-headings:font-bold
                        prose-a:text-theme-accent prose-a:no-underline hover:prose-a:underline
                        prose-strong:text-theme-primary
                        prose-code:text-theme-accent prose-code:bg-theme-tertiary prose-code:px-1 prose-code:rounded
                        prose-blockquote:border-theme-accent prose-blockquote:text-theme-muted
                        prose-img:rounded-lg">
                {!! nl2br(e($post->content)) !!}
            </div>

            <!-- Tags -->
            @if ($post->tags->count() > 0)
                <div class="mt-12 pt-8 border-t border-theme">
                    <div class="flex flex-wrap gap-2 items-center">
                        <span class="text-theme-muted mr-2">Tags:</span>
                        @foreach ($post->tags as $tag)
                            <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                               class="px-3 py-1 rounded-full text-sm border border-theme text-theme-secondary hover:border-theme-accent hover:text-theme-accent transition">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Share -->
            <div class="mt-8 pt-8 border-t border-theme">
                <h3 class="text-lg font-medium text-theme-primary mb-4">Share this story</h3>
                <div class="flex gap-3">
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('blog.show', $post)) }}&text={{ urlencode($post->title) }}"
                       target="_blank"
                       class="p-3 bg-theme-tertiary hover:bg-theme-accent/20 rounded-full text-theme-secondary hover:text-theme-accent transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('blog.show', $post)) }}"
                       target="_blank"
                       class="p-3 bg-theme-tertiary hover:bg-theme-accent/20 rounded-full text-theme-secondary hover:text-theme-accent transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('blog.show', $post)) }}&title={{ urlencode($post->title) }}"
                       target="_blank"
                       class="p-3 bg-theme-tertiary hover:bg-theme-accent/20 rounded-full text-theme-secondary hover:text-theme-accent transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                    <button onclick="navigator.clipboard.writeText('{{ route('blog.show', $post) }}'); alert('Link copied!')"
                            class="p-3 bg-theme-tertiary hover:bg-theme-accent/20 rounded-full text-theme-secondary hover:text-theme-accent transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <div class="mt-12 pt-8 border-t border-theme grid grid-cols-2 gap-4">
                @if ($previousPost)
                    <a href="{{ route('blog.show', $previousPost) }}" class="group">
                        <div class="text-sm text-theme-muted mb-1">&larr; Previous</div>
                        <div class="text-theme-primary group-hover:text-theme-accent transition font-medium">
                            {{ Str::limit($previousPost->title, 50) }}
                        </div>
                    </a>
                @else
                    <div></div>
                @endif

                @if ($nextPost)
                    <a href="{{ route('blog.show', $nextPost) }}" class="text-right group">
                        <div class="text-sm text-theme-muted mb-1">Next &rarr;</div>
                        <div class="text-theme-primary group-hover:text-theme-accent transition font-medium">
                            {{ Str::limit($nextPost->title, 50) }}
                        </div>
                    </a>
                @endif
            </div>

            <!-- Related Posts -->
            @if ($relatedPosts->count() > 0)
                <div class="mt-16 pt-12 border-t border-theme">
                    <h2 class="text-2xl font-bold text-theme-primary mb-8">Related Stories</h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        @foreach ($relatedPosts as $related)
                            <a href="{{ route('blog.show', $related) }}" class="group">
                                <div class="bg-theme-card border border-theme rounded-lg overflow-hidden hover:shadow-lg transition">
                                    @if ($related->featured_image)
                                        <div class="aspect-video">
                                            <img src="{{ asset('storage/' . $related->featured_image) }}"
                                                 alt="{{ $related->title }}"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition">
                                        </div>
                                    @endif
                                    <div class="p-4">
                                        <h3 class="font-medium text-theme-primary group-hover:text-theme-accent transition line-clamp-2">
                                            {{ $related->title }}
                                        </h3>
                                        <div class="text-sm text-theme-muted mt-2">
                                            {{ $related->published_at->format('M d, Y') }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Back to Blog -->
            <div class="mt-12 text-center">
                <a href="{{ route('blog.index') }}" class="inline-flex items-center text-theme-muted hover:text-theme-accent transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Blog
                </a>
            </div>
        </div>
    </article>
</x-layouts.public>
