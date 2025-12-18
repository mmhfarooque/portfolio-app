<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Contact Message
            </h2>
            <a href="{{ route('admin.contacts.index') }}" class="text-gray-600 hover:text-gray-800">
                &larr; Back to Messages
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $contact->subject }}</h3>
                            <p class="text-sm text-gray-500">Received {{ $contact->created_at->format('F j, Y \a\t g:i A') }}</p>
                        </div>
                        <span class="px-3 py-1 text-sm rounded-full {{ $contact->status_color }}">
                            {{ $contact->status_label }}
                        </span>
                    </div>

                    <!-- Sender Info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">From</label>
                                <p class="text-gray-900 font-medium">{{ $contact->name }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Email</label>
                                <p>
                                    <a href="mailto:{{ $contact->email }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $contact->email }}
                                    </a>
                                </p>
                            </div>
                            @if($contact->ip_address)
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">IP Address</label>
                                <p class="text-gray-600 text-sm">{{ $contact->ip_address }}</p>
                            </div>
                            @endif
                            @if($contact->read_at)
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Read At</label>
                                <p class="text-gray-600 text-sm">{{ $contact->read_at->format('M j, Y g:i A') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="mb-6">
                        <label class="text-xs text-gray-500 uppercase tracking-wider mb-2 block">Message</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-800 whitespace-pre-wrap">{{ $contact->message }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-3 pt-4 border-t">
                        <a href="mailto:{{ $contact->email }}?subject=Re: {{ urlencode($contact->subject) }}"
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            Reply via Email
                        </a>

                        <form action="{{ route('admin.contacts.update-status', $contact) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="replied">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Mark as Replied
                            </button>
                        </form>

                        <form action="{{ route('admin.contacts.update-status', $contact) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="archived">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                Archive
                            </button>
                        </form>

                        <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this message?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
