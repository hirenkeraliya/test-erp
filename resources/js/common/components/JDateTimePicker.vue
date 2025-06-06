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
            :enable-time-picker="true"
            :enable-seconds="true"
            :format="'dd-MM-yyyy HH:mm:ss'"
            auto-apply
            @update:model-value="dateSelected"
        />

        <ValidationError :validation-field-name="validationFieldName" />
    </div>
</template>
<script setup>
import Datepicker from '@vuepic/vue-datepicker';
import ValidationError from '@commonComponents/ValidationError.vue';
import { format } from 'date-fns';
import { debounce } from 'lodash';

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
});

const emits = defineEmits([
    'update:input-value'
]);

const debounceDelay = 500;

const dateSelected = debounce((modelData) => {
    emits('update:input-value', getFormattedDate(modelData));
}, debounceDelay);

const getFormattedDate = (selectedDate) => {
    if (!selectedDate) {
        return null;
    }

    if (props.rangePicker) {
        return [format(selectedDate[0], 'yyyy-MM-dd HH:mm:ss'), format(selectedDate[1], 'yyyy-MM-dd HH:mm:ss')];
    }

    return format(selectedDate, 'yyyy-MM-dd HH:mm:ss');
};
</script>
