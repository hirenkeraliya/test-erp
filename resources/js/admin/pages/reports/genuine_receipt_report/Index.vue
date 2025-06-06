<template>
    <PageTitle title="Verified Receipts Report" />

    <div class="flex flex-col items-center mt-6 intro-y sm:flex-row">
        <h2 class="mr-auto text-lg font-medium">
            Verified Receipts Report
        </h2>
    </div>
    <div
        v-if="state.displayFilters"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x products-report-filters"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.selectedLocations"
                    :records="locations"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    input-label="Locations"
                    validation-field-name="locations"
                    placeholder="Please select locations"
                    @update:selected-records="selectLocations"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.is_genuine"
                    :records="state.isGenuineFilter"
                    input-label="Genuine Product"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateIsGenuine"
                />
            </div>

            <div>
                <JDateTimePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    input-label="Date Range"
                    @update:input-value="updateDate($event)"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="w-24 h-10 btn-sm"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.receipt_verification_reports.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        :allow-column-customization="true"
        search-title="Search by receipt number"
    >
        <template #receipt_number="data">
            <div class="flex items-center justify-center cursor-pointer">
                <div class="mr-1">
                    <JBadge
                        :label="data.item.receipt_number"
                        type="primary"
                        @click="showSaleReport(data.item.receipt_number)"
                    />
                </div>
            </div>
        </template>

        <template #extra-header-data>
            <p
                v-if="state.isClear"
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <OutlinePrimaryButton
                    text="Clear"
                    class="text-sm shadow-md"
                    @click="refreshPage"
                />
            </p>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayFilters = !state.displayFilters"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { exportRecords, printReport, currentDateTime } from '@commonServices/helper';
import { reactive } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormSelectBox from "@commonComponents/FormSelectBox.vue";
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import JBadge from '@commonComponents/JBadge.vue';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    filterData: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'mobile_number',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'email',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'is_genuine',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'receipt_number',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'created_at',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'remarks',
            isDisplay: true,
        },
    ],
    refreshTableData: Math.random(),
    parameters: {
        location_ids: props.filterData.locationIds,
        date_range: props.filterData.dateRange,
        is_genuine: null,
    },
    displayFilters: false,
    selectedLocations: props.filterData.selectedLocations,
    isGenuineFilter: [
        {
            id: "1",
            name: "Genuine",
        },
        {
            id: "0",
            name: "Fake",
        },
    ],
});

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateIsGenuine = (value) => {
    state.parameters.is_genuine = value;
    refreshTable();
};

const selectLocations = (selectedLocations) => {
    state.selectedLocations = selectedLocations;
    const locationIds = selectedLocations.map((location) => {
        return location.id;
    });
    state.parameters.location_ids = locationIds;
    refreshTable();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.date_range = currentDateTime();
    state.selectedLocations = null;

    refreshTable();
};

const showSaleReport = (offlineSaleId) => {
    const url = route('admin.sales.index', { offline_sale_id: offlineSaleId });
    window.open(url, '_blank');
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-receipts-verification-report/',
        'genuine_receipt_verification_report.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-receipts-verification-report/',
        'genuine_receipt_verification_report.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('admin.receipt_verification_reports.print_receipts_verification_report', params), props.exportPermission);
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
