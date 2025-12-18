@props(['active' => false, 'align' => 'left'])

@php
$buttonClasses = $active
    ? 'inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-900 bg-gray-100 rounded-lg transition duration-150 ease-in-out'
    : 'inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition duration-150 ease-in-out';

$alignmentClasses = match ($align) {
    'right' => 'right-0',
    default => 'left-0',
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open" {{ $attributes->merge(['class' => $buttonClasses]) }}>
        {{ $trigger }}
        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute {{ $alignmentClasses }} z-50 mt-2 w-56 origin-top-left rounded-xl bg-white shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
         style="display: none;">
        <div class="p-2">
            {{ $content }}
        </div>
    </div>
</div>
