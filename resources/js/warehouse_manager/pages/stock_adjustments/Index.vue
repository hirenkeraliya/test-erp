<template>
    <PageTitle title="Stock Adjustments" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Adjustments
        </h2>
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
        :fetch-url="route('warehouse_manager.stock_adjustments.fetch')"
        :columns="state.columns"
        search-title="Search by date, reason, approved by, or type"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportListPageCsvRecords"
        :export-excel-records-callback="exportListPageExcelRecords"
        :refresh-table-data="state.refreshTableData"
    >
        <template #items="data">
            <div class="flex justify-center items-center">
                <Tippy
                    content="Stock Adjustment Items"
                    class="cursor-pointer"
                    @click="openStockAdjustmentItemsModal(data.item.id)"
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
        :modal-show="state.displayStockAdjustmentItemsModal"
        :columns="state.stockAdjustmentItemsField"
        :records="state.stockAdjustmentItems"
        title="Stock Adjustment Items"
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
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { List } from 'lucide-vue-next';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import { exportRecords, truncateDecimal } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import FormInput from '@commonComponents/FormInput.vue';

const props = defineProps({
    stockAdjustmentId: {
        type: String,
        default: '',
    },
    exportPermission: {
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
            key: 'items',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],

    stockAdjustmentItemsField: [
        {
            key: 'product_name',
            sortable: true
        }, {
            key: 'product_upc',
            sortable: true
        }, {
            key: 'quantity',
            sortable: true,
            bodyClass: 'text-center'
        }, {
            key: 'location',
            sortable: true
        },
    ],

    parameters: {
        stock_adjustment_id: props.stockAdjustmentId,
    },

    stockAdjustmentItems: [],
    displayStockAdjustmentItemsModal: false,
    stockAdjustmentId: null,
    isClear: false,
    displayStockAdjustmentFilter: false,
    refreshTableData: Math.random(),
});

const openStockAdjustmentItemsModal = (stockAdjustmentId) => {
    state.displayStockAdjustmentItemsModal = true;
    state.stockAdjustmentId = stockAdjustmentId;

    axios.get(route('warehouse_manager.stock_adjustments.fetch_items', stockAdjustmentId))
        .then((response) => {
            state.stockAdjustmentItems = response.data.data;
        });
};

const closeModal = () => {
    state.displayStockAdjustmentItemsModal = false;
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
    router.get(route('warehouse_manager.stock_adjustments.index'));
};

onMounted(() => {
    if (props.stockAdjustmentId) {
        state.isClear = true;
        state.displayStockAdjustmentFilter = true;
        refreshTable();
    }
});
</script>
