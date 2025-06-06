<template>
    <button
        :type="type"
        class="btn btn-outline-primary focus:ring-0"
        :disabled="state.isDisabled"
    >
        {{ text }}
    </button>
</template>
<script setup>
import { router } from '@inertiajs/vue3';
import { onMounted, onUpdated, reactive } from 'vue';

const props = defineProps({
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
    },
    disableOnSubmit: {
        type: Boolean,
        default: true
    }
});

const state = reactive({
    isDisabled: {
        type: Boolean,
        default: false
    }
});

onMounted(() => {
    state.isDisabled = props.disabled;

    router.on('start', () => {
        state.isDisabled = props.disableOnSubmit || props.disabled;
    });

    router.on('finish', () => {
        state.isDisabled = props.disabled;
    });
});

onUpdated(() => {
    state.isDisabled = props.disabled;
});
</script>
