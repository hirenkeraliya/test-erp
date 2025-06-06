<template>
    <PageTitle title="Barcodes" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Barcodes
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('store_manager.barcode_prints.create')">
                <PrimaryButton
                    text="Print Barcode"
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
                    :selected-record="state.parameters.status"
                    :records="exportRecordStatuses"
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
        :fetch-url="route('store_manager.barcode_prints.fetch_barcodes')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportPageCsvRecords"
        :export-excel-records-callback="exportPageExcelRecords"
        search-title="Search by file uploaded at, export type, status, records exported, or records failed"
    >
        <template #extra-header-data>
            <p class="text-lg font-bold mr-2 mb-2 sm:mb-0 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayImportRecordsFilter = !state.displayImportRecordsFilter"
                />
            </p>
        </template>

        <template #export_file_url="data">
            <div class="my-auto">
                <button
                    v-if="data.item.export_file_url"
                    class="btn btn-sm btn-primary"
                    @click="addEntryToExportRecordTransaction(data.item.export_file_url)"
                >
                    <Download class="w-5 h-5" />
                </button>
            </div>
        </template>

        <template #print="record">
            <div
                v-if="record.item.status === staticExportRecordStatuses.generated"
                class="text-center"
            >
                <PrimaryButton
                    type="button"
                    text="Print"
                    class="w-24"
                    @click="printBarcodeDetails(record.item.export_file_url)"
                />
            </div>

            <div v-if="record.item.status === staticExportRecordStatuses.inProgress">
                <LoaderSvg />
            </div>
        </template>
    </JTable>
</template>

<script setup>
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { route } from 'ziggy';
import { reactive } from 'vue';
import axios from 'axios';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { currentDate, exportRecords, printPdf } from '@commonServices/helper';
import JTable from '@commonComponents/JTable.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { Download } from 'lucide-vue-next';
import LoaderSvg from '@svg/LoaderSvg.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    exportRecordStatuses: {
        type: Object,
        required: true,
    },
    staticExportRecordStatuses: {
        type: Object,
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
        },
        {
            key: 'export_record_type',
        },
        {
            key: 'created_by_type',
            label: 'Created By'
        },
        {
            key: 'export_file_url',
            label: 'Export File'
        },
        {
            key: 'status',
            sortable: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        },
        {
            key: 'print',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    parameters: {
        status: null,
        date_range: [currentDate(), currentDate()],
    },
    refreshTableData: Math.random(),
    displayImportRecordsFilter: false,
    barcodeInCompleteStatusExists: true,
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

const addEntryToExportRecordTransaction = (fileName) => {
    axios.post(route('store_manager.barcode_prints.download_pdf_entry'))
        .then(() => {
            window.open(fileName);
        });
};

const exportPageCsvRecords = (params) => {
    return exportRecords(
        'export-barcode-records/',
        'barcode-records.csv',
        params,
        props.exportPermission
    );
};

const exportPageExcelRecords = (params) => {
    return exportRecords(
        'export-barcode-records/',
        'barcode-records.xlsx',
        params,
        props.exportPermission
    );
};

const printBarcodeDetails = (fileName) => {
    printPdf(fileName, props.exportPermission);
};

const clearAll = () => {
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.status = null;
    refreshTable();
};

const fetchExportRecordsPendingStatuses = () => {
    if (!state.barcodeInCompleteStatusExists) {
        return;
    }

    axios.get(route('store_manager.barcode_prints.get_pending_export_record_count'))
        .then(response => {
            const pendingCounts = response.data.pending_counts || 0;

            if (pendingCounts > 0) {
                refreshTable();
            } else if (state.barcodeInCompleteStatusExists) {
                state.barcodeInCompleteStatusExists = false;
                refreshTable();
            }
        });
};

const fetchInterval = 20000;

setInterval(fetchExportRecordsPendingStatuses, fetchInterval);

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);

</script>
