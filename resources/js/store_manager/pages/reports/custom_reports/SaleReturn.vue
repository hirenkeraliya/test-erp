<template>
    <PageTitle title="Sale Return Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.filter_by"
                :records="saleReturnFilters"
                input-label="Filter Type"
                placeholder="Select Filter Type"
            />
        </div>
        <div
            v-if="state.parameters.filter_by === saleReturnFilterStaticDetails.byCounter"
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
        <div
            v-if="state.parameters.filter_by === saleReturnFilterStaticDetails.byCashier"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.cashierIds"
                :records="state.cashiers"
                input-label="Cashiers"
                :placeholder="'Please select Cashier(s)'"
                @update:selected-records="updateCashierIds"
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
                @click="exportPDFSaleReturnReport"
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
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';
import { exportRecords, printReport } from '@commonServices/helper';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

defineProps({
    saleReturnFilters: {
        type: Object,
        required: true,
    },
    saleReturnFilterStaticDetails: {
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
        counter_ids: [],
        cashier_ids: [],
        filter_by: null,
    },
    counterIds: [],
    cashierIds: [],
    counters: [],
    cashiers: [],
});

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

const validationCheck = () => {
    if (state.parameters.date_range === null) {
        return true;
    }
    return false;
};

const updateCounterIds = (counterIds) => {
    state.counterIds = counterIds;
    state.parameters.counter_ids = state.counterIds.map((counter) => {
        return counter.id;
    });
};

const updateCashierIds = (cashierIds) => {
    state.cashierIds = cashierIds;
    state.parameters.cashier_ids = state.cashierIds.map((cashier) => {
        return cashier.id;
    });
};

const exportPDFSaleReturnReport = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    printReport(route('store_manager.custom_reports.print_sale_return', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sale-return/',
        'sale-return.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sale-return/',
        'sale-return.csv',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};
</script>
