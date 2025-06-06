<template>
    <PageTitle title="Sales Collection Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-2">
            <FormSelectBox
                :selected-record="state.parameters.e_invoice_submitted"
                :records="eInvoiceFilters"
                input-label="To Exclude By E-Invoice Generated"
                @update:selected-record="updateEInvoice"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="salesCollectionReports"
                input-label="Report Type"
                :required="true"
                placeholder="Select Report Type"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.filter_by"
                :records="salesCollectionFilters"
                input-label="Filter Type"
                placeholder="Select Filter Type"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === salesCollectionFilterStaticDetails.byCounter"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.counterIds"
                :records="state.counters === null ? [] : state.counters"
                input-label="Counters"
                placeholder="Please select Counter(s)"
                @update:selected-records="updateCounterIds"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === salesCollectionFilterStaticDetails.byCashier"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.cashierIds"
                :records="state.cashiers"
                input-label="Cashiers"
                placeholder="Please select Cashier(s)"
                @update:selected-records="updateCashierIds"
            />
        </div>

        <div
            v-if="state.parameters.report_type"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JDatePicker
                v-if="salesCollectionReportStaticDetails.byCurrentDayVsPreviousDay === state.parameters.report_type"
                v-model:input-value="state.parameters.date"
                :range-picker="false"
                :required="true"
                input-label="Date"
            />

            <JDatePicker
                v-else
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
                @click="exportSalesCollection"
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
import axios from 'axios';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import { exportRecords, printReport } from '@commonServices/helper';

const props = defineProps({
    salesCollectionFilters: {
        type: Object,
        required: true,
    },
    salesCollectionReports: {
        type: Object,
        required: true,
    },
    eInvoiceFilters: {
        type: Object,
        required: true,
    },
    salesCollectionFilterStaticDetails: {
        type: Object,
        required: true,
    },
    salesCollectionReportStaticDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const fetchCashiersAndCounters = () => {
    axios.get(route('store_manager.counters.get_location_counters'))
        .then((response) => {
            state.counters = response.data.counters;
        });
    axios.get(route('store_manager.cashiers.get_store_cashiers'))
        .then((response) => {
            state.cashiers = response.data.cashiers;
        });
};

fetchCashiersAndCounters();

const state = reactive({
    parameters: {
        counter_ids: null,
        cashier_ids: [],
        date_range: null,
        date: null,
        filter_by: null,
        report_type: null,
        e_invoice_submitted: null,
    },

    counterIds: [],
    cashierIds: [],
    counters: [],
    cashiers: [],
});

const updateCounterIds = (counterIds) => {
    state.counterIds = counterIds;
    state.parameters.counter_ids = state.counterIds.map((counter) => {
        return counter.id;
    });
};

const updateCashierIds = (cashierIds) => {
    state.cashierIds = cashierIds;
};

const validationCheck = () => {
    if (props.salesCollectionReportStaticDetails.byCurrentDayVsPreviousDay !== state.parameters.report_type && state.parameters.date_range === null) {
        return true;
    }

    if (props.salesCollectionReportStaticDetails.byCurrentDayVsPreviousDay === state.parameters.report_type && state.parameters.date === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    return false;
};

const exportSalesCollection = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    state.parameters.cashier_ids = state.cashierIds.map((cashier) => {
        return cashier.id;
    });

    printReport(route('store_manager.custom_reports.print_sales_collection', state.parameters));
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sales-collection/',
        'sales-collections.csv',
        state.parameters
    );
};

const updateEInvoice = (value) => {
    state.parameters.e_invoice_submitted = value;
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sales-collection/',
        'sales-collections.xlsx',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};
</script>
