<template>
    <PageTitle title="Void Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.filter_by"
                :records="voidFilters"
                input-label="Filter Type"
                placeholder="Select Filter Type"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === voidFilterStaticDetails.byCounter"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.counterIds"
                :records="state.counters === null ? [] : state.counters"
                input-label="Counters"
                :placeholder="'Please select Counter(s)'"
                @update:selected-records="updateCounterIds"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <JDatePicker
                v-model:input-value="state.parameters.date_range"
                :range-picker="true"
                :required="true"
                input-label="Date Range"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
            <OutlineDangerButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="clearData"
            />

            <PrimaryButton
                type="button"
                text="PDF"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportPDFVoidReport"
            />

            <PrimaryButton
                type="button"
                text="Excel"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportExcelVoidReport"
            />

            <PrimaryButton
                type="button"
                text="CSV"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportCsvVoidReport"
            />
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import { exportRecords, printReport } from '@commonServices/helper';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

defineProps({
    voidFilters: {
        type: Object,
        required: true,
    },
    voidFilterStaticDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        counter_ids: null,
        date_range: null,
        filter_by: null,
    },

    counterIds: [],
    counters: [],
});

const fetchCounters = () => {
    axios.get(route('store_manager.counters.get_location_counters'))
        .then((response) => {
            state.counters = response.data.counters;
        });
};

fetchCounters();

const updateCounterIds = (counterIds) => {
    state.counterIds = counterIds;
    state.parameters.counter_ids = state.counterIds.map((counter) => {
        return counter.id;
    });
};

const validationCheck = () => {
    if (state.parameters.date_range === null) {
        return true;
    }

    return false;
};

const exportPDFVoidReport = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    printReport(route('store_manager.custom_reports.print_void_report', state.parameters));
};

const exportCsvVoidReport = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-void-report/',
        'void-report.csv',
        state.parameters
    );
};

const exportExcelVoidReport = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-void-report/',
        'void-report.xlsx',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};
</script>
