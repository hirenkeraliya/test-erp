<template>
    <p class="font-bold my-3">
        {{ checkBoxLabel }}
    </p>

    <div class="intro-y grid grid-cols-12 gap-0 sm:gap-6">
        <div
            v-for="(record, index) in records"
            :key="index"
            :class="checkBoxClass"
        >
            <label
                :class="selectedRecords.includes(record.id) ? checkBoxLabelClass + 'font-bold text-black' : checkBoxLabelClass"
            >
                <input
                    type="checkbox"
                    class="form-check-input border mr-2"
                    :value="record.id"
                    :name="index"
                    :checked="selectedRecords.includes(record.id)"
                    @change="updateCheckbox"
                >
                {{ record.name }}
            </label>

            <slot
                :name="getStringAsSnakeCase(record.name)"
                :item="record.id"
            />
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';

const props = defineProps({
    records: {
        type: [Array, Object],
        default: () => [],
    },
    selectedRecords: {
        type: [Array, Object],
        default: () => [],
    },
    checkBoxLabel: {
        type: String,
        default: null,
    },
    checkBoxClass: {
        type: String,
        default: 'input-form col-span-12 sm:col-span-6 md:col-span-4 lg:col-span-3 xl:col-span-2',
    },
    checkBoxLabelClass: {
        type: String,
        default: 'cursor-pointer select-none text-slate-500 mr-2 flex items-center',
    },
});

const emits = defineEmits(['update:check-values']);

const state = reactive({
    selectedRecords: [],
});

const updateCheckbox = (element) => {
    const updatedId = element.target.value;
    state.selectedRecords = props.selectedRecords;

    if (!element.target.checked) {
        const index = state.selectedRecords.lastIndexOf(parseInt(updatedId));
        state.selectedRecords.splice(index, 1);
    }

    if (element.target.checked) {
        state.selectedRecords.push(parseInt(updatedId));
    }

    emits('update:check-values', state.selectedRecords);
};

const getStringAsSnakeCase = (name) => {
    return name.toLowerCase()
        .replace(/\s+/g, '_');
};
</script>
