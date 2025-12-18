<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.abtests.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create A/B Test</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('admin.abtests.store') }}" method="POST" class="space-y-6" x-data="{
                variants: [
                    { name: 'Control', value: 'default', weight: 50 },
                    { name: 'Variant B', value: '', weight: 50 }
                ],
                addVariant() {
                    this.variants.push({ name: 'Variant ' + String.fromCharCode(65 + this.variants.length), value: '', weight: 50 });
                },
                removeVariant(index) {
                    if (this.variants.length > 2) {
                        this.variants.splice(index, 1);
                    }
                }
            }">
                @csrf

                <!-- Basic Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Test Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Test Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="e.g., Dark Theme Test">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                      placeholder="What are you testing?">{{ old('description') }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Test Type</label>
                                <select name="type" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="theme">Theme Variation</option>
                                    <option value="layout">Layout Variation</option>
                                    <option value="content">Content Variation</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Goal</label>
                                <select name="goal" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="conversion">Conversion (Sales/Signups)</option>
                                    <option value="engagement">Engagement (Time on Site)</option>
                                    <option value="bounce">Reduce Bounce Rate</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variants -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Variants</h3>
                        <button type="button" @click="addVariant()"
                                class="text-sm text-indigo-600 hover:text-indigo-800">+ Add Variant</button>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(variant, index) in variants" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-medium text-gray-700" x-text="'Variant ' + (index + 1)"></span>
                                    <button type="button" @click="removeVariant(index)" x-show="variants.length > 2"
                                            class="text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Name</label>
                                        <input type="text" :name="'variants[' + index + '][name]'" x-model="variant.name" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Value (CSS class/theme name)</label>
                                        <input type="text" :name="'variants[' + index + '][value]'" x-model="variant.value" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                               placeholder="e.g., dark-theme">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Weight (%)</label>
                                        <input type="number" :name="'variants[' + index + '][weight]'" x-model="variant.weight" required
                                               min="1" max="100"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Test Settings</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sample Size</label>
                            <input type="number" name="sample_size" value="{{ old('sample_size', 1000) }}" required
                                   min="100" max="100000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <p class="mt-1 text-xs text-gray-500">Minimum visitors before declaring winner</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confidence Level (%)</label>
                            <input type="number" name="confidence_level" value="{{ old('confidence_level', 95) }}" required
                                   min="80" max="99" step="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <p class="mt-1 text-xs text-gray-500">Statistical confidence for results</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.abtests.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Create Test
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
