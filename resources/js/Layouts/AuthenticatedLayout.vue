<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import NavDropdown from '@/Components/NavDropdown.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

const page = usePage();
const mobileMenuOpen = ref(false);
const userDropdownOpen = ref(false);

const user = computed(() => page.props.auth?.user);
const userInitial = computed(() => user.value?.name?.charAt(0) || 'U');

const isRoute = (pattern) => {
    const currentUrl = page.url;
    if (pattern.includes('*')) {
        const regex = new RegExp('^' + pattern.replace('*', '.*'));
        return regex.test(currentUrl);
    }
    return currentUrl === pattern || currentUrl.startsWith(pattern);
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <FlashMessages />

        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-100 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-1">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2">
                                <svg class="block h-9 w-auto" viewBox="0 0 50 50" fill="currentColor">
                                    <path d="M25 5C13.97 5 5 13.97 5 25s8.97 20 20 20 20-8.97 20-20S36.03 5 25 5zm0 36c-8.82 0-16-7.18-16-16S16.18 9 25 9s16 7.18 16 16-7.18 16-16 16z"/>
                                    <circle cx="25" cy="25" r="8"/>
                                </svg>
                            </Link>
                        </div>

                        <!-- Desktop Navigation -->
                        <div class="hidden sm:flex sm:items-center sm:ms-8 sm:gap-1">
                            <!-- Dashboard -->
                            <Link :href="route('dashboard')"
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition duration-150 ease-in-out',
                                    isRoute('/dashboard') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Dashboard</span>
                            </Link>

                            <!-- Media Dropdown -->
                            <NavDropdown :active="isRoute('/admin/photos') || isRoute('/admin/categories') || isRoute('/admin/galleries') || isRoute('/admin/tags')">
                                <template #trigger>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Media</span>
                                </template>
                                <template #content>
                                    <Link :href="route('admin.photos.index')" class="dropdown-item" :class="{ 'active': isRoute('/admin/photos') }">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">Photos</div>
                                            <div class="text-xs text-gray-500">Manage your photos</div>
                                        </div>
                                    </Link>
                                    <Link :href="route('admin.categories.index')" class="dropdown-item" :class="{ 'active': isRoute('/admin/categories') }">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">Categories</div>
                                            <div class="text-xs text-gray-500">Organize by category</div>
                                        </div>
                                    </Link>
                                    <Link :href="route('admin.galleries.index')" class="dropdown-item" :class="{ 'active': isRoute('/admin/galleries') }">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">Galleries</div>
                                            <div class="text-xs text-gray-500">Create photo galleries</div>
                                        </div>
                                    </Link>
                                    <Link :href="route('admin.tags.index')" class="dropdown-item" :class="{ 'active': isRoute('/admin/tags') }">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">Tags</div>
                                            <div class="text-xs text-gray-500">Label your photos</div>
                                        </div>
                                    </Link>
                                </template>
                            </NavDropdown>

                            <!-- Posts -->
                            <Link :href="route('admin.posts.index')"
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition duration-150 ease-in-out',
                                    isRoute('/admin/posts') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                                <span>Posts</span>
                            </Link>

                            <!-- Orders -->
                            <Link :href="route('admin.orders.index')"
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition duration-150 ease-in-out',
                                    isRoute('/admin/orders') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span>Orders</span>
                            </Link>

                            <!-- Contacts -->
                            <Link :href="route('admin.contacts.index')"
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition duration-150 ease-in-out',
                                    isRoute('/admin/contacts') ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>Contacts</span>
                            </Link>

                            <!-- System Dropdown -->
                            <NavDropdown align="left" :active="isRoute('/admin/frontpage') || isRoute('/admin/settings') || isRoute('/admin/logs')">
                                <template #trigger>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>System</span>
                                </template>
                                <template #content>
                                    <Link :href="route('admin.frontpage.index')" class="dropdown-item">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">Front Page</div>
                                            <div class="text-xs text-gray-500">Customize homepage</div>
                                        </div>
                                    </Link>
                                    <Link :href="route('admin.settings.index')" class="dropdown-item">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">Settings</div>
                                            <div class="text-xs text-gray-500">Site configuration</div>
                                        </div>
                                    </Link>
                                    <Link :href="route('admin.logs.index')" class="dropdown-item">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">Logs</div>
                                            <div class="text-xs text-gray-500">Activity history</div>
                                        </div>
                                    </Link>
                                </template>
                            </NavDropdown>
                        </div>
                    </div>

                    <!-- Right Side -->
                    <div class="hidden sm:flex sm:items-center sm:gap-3">
                        <!-- View Site Button -->
                        <a :href="route('home')" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            View Site
                        </a>

                        <!-- User Dropdown -->
                        <div class="relative" @click.away="userDropdownOpen = false">
                            <button @click="userDropdownOpen = !userDropdownOpen" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-semibold">
                                    {{ userInitial }}
                                </div>
                                <span class="hidden lg:inline">{{ user?.name }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-show="userDropdownOpen" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1 z-50">
                                <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</Link>
                                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Log Out</button>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-show="mobileMenuOpen" class="sm:hidden border-t border-gray-100">
                <div class="px-4 py-4 bg-gray-50 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-semibold">
                            {{ userInitial }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ user?.name }}</div>
                            <div class="text-sm text-gray-500">{{ user?.email }}</div>
                        </div>
                    </div>
                </div>

                <div class="py-2 space-y-1">
                    <Link :href="route('dashboard')" class="mobile-nav-link" :class="{ 'active': isRoute('/dashboard') }">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </Link>

                    <div class="px-4 py-2"><div class="text-xs font-semibold text-gray-400 uppercase">Media</div></div>
                    <Link :href="route('admin.photos.index')" class="mobile-nav-link">Photos</Link>
                    <Link :href="route('admin.categories.index')" class="mobile-nav-link">Categories</Link>
                    <Link :href="route('admin.galleries.index')" class="mobile-nav-link">Galleries</Link>
                    <Link :href="route('admin.tags.index')" class="mobile-nav-link">Tags</Link>

                    <div class="px-4 py-2 mt-2"><div class="text-xs font-semibold text-gray-400 uppercase">Content</div></div>
                    <Link :href="route('admin.posts.index')" class="mobile-nav-link">Posts</Link>

                    <div class="px-4 py-2 mt-2"><div class="text-xs font-semibold text-gray-400 uppercase">Commerce</div></div>
                    <Link :href="route('admin.orders.index')" class="mobile-nav-link">Orders</Link>

                    <div class="px-4 py-2 mt-2"><div class="text-xs font-semibold text-gray-400 uppercase">Communication</div></div>
                    <Link :href="route('admin.contacts.index')" class="mobile-nav-link">Contacts</Link>

                    <div class="px-4 py-2 mt-2"><div class="text-xs font-semibold text-gray-400 uppercase">System</div></div>
                    <Link :href="route('admin.frontpage.index')" class="mobile-nav-link">Front Page</Link>
                    <Link :href="route('admin.settings.index')" class="mobile-nav-link">Settings</Link>
                    <Link :href="route('admin.logs.index')" class="mobile-nav-link">Logs</Link>

                    <div class="border-t border-gray-100 mt-2 pt-2">
                        <a :href="route('home')" target="_blank" class="mobile-nav-link text-indigo-600">View Site</a>
                        <Link :href="route('profile.edit')" class="mobile-nav-link">Profile</Link>
                        <button @click="logout" class="mobile-nav-link text-red-600 w-full text-left">Log Out</button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Header -->
        <header v-if="$slots.header" class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>

<style scoped>
.dropdown-item {
    @apply flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition;
}
.dropdown-item.active {
    @apply bg-gray-50 text-gray-900;
}
.mobile-nav-link {
    @apply flex items-center gap-3 px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition;
}
.mobile-nav-link.active {
    @apply text-gray-900 bg-gray-100;
}
</style>
