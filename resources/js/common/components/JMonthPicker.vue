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
            :model-value="inputValue"
            type="false"
            month-picker
            :max-date="maxDate"
            :min-date="minDate"
            :enable-time-picker="false"
            auto-apply
            @update:model-value="dateSelected"
        />

        <ValidationError :validation-field-name="validationFieldName" />
    </div>
</template>
<script setup>
import Datepicker from '@vuepic/vue-datepicker';
import ValidationError from '@commonComponents/ValidationError.vue';

defineProps({
    maxDate: {
        type: Object,
        default: null
    },
    minDate: {
        type: Object,
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

const emits = defineEmits([
    'update:input-value'
]);

const dateSelected = (modelData) => {
    emits('update:input-value',
        modelData
    );
};
</script>
