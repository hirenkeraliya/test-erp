<template>
    <div class="mt-3">
        <label
            v-if="inputLabel"
            :class="labelClass"
        >
            {{ inputLabel }}<span
                v-if="required"
                class="text-danger"
            >*</span>
        </label>

        <div class="relative">
            <VueMultiselect
                :model-value="selectedRecord"
                :options="state.records"
                :label="label"
                :placeholder="placeholder"
                :options-limit="10"
                :searchable="true"
                :internal-search="false"
                :track-by="trackBy"
                autocomplete="off"
                :loading="state.isLoading"
                :allow-empty="allowEmpty"
                :hide-selected="false"
                :clear-on-select="false"
                :multiple="multiSelect"
                open-direction="bottom"
                select-label=""
                @update:model-value="selectedRecordUpdated"
                @search-change="debounceSearch"
            >
                <template #noOptions>
                    Type to search…
                </template>
            </VueMultiselect>

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
import { reactive, nextTick } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    selectedRecord: {
        type: Object,
        default: null,
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
    searchRecords: {
        type: Function,
        default: Function,
    },
    trackBy: {
        type: String,
        default: 'id',
    },
    required: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: 'name',
    },
    multiSelect: {
        type: Boolean,
        default: false,
    },
    allowEmpty: {
        type: Boolean,
        default: true,
    },
    labelClass: {
        type: String,
        default: 'block font-medium text-base text-primary-p3 mb-2',
    },
});

const state = reactive({
    records: [],
    isLoading: false,
});

const emits = defineEmits([
    'update:selected-record'
]);

const selectedRecordUpdated = (selectedRecord) => {
    emits('update:selected-record', selectedRecord);
    nextTick(() => {
        state.isLoading = false;
    });
};

const debounceDelay = 1000;

const debounceSearch = debounce((searchText) => {
    if (!searchText.trim()) {
        return;
    }

    state.isLoading = true;
    props.searchRecords(searchText, state);
}, debounceDelay);
</script>
