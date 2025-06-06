<template>
    <PageTitle title="Goods Received Notes" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Goods Received Notes
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.goods_received_notes.create')">
                <PrimaryButton
                    text="Add New Goods Received Note"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayGoodReceivedNoteFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormInput
                    :input-value="state.parameters.grn_number"
                    input-label="GRN Reference"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    placeholder="Please type the grn reference number."
                    @update:input-value="grnReferenceNumber"
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
        :fetch-url="route('admin.goods_received_notes.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportListPageCsvRecords"
        :export-excel-records-callback="exportListPageExcelRecords"
        search-title="Search by id, grn ref, or po ref"
        :refresh-table-data="state.refreshTableData"
    >
        <template #vendor="record">
            {{ record.item.vendor ? record.item.vendor.name : 'N/A' }}
        </template>

        <template #info="record">
            <div v-if="record.item.upload_status !== importRecordStatus.completed && record.item.upload_status !== 'N/A'">
                <span class="flex flex-col gap-2">
                    <span>
                        Check Upload Status
                        <Link
                            :href="route('admin.import_records.index', record.item.import_record.id)"
                            class="text-blue-600 underline"
                        >
                            Here
                        </Link>
                    </span>

                    <ProgressBar
                        :percentage="completionPercentage(record.item.import_record.records_in_file, record.item.import_record.records_imported, record.item.import_record.records_failed)"
                    />
                </span>
            </div>

            <div
                v-else
                class="flex justify-center items-center"
            >
                <Tippy
                    content="Goods Received Note Products"
                    class="cursor-pointer"
                    @click="openGoodsReceivedNoteProductsModal(record.item)"
                >
                    <List />
                </Tippy>

                <tippy
                    v-if="record.item.notes"
                    :content="'Notes:' + record.item.notes"
                >
                    <Info class="text-cyan-400 ml-1" />
                </tippy>

                <span
                    class="cursor-pointer"
                    @click="printGoodsReceivedNote(record.item.id)"
                >
                    <Printer class="w-4 h-4 ml-1" />
                </span>
            </div>
        </template>

        <template #uploaded_file="record">
            <div class="flex justify-center">
                <Tippy
                    v-if="record.item.upload_file_url"
                    tag="a"
                    :content="`Records Uploaded for Stock Adjustment Items:` + record.item.import_record.records_in_file"
                    :href="record.item.upload_file_url"
                    download
                >
                    <Download />
                </Tippy>

                <span v-else>
                    N/A
                </span>
            </div>
        </template>

        <template #failed_file="record">
            <div class="flex justify-center">
                <div
                    v-if="record.item.upload_status === importRecordStatus.completed"
                    class="flex justify-center"
                >
                    <Tippy
                        v-if="record.item.import_record && record.item.failed_records_file_url && record.item.import_record.records_failed > 0"
                        tag="a"
                        :content="`Failure in uploading goods received note items. Download the list of failed items here. failed counts: ` + record.item.import_record.records_failed"
                        :href="record.item.failed_records_file_url"
                        class="mx-2 text-danger"
                        download
                    >
                        <FileX2 />
                    </Tippy>

                    <button
                        v-if="record.item.import_record && record.item.import_record.records_imported === 0 && record.item.import_record.records_failed > 0"
                        @click="showReUploadFileModal(record.item)"
                    >
                        <FileUp />
                    </button>
                </div>

                <span v-else>
                    N/A
                </span>
            </div>
        </template>

        <template #actions="record">
            <div v-if="!record.item.cancelled_at && record.item.upload_status === importRecordStatus.completed && record.item.upload_status !== 'N/A'">
                <OutlineDangerButton
                    type="button"
                    text="Cancel"
                    class="shadow-md flex flex-col sm:flex-row mt-2 sm:mt-0 md:mt-0 mr-2 w-18"
                    @click="cancelStatus(record.item.id)"
                />
            </div>

            <div v-if="record.item.cancelled_at">
                <div class="bg-red-200 text-red-800 text-md font-medium text-center me-2 px-2 py-2 rounded-full">
                    Cancelled
                </div>

                <Tippy
                    v-if="record.item.notes"
                    :content="'Cancelled remarks: ' + record.item.remarks"
                >
                    <Info class="text-cyan-400 ml-1" />
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
                    @click="state.displayGoodReceivedNoteFilter = !state.displayGoodReceivedNoteFilter"
                />
            </p>
        </template>
    </JTable>

    <SelectedProducts
        v-if="state.dynamicColumns.length > 0"
        v-model:columns="state.dynamicColumns"
        :modal-show="state.displayGoodsReceivedNoteProductsModal"
        :records="state.grnProducts"
        :title="'Goods Received Note Products (' + state.goodsReceivedNoteReference + ')'"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        @close-modal="closeModal"
    >
        <template
            v-if="pageProps.product_variant"
            #product_variant_values="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.product_variant_values"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>

        <template #quantity="record">
            {{ truncateDecimal(record.item.quantity) }}
        </template>

        <template #fob="record">
            {{ displayAmountWithCurrencySymbol(record.item.fob) }}
        </template>

        <template #freight_charges="record">
            {{ displayAmountWithCurrencySymbol(record.item.freight_charges) }}
        </template>

        <template #insurance_charges="record">
            {{ displayAmountWithCurrencySymbol(record.item.insurance_charges) }}
        </template>

        <template #duty="record">
            {{ displayAmountWithCurrencySymbol(record.item.duty) }}
        </template>

        <template #sst="record">
            {{ displayAmountWithCurrencySymbol(record.item.sst) }}
        </template>

        <template #handling_charges="record">
            {{ displayAmountWithCurrencySymbol(record.item.handling_charges) }}
        </template>

        <template #other_charges="record">
            {{ displayAmountWithCurrencySymbol(record.item.other_charges) }}
        </template>

        <template #landed_cost="record">
            {{ displayAmountWithCurrencySymbol(record.item.landed_cost) }}
        </template>
    </SelectedProducts>

    <ReUploadFailedImportRecordFile
        v-if="state.goodsReceivedNote"
        :is-display-re-upload-import-record-file-modal="state.isDisplayReUploadImportRecordFileModal"
        :modal-id="state.goodsReceivedNote.id"
        :failed-file-url="state.goodsReceivedNote.failed_records_file_url"
        fetch-pending-statuses-count-url="admin.goods_received_notes.re_upload_goods_received_note_record"
        product-matching-upc-url="admin.products.get_matching_upc_inventory_products"
        @close-modal="closeModalReUploadRecord"
    />

    <GoodsReceivedNoteCancelRemarks
        v-if="state.displayCancelRemarksModal"
        :modal-show="state.displayCancelRemarksModal"
        :goods-received-note-id="state.goodsReceivedNoteId"
        header-message="Cancel Remarks"
        route-url="admin.goods_received_notes.mark_as_cancel"
        @close-modal="closeCancelRemarksModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { Info, List, Printer, Download, FileX2, FileUp, } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import { exportRecords, displayAmountWithCurrencySymbol, truncateDecimal, printReport } from '@commonServices/helper';
