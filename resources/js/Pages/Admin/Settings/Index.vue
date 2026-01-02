<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Toast from '@/Components/Toast.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    settings: Object,
    currentTheme: String,
    themes: Object,
    watermarkSettings: Object,
    aiSettings: Object,
    photoCount: Number
});

// Theme selection
const selectedTheme = ref(props.currentTheme);
const themeSaving = ref(false);
const themeJustSaved = ref(false);

const selectTheme = (themeKey) => {
    if (selectedTheme.value === themeKey) return;
    selectedTheme.value = themeKey;
    themeSaving.value = true;

    router.post(route('admin.settings.update-theme'), {
        site_theme: themeKey
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            themeJustSaved.value = true;
            setTimeout(() => themeJustSaved.value = false, 3000);
            themeSaving.value = false;
        },
        onError: (errors) => {
            console.error('Theme save failed:', errors);
            themeSaving.value = false;
        },
        onFinish: () => {
            themeSaving.value = false;
        }
    });
};

// Main form
const form = useForm({
    // Branding
    site_name: props.settings?.branding?.site_name || '',
    site_tagline: props.settings?.branding?.site_tagline || '',
    site_logo: null,
    hero_image: null,

    // Social
    social_instagram: props.settings?.social?.social_instagram || '',
    social_twitter: props.settings?.social?.social_twitter || '',
    social_facebook: props.settings?.social?.social_facebook || '',
    social_linkedin: props.settings?.social?.social_linkedin || '',
    social_github: props.settings?.social?.social_github || '',

    // Contact
    contact_email: props.settings?.contact?.contact_email || '',
    contact_phone: props.settings?.contact?.contact_phone || '',
    contact_location: props.settings?.contact?.contact_location || '',

    // Watermark
    watermark_enabled: props.watermarkSettings?.enabled || false,
    watermark_type: props.watermarkSettings?.type || 'text',
    watermark_text: props.watermarkSettings?.text || '© Photography Portfolio',
    watermark_position: props.watermarkSettings?.position || 'bottom-right',
    watermark_opacity: props.watermarkSettings?.opacity || 40,
    watermark_size: props.watermarkSettings?.size || 24,
    watermark_image: null,
    watermark_image_size: props.watermarkSettings?.imageSize || 15,

    // AI Settings
    ai_enabled: props.aiSettings?.enabled || false,
    ai_provider: props.aiSettings?.provider || 'google',
    google_ai_api_key: props.aiSettings?.googleKey || '',
    openai_api_key: props.aiSettings?.openaiKey || '',
    claude_api_key: props.aiSettings?.claudeKey || '',
    ai_auto_title: props.aiSettings?.autoTitle || true,
    ai_auto_description: props.aiSettings?.autoDescription || true,

    // Image Optimization
    image_max_resolution: props.settings?.optimization?.image_max_resolution || '2048',
    image_quality: props.settings?.optimization?.image_quality || 82,

    // SEO
    seo_site_title: props.settings?.seo?.seo_site_title || '',
    seo_site_description: props.settings?.seo?.seo_site_description || '',
    seo_site_keywords: props.settings?.seo?.seo_site_keywords || '',
    seo_twitter_handle: props.settings?.seo?.seo_twitter_handle || '',
    seo_google_analytics: props.settings?.seo?.seo_google_analytics || '',
    seo_gtm_id: props.settings?.seo?.seo_gtm_id || '',
    seo_facebook_pixel: props.settings?.seo?.seo_facebook_pixel || '',
    seo_google_verification: props.settings?.seo?.seo_google_verification || '',
    seo_bing_verification: props.settings?.seo?.seo_bing_verification || '',
    seo_custom_head_scripts: props.settings?.seo?.seo_custom_head_scripts || '',
    seo_custom_body_scripts: props.settings?.seo?.seo_custom_body_scripts || '',
    seo_robots_allow: props.settings?.seo?.seo_robots_allow === '1',
});

const submit = () => {
    form.post(route('admin.settings.update'), {
        forceFormData: true
    });
};

// Section-specific saving
const sectionSaving = ref({});
const sectionSaved = ref({});

const saveSection = async (sectionName) => {
    sectionSaving.value[sectionName] = true;

    try {
        await form.post(route('admin.settings.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                sectionSaved.value[sectionName] = true;
                setTimeout(() => sectionSaved.value[sectionName] = false, 3000);
            }
        });
    } catch (e) {
        console.error('Section save failed:', e);
    }

    sectionSaving.value[sectionName] = false;
};

