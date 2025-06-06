<template>
    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="stockTransferStatusSummaryReportType"
                input-label="Report By"
                :required="true"
                placeholder="Report By"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="mt-3">
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.typeId"
                    input-label="Location Selection"
                    return-selected-record="id"
                    @update:selected-record="updateLocationType"
                />
            </div>
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <TabPanel
                v-if="state.typeId === staticLocationTypes.store"
                class="active"
            >
                <JMultiSelect
                    :selected-records="state.stores"
                    :records="stores"
                    input-label="Source Locations"
                    placeholder="Please select store"
                    @update:selected-records="updateStores"
                />
            </TabPanel>

            <TabPanel
                v-if="state.typeId === staticLocationTypes.warehouse"
                class="active"
            >
                <JMultiSelect
                    :selected-records="state.warehouses"
                    :records="warehouses"
                    input-label="Source Locations"
                    placeholder="Please select warehouse"
                    @update:selected-records="updateWarehouses"
                />
            </TabPanel>
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
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div
            v-if="state.parameters.report_type"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JDatePicker
                v-model:input-value="state.parameters.date_range"
                :required="true"
                :range-picker="true"
                input-label="Date Filter"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.status"
                :records="statuses"
                input-label="Select status for man days"
                :required="true"
                placeholder="Select status"
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
                @click="exportStockAdjustment"
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
import { TabPanel } from '@commonVendor/tab';
import { showErrorNotification } from '@commonServices/notifier';
import JTabs from '@commonComponents/JTabs.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { route } from 'ziggy';
import { exportRecords, printReport } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    stockTransferStatusSummaryReportType: {
        type: Object,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    typeId: props.staticLocationTypes.store,
    parameters: {
        location_ids: null,
        date_range: null,
        report_type: null,
        status: null,
    },
    stores: [],
    warehouses: [],
    displayInventoryUpdateFilterModal: false,
});

const updateLocationType = (typeId) => {
    state.typeId = typeId;
    state.parameters.location_ids = null;
    state.stores = null;
    state.warehouses = null;
};

const clearData = () => {
    emits('update:clear-button');
};

const validationCheck = () => {
    if (state.parameters.date_range === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    if (state.parameters.status === null) {
        return true;
    }

    return false;
};

const exportStockAdjustment = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, status and a date before proceeding.');
        return;
    }

    printReport(route('admin.custom_reports.print_stock_transfer_status_summary', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, status and a date before proceeding..');
        return;
    }

    return exportRecords(
        'export-stock-transfer-status-summary/',
        'stock-transfer-status-summary-report.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, status and a date before proceeding..');
        return;
    }

    return exportRecords(
        'export-stock-transfer-status-summary/',
        'stock-transfer-status-summary-report.csv',
        state.parameters
    );
};

const updateStores = (stores) => {
    state.stores = stores;
    state.parameters.location_ids = stores.map((store) => {
        return store.id;
    });
};

const updateWarehouses = (warehouses) => {
    state.warehouses = warehouses;
    state.parameters.location_ids = warehouses.map((warehouse) => {
        return warehouse.id;
    });
};

const selectAllLocations = () => {
    if (state.typeId === props.staticLocationTypes.store) {
        updateStores(props.stores);
        state.displayClearButton = true;
        return;
    }

    if (state.typeId === props.staticLocationTypes.warehouse) {
        updateWarehouses(props.warehouses);
        state.displayClearButton = true;
    }
};

const clearAllLocations = () => {
    state.warehouses = [];
    state.stores = [];
    state.parameters.location_ids = null;
    state.typeId = props.staticLocationTypes.store;
    state.displayClearButton = false;
};

</script>