import axios from 'axios';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import { router, usePage } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import ReUploadFailedImportRecordFile from '@commonComponents/ReUploadFailedImportRecordFile.vue';
import ProgressBar from '@commonComponents/ProgressBar.vue';
import { confirmDialogBox } from '@commonServices/notifier';
import GoodsReceivedNoteCancelRemarks from '@commonComponents/GoodsReceivedNoteCancelRemarks.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    grnNumber: {
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
    goodsReceivedNoteModelMappingType: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'created_at',
            label: 'Date',
            sortable: true
        }, {
            key: 'location_name',
            label: 'Location',
        }, {
            key: 'vendor',
        }, {
            key: 'grn_reference',
            label: 'GRN Ref',
            sortable: true
        }, {
            key: 'delivery_order_reference',
            label: 'DO Ref',
            sortable: true
        }, {
            key: 'purchase_order_reference',
            label: 'PO Ref',
            sortable: true
        }, {
            key: 'uploaded_file',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'failed_file',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'info',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'actions',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    grnProductsFields: [
        {
            key: 'product_name',
            sortable: true,
        }, {
            key: 'color',
        }, {
            key: 'size',
        }, {
            key: 'product_variant_values',
            label: 'Attributes',
        }, {
            key: 'quantity',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'fob',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'freight_charges',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'insurance_charges',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'duty',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'sst',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'handling_charges',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'other_charges',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',

        }, {
            key: 'landed_cost',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'expiry_date',
            sortable: true,
        }, {
            key: 'batch_number',
            sortable: true,
        }
    ],

    parameters: {
        grn_number: props.grnNumber,
    },

    grnProducts: [],
    displayGoodsReceivedNoteProductsModal: false,
    displayGoodReceivedNoteFilter: false,
    goodsReceivedNoteId: null,
    goodsReceivedNoteReference: null,
    isClear: false,
    refreshTableData: Math.random(),
    goodsReceivedNoteInCompleteStatusExists: true,
    isDisplayReUploadImportRecordFileModal: false,
    goodsReceivedNote: null,
    displayCancelRemarksModal: false,
    dynamicColumns: [],
});

