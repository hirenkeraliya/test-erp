<template>
    <PageTitle title="Stock Adjustments" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Adjustments
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.stock_adjustments.create')">
                <PrimaryButton
                    text="New Stock Adjustment"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayStockAdjustmentFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormInput
                    :input-value="state.parameters.stock_adjustment_id"
                    input-label="Stock Adjustment Number"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    placeholder="Please type the stock adjustment number."
                    @update:input-value="selectStockAdjustmentId"
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
        :fetch-url="route('admin.stock_adjustments.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportListPageCsvRecords"
        :export-excel-records-callback="exportListPageExcelRecords"
        search-title="Search by date, reason, approved by, or type"
        :refresh-table-data="state.refreshTableData"
    >
        <template #uploaded_file="record">
            <div class="flex justify-center">
                <Tippy
                    v-if="record.item.upload_file_url"
                    tag="a"
                    :content="`Records Uploaded for Stock Adjustment Items:` + record.item.total_records"
                    :href="record.item.upload_file_url"
                    download
                >
                    <Download />
                </Tippy>
            </div>
        </template>

        <template #actions="record">
            <div
                v-if="record.item.upload_status !== importRecordStatus.in_progress"
                class="flex justify-center"
            >
                <Tippy
                    v-if="record.item.failed_records_file_url && record.item.total_records_failed > 0"
                    tag="a"
                    :content="`Failure in uploading stock adjustment items.Download the list of failed items here. Failed counts: ` + record.item.total_records_failed"
                    :href="record.item.failed_records_file_url"
                    class="mx-2 text-danger"
                    download
                >
                    <FileX2 />
                </Tippy>

                <button
                    v-if="record.item.total_records_imported === 0 && record.item.total_records_failed > 0"
                    @click="showReUploadFileModal(record.item)"
                >
                    <FileUp />
                </button>
            </div>
        </template>

        <template #items="record">
            <div v-if="record.item.upload_status !== importRecordStatus.completed && record.item.upload_status !== 'N/A'">
                <span class="flex flex-col gap-2 text-center">
                    <span>
                        Check Upload Status
                        <Link
                            :href="route('admin.import_records.index', record.item.import_record_id)"
                            class="text-blue-600 underline"
                        >
                            Here
                        </Link>
                    </span>

                    <ProgressBar
                        :percentage="completionPercentage(record.item.total_records, record.item.total_records_imported, record.item.total_records_failed)"
                    />
                </span>
            </div>

            <div
                v-else
                class="flex justify-center items-center"
            >
                <Tippy
                    content="Stock Adjustment Items"
                    class="cursor-pointer"
                    @click="openStockAdjustmentItemModal(record.item.id)"
                >
                    <List />
                </Tippy>
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
                    @click="state.displayStockAdjustmentFilter = !state.displayStockAdjustmentFilter"
                />
            </p>
        </template>
    </JTable>

    <SelectedProducts
        :modal-show="state.displayStockAdjustmentItemModal"
        :columns="state.stockAdjustmentItemFields"
        :records="state.stockAdjustmentItem"
        :title="'Stock Adjustment Items'"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        @close-modal="closeModal"
    >
        <template #quantity="record">
            {{ truncateDecimal(record.item.quantity) }}
        </template>
    </SelectedProducts>

    <ReUploadFailedImportRecordFile
        v-if="state.stockAdjustment"
        :is-display-re-upload-import-record-file-modal="state.isDisplayReUploadImportRecordFileModal"
        :modal-id="state.stockAdjustment.id"
        :failed-file-url="state.stockAdjustment.failed_records_file_url"
        product-matching-upc-url="admin.products.get_matching_upc_inventory_products"
        fetch-pending-statuses-count-url="admin.stock_adjustments.re_upload_failed_record"
        @close-modal="closeModalReUploadRecord"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { List, Download, FileUp, FileX2 } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import { exportRecords, truncateDecimal } from '@commonServices/helper';
import ReUploadFailedImportRecordFile from '@commonComponents/ReUploadFailedImportRecordFile.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { router } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import axios from 'axios';
import ProgressBar from '@commonComponents/ProgressBar.vue';