// Watermark preview position styles
const watermarkPositionStyle = computed(() => {
    const pos = form.watermark_position;
    let styles = {};

    if (pos.includes('top')) styles.top = '5%';
    if (pos.includes('bottom')) styles.bottom = '5%';
    if (pos.includes('left')) styles.left = '5%';
    if (pos.includes('right')) styles.right = '5%';
    if (pos === 'center') {
        styles.top = '50%';
        styles.left = '50%';
        styles.transform = 'translate(-50%, -50%)';
    } else if (pos.includes('center') && pos.includes('top')) {
        styles.left = '50%';
        styles.transform = 'translateX(-50%)';
    } else if (pos.includes('center') && pos.includes('bottom')) {
        styles.left = '50%';
        styles.transform = 'translateX(-50%)';
    } else if (pos.includes('middle')) {
        styles.top = '50%';
        styles.transform = 'translateY(-50%)';
    }

    return styles;
});

// Watermark positions
const positions = [
    { key: 'top-left', label: 'TL' },
    { key: 'top-center', label: 'TC' },
    { key: 'top-right', label: 'TR' },
    { key: 'middle-left', label: 'ML' },
    { key: 'center', label: 'C' },
    { key: 'middle-right', label: 'MR' },
    { key: 'bottom-left', label: 'BL' },
    { key: 'bottom-center', label: 'BC' },
    { key: 'bottom-right', label: 'BR' },
];

// AI validation
const validating = ref(false);
const validatingProvider = ref(null);
const validationResults = reactive({ google: null, openai: null, claude: null });

const validateApiKey = async (provider) => {
    const keyMap = { google: 'google_ai_api_key', openai: 'openai_api_key', claude: 'claude_api_key' };
    const apiKey = form[keyMap[provider]];

    if (!apiKey) {
        validationResults[provider] = { valid: false, message: 'Please enter an API key first' };
        return;
    }

    validating.value = true;
    validatingProvider.value = provider;

    try {
        const response = await fetch(route('admin.settings.validate-ai-api'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ provider, api_key: apiKey })
        });
        validationResults[provider] = await response.json();
    } catch (e) {
        validationResults[provider] = { valid: false, message: 'Error validating API key' };
    }

    validating.value = false;
    validatingProvider.value = null;
};

// Regenerate watermarks
const regenerating = ref(false);
const regenerateProgress = ref(0);
const regenerateDone = ref(false);

const regenerateWatermarks = async () => {
    regenerating.value = true;
    regenerateProgress.value = 0;

    // First save settings
    await fetch(route('admin.settings.update'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: new FormData(document.querySelector('form'))
    });

    // Then regenerate
    const response = await fetch(route('admin.settings.regenerate-watermarks'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json'
        }
    });

    // Simulate progress
    for (let i = 1; i <= props.photoCount; i++) {
        await new Promise(r => setTimeout(r, 100));
        regenerateProgress.value = Math.round((i / props.photoCount) * 100);
    }

    regenerating.value = false;
    regenerateDone.value = true;
    setTimeout(() => regenerateDone.value = false, 3000);
};

// Re-optimize photos
const reoptimizing = ref(false);
const showReoptimizeConfirm = ref(false);

// Toast state
const toast = reactive({
    show: false,
    type: 'success',
    title: '',
    message: ''
});

const showToast = (type, title, message) => {
    toast.show = false;
    setTimeout(() => {
        toast.type = type;
        toast.title = title;
        toast.message = message;
        toast.show = true;
    }, 100);
};

