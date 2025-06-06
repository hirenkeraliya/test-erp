<template>
    <div class="w-full max-w-md sm:px-0">
        <label>
            {{ inputLabel }}
            <span
                v-if="required"
                class="text-danger"
            >*</span>
        </label>

        <TabGroup :selected-index="selectedTab">
            <TabList class="flex rounded bg-secondary border-1 px-1">
                <Tab
                    v-for="(record, index) in records"
                    :key="index"
                    as="template"
                    :disabled="disabled"
                    @click="updateSelectedRecord(record[returnSelectedRecord])"
                >
                    <button
                        :class="[
                            'w-full rounded-lg py-1.5 my-1 text-sm font-medium leading-5 text-primary',
                            'ring-primary ring-opacity-60 ring-offset-2 ring-offset-primary focus:outline-none focus:ring-2',
                            record[returnSelectedRecord] === selectedRecord
                                ? 'bg-primary text-white shadow'
                                : 'text-slate-600 hover:bg-white/[0.15] hover:text-red',
                            disabled ? 'bg-white/[0.18] text-white' : ''
                        ]"
                    >
                        {{ record[label] }}
                    </button>
                </Tab>
            </TabList>

            <TabPanels class="mt-2">
                <slot />
            </TabPanels>
        </TabGroup>
    </div>
</template>

<script setup>
import { TabGroup, TabList, Tab, TabPanels } from '@headlessui/vue';
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
    returnSelectedRecord: {
        type: String,
        default: 'name',
    },
    selectedRecord: {
        type: [String, Number],
        default: null,
    },
    inputLabel: {
        type: String,
        required: true,
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const selectedTab = computed(() => {
    return props.records.findIndex(
        (record) => record[props.returnSelectedRecord] === props.selectedRecord
    );
});

const emits = defineEmits([
    'update:selected-record'
]);

const updateSelectedRecord = (selectedRecord) => {
    emits('update:selected-record', selectedRecord);
};
</script>
