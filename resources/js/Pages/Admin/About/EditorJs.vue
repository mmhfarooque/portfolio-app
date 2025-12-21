<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    editorData: Object,
    target: String
});

const editorRef = ref(null);
let editorInstance = null;
const saving = ref(false);
const saved = ref(false);

onMounted(async () => {
    // Dynamically import Editor.js
    const EditorJS = (await import('@editorjs/editorjs')).default;
    const Header = (await import('@editorjs/header')).default;
    const List = (await import('@editorjs/list')).default;
    const Quote = (await import('@editorjs/quote')).default;
    const Delimiter = (await import('@editorjs/delimiter')).default;
    const ImageTool = (await import('@editorjs/image')).default;

    editorInstance = new EditorJS({
        holder: editorRef.value,
        data: props.editorData || { blocks: [] },
        placeholder: 'Start writing your content...',
        tools: {
            header: {
                class: Header,
                inlineToolbar: true,
                config: {
                    levels: [1, 2, 3],
                    defaultLevel: 2,
                },
            },
            list: {
                class: List,
                inlineToolbar: true,
            },
            quote: {
                class: Quote,
                inlineToolbar: true,
            },
            delimiter: Delimiter,
            image: {
                class: ImageTool,
                config: {
                    endpoints: {
                        byFile: route('admin.media.upload'),
                    },
                },
            },
        },
    });
});

onUnmounted(() => {
    if (editorInstance) {
        editorInstance.destroy();
    }
});

const saveContent = async () => {
    if (!editorInstance) return;

    saving.value = true;
    saved.value = false;

    try {
        const outputData = await editorInstance.save();

        const response = await fetch(route('admin.about.save-editorjs'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                content: outputData,
                target: props.target,
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

const targetLabel = props.target === 'profile' ? 'Profile Bio' : 'About Page';
</script>

<template>
    <Head :title="`Edit ${targetLabel}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit {{ targetLabel }}</h2>
                <div class="flex items-center gap-4">
                    <span v-if="saved" class="text-green-600 text-sm">Saved!</span>
                    <div v-if="target !== 'profile'" class="flex gap-2">
                        <Link :href="route('admin.about.editor')" class="text-indigo-600 hover:text-indigo-800 text-sm">
                            Switch to HTML Editor
                        </Link>
                    </div>
                    <PrimaryButton @click="saveContent" :disabled="saving">
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div ref="editorRef" class="prose max-w-none min-h-[400px]"></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.codex-editor {
    min-height: 400px;
}
.ce-block__content {
    max-width: 100%;
}
.ce-toolbar__content {
    max-width: 100%;
}
</style>
