<template>
    <Tippy
        tag="button"
        :type="type"
        :disabled="state.isDisabled"
        :content="title"
        :class="buttonClass"
    >
        {{ text }}
    </Tippy>
</template>
<script setup>
import { onMounted, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

const state = reactive({
    isDisabled: {
        type: Boolean,
        default: false
    }
});

const props = defineProps({
    text: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'submit'
    },
    title: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false
    },
    disableOnSubmit: {
        type: Boolean,
        default: true
    },
    buttonClass: {
        type: String,
        default: 'btn btn-primary'
    },
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
</script>
