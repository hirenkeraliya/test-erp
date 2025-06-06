<template>
    <label
        v-if="inputLabel"
        :class="labelClass"
    >
        {{ inputLabel }}
        <span
            v-if="required"
            class="text-danger"
        >*</span>
    </label>
    <TabGroup :selected-index="selectedTab">
        <TabList
            class="nav nav-pills bg-slate-300 rounded-md p-1"
        >
            <Tab
                v-for="(record, index) in records"
                :key="index"
                class="w-full py-1.5"
                :class="getButtonClass(record)"
                tag="button"
                :disabled="disabled"
                @click="updateSelectedRecord(record[returnSelectedRecord])"
            >
                {{ record[label] }}
            </Tab>
        </TabList>

        <TabPanels class="mt-3">
            <slot />
        </TabPanels>
    </TabGroup>
</template>

<script setup>
import { TabGroup, TabPanels, Tab, TabList } from '@commonVendor/tab';
import { computed } from 'vue';

const props = defineProps({
    records: {
        type: Array,
        required: true,
    },
    label: {
        type: String,
        default: 'name',
    },
    selectedRecord: {
        type: [String, Number],
        default: null,
    },
    inputLabel: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    labelClass: {
        type: String,
        default: 'form-label',
    },
    returnSelectedRecord: {
        type: String,
        default: 'name',
    },
});

const emits = defineEmits([
    'update:selected-record'
]);

const updateSelectedRecord = (selectedRecord) => {
    emits('update:selected-record', selectedRecord);
};

const getButtonClass = (record) => {
    if (record[props.returnSelectedRecord] === props.selectedRecord) {
        return 'active';
    }
};

const selectedTab = computed(() => {
    return props.records.findIndex(
        (record) => record[props.returnSelectedRecord] === props.selectedRecord
    );
});
</script>
