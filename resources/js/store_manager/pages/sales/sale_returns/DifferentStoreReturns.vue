<template>
    <PageTitle title="Different Locations Returns" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Different Locations Returns
        </h2>
    </div>

    <div
        v-if="state.displaySaleReturnFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.counters"
                    :records="counters"
                    input-label="Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    :placeholder="'Please select Counter'"
                    @update:selected-records="updateCounterId"
                />
            </div>
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.cashier_id"
                    :records="cashiers"
                    input-label="Cashiers"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Cashier"
                    @update:selected-record="updateCashierId"
                />
            </div>
            <div>
                <JMultiSelect
                    :selected-records="state.originalSaleLocations"
                    :records="locations"
                    input-label="Original Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateOriginalLocations"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.originalSaleCounters"
                    :disabled="null === state.originalSaleStoreCounters"
                    :records="state.originalSaleStoreCounters === null ? [] : state.originalSaleStoreCounters"
                    input-label="Original Sale Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    :placeholder="state.parameters.original_sale_location_ids ? 'Please select Original Counter' : 'Please select a Location First'"
                    @update:selected-records="updateOriginalSaleCounterId"
                />
            </div>

            <div>
                <FormSelectBox
                    :disabled="null === state.originalSaleCashiers"
                    :selected-record="state.parameters.cashier_id"
                    :records="state.originalSaleCashiers === null ? [] : state.originalSaleCashiers"
                    input-label="Original Cashiers"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    :placeholder="state.parameters.original_sale_location_ids ? 'Please select Original Sale Cashier' : 'Please select a Location First'"
                    @update:selected-record="updateOriginalCashierId"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedMember"
                    :search-records="searchMembers"
                    placeholder="Member Name to search..."
                    input-label="Member"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateMember"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedEmployee"
                    :search-records="searchEmployees"
                    placeholder="Employee Name to search..."
                    input-label="Employee"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateEmployee"
                />
            </div>

            <div>
                <JDateTimePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Date Range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
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
        v-model:columns="state.columns"
        :fetch-url="route('store_manager.different_store_returns.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-sales-return-with-different-stores-reports-columns"
        search-title="Search by receipt id"
    >
        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    v-if="record.data.total_return_sales"
                    :label="'Return Orders: ' + truncateDecimal(record.data.total_return_sales)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_units_returned"
                    :label="'Units Returned: ' + truncateDecimal(record.data.total_units_returned)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_return_amount"
                    :label="'Sale Returns: ' + displayAmountWithCurrencySymbol(record.data.total_return_amount)"
                />
            </div>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displaySaleReturnFilter = !state.displaySaleReturnFilter"
                />
            </p>
        </template>
        <template #offline_sale_return_id="data">
            <div class="flex justify-left items-center">
                <span>
                    {{ data.item.offline_sale_return_id }}
                </span>
                <Tippy
                    v-if="data.item.sale_mismatches"
                    :content="'There are ' + data.item.sale_mismatches + ' mismatches on this sale return.'"
                >
                    <Info
                        class="text-danger ml-2 cursor-pointer"
                        :size="15"
                        @click="showSaleReturnDetailsModal(data.item)"
                    />
                </Tippy>
            </div>
        </template>

        <template #original_store="data">
            {{ data.item.original_store.name }}
        </template>

        <template #return_store="data">
            {{ data.item.return_store.name }}
        </template>

        <template #sale_return_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.sale_return_amount) }}
        </template>

        <template #info="record">
            <div class="flex justify-center items-center cursor-pointer">
                <Printer
                    class="mr-1"
                    @click="printSaleReport(record.item.id)"
                />

                <List @click="showSaleReturnDetailsModal(record.item.id)" />
            </div>
        </template>
    </JTable>

    <SaleReturnDetails
        :modal-show="state.displaySaleReturnDetailsModal"
        :sale-return="state.saleReturn"
        :columns-for-sale-return-item-details="state.columnsForSaleReturnItemDetails"
        :columns-for-sale-mismatches="state.columnsForSaleMismatches"
        @close-modal="closeModal"
    />

    <Receipt
        v-if="Object.keys(state.saleReturn).length"
        :sale-return="state.saleReturn"
        :print-receipt-data="state.printReceiptData"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { Info, List, Printer } from 'lucide-vue-next';
