<script setup>
defineProps({
    message: String
});
</script>

<template>
    <div v-if="message">
        <p class="text-sm text-red-600 mt-1">{{ message }}</p>
    </div>
</template>
