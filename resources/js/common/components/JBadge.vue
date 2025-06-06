<template>
    <span
        :class="getBadgeClass()"
    >
        {{ label }}
    </span>
</template>

<script setup>
const props = defineProps({
    label: {
        type: [String, Number],
        default: null,
    },
    type: {
        type: String,
        default: 'primary',
    },
    spanClass: {
        type: String,
        default: 'inline-flex items-center justify-center px-1.5 py-1.5 mr-2 font-medium text-xs leading-none rounded cursor-pointer',
    }
});

const getBadgeClass = () => {
    switch (props.type) {
        case 'success':
            return `${props.spanClass}' alert-success-soft rounded px-2'`;
        case 'danger':
            return `${props.spanClass}' alert-danger-soft rounded px-2'`;
        case 'warning':
            return `${props.spanClass}' alert-warning-soft rounded px-2'`;
        case 'primary':
            return `${props.spanClass}' alert-primary-soft rounded px-2'`;
        case 'dark':
            return `${props.spanClass}' alert-dark-soft rounded px-2'`;
        default:
            return `${props.spanClass}' alert-secondary-soft rounded px-2'`;
    }
};
</script>
