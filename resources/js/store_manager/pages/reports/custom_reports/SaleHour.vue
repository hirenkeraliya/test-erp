<template>
    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div>
                <JDateTimePicker
                    :range-picker="true"
                    input-label="Date Range"
                    :input-value="state.parameters.date_range"
                    @update:input-value="updateDate($event)"
                />
            </div>
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
                @click="printSaleHour"
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
import { showErrorNotification } from '@commonServices/notifier';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JDateTimePicker from "@commonComponents/JDateTimePicker.vue";
import { route } from 'ziggy';
import { exportRecords, printReport } from '@commonServices/helper';

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        date_range: null,
    },
});

const clearData = () => {
    emits('update:clear-button');
};

const validationCheck = () => {
    if (state.parameters.date_range === null) {
        return true;
    }

    return false;
};

const printSaleHour = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    printReport(route('store_manager.custom_reports.sale_hour_print', state.parameters));
};

const updateDate = (date) => {
    state.parameters.date_range = date;
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sale-hour/',
        'sale-hour-report.csv',
        state.parameters
    );
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sale-hour/',
        'sale-hour-report.xlsx',
        state.parameters
    );
};
</script>
