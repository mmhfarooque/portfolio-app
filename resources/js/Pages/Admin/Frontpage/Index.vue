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
    const groupOrder = ['general', 'profile', 'social', 'contact', 'skills'];
    const groupLabels = {
        general: 'General Settings',
        profile: 'Profile Settings',
        social: 'Social Media',
        contact: 'Contact Information',
        skills: 'Skills & Expertise',
    };

    return groupOrder
        .filter(group => props.settings?.[group])
        .map(group => ({
            key: group,
            label: groupLabels[group] || group,
            settings: props.settings[group],
        }));
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
    return key.includes('image') || key.includes('photo') || key.includes('logo') || key.includes('avatar');
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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Front Page Settings</h2>
                <div class="flex items-center gap-4">
                    <span v-if="saved" class="text-green-600 text-sm">Saved!</span>
                    <PrimaryButton @click="saveSettings" :disabled="saving">
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Settings Groups -->
                <div v-for="group in groups" :key="group.key" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">{{ group.label }}</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div v-for="(setting, key) in group.settings" :key="key">
                            <InputLabel :for="key" :value="formatLabel(key)" />

                            <!-- File Input -->
                            <template v-if="getFieldType(setting) === 'file'">
                                <div class="mt-1">
                                    <div v-if="setting.value && !formData[key]?.name" class="mb-2">
                                        <img
                                            :src="`/storage/${setting.value}`"
                                            :alt="key"
                                            class="w-24 h-24 object-cover rounded-lg"
                                        />
                                    </div>
                                    <input
                                        type="file"
                                        :id="key"
                                        @change="(e) => handleFileChange(key, e)"
                                        accept="image/*"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    />
                                </div>
                            </template>

                            <!-- Textarea -->
                            <template v-else-if="getFieldType(setting) === 'textarea'">
                                <textarea
                                    :id="key"
                                    v-model="formData[key]"
                                    rows="4"
                                    class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </template>

                            <!-- Text/Email/URL Input -->
                            <template v-else>
                                <TextInput
                                    :id="key"
                                    v-model="formData[key]"
                                    :type="getFieldType(setting)"
                                    class="mt-1 block w-full"
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="groups.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No settings found</h3>
                    <p class="mt-1 text-gray-500">Settings will appear here once configured.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
