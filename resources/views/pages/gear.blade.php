<x-layouts.public>
    <x-slot name="pageTitle">My Gear - {{ App\Models\Setting::get('site_name', config('app.name')) }}</x-slot>

    <div class="min-h-screen py-12">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-theme-primary mb-4">My Gear</h1>
                <p class="text-theme-secondary max-w-2xl mx-auto">The cameras, lenses, and equipment I use to capture my photographs.</p>
            </div>

            <!-- Cameras -->
            @if($cameras->count() > 0)
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-theme-primary mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Cameras
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($cameras as $item)
                            @include('partials.equipment-card', ['equipment' => $item])
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Lenses -->
            @if($lenses->count() > 0)
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-theme-primary mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Lenses
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($lenses as $item)
                            @include('partials.equipment-card', ['equipment' => $item])
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Accessories -->
            @if($accessories->count() > 0)
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-theme-primary mb-6">Accessories</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($accessories as $item)
                            @include('partials.equipment-card', ['equipment' => $item])
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Lighting -->
            @if($lighting->count() > 0)
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-theme-primary mb-6">Lighting</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($lighting as $item)
                            @include('partials.equipment-card', ['equipment' => $item])
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Software -->
            @if($software->count() > 0)
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-theme-primary mb-6">Software</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($software as $item)
                            @include('partials.equipment-card', ['equipment' => $item])
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Previous Gear -->
            @if($previousGear->count() > 0)
                <section class="mt-16 pt-16 border-t border-theme">
                    <h2 class="text-xl font-bold text-theme-secondary mb-6">Previous Gear</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($previousGear as $item)
                            <div class="p-4 card-theme rounded-lg">
                                <span class="text-xs text-theme-muted uppercase">{{ $item->type_label }}</span>
                                <p class="text-theme-secondary">{{ $item->name }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts.public>
