<template>
    <div class="mt-3">
        <Tippy
            v-if="displayLabel"
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
                :model-value="Object.keys(state.selectedOption).length === 0 ? null : state.selectedOption"
                :options="records.length ? records : []"
                :multiple="false"
                :close-on-select="true"
                :clear-on-select="false"
                :placeholder="placeholder ? placeholder : 'Select ' + inputLabel"
                :label="recordKeyName"
                :track-by="trackBy"
                :disabled="disabled"
                select-label=""
                :allow-empty="true"
                :hide-selected="false"
                open-direction="bottom"
                @update:model-value="optionUpdated"
                @mousedown.stop=""
            >
                <template
                    v-if="displayBackgroundColors"
                    #option="data"
                >
                    <div class="flex items-center">
                        <div
                            :class="data.option.id"
                            class="rounded-full w-5 h-5 mr-2"
                        />
                        {{ data.option.name }}
                    </div>
                </template>
            </VueMultiselect>

            <ValidationError
                v-if="showValidationError"
                :validation-field-name="validationFieldName"
            />
        </div>
    </div>
</template>
<script setup>
import VueMultiselect from 'vue-multiselect';
import ValidationError from '@commonComponents/ValidationError.vue';
import { Info } from 'lucide-vue-next';
import { onMounted, reactive, watch } from 'vue';

const props = defineProps({
    inputLabel: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        default: null,
    },
    records: {
        type: Array,
        required: true,
    },
    selectedRecord: {
        type: [String, Number],
        default: null,
    },
    recordKeyName: {
        type: String,
        default: 'name',
    },
    recordValueKeyName: {
        type: String,
        default: 'id',
    },
    placeholder: {
        type: String,
        default: null,
    },
    showValidationError: {
        type: Boolean,
        default: true,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    trackBy: {
        type: String,
        default: 'name',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    displayLabel: {
        type: Boolean,
        default: true,
    },
    labelClass: {
        type: String,
        default: 'form-label',
    },
    displayBackgroundColors: {
        type: Boolean,
        default: false,
    },
});

const state = reactive({
    selectedOption: {},
});

const emits = defineEmits([
    'update:selected-record'
]);

const optionUpdated = (selectedOption) => {
    if (selectedOption) {
        state.selectedOption = selectedOption;
    }
    emits('update:selected-record', selectedOption ? selectedOption[`${props.recordValueKeyName}`] : null);
};

const updateSelectedOption = () => {
    state.selectedOption = {};
    for (const key in props.records) {
        if (props.records[key][props.recordValueKeyName] === props.selectedRecord) {
            state.selectedOption = props.records[key];
        }
    }
};

watch(() => props.selectedRecord,
    () => {
        updateSelectedOption();
    }
);

watch(() => props.records,
    () => {
        updateSelectedOption();
    }
);

onMounted(() => {
    updateSelectedOption();
});
</script>
