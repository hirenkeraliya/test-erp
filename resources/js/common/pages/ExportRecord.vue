<template>
    <PageTitle title="Export Records" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Export Records
        </h2>
    </div>

    <div
        v-if="state.displayExportRecordsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.export_type"
                    :records="exportTypes"
                    placeholder="Please select Export Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Export Type"
                    @update:selected-record="updateTypeId"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="statuses"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    @update:selected-record="updateSelectedStatus($event)"
                />
            </div>
            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Date Range"
                    @update:input-value="updateDate($event)"
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
        :fetch-url="route(exportRecordFetchUrl)"
        :columns="columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by file uploaded at, export type, status, records or exported records"
    >
        <template #exported_file="data">
            <div class="my-auto">
                <a
                    v-if="data.item.export_file_url"
                    :href="data.item.export_file_url"
                    class="btn btn-sm btn-primary"
                    target="_blank"
                >
                    <Download class="w-5 h-5" />
                </a>
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="mt-0 sm:mt-1 text-sm shadow-md"
                    @click="state.displayExportRecordsFilter = !state.displayExportRecordsFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { Download } from 'lucide-vue-next';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { currentDate, exportRecords } from '@commonServices/helper';

const props = defineProps({
    exportRecordFetchUrl: {
        type: String,
        default: null,
    },
    exportRecordExportUrl: {
        type: String,
        default: null,
    },
    exportRecordId: {
        type: Number,
        default: null,
    },
    exportTypes: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    columns: {
        type: Object,
        required: true,
    },
    staticStatuses: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    exportFilterData: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    parameters: {
        import_record_id: props.exportRecordId,
        status: null,
        date_range: [currentDate(), currentDate()],
        export_type: props.exportFilterData ?  props.exportFilterData : null,
    },
    refreshTableData: Math.random(),
    displayExportRecordsFilter: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateSelectedStatus = (statuses) => {
    state.parameters.status = statuses;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateTypeId = (typeId) => {
    state.parameters.export_type = parseInt(typeId);
    refreshTable();
};

const clearAll = () => {
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.status = null;
    state.parameters.export_type = null;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        props.exportRecordExportUrl,
        'export-records.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        props.exportRecordExportUrl,
        'export-records.xlsx',
        params,
        props.exportPermission
    );
};
</script>
