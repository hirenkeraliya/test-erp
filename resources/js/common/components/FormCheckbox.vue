<template>
    <label class="cursor-pointer select-none">
        <input
            type="checkbox"
            class="form-check-input border mr-2"
            :value="checkValue"
            :name="checkboxName"
            :checked="checkValue"
            :disabled="isDisabled"
            @change="updateCheckbox"
        >
        {{ checkLabel }}
    </label>
</template>

<script setup>
defineProps({
    checkboxName: {
        type: String,
        default: null,
    },
    checkValue: {
        type: [Boolean, Number],
        default: null,
    },
    checkLabel: {
        type: String,
        default: null,
    },
    isDisabled: {
        type: Boolean,
        default: false,
    }
});

const emits = defineEmits(['update:check-value']);

const updateCheckbox = (element) => {
    emits('update:check-value', element.target.checked);
};
</script>