const reoptimizePhotos = async () => {
    showReoptimizeConfirm.value = false;
    reoptimizing.value = true;
    try {
        const response = await fetch(route('admin.photos.reoptimize'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (data.success) {
            showToast('success', 'Optimization Complete', `Successfully re-optimized ${data.count} photos!`);
        } else {
            showToast('error', 'Optimization Failed', data.message || 'An error occurred during optimization');
        }
    } catch (e) {
        showToast('error', 'Error', 'An error occurred during re-optimization');
    }
    reoptimizing.value = false;
};
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Navigation Tabs -->
                <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 flex flex-wrap gap-4">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            General Settings
                        </span>
                        <Link :href="route('admin.backup.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Backup
                        </Link>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- Theme Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Site Theme</h3>
                                    <p class="text-sm text-gray-600 mt-1">Choose a theme for your portfolio</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span v-if="themeSaving" class="text-sm text-blue-600 flex items-center gap-1">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Saving...
                                    </span>
                                    <span v-else-if="themeJustSaved" class="text-sm text-green-600 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Theme Saved!
                                    </span>
                                    <a :href="route('home')" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Preview Site
                                    </a>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div
                                    v-for="(theme, key) in themes"
                                    :key="key"
                                    class="relative group cursor-pointer"
                                >
                                    <div :class="[
                                        'relative rounded-2xl border-2 transition-all overflow-hidden',
                                        selectedTheme === key
                                            ? 'border-blue-500 ring-4 ring-blue-100'
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]">
                                        <!-- Theme Card Content -->
                                        <div class="p-5 text-center">
                                            <h4 class="text-lg font-bold text-gray-900 mb-4">{{ theme.name }}</h4>
                                            <div class="flex justify-center gap-2">
                                                <div class="w-6 h-6 rounded-full ring-2 ring-white shadow-md" :style="{ backgroundColor: theme.colors?.['bg-primary'] || theme.preview?.bg }"></div>
                                                <div class="w-6 h-6 rounded-full ring-2 ring-white shadow-md" :style="{ backgroundColor: theme.colors?.['bg-secondary'] || '#f5f5f4' }"></div>
                                                <div class="w-6 h-6 rounded-full ring-2 ring-white shadow-md" :style="{ backgroundColor: theme.colors?.accent || theme.preview?.accent }"></div>
                                                <div class="w-6 h-6 rounded-full ring-2 ring-white shadow-md" :style="{ backgroundColor: theme.colors?.['text-primary'] || theme.preview?.text }"></div>
                                                <div class="w-6 h-6 rounded-full ring-2 ring-white shadow-md" :style="{ backgroundColor: theme.colors?.['text-secondary'] || '#666' }"></div>
                                            </div>
                                        </div>

                                        <!-- Active Checkmark -->
                                        <div v-if="selectedTheme === key" class="absolute top-3 right-3 w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>

                                        <!-- Hover Overlay -->
                                        <div
                                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center"
                                            @click="selectTheme(key)"
                                        >
                                            <button
                                                type="button"
                                                class="px-5 py-2.5 bg-white text-gray-900 font-semibold rounded-lg shadow-lg hover:bg-gray-100 transition transform hover:scale-105"
                                            >
                                                <span v-if="themeSaving && selectedTheme === key" class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    Saving...
                                                </span>
                                                <span v-else>Select Theme</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Branding Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Branding</h3>
                                <button type="button" @click="saveSection('branding')" :disabled="sectionSaving.branding || form.processing" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                                    <svg v-if="sectionSaving.branding" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else-if="sectionSaved.branding" class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ sectionSaved.branding ? 'Saved!' : 'Save' }}
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="site_name" value="Site Name" />
                                    <TextInput id="site_name" v-model="form.site_name" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="site_tagline" value="Tagline" />
                                    <TextInput id="site_tagline" v-model="form.site_tagline" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="site_logo" value="Logo" />
                                    <input type="file" id="site_logo" accept="image/*" @change="e => form.site_logo = e.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Social Media & Links</h3>
                                <button type="button" @click="saveSection('social')" :disabled="sectionSaving.social || form.processing" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                                    <svg v-if="sectionSaving.social" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else-if="sectionSaved.social" class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ sectionSaved.social ? 'Saved!' : 'Save' }}
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="social_instagram" value="Instagram" />
                                    <TextInput id="social_instagram" v-model="form.social_instagram" type="url" class="mt-1 block w-full" placeholder="https://instagram.com/..." />
                                </div>
                                <div>
                                    <InputLabel for="social_twitter" value="Twitter/X" />
                                    <TextInput id="social_twitter" v-model="form.social_twitter" type="url" class="mt-1 block w-full" placeholder="https://twitter.com/..." />
                                </div>
                                <div>
                                    <InputLabel for="social_facebook" value="Facebook" />
                                    <TextInput id="social_facebook" v-model="form.social_facebook" type="url" class="mt-1 block w-full" placeholder="https://facebook.com/..." />
                                </div>
                                <div>
                                    <InputLabel for="social_linkedin" value="LinkedIn" />
                                    <TextInput id="social_linkedin" v-model="form.social_linkedin" type="url" class="mt-1 block w-full" placeholder="https://linkedin.com/in/..." />
                                </div>
                                <div>
                                    <InputLabel for="social_github" value="GitHub" />
                                    <TextInput id="social_github" v-model="form.social_github" type="url" class="mt-1 block w-full" placeholder="https://github.com/..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                                <button type="button" @click="saveSection('contact')" :disabled="sectionSaving.contact || form.processing" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                                    <svg v-if="sectionSaving.contact" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else-if="sectionSaved.contact" class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ sectionSaved.contact ? 'Saved!' : 'Save' }}
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="contact_email" value="Email" />
                                    <TextInput id="contact_email" v-model="form.contact_email" type="email" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="contact_phone" value="Phone" />
                                    <TextInput id="contact_phone" v-model="form.contact_phone" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="contact_location" value="Location" />
                                    <TextInput id="contact_location" v-model="form.contact_location" class="mt-1 block w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Watermark Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-4">
                                    <h3 class="text-lg font-medium text-gray-900">Watermark</h3>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.watermark_enabled" class="sr-only peer" />
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700">{{ form.watermark_enabled ? 'Enabled' : 'Disabled' }}</span>
                                    </label>
                                </div>
                                <button type="button" @click="saveSection('watermark')" :disabled="sectionSaving.watermark || form.processing" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                                    <svg v-if="sectionSaving.watermark" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else-if="sectionSaved.watermark" class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ sectionSaved.watermark ? 'Saved!' : 'Save' }}
                                </button>
                            </div>

                            <div v-if="form.watermark_enabled" class="flex flex-col lg:flex-row gap-8">
                                <div class="flex-1 space-y-6">
                                    <!-- Type Toggle -->
                                    <div class="flex gap-2">
                                        <button type="button" @click="form.watermark_type = 'text'" :class="form.watermark_type === 'text' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-4 py-2 text-sm font-medium rounded-lg transition">
                                            Text Watermark
                                        </button>
                                        <button type="button" @click="form.watermark_type = 'image'" :class="form.watermark_type === 'image' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-4 py-2 text-sm font-medium rounded-lg transition">
                                            Image Watermark
                                        </button>
                                    </div>

                                    <!-- Text Options -->
                                    <div v-if="form.watermark_type === 'text'" class="space-y-4">
                                        <div>
                                            <InputLabel value="Text" />
                                            <TextInput v-model="form.watermark_text" class="mt-1 block w-full" />
                                        </div>
                                        <div>
                                            <InputLabel :value="`Font Size: ${form.watermark_size}px`" />
                                            <input type="range" v-model="form.watermark_size" min="16" max="120" step="2" class="mt-1 w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" />
                                        </div>
                                    </div>

                                    <!-- Image Options -->
                                    <div v-else class="space-y-4">
                                        <div>
                                            <InputLabel value="Upload PNG/SVG" />
                                            <input type="file" accept=".png,.svg" @change="e => form.watermark_image = e.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        </div>
                                        <div>
                                            <InputLabel :value="`Size: ${form.watermark_image_size}%`" />
                                            <input type="range" v-model="form.watermark_image_size" min="5" max="30" class="mt-1 w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" />
                                        </div>
                                    </div>

                                    <!-- Opacity -->
                                    <div>
                                        <InputLabel :value="`Opacity: ${form.watermark_opacity}%`" />
                                        <input type="range" v-model="form.watermark_opacity" min="10" max="100" step="5" class="mt-1 w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" />
                                    </div>

                                    <!-- Position Grid -->
                                    <div>
                                        <InputLabel value="Position" />
                                        <div class="mt-2 inline-block p-2 bg-gray-100 rounded-lg">
                                            <div class="grid gap-1" style="grid-template-columns: repeat(3, 32px);">
                                                <button v-for="pos in positions" :key="pos.key" type="button" @click="form.watermark_position = pos.key" :class="form.watermark_position === pos.key ? 'bg-blue-600 text-white' : 'bg-gray-300 hover:bg-gray-400 text-gray-600'" class="w-8 h-8 rounded transition text-xs font-bold flex items-center justify-center">
                                                    {{ pos.label }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview -->
                                <div class="lg:w-80">
                                    <InputLabel :value="`Preview (${form.watermark_position})`" />
                                    <div class="mt-2 relative bg-gradient-to-br from-gray-700 to-gray-900 rounded-lg overflow-hidden aspect-video shadow-lg">
                                        <div class="absolute inset-0 flex items-center justify-center opacity-20">
                                            <svg class="w-16 h-16 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div v-if="form.watermark_type === 'text'" class="absolute text-white font-semibold drop-shadow-lg px-1 bg-black/20 rounded" :style="{ ...watermarkPositionStyle, fontSize: `${Math.max(10, form.watermark_size / 4)}px`, opacity: form.watermark_opacity / 100 }">
                                            {{ form.watermark_text || '© Your Name' }}
                                        </div>
                                    </div>

                                    <button type="button" @click="regenerateWatermarks" :disabled="regenerating" class="w-full mt-4 flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition">
                                        <span v-if="regenerating" class="flex items-center gap-2">
                                            <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            {{ regenerateProgress }}%
                                        </span>
                                        <span v-else-if="regenerateDone">{{ photoCount }} photos updated!</span>
                                        <span v-else class="flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Save & Apply to All
                                        </span>
                                    </button>
                                    <div v-if="regenerating" class="mt-2 h-1 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-600 transition-all" :style="{ width: `${regenerateProgress}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-4">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">AI Image Analysis</h3>
                                        <p class="text-sm text-gray-500 mt-1">Automatically generate titles and descriptions</p>
                                    </div>
                                    <button type="button" @click="form.ai_enabled = !form.ai_enabled" :class="form.ai_enabled ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 hover:bg-gray-500'" class="px-4 py-2 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                                        <svg v-if="form.ai_enabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        {{ form.ai_enabled ? 'Enabled' : 'Disabled' }}
                                    </button>
                                </div>
                                <button type="button" @click="saveSection('ai')" :disabled="sectionSaving.ai || form.processing" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                                    <svg v-if="sectionSaving.ai" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else-if="sectionSaved.ai" class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ sectionSaved.ai ? 'Saved!' : 'Save' }}
                                </button>
                            </div>

                            <div v-if="form.ai_enabled" class="space-y-6">
                                <!-- Provider Selection -->
                                <div>
                                    <InputLabel value="AI Provider" class="mb-2" />
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <label class="cursor-pointer">
                                            <input type="radio" v-model="form.ai_provider" value="google" class="sr-only peer" />
                                            <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                                                <p class="font-medium text-gray-900">Google AI</p>
                                                <p class="text-xs text-gray-500">Gemini 2.0 Flash</p>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" v-model="form.ai_provider" value="openai" class="sr-only peer" />
                                            <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-gray-300">
                                                <p class="font-medium text-gray-900">OpenAI</p>
                                                <p class="text-xs text-gray-500">GPT-4 Vision</p>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" v-model="form.ai_provider" value="claude" class="sr-only peer" />
                                            <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-gray-300">
                                                <p class="font-medium text-gray-900">Anthropic</p>
                                                <p class="text-xs text-gray-500">Claude 3.5 Sonnet</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- API Key Input -->
                                <div v-if="form.ai_provider === 'google'">
                                    <InputLabel value="Google AI API Key" />
                                    <div class="flex gap-2 mt-1">
                                        <TextInput v-model="form.google_ai_api_key" type="password" class="flex-1" placeholder="Enter your Google AI Studio API key" />
                                        <button type="button" @click="validateApiKey('google')" :disabled="validating" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition">
                                            {{ validating && validatingProvider === 'google' ? 'Validating...' : 'Validate' }}
                                        </button>
                                    </div>
                                    <div v-if="validationResults.google" class="mt-2 p-3 rounded-lg text-sm" :class="validationResults.google.valid ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                        {{ validationResults.google.message }}
                                    </div>
                                </div>

                                <div v-if="form.ai_provider === 'openai'">
                                    <InputLabel value="OpenAI API Key" />
                                    <div class="flex gap-2 mt-1">
                                        <TextInput v-model="form.openai_api_key" type="password" class="flex-1" placeholder="Enter your OpenAI API key" />
                                        <button type="button" @click="validateApiKey('openai')" :disabled="validating" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 transition">
                                            {{ validating && validatingProvider === 'openai' ? 'Validating...' : 'Validate' }}
                                        </button>
                                    </div>
                                    <div v-if="validationResults.openai" class="mt-2 p-3 rounded-lg text-sm" :class="validationResults.openai.valid ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                        {{ validationResults.openai.message }}
                                    </div>
                                </div>

                                <div v-if="form.ai_provider === 'claude'">
                                    <InputLabel value="Claude API Key" />
                                    <div class="flex gap-2 mt-1">
                                        <TextInput v-model="form.claude_api_key" type="password" class="flex-1" placeholder="Enter your Anthropic API key" />
                                        <button type="button" @click="validateApiKey('claude')" :disabled="validating" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 disabled:opacity-50 transition">
                                            {{ validating && validatingProvider === 'claude' ? 'Validating...' : 'Validate' }}
                                        </button>
                                    </div>
                                    <div v-if="validationResults.claude" class="mt-2 p-3 rounded-lg text-sm" :class="validationResults.claude.valid ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                        {{ validationResults.claude.message }}
                                    </div>
                                </div>

                                <!-- Auto-generate Options -->
                                <div class="border-t pt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Auto-generate on Upload</h4>
                                    <div class="space-y-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="form.ai_auto_title" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                            <span class="ml-2 text-sm text-gray-600">Generate creative titles</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="form.ai_auto_description" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                            <span class="ml-2 text-sm text-gray-600">Generate descriptions and stories</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Optimization -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Image Optimization</h3>
                                    <p class="text-sm text-gray-500 mt-1">All images are automatically converted to WebP format</p>
                                </div>
                                <button type="button" @click="saveSection('optimization')" :disabled="sectionSaving.optimization || form.processing" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                                    <svg v-if="sectionSaving.optimization" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else-if="sectionSaved.optimization" class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ sectionSaved.optimization ? 'Saved!' : 'Save' }}
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="image_max_resolution" value="Max Resolution" />
                                    <select v-model="form.image_max_resolution" id="image_max_resolution" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="800">800px (Small)</option>
                                        <option value="1024">1024px (XGA)</option>
                                        <option value="1280">1280px (HD)</option>
                                        <option value="1440">1440px (HD+)</option>
                                        <option value="1600">1600px (UXGA)</option>
                                        <option value="1920">1920px (Full HD)</option>
                                        <option value="2048">2048px (2K - Recommended)</option>
                                        <option value="2560">2560px (QHD)</option>
                                        <option value="3840">3840px (4K)</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel :value="`Quality: ${form.image_quality}%`" />
                                    <input type="range" v-model="form.image_quality" min="60" max="95" class="mt-1 w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" />
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>60% (Smaller)</span>
                                        <span>82% (Balanced)</span>
                                        <span>95% (Best)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                                <h4 class="text-sm font-medium text-blue-800 mb-2">Re-optimize Existing Photos</h4>
                                <p class="text-xs text-blue-600 mb-3">Apply new settings to all existing photos</p>
                                <button type="button" @click="showReoptimizeConfirm = true" :disabled="reoptimizing" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition disabled:opacity-50">
                                    <svg v-if="reoptimizing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    {{ reoptimizing ? 'Processing...' : 'Re-optimize All Photos' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">SEO Settings</h3>
                                <button type="button" @click="saveSection('seo')" :disabled="sectionSaving.seo || form.processing" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                                    <svg v-if="sectionSaving.seo" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else-if="sectionSaved.seo" class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ sectionSaved.seo ? 'Saved!' : 'Save' }}
                                </button>
                            </div>
                            <div class="space-y-6">
                                <!-- Robots / Crawling Control -->
                                <div class="p-4 rounded-lg" :class="form.seo_robots_allow ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-medium" :class="form.seo_robots_allow ? 'text-green-800' : 'text-yellow-800'">
                                                {{ form.seo_robots_allow ? '✓ Search Engine Crawling Enabled' : '⚠ Search Engine Crawling Disabled' }}
                                            </h4>
                                            <p class="text-sm mt-1" :class="form.seo_robots_allow ? 'text-green-600' : 'text-yellow-600'">
                                                {{ form.seo_robots_allow ? 'Google and other search engines can index your site.' : 'Your site is hidden from search engines. Enable when ready to go live.' }}
                                            </p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="form.seo_robots_allow" class="sr-only peer" />
                                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Basic SEO -->
                                <div>
                                    <InputLabel for="seo_site_title" value="Site Title" />
                                    <TextInput id="seo_site_title" v-model="form.seo_site_title" class="mt-1 block w-full" maxlength="60" />
                                    <p class="text-xs text-gray-500 mt-1">Max 60 characters recommended</p>
                                </div>
                                <div>
                                    <InputLabel for="seo_site_description" value="Site Description" />
                                    <textarea id="seo_site_description" v-model="form.seo_site_description" rows="2" maxlength="160" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Max 160 characters recommended</p>
                                </div>
                                <div>
                                    <InputLabel for="seo_site_keywords" value="Keywords (comma separated)" />
                                    <TextInput id="seo_site_keywords" v-model="form.seo_site_keywords" class="mt-1 block w-full" placeholder="photography, landscape, nature" />
                                </div>
                                <div>
                                    <InputLabel for="seo_twitter_handle" value="Twitter/X Handle" />
                                    <TextInput id="seo_twitter_handle" v-model="form.seo_twitter_handle" class="mt-1 block w-full" placeholder="@yourusername" />
                                </div>

                                <!-- Analytics & Tracking -->
                                <div class="border-t pt-6">
                                    <h4 class="text-md font-medium text-gray-800 mb-4">Analytics & Tracking</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel for="seo_google_analytics" value="Google Analytics ID" />
                                            <TextInput id="seo_google_analytics" v-model="form.seo_google_analytics" class="mt-1 block w-full" placeholder="G-XXXXXXXXXX" />
                                            <p class="text-xs text-gray-500 mt-1">GA4 Measurement ID</p>
                                        </div>
                                        <div>
                                            <InputLabel for="seo_gtm_id" value="Google Tag Manager ID" />
                                            <TextInput id="seo_gtm_id" v-model="form.seo_gtm_id" class="mt-1 block w-full" placeholder="GTM-XXXXXXX" />
                                            <p class="text-xs text-gray-500 mt-1">Container ID from GTM</p>
                                        </div>
                                        <div>
                                            <InputLabel for="seo_facebook_pixel" value="Facebook Pixel ID" />
                                            <TextInput id="seo_facebook_pixel" v-model="form.seo_facebook_pixel" class="mt-1 block w-full" placeholder="XXXXXXXXXXXXXXXX" />
                                            <p class="text-xs text-gray-500 mt-1">Meta Pixel ID (numbers only)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Site Verification -->
                                <div class="border-t pt-6">
                                    <h4 class="text-md font-medium text-gray-800 mb-4">Site Verification</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel for="seo_google_verification" value="Google Search Console" />
                                            <TextInput id="seo_google_verification" v-model="form.seo_google_verification" class="mt-1 block w-full" placeholder="google-site-verification code" />
                                            <p class="text-xs text-gray-500 mt-1">Content value from meta tag</p>
                                        </div>
                                        <div>
                                            <InputLabel for="seo_bing_verification" value="Bing Webmaster Tools" />
                                            <TextInput id="seo_bing_verification" v-model="form.seo_bing_verification" class="mt-1 block w-full" placeholder="bing-site-verification code" />
                                            <p class="text-xs text-gray-500 mt-1">Content value from meta tag</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Custom Scripts -->
                                <div class="border-t pt-6">
                                    <h4 class="text-md font-medium text-gray-800 mb-4">Custom Scripts</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <InputLabel for="seo_custom_head_scripts" value="Custom Head Scripts" />
                                            <textarea id="seo_custom_head_scripts" v-model="form.seo_custom_head_scripts" rows="4" class="mt-1 block w-full font-mono text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="<!-- Paste scripts to be added in <head> -->"></textarea>
                                            <p class="text-xs text-gray-500 mt-1">Scripts added before closing &lt;/head&gt; tag</p>
                                        </div>
                                        <div>
                                            <InputLabel for="seo_custom_body_scripts" value="Custom Body Scripts" />
                                            <textarea id="seo_custom_body_scripts" v-model="form.seo_custom_body_scripts" rows="4" class="mt-1 block w-full font-mono text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="<!-- Paste scripts to be added before </body> -->"></textarea>
                                            <p class="text-xs text-gray-500 mt-1">Scripts added before closing &lt;/body&gt; tag</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <PrimaryButton :disabled="form.processing" class="px-6 py-3">
                            Save Settings
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>

        <!-- Toast Notification -->
        <Toast
            :show="toast.show"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            @close="toast.show = false"
        />

        <!-- Confirm Dialog for Re-optimize -->
        <ConfirmDialog
            :show="showReoptimizeConfirm"
            title="Re-optimize All Photos"
            message="This will re-process all photos with the current resolution and quality settings. This may take a while depending on the number of photos."
            confirm-text="Re-optimize"
            type="info"
            @confirm="reoptimizePhotos"
            @close="showReoptimizeConfirm = false"
        />
    </AuthenticatedLayout>
</template>
