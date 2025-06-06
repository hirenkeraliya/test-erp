<template>
    <PageTitle title="Closed Counters" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Closed Counters
        </h2>
    </div>

    <div
        v-if="state.displayClosedCounterReportsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.locations"
                    :records="locations"
                    placeholder="Please select location"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateLocations"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.counters"
                    :disabled="null === state.storeCounters"
                    :records="state.storeCounters === null ? [] : state.storeCounters"
                    :placeholder="state.parameters.location_ids ? 'Please select Counter' : 'Please select a Location First'"
                    input-label="Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateCounterId"
                />
            </div>

            <div>
                <FormSelectBox
                    :disabled="null === state.cashiers"
                    :selected-record="state.parameters.cashier_id"
                    :records="state.cashiers === null ? []: state.cashiers"
                    :placeholder="state.parameters.location_ids ? 'Please select Cashier' : 'Please select a Location First'"
                    input-label="Cashiers"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateCashierId"
                />
            </div>

            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Opened At"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
                />
            </div>

            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.closed_at"
                    input-label="Closed At"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateClosedDate($event)"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.closed_counters.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-closed-counters-reports-columns"
        search-title="Search by opening balance, cashier name, closing balance, or mismatch"
    >
        <template #id="data">
            <div class="flex justify-left items-center">
                <span>
                    {{ data.item.id }}
                </span>
            </div>
        </template>

        <template #counter="data">
            {{ (data.item.counter.name) }}
        </template>

        <template #opening_balance="data">
            {{ displayAmountWithCurrencySymbol(data.item.opening_balance) }}
        </template>

        <template #closing_balance="data">
            {{ displayAmountWithCurrencySymbol(data.item.closing_balance) }}
        </template>

        <template #mismatch_amount="data">
            <div class="flex flex-wrap items-center justify-end">
                {{ displayAmountWithCurrencySymbol(data.item.mismatch_amount) }}

                <Tippy
                    v-if="data.item.reason"
                    class="ml-2"
                    :content="'Notes: ' + data.item.reason"
                >
                    <Info class="text-cyan-400" />
                </Tippy>
            </div>
        </template>

        <template #total_sales_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_sales_amount) }}
        </template>

        <template #attempt_count="data">
            <JBadge
                :label="data.item.attempt_count"
                type="primary"
            />
        </template>

        <template #counter_information="data">
            <div class="flex justify-center items-center">
                <button
                    class="btn btn-info mr-1 mb-2"
                    @click="openClosedCounterDetailsModal(data.item.id)"
                >
                    <Info
                        class="w-5 h-5"
                        text="Display Closed Counter Details"
                    />
                </button>

                <button
                    v-if="data.item.counter_update_declaration_attempt"
                    class="btn btn-info mr-1 mb-2"
                    @click="printClosedCounterDetails(data.item.id)"
                >
                    <Printer
                        class="w-5 h-5"
                        text="Print"
                    />
                </button>
            </div>
        </template>

        <template #sales_collection_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.sales_collection_amount) }}
        </template>

        <template #extra-header-data="data">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    :label="'Collection: ' + displayAmountWithCurrencySymbol(data.data.total_sales_collection)"
                />
            </div>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayClosedCounterReportsFilter = !state.displayClosedCounterReportsFilter"
                />
            </p>
        </template>
    </JTable>

    <ClosedCounterDetails
        v-if="state.displayClosedCounterDetailsModal"
        :modal-show="state.displayClosedCounterDetailsModal"
        :counter-closing-details="state.counterClosingDetails"
        :closing-counter-id="state.currentOpenModalClosedCounterId"
        @close-modal="closeModal"
    />

    <Receipt
        v-if="Object.keys(state.counterClosingDetailsForPrint).length"
        :close-counter-details="state.counterClosingDetailsForPrint"
        :print-receipt-data="state.printReceiptData"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { displayAmountWithCurrencySymbol, exportRecords, currentDate, printHtml, isPrintRecords } from '@commonServices/helper';
