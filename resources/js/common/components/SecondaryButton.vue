<template>
    <button
        :type="type"
        class="btn btn-outline-secondary focus:ring-0"
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
    }
});
</script>
