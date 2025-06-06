<template>
    <div
        class="intro-y rounded"
        :class="getAlertClass()"
    >
        <p class="p-2">
            <slot />
        </p>
    </div>
</template>

<script setup>
const props = defineProps({
    color: {
        type: String,
        default: 'primary',
    },
});

const getAlertClass = () => {
    if (props.color === 'danger') {
        return 'alert-danger-soft';
    }

    if (props.color === 'warning') {
        return 'alert-warning-soft';
    }

    if (props.color === 'primary') {
        return 'alert-primary-soft';
    }

    if (props.color === 'success') {
        return 'alert-success-soft';
    }
};
</script>
