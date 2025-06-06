<template>
    <label
        class="block font-medium text-base text-primary-p3 mb-2"
        :for="validationFieldName"
    >
        {{ inputLabel }}<span
            v-if="required"
            class="text-accent-a4"
        >*</span>
        <slot />
    </label>

    <div class="flex items-center">
        <button
            v-if="isButtonRequired"
            type="button"
            :class="decrementButtonClass"
            @click="decrementInputValue"
        >
            -
        </button>

        <input
            :id="validationFieldName"
            type="number"
            :class="inputClass"
            :value="inputValue"
            step="any"
            @input="updateInput"
        >

        <button
            v-if="isButtonRequired"
            type="button"
            :class="incrementButtonClass"
            @click="incrementInputValue"
        >
            +
        </button>
    </div>

    <ValidationError :validation-field-name="validationFieldName" />
</template>

<script setup>
import ValidationError from '@commonComponents/ValidationError.vue';
import { numberFormat } from '@commonServices/helper';

const props = defineProps({
    inputValue: {
        type: [Number, String],
        default: null,
    },
    maximumIncrementValue: {
        type: Number,
        default: 0
    },
    isMaximumIncrementRequired: {
        type: Boolean,
        default: true
    },
    inputLabel: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    decrementButtonClass: {
        type: String,
        default: 'btn w-12 border-slate-200 bg-slate-100 text-slate-500 mr-1',
    },
    incrementButtonClass: {
        type: String,
        default: 'btn w-12 border-slate-200 bg-slate-100 text-slate-500 ml-1',
    },
    inputClass: {
        type: String,
        default: 'form-control w-24 text-center',
    },
    isButtonRequired: {
        type: Boolean,
        default: true,
    }
});

const emits = defineEmits(['update:input-value']);

const updateInput = (element) => {
    if (props.isMaximumIncrementRequired && parseFloat(element.target.value) >= parseFloat(props.maximumIncrementValue)) {
        emits('update:input-value', parseFloat(props.maximumIncrementValue));
        return false;
    }

    emits('update:input-value', parseFloat(element.target.value));
};

const decrementInputValue = () => {
    if (props.inputValue === 0) {
        return;
    }

    emits('update:input-value', numberFormat(parseFloat(props.inputValue) - 1));
    checkMaximumIncrementValue();
};

const incrementInputValue = () => {
    emits('update:input-value', numberFormat(parseFloat(props.inputValue) + 1));
    checkMaximumIncrementValue();
};

const checkMaximumIncrementValue = () => {
    if (props.inputValue < 0) {
        emits('update:input-value', 0);
    }

    if (props.isMaximumIncrementRequired && parseFloat(props.inputValue) >= parseFloat(props.maximumIncrementValue)) {
        emits('update:input-value', parseFloat(props.maximumIncrementValue));
    }
};
</script>

<style scope>
    /* Chrome, Safari, Edge, Opera */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>