const closeModalReUploadRecord = (closeWithRefresh) => {
    state.isDisplayReUploadImportRecordFileModal = false;
    if (closeWithRefresh) {
        fetchGoodsReceivedNoteImportRecordsPendingStatuses();
        refreshPage();
    }
};

const closeModal = () => {
    state.displayGoodsReceivedNoteProductsModal = false;
};

const openGoodsReceivedNoteProductsModal = (data) => {
    state.displayGoodsReceivedNoteProductsModal = true;
    state.goodsReceivedNoteReference = data.grn_reference;
    state.goodsReceivedNoteId = data.id;

    axios.get(route('admin.goods_received_notes.products', data.id))
        .then((response) => {
            state.grnProducts = response.data.data;
        });
};

const exportCsvRecords = (params) => {
    const fileName = state.goodsReceivedNoteReference.replaceAll('/', '-').toLowerCase();
    return exportRecords(
        'export-goods-received-note-products/' + state.goodsReceivedNoteId + '/',
        fileName + '.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    const fileName = state.goodsReceivedNoteReference.replaceAll('/', '-').toLowerCase();
    return exportRecords(
        'export-goods-received-note-products/' + state.goodsReceivedNoteId + '/',
        fileName + '.xlsx',
        params,
        props.exportPermission
    );
};

const exportListPageCsvRecords = (params) => {
    return exportRecords(
        'export-goods-received-note/',
        'goods-received-notes.csv',
        params,
        props.exportPermission
    );
};
const exportListPageExcelRecords = (params) => {
    return exportRecords(
        'export-goods-received-note/',
        'goods-received-notes.xlsx',
        params,
        props.exportPermission
    );
};

const printGoodsReceivedNote = (goodsReceivedNoteId) => {
    printReport(route('admin.goods_received_notes.goods_received_note_print', goodsReceivedNoteId), props.exportPermission);
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.grn_number = null;
    refreshTable();
};

const grnReferenceNumber = (grnReferenceNumber) => {
    state.parameters.grn_number = grnReferenceNumber;
    refreshTable();
};

const refreshPage = () => {
    router.get(route('admin.goods_received_notes.index'));
};

onMounted(() => {
    if (props.grnNumber) {
        state.isClear = true;
        state.displayGoodReceivedNoteFilter = true;
        refreshTable();
    }
});

const fetchGoodsReceivedNoteImportRecordsPendingStatuses = () => {
    if (!state.goodsReceivedNoteInCompleteStatusExists) {
        return;
    }

    axios.get(route('admin.import_records.get_import_record_pending_statuses', props.goodsReceivedNoteModelMappingType))
        .then(response => {
            const pendingCounts = response.data.pending_counts || 0;

            if (pendingCounts > 0) {
                refreshTable();
            } else if (state.goodsReceivedNoteInCompleteStatusExists) {
                state.goodsReceivedNoteInCompleteStatusExists = false;
                refreshTable();
            }
        });
};

const fetchInterval = 10000;

setInterval(fetchGoodsReceivedNoteImportRecordsPendingStatuses, fetchInterval);

const completionPercentage = (totals, totalImported, totalRecordsFailed) => {
    const percentageMultiplier = 100;
    const percentage = ((parseInt(totalImported) + parseInt(totalRecordsFailed)) / totals) * percentageMultiplier;

    if (isNaN(percentage)) {
        return 0;
    }

    return Math.round(percentage);
};

const showReUploadFileModal = (goodsReceivedNote) => {
    state.goodsReceivedNote = goodsReceivedNote;
    state.isDisplayReUploadImportRecordFileModal = true;
};

const closeCancelRemarksModal = () => {
    state.displayCancelRemarksModal = false;
    refreshTable();
};

const cancelStatus = (goodsReceivedNoteId) => {
    confirmDialogBox('Are you sure you want to cancel the Goods Received Note?', () => {
        state.goodsReceivedNoteId = goodsReceivedNoteId;
        state.displayCancelRemarksModal = true;
    });
};

const getFilteredColumns = () => {
    const columns = state.grnProductsFields || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['color', 'size'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'product_variant_values');
};

onMounted(() => {
    state.dynamicColumns = getFilteredColumns();
});
</script>
