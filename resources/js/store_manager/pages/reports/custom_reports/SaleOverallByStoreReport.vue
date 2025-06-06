<template>
    <PageTitle title="Sales Overall Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_by"
                :records="salesOverallByLocationFilters"
                input-label="Report By"
                :required="true"
            />
        </div>
        <div
            v-if="state.parameters.report_by"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
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
                @click="exportSalesOverallByStore"
            />

            <PrimaryButton
                type="button"
                text="Excel"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportExcelRecord"
            />

            <PrimaryButton
                type="button"
                text="CSV"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportCsvRecord"
            />
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import { exportRecords, printReport } from '@commonServices/helper';

defineProps({
    salesOverallByLocationFilters: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        date_range: null,
        report_by: null,
    },
});

const validationCheck = () => {
    if (state.parameters.date_range === null) {
        return true;
    }

    if (state.parameters.report_by === null) {
        return true;
    }

    return false;
};

const exportSalesOverallByStore = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    printReport(route('store_manager.custom_reports.print-sales-overall-report', state.parameters));
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sales-overall-report/',
        'sales-overall-report.csv',
        state.parameters
    );
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sales-overall-report/',
        'sales-overall-report.xlsx',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};
</script>
