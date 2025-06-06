<template>
    <TabGroup>
        <TabListForSellThroughReport report-title="Categories" />

        <TabPanels>
            <TabPanel class="active">
                <JTable
                    v-if="sellThroughReportFilterValidationCheck(parameters, isLocationCompulsorySelection)"
                    :fetch-url="route('admin.sell_through_aggregate_reports.fetch_details')"
                    :columns="state.columns"
                    :refresh-table-data="state.refreshTableData"
                    :allow-column-customization="true"
                    :additional-query-params="parameters"
                    :allow-csv-export="true"
                    :allow-excel-export="true"
                    :allow-pdf-export="true"
                    :export-csv-records-callback="exportCsvRecord"
                    :export-excel-records-callback="exportExcelRecord"
                    :export-pdf-records-callback="exportSellThrough"
                    :token-controller="tokenController"
                    search-title="Search by name"
                    @update:get-cancel-controller="getAbortController"
                    @get-filter-columns="getFilterColumns"
                >
                    <template #extra-header-data="record">
                        <div
                            v-if="record.data.total && Object.keys(record.data.total).length > 0"
                            class="ml-0 mb-2 sm:mb-0 md:ml-2"
                        >
                            <JBadge
                                :label="`Received: ${record.data.total.received}`"
                                class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                            />

                            <JBadge
                                :label="`Sold: ${record.data.total.sold}`"
                                class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                            />

                            <JBadge
                                :label="`Balance: ${record.data.total.remaining}`"
                                class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                            />

                            <JBadge
                                :label="`Sell Through (%): ${record.data.total.sell_through}`"
                                class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                            />
                        </div>
                    </template>

                    <template #balance="record">
                        <div class="flex items-center gap-2">
                            {{ record.item.balance }}
                            <Info
                                class="w-4 h-4"
                                @click="showBalanceDetailsModal(record.item.id)"
                            />
                        </div>
                    </template>

                    <template #sold="record">
                        <div class="flex items-center gap-2">
                            {{ record.item.sold }}
                            <Info
                                class="w-4 h-4"
                                @click="showSoldDetailsModal(record.item.id)"
                            />
                        </div>
                    </template>

                    <template #received="record">
                        <div class="flex items-center gap-2">
                            {{ record.item.received }}
                            <Info
                                class="w-4 h-4"
                                @click="showReceivedDetailsModal(record.item.id)"
                            />
                        </div>
                    </template>
                </JTable>
            </TabPanel>

            <TabPanel>
                <div class="mt-10 grid grid-cols-1 gap-4 place-items-center">
                    <VerticalBarChart
                        v-if="isNotEmpty(state.chartRecords)"
                        chart-id="by-category-bar-chart"
                        chart-title="Sell Through By Category"
                        class="w-full"
                        :data="isNotEmpty(state.chartRecords.sell_through) ? state.chartRecords.sell_through : [0]"
                        data-set-label="Sell Through By Category"
                        :labels="state.chartRecords.labels"
                        :background-color="isNotEmpty(state.chartRecords.sell_through)"
                    />
                </div>
            </TabPanel>
        </TabPanels>
    </TabGroup>

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
import TabListForSellThroughReport from '@adminPages/reports/sell_through_aggregate_reports/partial/TabListForSellThroughReport.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JTable from '@commonComponents/JTable.vue';
import VerticalBarChart from '@commonComponents/VerticalBarChart.vue';
import { exportRecords, printReportForChart, sellThroughReportFilterValidationCheck } from '@commonServices/helper';
import { TabGroup, TabPanel, TabPanels, } from '@commonVendor/tab';
import axios from 'axios';
import { onMounted, onUnmounted, reactive, watch } from 'vue';
import { route } from 'ziggy';
import StrDetailsModal from '@adminPages/reports/sell_through_aggregate_reports/partial/StrDetailsModal.vue';
import { Info } from 'lucide-vue-next';

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
    isLocationCompulsorySelection: {
        type: Boolean,
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
            key: 'date_released',
            isDisplay: false,
        },
        {
            key: 'received',
            bodyClass: 'text-center',
            sortable: true,
        },
        {
            key: 'sold',
            bodyClass: 'text-center',
            sortable: true,
        },
        {
            key: 'online_sold',
            bodyClass: 'text-center',
            sortable: true,
            isDisplay: false,
        },
        {
            key: 'net_sale_amount',
            label: 'Net Sale',
            bodyClass: 'text-center',
            sortable: true,
            isDisplay: false,
        },
        {
            key: 'online_sale_amount',
            label: 'Online Net Sale',
            bodyClass: 'text-center',
            sortable: true,
            isDisplay: false,
        },
        {
            key: 'balance',
            bodyClass: 'text-center',
            sortable: true,
        },
        {
            key: 'sell_through',
            bodyClass: 'text-center',
            label: 'Sell Through (%)',
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
    receivedDetailsColumns: [
        {
            key: 'location_name',
            label: 'Location Name',
            headerClass: 'text-left',
        },
        {
            key: 'goods_receive_note_in_balance',
            label: 'GRN In',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'goods_receive_note_out_balance',
            label: 'GRN Out',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'stock_adjustment_in_balance',
            label: 'Adjustment In',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'stock_adjustment_out_balance',
            label: 'Adjustment Out',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'stock_transfer_in_balance',
            label: 'Transfer In',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'stock_transfer_out_balance',
            label: 'Transfer Out',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'delivery_order_in_balance',
            label: 'Delivery Order In',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
        {
            key: 'delivery_order_out_balance',
            label: 'Delivery Order Out',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
    ],

    refreshTableData: Math.random(),
    chartRecords: {},
    cancelController: props.tokenController,
    cancelTokenForChart: null,
    cancelControllerForChart: new AbortController(),

    displayDetailsModal: false,
    title: '',
    modelRecord: [],
    modelColumns: [],
    is_fetching: true,
    printUrl: '',
    exportUrl: '',
    exportFileName: '',
    badgesTotals: [],
    parameters: props.parameters,
});

watch(() => props.refreshTableData, () => {
    refreshTable();
    fetchRecords();
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const exportSellThrough = () => {
    printReportForChart(route('admin.sell_through_aggregate_reports.print_details', state.parameters), props.exportPermission);
};

const exportCsvRecord = () => {
    return exportRecords(
        'export-sell-through-aggregate-report/',
        'sell-through-category.csv',
        state.parameters,
        props.exportPermission,
        state.parameters.export_columns
    );
};

const exportExcelRecord = () => {
    return exportRecords(
        'export-sell-through-aggregate-report/',
        'sell-through-category.xlsx',
        state.parameters,
        props.exportPermission,
        state.parameters.export_columns
    );
};

onMounted(() => {
    fetchRecords();
});

const fetchRecords = () => {
    if (!sellThroughReportFilterValidationCheck(props.parameters, props.isLocationCompulsorySelection)) {
        return;
    }

    if (state.cancelTokenForChart !== null) {
        state.cancelControllerForChart.abort();
        state.cancelControllerForChart = new AbortController();
    }

    state.cancelTokenForChart = state.cancelControllerForChart.signal;

    state.chartRecords = {};
    axios.get(route('admin.sell_through_aggregate_reports.fetch_records_for_chart', props.parameters), {
        signal: state.cancelToken
    })
        .then((response) => {
            state.chartRecords = response.data.records;
        }).catch((error) => {
            if (error.message === 'canceled') {
                return;
            }
        });
};

const isNotEmpty = (object) => {
    if (typeof (object) === 'object') {
        return Object.keys(object).length !== 0;
    }
};

const getAbortController = (cancelController) => {
    state.cancelController = cancelController;
};

onUnmounted(() => {
    state.cancelController.abort();
    state.cancelControllerForChart.abort();
});

const showBalanceDetailsModal = (categoryId) => {
    state.parameters.categoryId = categoryId;
    state.printUrl = 'admin.sell_through_aggregate_reports.print_balance_details_by_category';
    state.exportUrl = 'export-balance-details-by-category/';
    state.exportFileName = 'sell-through-balance-category';
    state.title = "Balance Details";
    state.modelColumns = state.balanceDetailsColumns;
    state.modelRecord = [];
    state.badgesTotals = [];
    state.is_fetching = true,
    axios.get(route('admin.sell_through_aggregate_reports.fetch_balance_details_by_category', categoryId), {
        params: props.parameters,
    })
        .then((response) => {
            state.modelRecord = response.data.data;
            state.badgesTotals = response.data.totals;
            state.is_fetching = false;
        });
    state.displayDetailsModal = true;
};

const showSoldDetailsModal = (categoryId) => {
    state.parameters.categoryId = categoryId;
    state.printUrl = 'admin.sell_through_aggregate_reports.print_sold_details_by_category';
    state.exportUrl = 'export-sold-details-by-category/';
    state.exportFileName = 'sell-through-sold-category';
    state.title = "Sold Details";
    state.modelColumns = state.soldDetailsColumns;
    state.modelRecord = [];
    state.badgesTotals = [];
    state.is_fetching = true,
    axios.get(route('admin.sell_through_aggregate_reports.fetch_sold_details_by_category', categoryId), {
        params: props.parameters,
    })
        .then((response) => {
            state.modelRecord = response.data.data;
            state.badgesTotals = response.data.totals;
            state.is_fetching = false;
        });
    state.displayDetailsModal = true;
};

const showReceivedDetailsModal = (categoryId) => {
    state.parameters.categoryId = categoryId;
    state.printUrl = 'admin.sell_through_aggregate_reports.print_received_details_by_category';
    state.exportUrl = 'export-received-details-by-category/';
    state.exportFileName = 'sell-through-received-category';
    state.title = "Received Details";
    state.modelColumns = state.receivedDetailsColumns;
    state.modelRecord = [];
    state.badgesTotals = [];
    state.is_fetching = true,
    axios.get(route('admin.sell_through_aggregate_reports.fetch_received_details_by_category', categoryId), {
        params: props.parameters,
    })
        .then((response) => {
            state.modelRecord = response.data.data;
            state.badgesTotals = response.data.totals;
            state.is_fetching = false;
        });
    state.displayDetailsModal = true;
};

const hideDetailsModal = () => {
    state.displayDetailsModal = false;
    state.badgesTotals = [];
};

const getFilterColumns = (columns) => {
    state.parameters.export_columns = columns;
};
</script>