import ClosedCounterDetails from '@adminPages/sales/partials/ClosedCounterDetails.vue';
import { Info, Printer } from 'lucide-vue-next';
import { reactive, nextTick, watch } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import axios from 'axios';
import JBadge from '@commonComponents/JBadge.vue';
import Receipt from '@commonComponents/Receipt.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    helpCenterMessage: {
        type: String,
        required: true,
    }
});

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true,
            label: 'Id',
            isDisplay: true,
        }, {
            key: 'counter',
            label: 'Counter',
            isDisplay: true,
        }, {
            key: 'cashier_name',
            label: 'Cashier',
            isDisplay: true,
        }, {
            key: 'location_name',
            label: 'Location',
            isDisplay: true,
        }, {
            key: 'opening_balance',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'closing_balance',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'sales_collection_amount',
            bodyClass: 'text-right',
            label: 'Collection',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'opened_at',
            isDisplay: true,
        }, {
            key: 'closed_at',
            isDisplay: true,
        }, {
            key: 'mismatch_amount',
            bodyClass: 'text-right',
            label: 'Mismatch',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'attempt_count',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            label: 'Attempts',
            isDisplay: true,
        }, {
            key: 'counter_information',
            label: 'Counter Info',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    displayClosedCounterDetailsModal: false,
    currentOpenModalClosedCounterId: null,
    counterClosingDetails: [],
    counterClosingPrintDetails: [],
    counters: null,
    storeCounters: null,
    cashiers: null,
    locations: null,
    refreshTableData: Math.random(),
    displayClosedCounterReportsFilter: false,
    printReceiptData: Math.random(),
    counterClosingDetailsForPrint: [],
    parameters: {
        location_ids: null,
        counter_ids: null,
        cashier_id: null,
        date_range: [currentDate(), currentDate()],
        closed_at: [],
    },
});
const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateLocations = (locations) => {
    state.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });
    if (locationIds.length) {
        state.parameters.location_ids = locationIds;
        state.parameters.counter_ids = null;
        state.parameters.cashier_id = null;

        axios.post(route('admin.counters.get_counters_of_locations', { locations_ids: locationIds }))
            .then((response) => {
                state.storeCounters = response.data.counters;
            });

        axios.post(route('admin.cashiers.get_cashiers_of_stores', { location_ids: locationIds }))
            .then((response) => {
                state.cashiers = response.data.cashiers;
            });

        refreshTable();

        return;
    }

    clearAll();
};
const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.counter_ids = null;
    state.parameters.cashier_id = null;
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.closed_at = [];
    state.counters = null;
    state.cashiers = null;
    state.locations = null;
    state.storeCounters = null;
    refreshTable();
};

const updateCounterId = (counters) => {
    state.counters = counters;
    const counterIds = counters.map((counter) => {
        return counter.id;
    });
    state.parameters.counter_ids = counterIds;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateClosedDate = (date) => {
    state.parameters.closed_at = date;
    refreshTable();
};

const updateCashierId = (cashierId) => {
    state.parameters.cashier_id = null;
    if (cashierId !== null) {
        state.parameters.cashier_id = parseInt(cashierId);
    }
    refreshTable();
};

const openClosedCounterDetailsModal = (counterUpdateId) => {
    state.counterClosingDetails = [];
    axios.get(route('admin.closed_counters.fetch_closed_counter_details', counterUpdateId))
        .then((response) => {
            state.counterClosingDetails = response.data.closed_counter_update_details;
        });

    state.displayClosedCounterDetailsModal = true;
    state.currentOpenModalClosedCounterId = counterUpdateId;
};

const printClosedCounterDetails = (counterUpdateId) => {
    if (isPrintRecords(props.exportPermission)) {
        state.counterClosingDetailsForPrint = [];
        axios.get(route('admin.closed_counters.fetch_closed_counter_print_details', counterUpdateId))
            .then((response) => {
                state.counterClosingDetailsForPrint = response.data.closed_counter_update_print_details;

                nextTick(() => {
                    state.printReceiptData = Math.random();
                });
            });
    }
};

const closeModal = () => {
    state.displayClosedCounterDetailsModal = false;
    state.currentOpenModalClosedCounterId = null;
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-closed-counters/',
        'closed_Counters.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-closed-counters/',
        'closed_Counters.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

watch(() => state.printReceiptData,
    () => {
        printHtml();
    }
);

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessage);
</script>
