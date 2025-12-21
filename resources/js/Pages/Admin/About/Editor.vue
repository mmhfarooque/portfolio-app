<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    content: String
});

const editorContent = ref(props.content);
const saving = ref(false);
const saved = ref(false);

const saveContent = async () => {
    saving.value = true;
    saved.value = false;

    try {
        const response = await fetch(route('admin.about.save'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                content: editorContent.value,
            }),
        });

        const data = await response.json();
        if (data.success) {
            saved.value = true;
            setTimeout(() => saved.value = false, 3000);
        }
    } catch (error) {
        console.error('Failed to save:', error);
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Head title="Edit About Page" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit About Page</h2>
                <div class="flex items-center gap-4">
                    <span v-if="saved" class="text-green-600 text-sm">Saved!</span>
                    <Link :href="route('admin.about.editorjs')" class="text-indigo-600 hover:text-indigo-800 text-sm">
                        Switch to Editor.js
                    </Link>
                    <PrimaryButton @click="saveContent" :disabled="saving">
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">HTML Content Editor</label>
                            <p class="text-sm text-gray-500 mb-4">
                                Edit the raw HTML content for your About page. For a visual editor experience,
                                <Link :href="route('admin.about.editorjs')" class="text-indigo-600 hover:underline">use Editor.js</Link>.
                            </p>
                        </div>
                        <textarea
                            v-model="editorContent"
                            class="w-full h-[600px] font-mono text-sm border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Enter HTML content..."
                        ></textarea>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Preview</h3>
                    </div>
                    <div class="p-6">
                        <div v-html="editorContent" class="prose max-w-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
