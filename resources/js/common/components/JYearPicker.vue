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
            :range="rangePicker"
            year-picker
            auto-apply
            @update:model-value="yearSelected"
        />

        <ValidationError :validation-field-name="validationFieldName" />
    </div>
</template>
<script setup>
import Datepicker from '@vuepic/vue-datepicker';
import ValidationError from '@commonComponents/ValidationError.vue';

defineProps({
    rangePicker: {
        type: Boolean,
        default: false,
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
        type: [String, Date, Array, Object, Number],
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

const yearSelected = (modelData) => {
    emits('update:input-value',
        modelData
    );
};
</script>
