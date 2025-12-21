<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    accounts: Array,
    platforms: Object
});

const showDisconnectModal = ref(false);
const accountToDisconnect = ref(null);
const isProcessing = ref(false);

const confirmDisconnect = (account) => {
    accountToDisconnect.value = account;
    showDisconnectModal.value = true;
};

const disconnectAccount = () => {
    isProcessing.value = true;
    router.post(route('admin.social.disconnect', accountToDisconnect.value.id), {}, {
        onFinish: () => {
            isProcessing.value = false;
            showDisconnectModal.value = false;
            accountToDisconnect.value = null;
        }
    });
};
</script>

<template>
    <Head title="Social Accounts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.social.index')" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Social Accounts</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Connected Accounts -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Connected Accounts</h3>
                    <div v-if="accounts.length > 0" class="space-y-4">
                        <div v-for="account in accounts" :key="account.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <p class="font-medium capitalize">{{ account.platform }}</p>
                                <p class="text-sm text-gray-500">{{ account.username || account.email }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <span v-if="account.is_active" class="text-green-600 text-sm">Active</span>
                                <span v-else class="text-gray-400 text-sm">Inactive</span>
                                <button @click="confirmDisconnect(account)" class="text-sm text-red-600 hover:text-red-800">Disconnect</button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-8">No accounts connected.</p>
                </div>

                <!-- Available Platforms -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Available Platforms</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div v-for="(platform, key) in platforms" :key="key" class="p-4 border rounded-lg text-center">
                            <p class="font-medium">{{ platform.name }}</p>
                            <span v-if="platform.connected" class="text-green-600 text-sm">Connected</span>
                            <a v-else :href="platform.connect_url" class="text-indigo-600 text-sm hover:underline">Connect</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal :show="showDisconnectModal" title="Disconnect Account" :message="`Are you sure you want to disconnect this ${accountToDisconnect?.platform} account?`" confirm-text="Disconnect" variant="danger" :processing="isProcessing" @confirm="disconnectAccount" @close="showDisconnectModal = false" />
    </AuthenticatedLayout>
</template>
