<template>
    <input
        class="intro-x login__input form-control py-3 px-4 block mt-4"
        :type="type"
        :placeholder="placeholder"
        :name="inputName"
        :value="inputValue"
        :required="required"
        @input="updateInput"
    >

    <ValidationError :validation-field-name="validationFieldName ?? inputName" />
</template>
<script setup>
import ValidationError from '@commonComponents/ValidationError.vue';
defineProps({
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: null,
    },
    inputName: {
        type: String,
        default: null,
    },
    inputValue: {
        type: [String, Number],
        default: null,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
});
const emits = defineEmits(['update:input-value']);
const updateInput = (element) => {
    emits('update:input-value', element.target.value);
};
</script>
