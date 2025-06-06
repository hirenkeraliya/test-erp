<template>
    <div class="mt-3">
        <Tippy
            tag="label"
            :for="validationFieldName ?? inputName"
            :class="labelClass"
            :content="title"
        >
            {{ inputLabel }}
            <span
                v-if="required"
                class="text-danger"
            >*</span>
            <Info
                v-if="title"
                class="text-cyan-400 inline-block ml-2"
                :size="15"
            />
        </Tippy>

        <div class="input-group">
            <div
                v-if="inputGroupPrefix"
                :id="validationFieldName ?? inputName"
                class="input-group-text"
            >
                {{ inputGroupPrefix }}
            </div>

            <input
                :id="validationFieldName ?? inputName"
                class="form-control"
                :type="type"
                :step="type === 'number' ? 'any' : ''"
                :min="type === 'number' ? min : ''"
                :max="type === 'number' ? max : ''"
                :placeholder="placeholder ? placeholder : 'Enter ' + inputLabel"
                :name="inputName"
                :value="inputValue"
                :required="required"
                :readonly="readonly"
                @input="updateInput"
            >

            <div
                v-if="inputGroupSuffix"
                :id="validationFieldName ?? inputName"
                class="input-group-text"
            >
                {{ inputGroupSuffix }}
            </div>
        </div>

        <ValidationError :validation-field-name="validationFieldName ?? inputName" />
    </div>
</template>

<script setup>
import ValidationError from '@commonComponents/ValidationError.vue';
import { Info } from 'lucide-vue-next';
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
    inputLabel: {
        type: String,
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
    readonly: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: null,
    },
    inputGroupPrefix: {
        type: String,
        default: null,
    },
    inputGroupSuffix: {
        type: String,
        default: null,
    },
    labelClass: {
        type: String,
        default: 'form-label',
    },
    min: {
        type: String,
        default: null,
    },
    max: {
        type: String,
        default: null,
    },
});
const emits = defineEmits(['update:input-value']);
const updateInput = (element) => {
    emits('update:input-value', element.target.value);
};
</script>
