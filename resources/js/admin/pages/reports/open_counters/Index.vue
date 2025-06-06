<template>
    <div v-if="!state.displayOpenCounterSaleDetails">
        <PageTitle title="Open Counters Report" />

        <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
            <h2 class="text-lg font-medium mr-auto">
                Open Counters Report
            </h2>
        </div>

        <div
            v-if="state.displayOpenCounterFilter"
            class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
                <div>
                    <JMultiSelect
                        :selected-records="state.locations"
                        :records="locations"
                        placeholder="Please select location"
                        input-label="Locations"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-records="updateLocations"
                    />
                </div>

                <div>
                    <JMultiSelect
                        :selected-records="state.counters"
                        :records="state.storeCounters === null ? [] : state.storeCounters"
                        :placeholder="state.parameters.location_ids ? 'Please select Counter' : 'Please select a Location First'"
                        :disabled="null === state.storeCounters"
                        input-label="Counters"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-records="updateCounterId"
                    />
                </div>

                <div>
                    <FormSelectBox
                        :disabled="null === state.cashiers"
                        :selected-record="state.parameters.cashier_id"
                        :records="state.cashiers === null ? []: state.cashiers"
                        :placeholder="state.parameters.location_ids ? 'Please select Cashier' : 'Please select a Location First'"
                        input-label="Cashier"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-record="updateCashierId"
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
            :fetch-url="route('admin.open_counter_reports.fetch')"
            :refresh-table-data="state.refreshTableData"
            :additional-query-params="state.parameters"
            :allow-csv-export="true"
            :allow-excel-export="true"
            :export-csv-records-callback="exportCsvRecords"
            :export-excel-records-callback="exportExcelRecords"
            search-title="Search by item "
        >
            <template #extra-header-data>
                <p class="text-lg font-bold mr-2">
                    <OutlinePrimaryButton
                        text="Filters"
                        class="text-sm shadow-md"
                        @click="state.displayOpenCounterFilter = !state.displayOpenCounterFilter"
                    />
                </p>
            </template>

            <template #action="data">
                <div class="flex justify-center items-center">
                    <OutlinePrimaryButton
                        text="Details"
                        class="text-sm shadow-md"
                        @click="openDiv(data.item.id)"
                    />
                </div>
            </template>
        </JTable>
    </div>

    <div v-else>
        <PageTitle title="Open Counter Sales" />

        <div class="flex flex-col items-center mt-8 intro-y sm:flex-row">
            <h2 class="mr-auto text-lg font-medium">
                Open Counter Sales
            </h2>
        </div>

        <JTable
            v-model:columns="state.saleDetailColumns"
            :fetch-url="route('admin.open_counter_reports.sales_fetch', state.counterUpdateId)"
            :refresh-table-data="state.refreshTableData"
            :additional-query-params="state.parameters"
            search-title="Search by receipt id"
        >
            <template #extra-header-data="record">
                <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                    <JBadge
                        v-if="record.data.total_sales"
                        :label="'Transactions: ' + truncateDecimal(record.data.total_sales)"
                        class="mb-1 lg:mb-1 xl:mb-0"
                    />

                    <JBadge
                        v-if="record.data.total_units_sold"
                        :label="'Units Sold: ' + truncateDecimal(record.data.total_units_sold)"
                        class="mb-1 lg:mb-1 xl:mb-0"
                    />

                    <JBadge
                        v-if="record.data.total_sales_amount"
                        :label="'Sales: ' + displayAmountWithCurrencySymbol(record.data.total_sales_amount)"
                    />
                </div>

                <OutlinePrimaryButton
                    text="Back"
                    class="shadow-md text-sm mx-1"
                    @click="backToOpenCounterReport"
                />
            </template>
            <template #offline_sale_id="data">
                <div class="flex items-center justify-left">
                    <span>
                        {{ data.item.offline_sale_id }}
                    </span>
                    <Tippy
                        v-if="data.item.sale_mismatches"
                        :content="'There are ' + data.item.sale_mismatches + ' mismatches on this sale.'"
                    >
                        <Info
                            class="ml-2 cursor-pointer text-danger"
                            :size="15"
                            @click="showSaleDetailsModal(data.item.id)"
                        />
                    </Tippy>
                </div>
            </template>
            <template #info="record">
                <div class="flex items-center justify-center cursor-pointer">
                    <List
                        @click="showSaleDetailsModal(record.item.id)"
                    />
                </div>
            </template>
        </JTable>
        <SaleDetails
            v-if="Object.keys(state.sale).length > 0"
            :modal-show="state.displaySaleDetailsModal"
            :sale="state.sale"
            :columns-for-payment-details="state.columnsForPaymentDetails"
            :columns-for-sale-item-details="state.columnsForSaleItemDetails"
            :columns-for-sale-discounts="state.columnsForSaleDiscounts"
            :columns-for-sale-mismatches="state.columnsForSaleMismatches"
            :columns-for-sale-cashback="state.columnsForSaleCashback"
            @close-modal="closeModal"
        />
    </div>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive, computed } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';
