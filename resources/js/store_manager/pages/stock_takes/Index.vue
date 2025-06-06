<template>
    <PageTitle title="Stock Take" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Take
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <PrimaryButton
                type="button"
                text="Add New Stock Take"
                class="shadow-md"
                @click="state.showAddNewModal = true"
            />
        </div>
    </div>

    <JTable
        :fetch-url="route('store_manager.stock_takes.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportListPageCsvRecords"
        :export-excel-records-callback="exportListPageExcelRecords"
        search-title="Search by location, requested store manager, or submitted store manager"
        :refresh-table-data="state.refreshTableData"
    >
        <template #submitted_at="data">
            {{ data.item.submitted_at }}<br>
            <p>Compare Stock Date: {{ data.item.compare_stock_date }}</p>
        </template>
        <template #action="record">
            <div v-if="record.item.upload_status !== statuses.completed && record.item.upload_status !== 'N/A'">
                <span class="flex flex-col gap-2">
                    <span>
                        Check Upload Status
                        <Link
                            :href="route('store_manager.import_records.index', record.item.import_record_id)"
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
            <div v-else>
                <div
                    v-if="!record.item.is_uploaded_products"
                    class="flex justify-center items-center"
                >
                    <LoaderSvg
                        label="We are currently preparing a stock take. Once it is complete, you may proceed with updating the stocks."
                    />
                </div>
                <div
                    v-else-if="record.item.submitted_at === 'N/A'"
                    class="flex justify-center items-center"
                >
                    <Link
                        class="flex items-center mr-3"
                        :href="route('store_manager.stock_takes.stock_take_products', record.item.id)"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" />
                        Update Stock
                    </Link>
                </div>

                <ExportDropDown
                    v-else
                    class="mr-3"
                    :allow-csv-export="true"
                    :allow-excel-export="true"
                    @update:export-csv-file="exportCsvRecord(record.item.id)"
                    @update:export-excel-file="exportExcelRecord(record.item.id)"
                />
            </div>
        </template>
    </JTable>

    <Modal
        size="modal-lg"
        :show="state.showAddNewModal"
        @hidden="state.showAddNewModal = false"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Add New Stock Take
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="state.showAddNewModal = false"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <form
                @submit.prevent="saveStockTake();"
            >
                <div class="grid grid-cols-12 gap-0 sm:gap-6">
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                        <JDatePicker
                            v-model:input-value="state.stockTakeForm.stock_record_date"
                            input-label="Stock Record Date"
                            :required="true"
                        />
                    </div>
                    <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
                        <FormTextarea
                            v-model:input-value="state.stockTakeForm.notes"
                            input-name="notes"
                            input-label="Notes"
                        />
                    </div>
                </div>

                <div class="mt-5">
                    <PrimaryButton
                        type="submit"
                        text="Submit"
                        class="w-24"
                    />
                </div>
            </form>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import ExportDropDown from '@commonComponents/ExportDropDown.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, X } from 'lucide-vue-next';
import { exportRecords } from '@commonServices/helper';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import LoaderSvg from '@svg/LoaderSvg.vue';
import ProgressBar from '@commonComponents/ProgressBar.vue';

const props = defineProps({
    statuses: {
        type: Object,
        required: true,
    },
    stockTakeModelMappingType: {
        type: String,
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
            key: 'stock_record_date',
            label: 'Date',
        }, {
            key: 'requested_store_manager',
            label: 'Requested By',
        }, {
            key: 'location',
        }, {
            key: 'submitted_store_manager',
            label: 'Submitted By',
        }, {
            key: 'submitted_at',
            sortable: true
        }, {
            key: 'compare_stock_date',
            label: 'Comparison Date',

        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    showAddNewModal: false,
    stockTakeForm: {
        stock_record_date: null,
        notes: null,
    },
    refreshTableData: Math.random(),
    stockTakeInCompleteStatusExists: true,
});

const saveStockTake = () => {
    state.showAddNewModal = false;
    axios.post(route('store_manager.stock_takes.add_stock_take'), state.stockTakeForm)
        .then((response) => {
            showSuccessNotification('The process of adding stock products will be happening in the background. We will show it soon.');
            router.get(route('store_manager.stock_takes.index', response.data.stock_take_id));
        }).catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const exportCsvRecord = (stockTakeId, params) => {
    return exportRecords(
        'export-stock-take-products/' + stockTakeId + '/',
        'stock-take-products-' + stockTakeId + '.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecord = (stockTakeId, params) => {
    return exportRecords(
        'export-stock-take-products/' + stockTakeId + '/',
        'stock-take-products-' + stockTakeId + '.xlsx',
        params,
        props.exportPermission
    );
};

const exportListPageCsvRecords = (params) => {
    return exportRecords(
        'export-stock-takes/',
        'stock-takes.csv',
        params,
        props.exportPermission
    );
};

const exportListPageExcelRecords = (params) => {
    return exportRecords(
        'export-stock-takes/',
        'stock-takes.xlsx',
        params,
        props.exportPermission
    );
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const fetchStockTakeImportRecordsPendingStatuses = () => {
    if (!state.stockTakeInCompleteStatusExists) {
        return;
    }

    axios.get(route('store_manager.import_records.get_import_record_pending_statuses', props.stockTakeModelMappingType))
        .then(response => {
            const pendingCounts = response.data.pending_counts || 0;

            if (pendingCounts > 0) {
                refreshTable();
            } else if (state.stockTakeInCompleteStatusExists) {
                state.stockTakeInCompleteStatusExists = false;
                refreshTable();
            }
        });
};

const fetchInterval = 10000;

setInterval(fetchStockTakeImportRecordsPendingStatuses, fetchInterval);

const completionPercentage = (totals, totalImported, totalRecordsFailed) => {
    const percentageMultiplier = 100;
    const percentage = ((parseInt(totalImported) + parseInt(totalRecordsFailed)) / totals) * percentageMultiplier;

    if (isNaN(percentage)) {
        return 0;
    }

    return Math.round(percentage);
};
</script>
