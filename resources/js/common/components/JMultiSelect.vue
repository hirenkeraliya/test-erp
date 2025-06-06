<template>
    <div class="mt-3">
        <Tippy
            tag="label"
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

        <div class="relative">
            <VueMultiselect
                :model-value="selectedRecords"
                :options="records.length ? records : []"
                :multiple="true"
                :taggable="taggable"
                :close-on-select="true"
                select-label=""
                deselect-label=""
                :disabled="disabled"
                :placeholder="placeholder ? placeholder : 'Select ' + inputLabel"
                :hide-selected="true"
                :label="label"
                :track-by="trackBy"
                open-direction="bottom"
                @update:model-value="optionUpdated"
                @select="optionAdded"
                @remove="optionRemoved"
                @mousedown.stop=""
                @tag="optionCreate"
            />

            <ValidationError
                v-if="validationFieldName"
                :validation-field-name="validationFieldName"
            />
        </div>
    </div>
</template>

<script setup>
import VueMultiselect from 'vue-multiselect';
import ValidationError from '@commonComponents/ValidationError.vue';
import { Info } from 'lucide-vue-next';

const props = defineProps({
    records: {
        type: Array,
        required: true,
    },
    selectedRecords: {
        type: Array,
        default: () => [],
    },
    inputLabel: {
        type: String,
        default: null,
    },
    placeholder: {
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
    label: {
        type: String,
        default: 'name',
    },
    trackBy: {
        type: String,
        default: 'name',
    },
    optionCreate: {
        type: Function,
        default: Function,
    },
    title: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    taggable: {
        type: Boolean,
        default: false,
    },
    labelClass: {
        type: String,
        default: 'form-label',
    },
});

const emits = defineEmits([
    'update:selected-records',
    'option-added',
    'option-removed',
]);

const optionCreate = (option) => {
    props.optionCreate(option);
};

const optionUpdated = (selectedOptions) => {
    emits('update:selected-records', selectedOptions);
};

const optionAdded = (addedOption) => {
    emits('option-added', addedOption);
};

const optionRemoved = (removedOption) => {
    emits('option-removed', removedOption);
};

</script>
