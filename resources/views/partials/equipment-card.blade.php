<div class="card-theme rounded-lg overflow-hidden">
    @if($equipment->image_url)
        <div class="aspect-video overflow-hidden">
            <img src="{{ $equipment->image_url }}" alt="{{ $equipment->name }}" class="w-full h-full object-cover">
        </div>
    @endif
    <div class="p-4">
        <h3 class="font-semibold text-theme-primary">{{ $equipment->name }}</h3>
        @if($equipment->brand)
            <p class="text-sm text-theme-muted">{{ $equipment->brand }}</p>
        @endif
        @if($equipment->description)
            <p class="text-theme-secondary text-sm mt-2 line-clamp-2">{{ $equipment->description }}</p>
        @endif
        <div class="mt-3 flex items-center justify-between">
            <span class="text-xs text-theme-muted">{{ $equipment->photo_count }} photos</span>
            @if($equipment->affiliate_link)
                <a href="{{ $equipment->affiliate_link }}" target="_blank" rel="noopener noreferrer nofollow" class="text-xs text-theme-accent hover:underline">
                    View Product
                </a>
            @endif
        </div>
    </div>
</div>