import { displayAmountWithCurrencySymbol, truncateDecimal, exportRecords } from '@commonServices/helper';
import JBadge from '@commonComponents/JBadge.vue';
import { List, Info } from 'lucide-vue-next';
import SaleDetails from '@adminPages/sales/SaleDetails.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { usePage } from "@inertiajs/vue3";

const pageProps = computed(() => usePage().props);

const props = defineProps({
    locations: {
        type: Array,
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
            key: 'location',
            sortable: true,
        }, {
            key: 'cashier_name',
            label: 'Cashier',
            sortable: true,
        }, {
            key: 'counter_name',
            label: 'Counter',
            sortable: true,
        }, {
            key: 'opening_balance',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    saleDetailColumns: [
        {
            key: 'offline_sale_id',
            label: 'Receipt Id',
            isDisplay: true,
        }, {
            key: 'location',
            isDisplay: true,
        }, {
            key: 'counter',
            isDisplay: true,
        }, {
            key: 'cashier',
            isDisplay: true,
        }, {
            key: 'happened_at',
            label: 'Date & Time',
            isDisplay: true,
        }, {
            key: 'member',
            isDisplay: true,
        }, {
            key: 'gross_sales',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'total_discount_amount',
            bodyClass: 'text-right',
            label: 'Discount',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'units_sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'units_returned',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'total_tax_amount',
            bodyClass: 'text-right',
            label: 'Tax',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'total_amount_paid',
            bodyClass: 'text-right',
            label: 'Paid',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'status',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'info',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    columnsForPaymentDetails: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'payment_type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }
    ],

    columnsForSaleItemDetails: [
        {
            key: 'upc',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'product',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'attributes',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                    isDisplay: true,
                },
            ]
            : [
                {
                    key: 'color',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
                {
                    key: 'size',
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
            ]),
        {
            key: 'quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'unit_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'subtotal',
            bodyClass: 'text-right',
            label: 'Gross Sales',
            headerClass: 'text-right',
        }, {
            key: 'total_discount_amount',
            bodyClass: 'text-right',
            label: 'Discount',
            headerClass: 'text-right',
        }, {
            key: 'total_tax_amount',
            bodyClass: 'text-right',
            label: 'Tax',
            headerClass: 'text-right',
        }, {
            key: 'total_price_paid',
            bodyClass: 'text-right',
            label: 'Net Sales',
            headerClass: 'text-right',
        }
    ],

    columnsForSaleDiscounts: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'discount_type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }
    ],

    columnsForSaleMismatches: [
        {
            key: 'message',
            label: 'Sale mismatch messages',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }
    ],

    columnsForSaleCashback: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
        {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
        {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }
    ],
    displaySaleDetailsModal: false,
    sale: [],
    refreshTableData: Math.random(),
    displayOpenCounterFilter: false,
    displayOpenCounterSaleDetails: false,
    counters: null,
    storeCounters: null,
    cashiers: null,
    locations: null,
    counterUpdateId: null,

    parameters: {
        location_ids: null,
        cashier_id: null,
        counter_ids: null,
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.cashier_id = null;
    state.parameters.counter_ids = null;
    state.counters = null;
    state.locations = null;
    state.storeCounters = null;
    state.cashiers = null;
    refreshTable();
};

const updateLocations = (locations) => {
    state.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });

    if (locationIds.length) {
        state.parameters.location_ids = locationIds;
        state.parameters.counter_ids = null;
        state.parameters.cashier_id = null;

        axios.post(route('admin.counters.get_counters_of_locations', { locations_ids: locationIds }))
            .then((response) => {
                state.storeCounters = response.data.counters;
            });

        axios.post(route('admin.cashiers.get_cashiers_of_stores', { location_ids: locationIds }))
            .then((response) => {
                state.cashiers = response.data.cashiers;
            });

        refreshTable();

        return;
    }

    clearAll();
};

const updateCounterId = (counters) => {
    state.counters = counters;

    const counterIds = counters.map((counter) => {
        return counter.id;
    });
    state.parameters.counter_ids = counterIds;
    refreshTable();
};

const updateCashierId = (cashierId) => {
    state.parameters.cashier_id = null;
    if (cashierId !== null) {
        state.parameters.cashier_id = parseInt(cashierId);
    }
    refreshTable();
};

const openDiv = (counterUpdateId) => {
    state.counterUpdateId = counterUpdateId;
    state.displayOpenCounterSaleDetails = true;
};

const backToOpenCounterReport = () => {
    state.displayOpenCounterSaleDetails = false;
};

const closeModal = () => {
    state.sale = {};
    state.displaySaleDetailsModal = false;
};

const showSaleDetailsModal = (saleId) => {
    axios.get(route('admin.sales.fetch_sale_items', saleId))
        .then((response) => {
            state.sale = response.data.sale_details;
            state.displaySaleDetailsModal = true;
        });
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-open-counter/',
        'openCounters.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-open-counter/',
        'openCounters.xlsx',
        params,
        props.exportPermission
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
