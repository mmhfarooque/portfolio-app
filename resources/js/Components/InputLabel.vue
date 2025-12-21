<script setup>
defineProps({
    value: String,
    required: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <label class="block font-medium text-sm text-gray-700">
        <slot v-if="!value" />
        <span v-else>{{ value }}</span>
        <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
</template>
