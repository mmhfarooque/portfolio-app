@props([
    'src',
    'alt' => '',
    'class' => '',
    'dominantColor' => null,
    'aspectRatio' => null,
])

<div
    x-data="{ loaded: false }"
    class="lazy-image-wrapper overflow-hidden {{ $class }}"
    style="{{ $dominantColor ? "background-color: {$dominantColor};" : 'background-color: #1a1a1a;' }} {{ $aspectRatio ? "aspect-ratio: {$aspectRatio};" : '' }}"
>
    <img
        x-ref="img"
        data-src="{{ $src }}"
        alt="{{ $alt }}"
        class="lazy-image w-full h-full object-cover transition-opacity duration-300"
        :class="loaded ? 'opacity-100' : 'opacity-0'"
        x-init="
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = $refs.img;
                        img.src = img.dataset.src;
                        img.onload = () => { loaded = true; };
                        observer.unobserve(img);
                    }
                });
            }, { rootMargin: '50px' });
            observer.observe($refs.img);
        "
    >
</div>
