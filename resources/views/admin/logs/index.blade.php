<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Activity Logs') }}
            </h2>
            <form action="{{ route('admin.logs.clear') }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to clear old logs?')">
                @csrf
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                    Clear logs older than 30 days
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Stats Cards -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                    <div class="text-sm text-gray-500">Total Logs</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-red-600">{{ number_format($stats['errors']) }}</div>
                    <div class="text-sm text-gray-500">Errors</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-yellow-600">{{ number_format($stats['warnings']) }}</div>
                    <div class="text-sm text-gray-500">Warnings</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['today']) }}</div>
                    <div class="text-sm text-gray-500">Today</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6 p-4">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                        <select name="type" class="rounded-md border-gray-300 text-sm">
                            <option value="">All Types</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Level</label>
                        <select name="level" class="rounded-md border-gray-300 text-sm">
                            <option value="">All Levels</option>
                            @foreach ($levels as $level)
                                <option value="{{ $level }}" {{ request('level') === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search in messages..." class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                            Filter
                        </button>
                        <a href="{{ route('admin.logs.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-300">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50 {{ $log->type === 'error' ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap" title="{{ $log->created_at }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $log->type === 'error' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $log->type === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $log->type === 'info' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $log->type === 'activity' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $log->type === 'debug' ? 'bg-gray-100 text-gray-800' : '' }}
                                    ">
                                        {{ ucfirst($log->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ in_array($log->level, ['critical', 'error']) ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $log->level === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ in_array($log->level, ['info', 'notice']) ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $log->level === 'debug' ? 'bg-gray-100 text-gray-800' : '' }}
                                    ">
                                        {{ ucfirst($log->level) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-mono">
                                    {{ $log->action }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-md truncate" title="{{ $log->message }}">
                                    {{ Str::limit($log->message, 60) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ $log->user?->name ?? 'System' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <button type="button" onclick="showLogDetails({{ $log->id }})" class="text-blue-600 hover:text-blue-800 mr-2">
                                        View
                                    </button>
                                    <form action="{{ route('admin.logs.destroy', $log) }}" method="POST" class="inline" onsubmit="return confirm('Delete this log?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    No logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 py-3 bg-gray-50 border-t">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div id="log-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
        <div class="min-h-screen px-4 py-8 flex items-start justify-center">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full">
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Log Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="log-content" class="p-6 space-y-4">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script>
        async function showLogDetails(id) {
            const modal = document.getElementById('log-modal');
            const content = document.getElementById('log-content');

            content.innerHTML = '<div class="text-center py-8"><div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"></div></div>';
            modal.classList.remove('hidden');

            try {
                const response = await fetch(`/admin/logs/${id}/details`);
                const data = await response.json();

                content.innerHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs font-medium text-gray-500 block">Type</span>
                            <span class="text-sm">${data.type}</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 block">Level</span>
                            <span class="text-sm">${data.level}</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 block">Action</span>
                            <span class="text-sm font-mono">${data.action}</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 block">User</span>
                            <span class="text-sm">${data.user}</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 block">Time</span>
                            <span class="text-sm">${data.created_at} (${data.created_at_human})</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 block">IP Address</span>
                            <span class="text-sm">${data.ip_address || 'N/A'}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-xs font-medium text-gray-500 block">URL</span>
                            <span class="text-sm font-mono break-all">${data.method || ''} ${data.url || 'N/A'}</span>
                        </div>
                        ${data.duration_ms ? `
                        <div>
                            <span class="text-xs font-medium text-gray-500 block">Duration</span>
                            <span class="text-sm">${data.duration_ms}ms</span>
                        </div>` : ''}
                        ${data.memory_usage ? `
                        <div>
                            <span class="text-xs font-medium text-gray-500 block">Memory</span>
                            <span class="text-sm">${data.memory_usage}</span>
                        </div>` : ''}
                    </div>

                    ${data.message ? `
                    <div>
                        <span class="text-xs font-medium text-gray-500 block mb-1">Message</span>
                        <div class="bg-gray-50 rounded p-3 text-sm">${escapeHtml(data.message)}</div>
                    </div>` : ''}

                    ${data.context && Object.keys(data.context).length > 0 ? `
                    <div>
                        <span class="text-xs font-medium text-gray-500 block mb-1">Context</span>
                        <pre class="bg-gray-50 rounded p-3 text-xs overflow-x-auto">${JSON.stringify(data.context, null, 2)}</pre>
                    </div>` : ''}

                    ${data.exception_class ? `
                    <div class="border-t pt-4 mt-4">
                        <h4 class="text-sm font-semibold text-red-600 mb-2">Exception Details</h4>
                        <div class="space-y-2">
                            <div>
                                <span class="text-xs font-medium text-gray-500 block">Class</span>
                                <span class="text-sm font-mono">${data.exception_class}</span>
                            </div>
                            ${data.file ? `
                            <div>
                                <span class="text-xs font-medium text-gray-500 block">Location</span>
                                <span class="text-sm font-mono">${data.file}:${data.line}</span>
                            </div>` : ''}
                            ${data.exception_message ? `
                            <div>
                                <span class="text-xs font-medium text-gray-500 block">Exception Message</span>
                                <div class="bg-red-50 rounded p-3 text-sm text-red-800">${escapeHtml(data.exception_message)}</div>
                            </div>` : ''}
                            ${data.exception_trace ? `
                            <div>
                                <span class="text-xs font-medium text-gray-500 block mb-1">Stack Trace</span>
                                <pre class="bg-gray-900 text-green-400 rounded p-3 text-xs overflow-x-auto max-h-64">${escapeHtml(data.exception_trace)}</pre>
                            </div>` : ''}
                        </div>
                    </div>` : ''}

                    ${data.user_agent ? `
                    <div class="border-t pt-4 mt-4">
                        <span class="text-xs font-medium text-gray-500 block mb-1">User Agent</span>
                        <div class="text-xs text-gray-600">${escapeHtml(data.user_agent)}</div>
                    </div>` : ''}
                `;
            } catch (error) {
                content.innerHTML = '<div class="text-red-600">Failed to load log details.</div>';
            }
        }

        function closeModal() {
            document.getElementById('log-modal').classList.add('hidden');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Close modal on background click
        document.getElementById('log-modal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeModal();
        });
    </script>
</x-app-layout>
