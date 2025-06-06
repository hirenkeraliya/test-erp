<template>
    <div class="mt-3">
        <label
            :for="validationFieldName"
        >
            {{ inputLabel }}
            <span
                v-if="required"
                class="text-danger"
            >*</span>
        </label>

        <input
            :id="validationFieldName"
            class="form-control shadow-transparent"
            type="file"
            :accept="accept"
            :required="required"
            :multiple="isMultiple"
            @change="updateFile"
        >

        <ValidationError :validation-field-name="validationFieldName" />
    </div>
</template>
<script setup>
import ValidationError from '@commonComponents/ValidationError.vue';

const props = defineProps({
    inputLabel: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    accept: {
        type: String,
        default: null,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    isMultiple: {
        type: Boolean,
        default: false,
    },
});

const emits = defineEmits(['update:input-file']);

const updateFile = (inputFile) => {
    if (props.isMultiple) {
        emits('update:input-file', inputFile.target.files);
    }
    emits('update:input-file', inputFile.target.files[0]);
};
</script>
