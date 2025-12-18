<x-layouts.public>
    <x-slot name="title">
        @if ($currentCategory)
            {{ $currentCategory->name }} - Blog
        @elseif ($currentTag)
            Tagged: {{ $currentTag->name }} - Blog
        @else
            Blog
        @endif
        - {{ config('app.name', 'Photography Portfolio') }}
    </x-slot>

    <div class="min-h-screen py-20 px-4">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold mb-4 text-theme-primary">
                    @if ($currentCategory)
                        {{ $currentCategory->name }}
                    @elseif ($currentTag)
                        Tagged: {{ $currentTag->name }}
                    @else
                        Stories & Insights
                    @endif
                </h1>
                <p class="text-theme-muted max-w-2xl mx-auto">
                    Behind-the-scenes stories, photography tips, and creative adventures.
                </p>
            </div>

            <!-- Search Bar -->
            <div class="max-w-xl mx-auto mb-8">
                <form action="{{ route('blog.index') }}" method="GET" class="relative">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search posts..."
                           class="w-full rounded-full py-3 px-5 pr-12 transition input-theme border-2">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
                @if (request('search'))
                    <div class="text-center mt-3">
                        <span class="text-theme-muted">Showing results for "{{ request('search') }}"</span>
                        <a href="{{ route('blog.index') }}" class="text-theme-accent hover:opacity-80 ml-2">Clear</a>
                    </div>
                @endif
            </div>

            <!-- Category Filters -->
            @if ($categories->count() > 0)
                <div class="flex flex-wrap gap-3 justify-center mb-12">
                    <a href="{{ route('blog.index') }}"
                       class="px-4 py-2 rounded-full text-sm transition {{ !$currentCategory && !$currentTag ? 'btn-theme-primary' : 'border border-theme text-theme-secondary hover:text-theme-primary hover:border-theme-accent' }}">
                        All
                    </a>
                    @foreach ($categories as $category)
                        @if ($category->publishedPosts->count() > 0)
                            <a href="{{ route('blog.index', ['category' => $category->slug]) }}"
                               class="px-4 py-2 rounded-full text-sm transition {{ $currentCategory && $currentCategory->id === $category->id ? 'btn-theme-primary' : 'border border-theme text-theme-secondary hover:text-theme-primary hover:border-theme-accent' }}">
                                {{ $category->name }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            <!-- Posts Grid -->
            @if ($posts->count() > 0)
                <div class="grid gap-8">
                    @foreach ($posts as $post)
                        <article class="bg-theme-card border border-theme rounded-xl overflow-hidden hover:shadow-xl transition-shadow">
                            <a href="{{ route('blog.show', $post) }}" class="flex flex-col md:flex-row">
                                @if ($post->featured_image)
                                    <div class="md:w-1/3 aspect-video md:aspect-square">
                                        <img src="{{ asset('storage/' . $post->featured_image) }}"
                                             alt="{{ $post->title }}"
                                             class="w-full h-full object-cover">
                                    </div>
                                @endif
                                <div class="flex-1 p-6">
                                    <div class="flex items-center gap-3 mb-3">
                                        @if ($post->category)
                                            <span class="text-xs font-medium px-2 py-1 bg-theme-accent/10 text-theme-accent rounded">
                                                {{ $post->category->name }}
                                            </span>
                                        @endif
                                        <span class="text-xs text-theme-muted">
                                            {{ $post->published_at->format('M d, Y') }}
                                        </span>
                                        <span class="text-xs text-theme-muted">
                                            {{ $post->reading_time }} min read
                                        </span>
                                    </div>

                                    <h2 class="text-xl font-bold text-theme-primary mb-2 group-hover:text-theme-accent transition">
                                        {{ $post->title }}
                                    </h2>

                                    @if ($post->excerpt)
                                        <p class="text-theme-secondary mb-4 line-clamp-2">
                                            {{ $post->excerpt }}
                                        </p>
                                    @endif

                                    @if ($post->tags->count() > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($post->tags->take(3) as $tag)
                                                <span class="text-xs text-theme-muted">#{{ $tag->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>

                <div class="mt-12">
                    {{ $posts->links() }}
                </div>
            @else
                <div class="text-center py-20">
                    <svg class="mx-auto h-16 w-16 text-theme-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-theme-secondary">No posts found</h3>
                    @if (request('search'))
                        <p class="mt-2 text-theme-muted">Try a different search term.</p>
                    @else
                        <p class="mt-2 text-theme-muted">Check back later for new stories.</p>
                    @endif
                </div>
            @endif

            <!-- Tags Cloud -->
            @if ($tags->count() > 0)
                <div class="mt-16 border-t border-theme pt-12">
                    <h2 class="text-xl font-medium mb-6 text-center text-theme-primary">Browse by Tag</h2>
                    <div class="flex flex-wrap gap-2 justify-center">
                        @foreach ($tags as $tag)
                            @if ($tag->publishedPosts->count() > 0)
                                <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                                   class="px-3 py-1 rounded-full text-sm border border-theme text-theme-secondary hover:border-theme-accent hover:text-theme-accent transition {{ $currentTag && $currentTag->id === $tag->id ? 'bg-theme-accent text-white border-theme-accent' : '' }}">
                                    #{{ $tag->name }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.public>