const props = defineProps({
    stockAdjustmentId: {
        type: String,
        default: '',
    },
    exportPermission: {
        type: String,
        required: true,
    },
    importRecordStatus: {
        type: Object,
        required: true,
    },
    stockAdjustmentModelMappingType: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'id',
            label: 'Ref #',
            sortable: true
        }, {
            key: 'adjustment_date',
            label: 'Date',
            sortable: true
        }, {
            key: 'reason',
            sortable: true
        }, {
            key: 'approved_by',
        }, {
            key: 'type',
        }, {
            key: 'uploaded_file',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'items',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'actions',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    stockAdjustmentItemFields: [
        {
            key: 'product_name',
            sortable: true,
        }, {
            key: 'product_upc',
            sortable: true,
        }, {
            key: 'quantity',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'location',
            bodyClass: 'text-left',
            sortable: true,
        }
    ],

    parameters: {
        stock_adjustment_id: props.stockAdjustmentId,
    },

    stockAdjustmentItem: [],
    displayStockAdjustmentItemModal: false,
    displayStockAdjustmentFilter: false,
    stockAdjustmentId: null,
    stockAdjustment: null,
    isClear: false,
    stockAdjustmentImportRecordStatus: null,
    refreshTableData: Math.random(),
    isDisplayReUploadImportRecordFileModal: false,
    stockAdjustmentInCompleteStatusExists: true,
});

const closeModal = () => {
    state.displayStockAdjustmentItemModal = false;
};

const closeModalReUploadRecord = (closeWithRefresh) => {
    state.isDisplayReUploadImportRecordFileModal = false;

    if (closeWithRefresh) {
        refreshPage();
    }
};

const openStockAdjustmentItemModal = (stockAdjustmentId) => {
    state.displayStockAdjustmentItemModal = true;
    state.stockAdjustmentId = stockAdjustmentId;

    axios.get(route('admin.stock_adjustments.items', stockAdjustmentId))
        .then((response) => {
            state.stockAdjustmentItem = response.data.data;
        });
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-stock-adjustment-items/' + state.stockAdjustmentId + '/',
        'stock-adjustment-items.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-stock-adjustment-items/' + state.stockAdjustmentId + '/',
        'stock-adjustment-items.xlsx',
        params,
        props.exportPermission
    );
};

const exportListPageCsvRecords = (params) => {
    return exportRecords(
        'export-stock-adjustment/',
        'stock-adjustments.csv',
        params,
        props.exportPermission
    );
};

const exportListPageExcelRecords = (params) => {
    return exportRecords(
        'export-stock-adjustment/',
        'stock-adjustments.xlsx',
        params,
        props.exportPermission
    );
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.stock_adjustment_id = null;
    refreshTable();
};

const selectStockAdjustmentId = (stockAdjustment) => {
    state.parameters.stock_adjustment_id = stockAdjustment;
    refreshTable();
};

const refreshPage = () => {
    router.get(route('admin.stock_adjustments.index'));
};

onMounted(() => {
    if (props.stockAdjustmentId) {
        state.isClear = true;
        state.displayStockAdjustmentFilter = true;
        refreshTable();
    }
});

const fetchStockAdjustmentImportRecordsPendingStatuses = () => {
    if (!state.stockAdjustmentInCompleteStatusExists) {
        return;
    }

    axios.get(route('admin.import_records.get_import_record_pending_statuses', props.stockAdjustmentModelMappingType))
        .then(response => {
            const pendingCounts = response.data.pending_counts || 0;

            if (pendingCounts > 0) {
                refreshTable();
            } else if (state.stockAdjustmentInCompleteStatusExists) {
                state.stockAdjustmentInCompleteStatusExists = false;
                refreshTable();
            }
        });
};

const fetchInterval = 10000;

setInterval(fetchStockAdjustmentImportRecordsPendingStatuses, fetchInterval);

const completionPercentage = (totals, totalImported, totalRecordsFailed) => {
    const percentageMultiplier = 100;
    const percentage = ((parseInt(totalImported) + parseInt(totalRecordsFailed)) / totals) * percentageMultiplier;

    if (isNaN(percentage)) {
        return 0;
    }

    return Math.round(percentage);
};

const showReUploadFileModal = (stockAdjustment) => {
    state.stockAdjustment = stockAdjustment;
    state.isDisplayReUploadImportRecordFileModal = true;
};
</script>
