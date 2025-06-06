<template>
    <button
        :type="type"
        class="btn btn-outline-danger focus:ring-0"
        :disabled="disabled"
    >
        {{ text }}
    </button>
</template>
<script setup>
defineProps({
    text: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'button'
    },
    disabled: {
        type: Boolean,
        default: false,
    }
});
</script>
