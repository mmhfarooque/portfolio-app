<script setup>
defineProps({
    links: Array
});
</script>

<template>
    <nav v-if="links.length > 3" class="flex items-center justify-between">
        <div class="flex items-center gap-1">
            <template v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    :class="[
                        'px-3 py-2 text-sm rounded-lg transition',
                        link.active
                            ? 'bg-blue-600 text-white'
                            : 'text-gray-600 hover:bg-gray-100'
                    ]"
                    preserve-scroll
                />
                <span
                    v-else
                    v-html="link.label"
                    class="px-3 py-2 text-sm text-gray-400"
                />
            </template>
        </div>
    </nav>
</template>
