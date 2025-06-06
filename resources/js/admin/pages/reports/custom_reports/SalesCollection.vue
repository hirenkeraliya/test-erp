<template>
    <PageTitle title="Sales Collection Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-2">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="salesCollectionReports"
                input-label="Report Type"
                :required="true"
                placeholder="Select Report Type"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <JMultiSelect
                :selected-records="state.locationIds"
                :records="locations"
                input-label="Locations"
                placeholder="Please select location(s)"
                :required="true"
                @update:selected-records="updateLocationId"
            />
        </div>

        <div class="w-full lg:w-1/2 px-3 mt-2 sm:mt-2 lg:mt-8">
            <PrimaryButton
                type="button"
                text="Select all"
                class="w-auto sm:w-24 md:w-1/1"
                @click="selectAllLocations"
            />

            <OutlinePrimaryButton
                v-if="state.displayClearButton"
                type="button"
                text="Clear All"
                class="w-auto sm:w-24 md:w-1/1 mt-2"
                @click="clearAllLocations"
            />
        </div>

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
                :disabled="0 === state.locationIds.length"
                :selected-records="state.counterIds"
                :records="state.counters === null ? [] : state.counters"
                input-label="Counters"
                :placeholder="!state.locationIds.length ? 'Please select a Location First' : 'Please select Counter(s)'"
                @update:selected-records="updateCounterIds"
            />
        </div>
        <div
            v-if="state.parameters.filter_by === salesCollectionFilterStaticDetails.byCashier"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :disabled="0 === state.locationIds.length"
                :selected-records="state.cashierIds"
                :records="state.cashiers"
                input-label="Cashiers"
                :placeholder="!state.locationIds.length ? 'Please Select a Location first' : 'Please select Cashier(s)'"
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
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const props = defineProps({
    salesCollectionFilters: {
        type: Object,
        required: true,
    },
    eInvoiceFilters: {
        type: Object,
        required: true,
    },
    salesCollectionReports: {
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
    locations: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        location_ids: null,
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
    locationIds: [],
    counters: [],
    cashiers: [],
    displayClearButton: false
});

const updateLocationId = (locationIds) => {
    state.locationIds = locationIds;
    state.parameters.location_ids = null;

    if (state.locationIds.length) {
        state.cashierIds = [];

        state.parameters.location_ids = state.locationIds.map((location) => {
            return location.id;
        });

        axios.post(route('admin.counters.get_counters_of_locations'), { locations_ids: state.parameters.location_ids })
            .then((response) => {
                state.counters = response.data.counters;
            });

        axios.post(route('admin.cashiers.get_cashiers_of_stores'), { location_ids: state.parameters.location_ids })
            .then((response) => {
                state.cashiers = response.data.cashiers;
            });
    }
};

const updateCounterIds = (counterIds) => {
    state.counterIds = counterIds;
    state.parameters.counter_ids = state.counterIds.map((counter) => {
        return counter.id;
    });
};

const updateCashierIds = (cashierIds) => {
    state.cashierIds = cashierIds;
};

const selectAllLocations = () => {
    updateLocationId(props.locations);
    state.displayClearButton = true;
};

const clearAllLocations = () => {
    state.counters = [];
    state.cashiers = [];
    state.locationIds = [];
    state.counterIds = [];
    state.cashierIds = [];
    state.displayClearButton = false;
    state.parameters.location_ids = null;
    state.parameters.counter_ids = null;
    state.parameters.date_range = null;
    state.parameters.filter_by = null;
    state.parameters.cashier_ids = null;
    state.parameters.report_type = null;
};

const validationCheck = () => {
    if (state.parameters.location_ids === null) {
        return true;
    }

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
        showErrorNotification('Please select a report type, location and a date before proceeding.');
        return;
    }

    state.parameters.cashier_ids = state.cashierIds.map((cashier) => {
        return cashier.id;
    });

    printReport(route('admin.custom_reports.print_sales_collection', state.parameters));
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, location and a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sales-collection/',
        'sales-collections.csv',
        state.parameters
    );
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, location and a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sales-collection/',
        'sales-collections.xlsx',
        state.parameters
    );
};

const updateEInvoice = (value) => {
    state.parameters.e_invoice_submitted = value;
};

const clearData = () => {
    emits('update:clear-button');
};
</script>
