<template>
    <div :class="firstDivClass">
        <label
            :for="validationFieldName"
            :class="labelClass"
        >
            {{ inputLabel }}
            <span
                v-if="required"
                class="text-danger"
            >*</span>
        </label>

        <Datepicker
            :model-value="state.date"
            week-picker
            :enable-time-picker="false"
            format="dd-MM-yyyy"
            auto-apply
            @update:model-value="dateSelected"
        />

        <ValidationError :validation-field-name="validationFieldName" />
    </div>
</template>
<script setup>
import Datepicker from '@vuepic/vue-datepicker';
import ValidationError from '@commonComponents/ValidationError.vue';
import { onUpdated, reactive } from 'vue';
const props = defineProps({
    validationFieldName: {
        type: String,
        default: null,
    },
    inputLabel: {
        type: String,
        default: null,
    },
    inputValue: {
        type: [String, Date, Array, Object],
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    labelClass: {
        type: String,
        default: 'form-label',
    },
    firstDivClass: {
        type: String,
        default: 'mt-3',
    },
});

const state = reactive({
    date: props.inputValue,
});

const emits = defineEmits([
    'update:input-value'
]);

const dateSelected = (modelData) => {
    state.date = modelData;
    emits('update:input-value', modelData ? [getDate(modelData[0]), getDate(modelData[1])] : null);
};

const padLength = 2;

const getDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(padLength, '0');
    const day = String(date.getDate()).padStart(padLength, '0');
    return `${year}-${month}-${day}`;
};

onUpdated(() => {
    state.date = props.inputValue;
});
</script>
