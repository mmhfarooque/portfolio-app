<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    isConfigured: Boolean,
    storageStats: Object,
    lastBackupAt: String,
    lastBackupStats: Object
});

const testing = ref(false);
const testResult = ref('');
const testSuccess = ref(false);
const backing = ref(false);
const backupOutput = ref('');
const backupMessage = ref('');
const backupSuccess = ref(false);
const includeDatabase = ref(true);

const testConnection = async () => {
    testing.value = true;
    testResult.value = '';

    try {
        const response = await fetch(route('admin.backup.test'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        testResult.value = data.message;
        testSuccess.value = data.success;
    } catch (e) {
        testResult.value = 'Connection test failed: ' + e.message;
        testSuccess.value = false;
    }

    testing.value = false;
};

const runBackup = async () => {
    backing.value = true;
    backupOutput.value = '';
    backupMessage.value = '';

    try {
        const response = await fetch(route('admin.backup.run'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                include_database: includeDatabase.value
            })
        });

        const data = await response.json();
        backupMessage.value = data.message;
        backupOutput.value = data.output || '';
        backupSuccess.value = data.success;

        if (data.success) {
            setTimeout(() => router.reload(), 2000);
        }
    } catch (e) {
        backupMessage.value = 'Backup failed: ' + e.message;
        backupOutput.value = '';
        backupSuccess.value = false;
    }

    backing.value = false;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Backup Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Backup Settings</h2>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Settings Sub-Navigation -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-4 flex flex-wrap gap-4">
                        <Link :href="route('admin.settings.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            General Settings
                        </Link>
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Backup
                        </span>
                    </div>
                </div>

                <!-- Configuration Status -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Backblaze B2 Configuration</h3>

                        <div v-if="isConfigured">
                            <div class="flex items-center gap-2 text-green-600 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Backblaze B2 is configured</span>
                            </div>
                            <button @click="testConnection" :disabled="testing" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 disabled:opacity-50">
                                <span v-if="!testing">Test Connection</span>
                                <span v-else>Testing...</span>
                            </button>
                            <p v-if="testResult" :class="testSuccess ? 'text-green-600' : 'text-red-600'" class="mt-2 text-sm">{{ testResult }}</p>
                        </div>
                        <div v-else>
                            <div class="flex items-center gap-2 text-yellow-600 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>Backblaze B2 is not configured</span>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-3">Add the following to your <code class="bg-gray-200 px-1 rounded">.env</code> file:</p>
                                <pre class="bg-gray-800 text-green-400 p-4 rounded text-sm overflow-x-auto">B2_ACCESS_KEY_ID=your_key_id
B2_SECRET_ACCESS_KEY=your_application_key
B2_BUCKET=your_bucket_name
B2_REGION=us-west-004
B2_ENDPOINT=https://s3.us-west-004.backblazeb2.com</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Storage Statistics -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Local Storage Statistics</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Display Photos</p>
                                <p class="text-xl font-semibold">{{ storageStats?.photos_formatted || '0 B' }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Thumbnails</p>
                                <p class="text-xl font-semibold">{{ storageStats?.thumbnails_formatted || '0 B' }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Watermarked</p>
                                <p class="text-xl font-semibold">{{ storageStats?.watermarked_formatted || '0 B' }}</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <p class="text-sm text-blue-600">Total Size</p>
                                <p class="text-xl font-semibold text-blue-700">{{ storageStats?.total_size_formatted || '0 B' }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-3">{{ storageStats?.file_count || 0 }} files total</p>
                    </div>
                </div>

                <!-- Last Backup Status -->
                <div v-if="lastBackupAt" class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Last Backup</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Date</p>
                                <p class="text-lg font-semibold">{{ formatDate(lastBackupAt) }}</p>
                                <p class="text-sm text-gray-500">{{ formatTime(lastBackupAt) }}</p>
                            </div>
                            <div v-if="lastBackupStats" class="bg-green-50 p-4 rounded-lg">
                                <p class="text-sm text-green-600">Uploaded</p>
                                <p class="text-xl font-semibold text-green-700">{{ lastBackupStats.uploaded || 0 }}</p>
                            </div>
                            <div v-if="lastBackupStats" class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Skipped</p>
                                <p class="text-xl font-semibold">{{ lastBackupStats.skipped || 0 }}</p>
                            </div>
                            <div v-if="lastBackupStats" class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Duration</p>
                                <p class="text-xl font-semibold">{{ lastBackupStats.duration || 0 }}s</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Run Backup -->
                <div v-if="isConfigured" class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Run Backup</h3>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="includeDatabase" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Include database backup</span>
                            </label>

                            <button @click="runBackup" :disabled="backing" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="backing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>{{ backing ? 'Backing up...' : 'Start Backup' }}</span>
                            </button>

                            <p class="text-sm text-gray-500">This may take several minutes depending on the number of photos.</p>

                            <div v-if="backupOutput" class="mt-4 bg-gray-50 p-4 rounded-lg">
                                <p :class="backupSuccess ? 'text-green-600' : 'text-red-600'" class="font-medium mb-2">{{ backupMessage }}</p>
                                <pre class="text-xs text-gray-600 whitespace-pre-wrap max-h-60 overflow-y-auto">{{ backupOutput }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Automatic Backups Info -->
                <div v-if="isConfigured" class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-900 mb-2">Automatic Backups</h4>
                    <p class="text-sm text-gray-600 mb-2">To run automatic daily backups, add this to your server's crontab:</p>
                    <pre class="bg-gray-800 text-green-400 p-3 rounded text-sm overflow-x-auto">0 3 * * * cd /path/to/portfolio-app && php artisan backup:photos --database >> /dev/null 2>&amp;1</pre>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
