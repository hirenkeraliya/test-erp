<template>
    <PageTitle title="Sales" />

    <div class="flex flex-col items-center mt-8 intro-y sm:flex-row">
        <h2 class="mr-auto text-lg font-medium">
            Sales
        </h2>
    </div>
    <div
        v-if="state.displaySalesFilter"
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
                <FormAjaxSelect
                    :selected-record="state.selectedMember"
                    :search-records="searchMembers"
                    input-label="Member"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Member Name to search..."
                    @update:selected-record="updateMember"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedEmployee"
                    :search-records="searchEmployees"
                    input-label="Employee"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Employee Name to search..."
                    @update:selected-record="updateEmployee"
                />
            </div>

            <div>
                <FormInput
                    :input-value="state.parameters.offline_sale_id"
                    input-label="Receipt Id"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    placeholder="Please type the receipt id."
                    @update:input-value="receiptId"
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
        :fetch-url="route('store_manager.sales.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-sales-return-reports-columns"
        search-title="Search by receipt id"
    >
        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    v-if="record.data.total_sales"
                    :label="'Sales: ' + truncateDecimal(record.data.total_sales)"
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
                    @click="state.displaySalesFilter = !state.displaySalesFilter"
                />
            </p>
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

        <template #location="data">
            {{ data.item.location.name }}
        </template>

        <template #gross_sales="data">
            {{ displayAmountWithCurrencySymbol(data.item.gross_sales) }}
        </template>

        <template #total_discount_amount="data">
            -{{ displayAmountWithCurrencySymbol(data.item.total_discount_amount) }}
        </template>

        <template #total_tax_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_tax_amount) }}
        </template>

        <template #total_amount_paid="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_amount_paid) }}
        </template>

        <template #info="record">
            <div class="flex items-center justify-center cursor-pointer">
                <Printer
                    class="mr-1"
                    @click="printSaleReport(record.item)"
                />

                <List
                    @click="showSaleDetailsModal(record.item.id)"
                />
            </div>
        </template>
    </JTable>

    <SaleDetails
        :modal-show="state.displaySaleDetailsModal"
        :sale="state.sale"
        :columns-for-payment-details="state.columnsForPaymentDetails"
        :columns-for-sale-item-details="state.columnsForSaleItemDetails"
        :columns-for-sale-discounts="state.columnsForSaleDiscounts"
        :columns-for-sale-mismatches="state.columnsForSaleMismatches"
        :columns-for-sale-cashback="state.columnsForSaleCashback"
        @close-modal="closeModal"
    />

    <Receipt
        v-if="Object.keys(state.sale).length"
        :sale="state.sale"
        :print-receipt-data="state.printReceiptData"
    />
</template>

<script setup>
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JTable from '@commonComponents/JTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import Receipt from '@commonComponents/Receipt.vue';
import { currentDateTime, displayAmountWithCurrencySymbol, exportRecords, isPrintRecords, truncateDecimal } from '@commonServices/helper';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { router, usePage } from '@inertiajs/vue3';
import SaleDetails from '@storeManagerPages/sales/SaleDetails.vue';
import axios from 'axios';
import { Info, List, Printer } from 'lucide-vue-next';
import { computed, nextTick, onMounted, reactive } from 'vue';
import { route } from 'ziggy';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    cashiers: {
        type: Array,
        required: true,
    },
    counters: {
        type: Array,
        required: true,
    },
    offlineSaleId: {
        type: String,
        default: '',
    },
    startDate: {
        type: String,
        default: '',
    },
    endDate: {
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
            isDisplay: true,
            headerClass: 'text-right',
        }, {
            key: 'total_discount_amount',
            label: 'Discount',
            bodyClass: 'text-right',
            isDisplay: true,
            headerClass: 'text-right',
        }, {
            key: 'units_sold',
            bodyClass: 'text-right',
            isDisplay: true,
            headerClass: 'text-right',
        }, {
            key: 'units_returned',
            bodyClass: 'text-right',
            isDisplay: true,
            headerClass: 'text-right',
        }, {
            key: 'total_tax_amount',
            label: 'Tax',
            bodyClass: 'text-right',
            isDisplay: true,
            headerClass: 'text-right',
        }, {
            key: 'total_amount_paid',
            label: 'Paid',
            bodyClass: 'text-right',
            isDisplay: true,
            headerClass: 'text-right',
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
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'payment_type',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'amount',
            sortable: true,
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }
    ],

    columnsForSaleItemDetails: [
        {
            key: 'upc',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
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
            key: 'quantity',
            bodyClass: 'text-right',
            sortable: true,
            headerClass: 'text-right',
        }, {
            key: 'unit_price',
            bodyClass: 'text-right',
            sortable: true,
            headerClass: 'text-right',
        }, {
            key: 'subtotal',
            bodyClass: 'text-right',
            sortable: true,
            headerClass: 'text-right',
        }, {
            key: 'total_discount_amount',
            label: 'Discount',
            bodyClass: 'text-right',
            sortable: true,
            headerClass: 'text-right',
        }, {
            key: 'total_tax_amount',
            label: 'Tax',
            bodyClass: 'text-right',
            sortable: true,
            headerClass: 'text-right',
        }, {
            key: 'total_price_paid',
            label: 'Price Paid',
            bodyClass: 'text-right',
            sortable: true,
            headerClass: 'text-right',
        }
    ],

    columnsForSaleDiscounts: [
        {
            key: 'id',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'discount_type',
            sortable: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'amount',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
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

    columnsForSaleCashback: [
        {
            key: 'id',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        {
            key: 'name',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        {
            key: 'amount',
            sortable: true,
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }
    ],

    sale: {},
    counters: null,
    cashiers: null,
    displaySaleDetailsModal: false,
    printReceiptData: Math.random(),
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displaySalesFilter: false,
    isClear: false,
    parameters: {
        location_id: null,
        counter_ids: null,
        cashier_id: null,
        member_id: null,
        employee_id: null,
        date_range: props.startDate && props.endDate ? [props.startDate, props.endDate] : currentDateTime(),
        offline_sale_id: props.offlineSaleId
    },
});

const closeModal = () => {
    state.sale = {};
    state.displaySaleDetailsModal = false;
};

const showSaleDetailsModal = (saleId) => {
    state.sale = [];
    axios.get(route('store_manager.sales.fetch_sale_items', saleId))
        .then((response) => {
            state.sale = response.data.sale_details;
        });

    state.displaySaleDetailsModal = true;
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    state.parameters.counter_ids = null;
    state.parameters.cashier_id = null;
    state.parameters.member_id = null;
    state.parameters.employee_id = null;
    state.selectedMember = null;
    state.selectedEmployee = null;
    state.counters = null;
    state.parameters.offline_sale_id = null;
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

const printSaleReport = (saleId) => {
    if (isPrintRecords(props.exportPermission)) {
        state.sale = [];
        axios.get(route('store_manager.sales.fetch_sale_items', saleId))
            .then((response) => {
                state.sale = response.data.sale_details;

                nextTick(() => {
                    state.printReceiptData = Math.random();
                });
            });
    }
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-sales/',
        'sales.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-sales/',
        'sales.xlsx',
        params,
        props.exportPermission,
        columns
    );
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

const receiptId = (offlineSaleId) => {
    state.parameters.offline_sale_id = offlineSaleId;
    refreshTable();
};

const refreshPage = () => {
    router.get(route('store_manager.sales.index'));
};

onMounted(() => {
    if (props.offlineSaleId) {
        state.isClear = true;
        state.displaySalesFilter = true;
        refreshTable();
    }
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
