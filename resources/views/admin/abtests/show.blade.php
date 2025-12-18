<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('admin.abtests.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $abtest->name }}</h2>
            </div>
            <div class="flex items-center gap-2">
                @if($abtest->status === 'draft' || $abtest->status === 'paused')
                    <form action="{{ route('admin.abtests.start', $abtest) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Start Test
                        </button>
                    </form>
                @endif
                @if($abtest->isRunning())
                    <form action="{{ route('admin.abtests.pause', $abtest) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                            Pause
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Status Banner -->
            @if($abtest->status === 'completed' && $abtest->winner_variant)
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <div class="font-medium text-green-800">Test Complete!</div>
                            <div class="text-sm text-green-700">Winner: <strong>{{ $abtest->winner_variant }}</strong></div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Progress -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Test Progress</span>
                    <span class="text-sm text-gray-500">{{ $results['total_participants'] }} / {{ $abtest->sample_size }} participants</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-indigo-600 h-3 rounded-full transition-all" style="width: {{ $results['progress'] }}%"></div>
                </div>
            </div>

            <!-- Results Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                @foreach($results['conversion_rates'] as $variant => $data)
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ $variant }}</h3>
                            @if($abtest->winner_variant === $variant)
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">WINNER</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ $data['total'] }}</div>
                                <div class="text-sm text-gray-500">Visitors</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ $data['converted'] }}</div>
                                <div class="text-sm text-gray-500">Conversions</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold {{ $data['rate'] > 0 ? 'text-green-600' : 'text-gray-900' }}">{{ $data['rate'] }}%</div>
                                <div class="text-sm text-gray-500">Conv. Rate</div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, $data['rate'] * 10) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Statistical Significance -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Statistical Analysis</h3>
                @if($results['statistical_significance']['significant'])
                    <div class="flex items-center text-green-600 mb-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="font-medium">Results are statistically significant!</span>
                    </div>
                @else
                    <div class="flex items-center text-yellow-600 mb-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Not yet significant. More data needed.</span>
                    </div>
                @endif
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">P-Value:</span>
                        <span class="font-medium ml-2">{{ $results['statistical_significance']['p_value'] ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Z-Score:</span>
                        <span class="font-medium ml-2">{{ $results['statistical_significance']['z_score'] ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Confidence Level:</span>
                        <span class="font-medium ml-2">{{ $abtest->confidence_level }}%</span>
                    </div>
                </div>
            </div>

            <!-- Test Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Test Details</h3>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Type</dt>
                        <dd class="text-sm font-medium text-gray-900 capitalize">{{ $abtest->type }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Goal</dt>
                        <dd class="text-sm font-medium text-gray-900 capitalize">{{ $abtest->goal }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Started</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $abtest->started_at?->format('M d, Y H:i') ?? 'Not started' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Ended</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $abtest->ended_at?->format('M d, Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>
                @if($abtest->description)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <dt class="text-sm text-gray-500 mb-1">Description</dt>
                        <dd class="text-sm text-gray-700">{{ $abtest->description }}</dd>
                    </div>
                @endif
            </div>

            <!-- Actions -->
            @if($abtest->isRunning())
                <div class="mt-6 bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Declare Winner Manually</h3>
                    <form action="{{ route('admin.abtests.complete', $abtest) }}" method="POST" class="flex items-center gap-4">
                        @csrf
                        <select name="winner" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select winner...</option>
                            @foreach($abtest->getVariantNames() as $variant)
                                <option value="{{ $variant }}">{{ $variant }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                onclick="return confirm('Are you sure you want to end this test?')">
                            Complete Test
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
