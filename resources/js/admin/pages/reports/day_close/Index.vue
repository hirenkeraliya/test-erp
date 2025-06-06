<template>
    <PageTitle title="Day Close Report (Z Report)" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Day Close Report (Z Report)
        </h2>
    </div>

    <div
        v-if="state.displayDayCloseFilter"
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
                <FormSelectBox
                    :disabled="null === state.storeManagers"
                    :selected-record="state.parameters.employee_id"
                    :records="state.storeManagers === null ? [] : state.storeManagers"
                    :placeholder="null === state.storeManagers ? 'Please select a Location First' : 'Please select StoreManager'"
                    input-label="Store Managers"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStoreManagerId"
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
        :fetch-url="route('admin.day_close_report.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-day-close-reports-columns"
        search-title="Search by location or store manager"
    >
        <template #info="record">
            <div class="flex justify-center items-center cursor-pointer">
                <List
                    @click="showDayCloseDetailsModal(record.item)"
                />
            </div>
        </template>

        <template #location="record">
            {{ record.item.location }}
        </template>

        <template #sales_collection_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.sales_collection_amount) }}
        </template>

        <template #orders_collection_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.orders_collection_amount) }}
        </template>

        <template #pdf="record">
            <div class="flex justify-center items-center cursor-pointer">
                <Download
                    class="mr-1"
                    @click="exportDayCloseDetails(record.item.id)"
                />
                <Printer @click="printDayClose(record.item.id)" />
            </div>
        </template>

        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    v-if="record.data.total_sale_collection"
                    :label="'Sales Collection: ' + displayAmountWithCurrencySymbol(record.data.total_sale_collection)"
                />

                <JBadge
                    v-if="record.data.total_order_collection"
                    :label="'Orders Collection: ' + displayAmountWithCurrencySymbol(record.data.total_order_collection)"
                />
            </div>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayDayCloseFilter = !state.displayDayCloseFilter"
                />
            </p>
        </template>
    </JTable>

    <DayCloseDetails
        :modal-show="state.displayDayCloseDetailsModal"
        :day-close="state.dayClose"
        @close-modal="closeModal"
    />

    <DayClosePrint
        v-if="Object.keys(state.printDayCloseReport).length !== 0"
        :day-close="state.printDayCloseReport"
    />
</template>

<script setup>
import { route } from 'ziggy';
import { reactive, nextTick } from 'vue';
import { List, Download, Printer } from 'lucide-vue-next';
import JTable from '@commonComponents/JTable.vue';
import DayCloseDetails from '@adminPages/reports/day_close/DayCloseDetails.vue';
import DayClosePrint from '@commonComponents/DayClosePrint.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { exportRecords, currentDate, displayAmountWithCurrencySymbol, printReport, isPrintRecords } from '@commonServices/helper';
import axios from 'axios';
import JBadge from '@commonComponents/JBadge.vue';
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
    helpCenterMessages: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'id',
            isDisplay: true,
        },
        {
            key: 'location',
            isDisplay: true,
        },
        {
            key: 'store_manager',
            isDisplay: true,
        },
        {
            key: 'opened_at',
            isDisplay: true,
        },
        {
            key: 'closed_at',
            isDisplay: true,
        },
        {
            key: 'sales_collection_amount',
            label: 'Sales Collection',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'orders_collection_amount',
            label: 'Orders Collection',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'info',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
        {
            key: 'pdf',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],

    dayClose: {},
    displayDayCloseDetailsModal: false,
    refreshTableData: Math.random(),
    storeManagers: null,
    locations: null,
    displayDayCloseFilter: false,
    printReceiptData: Math.random(),
    printDayCloseReport: {},
    parameters: {
        location_ids: null,
        employee_id: null,
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
        axios.post(route('admin.store_managers.get_locations_store_managers', { location_ids: locationIds }))
            .then((response) => {
                state.storeManagers = response.data.store_managers;
            });

        refreshTable();

        return;
    }

    clearAll();
};

const updateStoreManagerId = (storeManagerId) => {
    state.parameters.employee_id = null;
    if (storeManagerId !== null) {
        state.parameters.employee_id = parseInt(storeManagerId);
    }
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

const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.employee_id = null;
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.closed_at = [];
    state.storeManagers = null;
    state.locations = null;
    refreshTable();
};

const closeModal = () => {
    state.displayDayCloseDetailsModal = false;
};

const showDayCloseDetailsModal = (dayClose) => {
    state.dayClose = dayClose;
    state.displayDayCloseDetailsModal = true;
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-store-day-close/',
        'location_day_close.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-store-day-close/',
        'location_day_close.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const exportDayCloseDetails = (dayCloseId) => {
    printReport(route('admin.day_close_report.print_day_close_report', dayCloseId), props.exportPermission);
};

const printDayClose = (dayCloseId) => {
    if (isPrintRecords(props.exportPermission)) {
        state.printDayCloseReport = [];
        state.printReceiptData = Math.random();

        axios.get(route('admin.day_close_report.fetch_day_close_report_by_id', dayCloseId))
            .then((response) => {
                state.printDayCloseReport = response.data.day_close_details;
                nextTick(() => {
                    state.printReceiptData = Math.random();
                });
            });
    }
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
