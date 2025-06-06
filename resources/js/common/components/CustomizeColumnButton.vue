<template>
    <button
        type="button"
        class="btn btn-outline-primary focus:ring-0"
        @click="clicked()"
    >
        <span class="text-center">
            <slot />
        </span>
    </button>
</template>

<script setup>
const emits = defineEmits(['clicked']);

const clicked = () => {
    emits('clicked', true);
};
</script>
