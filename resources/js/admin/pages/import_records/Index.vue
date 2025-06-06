<template>
    <PageTitle title="Import Records" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Import Records
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.import_records.create')">
                <PrimaryButton
                    text="Import"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayImportRecordsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.import_type"
                    :records="importTypes"
                    placeholder="Please select Import Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Import Type"
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
        :fetch-url="route('admin.import_records.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by file uploaded at, import type, status, records imported, or records failed"
    >
        <template #records_failed="data">
            <div class="my-auto">
                {{ data.item.records_failed }}

                <a
                    v-if="data.item.records_failed !== 0 && data.item.failed_records_file_url && data.item.status === staticStatuses.completed"
                    :href="data.item.failed_records_file_url"
                    class="btn btn-sm btn-primary ml-1"
                    target="_blank"
                >
                    <Download class="w-5 h-5" />
                </a>
            </div>
        </template>

        <template #uploaded_file="data">
            <div class="my-auto">
                <a
                    v-if="data.item.upload_file_url"
                    :href="data.item.upload_file_url"
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
                    @click="state.displayImportRecordsFilter = !state.displayImportRecordsFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { Download } from 'lucide-vue-next';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { currentDate, exportRecords } from '@commonServices/helper';

const props = defineProps({
    importRecordId: {
        type: Number,
        default: null,
    },

    importTypes: {
        type: Array,
        required: true,
    },

    statuses: {
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
});

const state = reactive({
    columns: [
        {
            key: 'file_uploaded_at',
        },
        {
            key: 'import_type',
        },
        {
            key: 'created_by_type',
            label: 'Created By'
        },
        {
            key: 'staff_id',
        },
        {
            key: 'module_type',
        },
        {
            key: 'status',
            sortable: true
        },
        {
            key: 'records_imported',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        },
        {
            key: 'uploaded_file',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
        {
            key: 'records_failed',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ],

    parameters: {
        import_record_id: props.importRecordId,
        status: null,
        date_range: [currentDate(), currentDate()],
        import_type: null,
    },
    refreshTableData: Math.random(),
    displayImportRecordsFilter: false,
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
    state.parameters.import_type = parseInt(typeId);
    refreshTable();
};

const clearAll = () => {
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.status = null;
    state.parameters.import_type = null;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-import-records/',
        'import-records.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-import-records/',
        'import-records.xlsx',
        params,
        props.exportPermission
    );
};

</script>
