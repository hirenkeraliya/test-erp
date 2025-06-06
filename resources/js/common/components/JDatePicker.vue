<template>
    <div class="mt-3">
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
            :model-value="inputValue"
            :range="rangePicker"
            :max-date="maxDate"
            :min-date="minDate"
            :enable-time-picker="false"
            :placeholder="placeholder"
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

const props = defineProps({
    rangePicker: {
        type: Boolean,
        default: false,
    },
    maxDate: {
        type: [Object, String],
        default: null
    },
    minDate: {
        type: [Object, String],
        default: null,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    inputLabel: {
        type: String,
        default: null,
    },
    inputValue: {
        type: [String, Date, Array],
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
    placeholder: {
        type: String,
        default: 'Select Date',
    },
});

const emits = defineEmits([
    'update:input-value'
]);

const dateSelected = (modelData) => {
    // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date/toISOString
    emits('update:input-value', modelData ? props.rangePicker ? [modelData[0].toISOString().split('T')[0], modelData[1].toISOString().split('T')[0]] : modelData.toISOString().split('T')[0] : null);
};
</script>
