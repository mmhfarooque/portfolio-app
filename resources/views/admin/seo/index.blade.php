<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">SEO Audit Dashboard</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Overall Score -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Overall SEO Health</h3>
                        <p class="text-sm text-gray-500">Based on {{ $audit['photos']['count'] }} photos and {{ $audit['posts']['count'] }} posts</p>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold {{
                            $audit['overall_score'] >= 80 ? 'text-green-600' :
                            ($audit['overall_score'] >= 60 ? 'text-yellow-600' : 'text-red-600')
                        }}">
                            {{ $audit['overall_score'] }}
                        </div>
                        <div class="text-3xl font-semibold {{
                            $audit['overall_grade'] === 'A' ? 'text-green-600' :
                            ($audit['overall_grade'] === 'B' ? 'text-green-500' :
                            ($audit['overall_grade'] === 'C' ? 'text-yellow-500' :
                            ($audit['overall_grade'] === 'D' ? 'text-orange-500' : 'text-red-600')))
                        }}">
                            Grade: {{ $audit['overall_grade'] }}
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="h-4 rounded-full transition-all {{
                            $audit['overall_score'] >= 80 ? 'bg-green-600' :
                            ($audit['overall_score'] >= 60 ? 'bg-yellow-500' : 'bg-red-500')
                        }}" style="width: {{ $audit['overall_score'] }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Photos Summary -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Photos</h3>
                        <span class="text-2xl font-bold {{
                            $audit['photos']['average_score'] >= 80 ? 'text-green-600' :
                            ($audit['photos']['average_score'] >= 60 ? 'text-yellow-600' : 'text-red-600')
                        }}">{{ $audit['photos']['average_score'] }}/100</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center text-red-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Errors
                            </span>
                            <span class="font-medium">{{ $audit['photos']['issues_by_type']['error'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center text-yellow-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Warnings
                            </span>
                            <span class="font-medium">{{ $audit['photos']['issues_by_type']['warning'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center text-blue-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                Info
                            </span>
                            <span class="font-medium">{{ $audit['photos']['issues_by_type']['info'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Posts Summary -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Blog Posts</h3>
                        <span class="text-2xl font-bold {{
                            $audit['posts']['average_score'] >= 80 ? 'text-green-600' :
                            ($audit['posts']['average_score'] >= 60 ? 'text-yellow-600' : 'text-red-600')
                        }}">{{ $audit['posts']['average_score'] }}/100</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center text-red-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Errors
                            </span>
                            <span class="font-medium">{{ $audit['posts']['issues_by_type']['error'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center text-yellow-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Warnings
                            </span>
                            <span class="font-medium">{{ $audit['posts']['issues_by_type']['warning'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center text-blue-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                Info
                            </span>
                            <span class="font-medium">{{ $audit['posts']['issues_by_type']['info'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photos with Issues -->
            @if($audit['photos']['audits']->isNotEmpty())
                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Photos Needing Attention</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Photo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issues</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($audit['photos']['audits'] as $photoAudit)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ Str::limit($photoAudit['title'] ?? 'Untitled', 40) }}</div>
                                        <div class="text-sm text-gray-500">/photo/{{ $photoAudit['slug'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-sm font-semibold rounded {{
                                            $photoAudit['grade'] === 'A' ? 'bg-green-100 text-green-800' :
                                            ($photoAudit['grade'] === 'B' ? 'bg-green-50 text-green-700' :
                                            ($photoAudit['grade'] === 'C' ? 'bg-yellow-100 text-yellow-800' :
                                            ($photoAudit['grade'] === 'D' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')))
                                        }}">
                                            {{ $photoAudit['score'] }} ({{ $photoAudit['grade'] }})
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($photoAudit['issues'] as $issue)
                                                <span class="px-2 py-0.5 text-xs rounded {{
                                                    $issue['type'] === 'error' ? 'bg-red-100 text-red-700' :
                                                    ($issue['type'] === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700')
                                                }}">
                                                    {{ $issue['message'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.photos.edit', $photoAudit['id']) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Posts with Issues -->
            @if($audit['posts']['audits']->isNotEmpty())
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Blog Posts Needing Attention</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Post</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issues</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($audit['posts']['audits'] as $postAudit)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ Str::limit($postAudit['title'], 40) }}</div>
                                        <div class="text-sm text-gray-500">/blog/{{ $postAudit['slug'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-sm font-semibold rounded {{
                                            $postAudit['grade'] === 'A' ? 'bg-green-100 text-green-800' :
                                            ($postAudit['grade'] === 'B' ? 'bg-green-50 text-green-700' :
                                            ($postAudit['grade'] === 'C' ? 'bg-yellow-100 text-yellow-800' :
                                            ($postAudit['grade'] === 'D' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')))
                                        }}">
                                            {{ $postAudit['score'] }} ({{ $postAudit['grade'] }})
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($postAudit['issues'] as $issue)
                                                <span class="px-2 py-0.5 text-xs rounded {{
                                                    $issue['type'] === 'error' ? 'bg-red-100 text-red-700' :
                                                    ($issue['type'] === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700')
                                                }}">
                                                    {{ $issue['message'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.posts.edit', $postAudit['id']) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- SEO Tips -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-blue-900 mb-3">SEO Quick Tips</h3>
                <ul class="space-y-2 text-sm text-blue-800">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong>Titles:</strong> Keep between 10-60 characters, include keywords</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong>Meta descriptions:</strong> 50-160 characters, compelling call to action</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong>Tags:</strong> Use 3+ relevant tags per photo for better discoverability</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong>Images:</strong> Keep files under 5MB, add location data when possible</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 mr-2 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong>Content:</strong> Blog posts should have 300+ words for better ranking</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
