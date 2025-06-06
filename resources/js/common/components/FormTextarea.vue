<template>
    <div class="mt-3">
        <Tippy
            :content="title"
            tag="label"
            :for="validationFieldName ?? inputName"
            :class="labelClass"
        >
            {{ inputLabel }}
            <span
                v-if="required"
                class="text-danger"
            >*</span>
            <Info
                v-if="title"
                class="inline-block ml-2 text-cyan-400"
                :size="15"
            />
        </Tippy>
        <textarea
            :id="validationFieldName ?? inputName"
            :value="inputValue"
            class="h-10 form-control"
            :placeholder="placeholder ? placeholder : 'Enter ' + inputLabel"
            :name="inputName"
            :required="required"
            @input="updateInput"
        />
        <ValidationError :validation-field-name="validationFieldName ?? inputName" />
    </div>
</template>

<script setup>
import ValidationError from '@commonComponents/ValidationError.vue';
import { Info } from 'lucide-vue-next';

defineProps({
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
    title: {
        type: String,
        default: null,
    },
    labelClass: {
        type: String,
        default: 'form-label',
    },
});
const emits = defineEmits(['update:input-value']);
const updateInput = (element) => {
    emits('update:input-value', element.target.value);
};
</script>
