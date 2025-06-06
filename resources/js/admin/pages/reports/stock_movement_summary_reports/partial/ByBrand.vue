<template>
    <div>
        <h2 class="font-medium text-base mr-auto mt-6">
            Stock Movement Summary By Brand
        </h2>
    </div>

    <div>
        <JTable
            v-if="stockMovementSummaryReportFilterValidationCheck(parameters)"
            :fetch-url="route('admin.stock_movement_summary_reports.fetch_details')"
            :columns="state.columns"
            :refresh-table-data="state.refreshTableData"
            :additional-query-params="parameters"
            :allow-csv-export="true"
            :allow-excel-export="true"
            :export-csv-records-callback="exportCsvRecord"
            :export-excel-records-callback="exportExcelRecord"
            search-title="Search by name"
            :token-controller="tokenController"
            @update:get-cancel-controller="getAbortController"
        >
            <template #extra-header-data="record">
                <div
                    v-if="Object.keys(record.data).length > 0"
                    class="ml-0 mb-2 sm:mb-0 md:ml-2"
                >
                    <JBadge
                        :label="`GRN In: ${record.data.total.grn_in}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />

                    <JBadge
                        :label="`GRN Out: ${record.data.total.grn_out}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />

                    <JBadge
                        :label="`Adjustment In: ${record.data.total.adjustment_in}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />

                    <JBadge
                        :label="`Adjustment Out: ${record.data.total.adjustment_out}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />

                    <JBadge
                        :label="`Transfer In: ${record.data.total.transfer_in}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />

                    <JBadge
                        :label="`Transfer Out: ${record.data.total.transfer_out}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />

                    <JBadge
                        :label="`Delivery In: ${record.data.total.delivery_in}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />

                    <JBadge
                        :label="`Delivery Out: ${record.data.total.delivery_out}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />
                    <JBadge
                        :label="`Sold: ${record.data.total.sold}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                    />

                    <JBadge
                        :label="`Balance: ${record.data.total.remaining}`"
                        class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0 mt-2"
                    />
                </div>

                <PrimaryButton
                    type="button"
                    text="PDF"
                    class="mr-1 sm:mr-2 float-left sm:float-none"
                    @click="exportSellThrough"
                />
            </template>

            <template #sold="record">
                <div class="flex items-center gap-2">
                    {{ record.item.sold }}
                </div>
            </template>

            <template #balance="record">
                <div class="flex items-center gap-2">
                    {{ record.item.balance }}
                </div>
            </template>
        </JTable>
    </div>


    <StrDetailsModal
        v-if="state.displayDetailsModal"
        :show-str-details-modal="state.displayDetailsModal"
        :title="state.title"
        :parameters="state.parameters"
        :export-permission="exportPermission"
        :export-url="state.exportUrl"
        :print-url="state.printUrl"
        :export-file-name="state.exportFileName"
        :columns="state.modelColumns"
        :records="state.modelRecord"
        :is-fetching="state.is_fetching"
        :badge-totals="state.badgesTotals"
        @update:hide-str-details-modal="hideDetailsModal"
    />
</template>

<script setup>
import StrDetailsModal from '@adminPages/reports/stock_movement_summary_reports/partial/StrDetailsModal.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords, printReportForChart, stockMovementSummaryReportFilterValidationCheck } from '@commonServices/helper';
import { onMounted, onUnmounted, reactive, watch } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    parameters: {
        type: Object,
        required: true,
    },
    refreshTableData: {
        type: Number,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    tokenController: {
        type: AbortController,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true,
        },
        {
            key: 'location_name',
            label: 'Location',
            headerClass: 'text-left',
        },

        {
            key: 'goods_receive_note_in_balance',
            label: "GRN In",
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'goods_receive_note_out_balance',
            label: 'GRN Out',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'stock_adjustment_in_balance',
            label: 'Adjustment In',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'stock_adjustment_out_balance',
            label: 'Adjustment Out',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'stock_transfer_in_balance',
            label: 'Transfer In',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'stock_transfer_out_balance',
            label: 'Transfer Out',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'delivery_order_in_balance',
            label: 'Delivery In',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'delivery_order_out_balance',
            label: 'Delivery Out',
            bodyClass: "text-right",
            headerClass: "text-right",
        },
        {
            key: 'sold',
            bodyClass: 'text-center',
            sortable: true,
        },
        {
            key: 'balance',
            bodyClass: 'text-center',
            sortable: true,
        },
    ],

    balanceDetailsColumns: [
        {
            key: 'location_name',
            label: 'Location Name',
            headerClass: 'text-left',
        },
        {
            key: 'balance',
            label: 'Balance',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
    ],
    soldDetailsColumns: [
        {
            key: 'location_name',
            label: 'Location Name',
            headerClass: 'text-left',
        },
        {
            key: 'sold',
            label: 'Sold',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'foc_sold',
            label: 'Foc Sold',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'return',
            label: 'Return',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
    ],

    refreshTableData: Math.random(),
    cancelToken: props.tokenController.signal,
    cancelController: props.tokenController,
    displayDetailsModal: false,
    title: '',
    modelRecord: [],
    modelColumns: [],
    badgesTotals: [],
    is_fetching: true,
    printUrl: '',
    exportUrl: '',
    exportFileName: '',
    parameters: props.parameters,
});

watch(() => props.refreshTableData, () => {
    refreshTable();
    fetchRecords();
});

onMounted(() => {
    fetchRecords();
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const exportSellThrough = () => {
    printReportForChart(route('admin.stock_movement_summary_reports.print_details', props.parameters), props.exportPermission);
};

const exportCsvRecord = () => {
    return exportRecords(
        'export-stock-movement-report/',
        'stock-movement-by-brand.csv',
        props.parameters,
        props.exportPermission
    );
};

const exportExcelRecord = () => {
    return exportRecords(
        'export-stock-movement-report/',
        'stock-movement-by-brand.xlsx',
        props.parameters,
        props.exportPermission
    );
};

const fetchRecords = () => {
    if (!stockMovementSummaryReportFilterValidationCheck(props.parameters)) {
        return;
    }
};

const getAbortController = (cancelController) => {
    state.cancelController = cancelController;
};

onUnmounted(() => {
    state.cancelController.abort();
});

const hideDetailsModal = () => {
    state.displayDetailsModal = false;
    state.badgesTotals = [];
};
</script>
