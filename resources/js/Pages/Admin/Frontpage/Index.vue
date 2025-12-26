<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    settings: Object
});

const formData = ref({});
const saving = ref(false);
const saved = ref(false);

// Initialize form data from settings
const initFormData = () => {
    const data = {};
    Object.entries(props.settings || {}).forEach(([group, groupSettings]) => {
        Object.entries(groupSettings || {}).forEach(([key, setting]) => {
            data[key] = setting.value || '';
        });
    });
    formData.value = data;
};

initFormData();

const groups = computed(() => {
    const groupOrder = ['profile', 'social', 'contact', 'skills'];
    const groupLabels = {
        general: 'General Settings',
        profile: 'Profile Settings',
        social: 'Social Media',
        contact: 'Contact Information',
        skills: 'Skills & Expertise',
    };

    // Settings to exclude (managed elsewhere)
    const excludeKeys = ['image_max_resolution', 'image_quality'];

    return groupOrder
        .filter(group => props.settings?.[group])
        .map(group => {
            // Filter out excluded settings
            const filteredSettings = Object.fromEntries(
                Object.entries(props.settings[group] || {})
                    .filter(([key]) => !excludeKeys.includes(key))
            );
            return {
                key: group,
                label: groupLabels[group] || group,
                settings: filteredSettings,
            };
        })
        .filter(group => Object.keys(group.settings).length > 0);
});

const handleFileChange = (key, e) => {
    const file = e.target.files[0];
    if (file) {
        formData.value[key] = file;
    }
};

const saveSettings = () => {
    saving.value = true;
    saved.value = false;

    const data = new FormData();
    Object.entries(formData.value).forEach(([key, value]) => {
        if (value instanceof File) {
            data.append(key, value);
        } else if (value !== null && value !== undefined) {
            data.append(key, value);
        }
    });

    router.post(route('admin.frontpage.update'), data, {
        onSuccess: () => {
            saved.value = true;
            setTimeout(() => saved.value = false, 3000);
        },
        onFinish: () => {
            saving.value = false;
        },
    });
};

const isImageField = (key) => {
    // Only match specific image field patterns that are ACTUAL image uploads
    // NOT settings that just mention "image" (like image_quality, image_max_resolution)
    const imagePatterns = [
        /_(image|photo|logo|avatar|picture|thumbnail|icon)$/i,  // ends with image type
        /^profile_(image|photo|avatar)$/i,
        /^site_(logo|favicon|icon)$/i,
        /^hero_(image|background)$/i,
        /^(logo|avatar|favicon|thumbnail|cover_image)$/i,  // exact matches
    ];
    return imagePatterns.some(pattern => pattern.test(key));
};

const getFieldType = (setting) => {
    if (setting.type === 'textarea' || setting.key?.includes('bio') || setting.key?.includes('description')) {
        return 'textarea';
    }
    if (setting.type === 'email' || setting.key?.includes('email')) {
        return 'email';
    }
    if (setting.type === 'url' || setting.key?.includes('url') || setting.key?.includes('link')) {
        return 'url';
    }
    if (setting.type === 'number' || setting.key?.includes('quality') || setting.key?.includes('resolution') || setting.key?.includes('_count') || setting.key?.includes('_limit')) {
        return 'number';
    }
    if (setting.type === 'file' || isImageField(setting.key)) {
        return 'file';
    }
    return 'text';
};

const formatLabel = (key) => {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());
};
</script>

<template>
    <Head title="Front Page Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-lg text-gray-800 dark:text-white leading-tight">Front Page Settings</h2>
                <div class="flex items-center gap-3">
                    <span v-if="saved" class="text-green-500 text-sm font-medium">Saved!</span>
                    <PrimaryButton @click="saveSettings" :disabled="saving" class="text-sm px-4 py-2">
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
                <!-- Settings Groups -->
                <div v-for="group in groups" :key="group.key" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide">{{ group.label }}</h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <div v-for="(setting, key) in group.settings" :key="key" class="grid grid-cols-3 gap-4 items-start">
                            <InputLabel :for="key" :value="formatLabel(key)" class="pt-2 text-sm text-gray-600 dark:text-gray-400" />

                            <div class="col-span-2">
                                <!-- File Input -->
                                <template v-if="getFieldType(setting) === 'file'">
                                    <div class="flex items-center gap-4">
                                        <div v-if="setting.value && !formData[key]?.name" class="flex-shrink-0">
                                            <img
                                                :src="`/storage/${setting.value}`"
                                                :alt="key"
                                                class="w-16 h-16 object-cover rounded-md border border-gray-200 dark:border-gray-600"
                                            />
                                        </div>
                                        <input
                                            type="file"
                                            :id="key"
                                            @change="(e) => handleFileChange(key, e)"
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer"
                                        />
                                    </div>
                                </template>

                                <!-- Textarea -->
                                <template v-else-if="getFieldType(setting) === 'textarea'">
                                    <textarea
                                        :id="key"
                                        v-model="formData[key]"
                                        rows="3"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    ></textarea>
                                </template>

                                <!-- Text/Email/URL Input -->
                                <template v-else>
                                    <TextInput
                                        :id="key"
                                        v-model="formData[key]"
                                        :type="getFieldType(setting)"
                                        class="block w-full text-sm"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="groups.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-white">No settings found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Settings will appear here once configured.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
