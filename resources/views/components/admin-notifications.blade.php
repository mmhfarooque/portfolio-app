{{-- Admin Notification System --}}
<div x-data="adminNotifications()"
     x-init="init()"
     @notify.window="addNotification($event.detail)"
     class="fixed top-4 right-4 z-50 space-y-2 max-w-md">

    <template x-for="notification in notifications" :key="notification.id">
        <div x-show="notification.visible"
             x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="{
                 'bg-green-50 border-green-500 text-green-800': notification.type === 'success',
                 'bg-red-50 border-red-500 text-red-800': notification.type === 'error',
                 'bg-yellow-50 border-yellow-500 text-yellow-800': notification.type === 'warning',
                 'bg-blue-50 border-blue-500 text-blue-800': notification.type === 'info',
             }"
             class="border-l-4 p-4 rounded-r-lg shadow-lg bg-white">

            <div class="flex items-start gap-3">
                {{-- Icon --}}
                <div class="flex-shrink-0">
                    <template x-if="notification.type === 'success'">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                    <template x-if="notification.type === 'error'">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                    <template x-if="notification.type === 'warning'">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <template x-if="notification.type === 'info'">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm" x-text="notification.title"></p>
                    <p class="text-sm mt-0.5 opacity-90" x-text="notification.message" x-show="notification.message"></p>

                    {{-- Details (for duplicates list, etc.) --}}
                    <template x-if="notification.details && notification.details.length > 0">
                        <div class="mt-2 text-xs">
                            <p class="font-medium mb-1">Skipped files:</p>
                            <ul class="list-disc list-inside space-y-0.5 max-h-32 overflow-y-auto">
                                <template x-for="detail in notification.details" :key="detail">
                                    <li x-text="detail" class="truncate"></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>

                {{-- Close Button --}}
                <button @click="removeNotification(notification.id)" class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>

<script>
function adminNotifications() {
    return {
        notifications: [],
        nextId: 0,

        init() {
            // Check for any flash messages from Laravel session
            @if(session('notification'))
                this.addNotification({!! json_encode(session('notification')) !!});
            @endif
        },

        addNotification(data) {
            const id = this.nextId++;
            const notification = {
                id,
                type: data.type || 'info',
                title: data.title || '',
                message: data.message || '',
                details: data.details || [],
                visible: true,
                duration: data.duration || 5000,
            };

            this.notifications.push(notification);

            // Auto-remove after duration (unless duration is 0)
            if (notification.duration > 0) {
                setTimeout(() => {
                    this.removeNotification(id);
                }, notification.duration);
            }
        },

        removeNotification(id) {
            const notification = this.notifications.find(n => n.id === id);
            if (notification) {
                notification.visible = false;
                setTimeout(() => {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                }, 300);
            }
        },

        clearAll() {
            this.notifications.forEach(n => n.visible = false);
            setTimeout(() => {
                this.notifications = [];
            }, 300);
        }
    }
}

// Global function to show notification from anywhere
window.showNotification = function(type, title, message = '', details = [], duration = 5000) {
    window.dispatchEvent(new CustomEvent('notify', {
        detail: { type, title, message, details, duration }
    }));
};
</script>