import { nextTick, reactive, computed } from 'vue';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol, exportRecords, currentDateTime, truncateDecimal, isPrintRecords } from '@commonServices/helper';
import SaleReturnDetails from '@storeManagerPages/sales/sale_returns/SaleReturnDetails.vue';
import Receipt from '@commonComponents/Receipt.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    cashiers: {
        type: Array,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    counters: {
        type: Array,
        required: true,
    },
    offlineSaleReturnId: {
        type: String,
        default: '',
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
            key: 'digital_invoice_number',
            label: 'Sequence#',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'offline_sale_return_id',
            label: 'Receipt Id',
            isDisplay: true,
        }, {
            key: 'original_receipt_id',
            isDisplay: true,
        }, {
            key: 'original_store',
            isDisplay: true,
        }, {
            key: 'return_store',
            isDisplay: true,
        }, {
            key: 'sale_counter',
            isDisplay: true,
        }, {
            key: 'sale_return_counter',
            isDisplay: true,
        }, {
            key: 'sale_cashier',
            isDisplay: true,
        }, {
            key: 'sale_return_cashier',
            isDisplay: true,
        }, {
            key: 'sale_happened_at',
            label: 'Sale Date & Time',
            isDisplay: true,
        }, {
            key: 'sale_return_happened_at',
            label: 'Sale Return Date & Time',
            isDisplay: true,
        }, {
            key: 'member',
            isDisplay: true,
        }, {
            key: 'sale_return_amount',
            label: 'Sale Return',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'info',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    columnsForSaleReturnItemDetails: [
        {
            key: 'upc',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        {
            key: 'product',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, 
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                    headerClass: 'text-left',
                    bodyClass: 'text-left',
                },
            ]
            : [
                {
                    key: 'color',
                    sortable: true,
                    headerClass: 'text-left',
                    bodyClass: 'text-left',
                },
                {
                    key: 'size',
                    sortable: true,
                    headerClass: 'text-left',
                    bodyClass: 'text-left',
                },
            ]), 
        {
            key: 'put_back_in_inventory',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'sale_return_reason',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'quantity',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }, {
            key: 'unit_price',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }, {
            key: 'subtotal',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }, {
            key: 'total_discount_amount',
            label: 'Discount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }, {
            key: 'total_tax_amount',
            label: 'Tax',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }, {
            key: 'total_price_paid',
            label: 'Price Paid',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }
    ],

    columnsForSaleMismatches: [
        {
            key: 'message',
            label: 'Sale mismatch messages',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }
    ],

    saleReturn: {},
    counters: null,
    cashiers: null,
    displaySaleReturnDetailsModal: false,
    printReceiptData: Math.random(),
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displaySaleReturnFilter: false,
    originalSaleCounters: null,
    originalSaleLocations: null,
    originalSaleCashiers: null,
    originalSaleStoreCounters: null,
    parameters: {
        counter_ids: null,
        cashier_id: null,
        member_id: null,
        date_range: currentDateTime(),
        employee_id: null,
        original_sale_location_ids: null,
        original_sale_counter_ids: null,
        original_sale_cashier_id: null,
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    state.parameters.counter_ids = null;
    state.parameters.original_sale_location_ids = null;
    state.parameters.original_sale_counter_ids = null;
    state.parameters.original_sale_cashier_id = null;
    state.parameters.cashier_id = null;
    state.parameters.member_id = null;
    state.parameters.employee_id = null;
    state.selectedMember = null;
    state.selectedEmployee = null;
    state.counters = null;
    state.originalSaleCounters = null;
    state.originalSaleLocations = null;
    state.originalSaleCashiers = null;
    state.originalSaleStoreCounters = null;
    refreshTable();
};
const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};
const updateCounterId = (counters) => {
    state.counters = counters;
    const counterIds = counters.map((counter) => {
        return counter.id;
    });
    state.parameters.counter_ids = counterIds;
    refreshTable();
};
const updateMember = (selectMember) => {
    state.selectedMember = selectMember;
    if (selectMember !== null) {
        state.parameters.member_id = selectMember.id;
    }
    refreshTable();
};
const updateCashierId = (cashierId) => {
    state.parameters.cashier_id = parseInt(cashierId);
    refreshTable();
};
const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };
    axios.get(route('store_manager.members.get_filtered_members', filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

const searchEmployees = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.get(route('store_manager.employees.get_filtered_employees', filterData)).then((response) => {
        componentState.records = response.data.employees;
        componentState.isLoading = false;
    });
};

const updateEmployee = (selectEmployee) => {
    state.selectedEmployee = selectEmployee;
    if (selectEmployee !== null) {
        state.parameters.employee_id = selectEmployee.id;
    }
    refreshTable();
};

const closeModal = () => {
    state.displaySaleReturnDetailsModal = false;
};

const showSaleReturnDetailsModal = (saleReturnId) => {
    state.saleReturn = [];
    axios.get(route('store_manager.different_store_returns.fetch_sale_return_items', saleReturnId))
        .then((response) => {
            state.saleReturn = response.data.sale_return_details;
            state.displaySaleReturnDetailsModal = true;
        });
};

const printSaleReport = (saleReturnId) => {
    if (isPrintRecords(props.exportPermission)) {
        state.saleReturn = [];
        axios.get(route('store_manager.different_store_returns.fetch_sale_return_items', saleReturnId))
            .then((response) => {
                state.saleReturn = response.data.sale_return_details;
                nextTick(() => {
                    state.printReceiptData = Math.random();
                });
            });
    }
};

const exportCsvRecords = (params,columns) => {
    return exportRecords(
        'export-different-store-returns/',
        'different_locations_returns.csv',
        params,
        props.exportPermission,
        columns
    );
};
const exportExcelRecords = (params,columns) => {
    return exportRecords(
        'export-different-store-returns/',
        'different_locations_returns.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const updateOriginalCashierId = (cashierId) => {
    state.parameters.original_sale_cashier_id = parseInt(cashierId);
    refreshTable();
};

const updateOriginalLocations = (locations) => {
    state.originalSaleLocations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });

    if (locationIds.length) {
        state.parameters.original_sale_location_ids = locationIds;
        state.parameters.original_sale_counter_ids = null;
        state.parameters.original_sale_cashier_id = null;

        axios.post(route('store_manager.counters.get_specific_locations_counters', { location_ids: locationIds }))
            .then((response) => {
                state.originalSaleStoreCounters = response.data.counters;
            });

        axios.post(route('store_manager.cashiers.get_specific_stores_cashiers', { location_ids: locationIds }))
            .then((response) => {
                state.originalSaleCashiers = response.data.cashiers;
            });

        refreshTable();

        return;
    }

    clearAll();
};

const updateOriginalSaleCounterId = (counters) => {
    state.originalSaleCounters = counters;
    const counterIds = counters.map((counter) => {
        return counter.id;
    });
    state.parameters.original_sale_counter_ids = counterIds;
    refreshTable();
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
