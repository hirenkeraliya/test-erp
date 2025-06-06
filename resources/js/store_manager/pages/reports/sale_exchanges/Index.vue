<template>
    <PageTitle title="Exchanges" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Exchanges
        </h2>
    </div>

    <div
        v-if="state.displaySaleExchangesFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.counters"
                    :disabled="null === counters"
                    :records="counters === null ? [] : counters"
                    placeholder="Please select Counter"
                    input-label="Counter"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateCounterId"
                />
            </div>

            <div>
                <FormSelectBox
                    :disabled="null === cashiers"
                    :selected-record="state.parameters.cashier_id"
                    :records="cashiers === null ? []: cashiers"
                    placeholder="Please select Cashier"
                    input-label="Cashiers"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateCashierId"
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
        :fetch-url="route('store_manager.sale_exchanges.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-sale-exchanges-reports-columns"
        search-title="Search by receipt id"
    >
        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    v-if="record.data.total_records"
                    :label="'Exchanges: ' + truncateDecimal(record.data.total_records)"
                />
            </div>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displaySaleExchangesFilter = !state.displaySaleExchangesFilter"
                />
            </p>
        </template>
        <template #offline_sale_id="data">
            <div class="flex justify-left items-center">
                <span>
                    {{ data.item.offline_sale_id }}
                </span>
                <Tippy
                    v-if="data.item.mismatch_count > 0 "
                    :content="'There are ' + data.item.mismatch_count + ' mismatches on this sale.'"
                >
                    <Info
                        class="text-danger ml-2 cursor-pointer"
                        :size="15"
                        @click="showSaleDetailsModal(data.item)"
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

        <template #bill_reference_number="data">
            {{ data.item.bill_reference_number ? data.item.bill_reference_number : 'N/A' }}
        </template>

        <template #info="record">
            <div class="flex justify-center items-center cursor-pointer">
                <Printer
                    class="mr-1"
                    @click="printSaleReport(record.item)"
                />

                <List
                    @click="showSaleDetailsModal(record.item)"
                />
            </div>
        </template>
    </JTable>

    <SaleExchangesDetails
        :modal-show="state.displaySaleDetailsModal"
        :sale="state.sale"
        :columns-for-payment-details="state.columnsForPaymentDetails"
        :columns-for-sale-item-details="state.columnsForSaleItemDetails"
        :columns-for-sale-discounts="state.columnsForSaleDiscounts"
        :columns-for-sale-mismatches="state.columnsForSaleMismatches"
        :columns-for-sale-return-mismatches="state.columnsForSaleReturnMismatches"
        :columns-for-sale-cashback="state.columnsForSaleCashback"
        :columns-for-sale-return-item-details="state.columnsForSaleReturnItemDetails"
        @close-modal="closeModal"
    />

    <SaleExchangesReceipt
        v-if="Object.keys(state.sale).length"
        :sale="state.sale"
        :print-receipt-data="state.printReceiptData"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { displayAmountWithCurrencySymbol, exportRecords, currentDateTime, truncateDecimal, isPrintRecords } from '@commonServices/helper';
import { Info, List, Printer } from 'lucide-vue-next';
import { nextTick, reactive, computed } from 'vue';
import { route } from 'ziggy';
import SaleExchangesDetails from '@commonComponents/SaleExchangesDetails.vue';
import SaleExchangesReceipt from '@commonComponents/SaleExchangesReceipt.vue';
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
    counters: {
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
            key: 'offline_sale_id',
            label: 'Receipt Id',
            isDisplay: true,
        }, {
            key: 'bill_reference_number',
            label: '# Reference',
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
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'total_discount_amount',
            label: 'Discount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'units_sold',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'units_returned',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'total_tax_amount',
            label: 'Tax',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'total_amount_paid',
            label: 'Paid',
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

    columnsForPaymentDetails: [
        {
            key: 'payment_type',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'amount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
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

    columnsForSaleDiscounts: [
        {
            key: 'id',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'discount_type',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'amount',
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

    columnsForSaleReturnMismatches: [
        {
            key: 'message',
            label: 'Sale Return mismatch messages',
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
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }
    ],

    columnsForSaleReturnItemDetails: [
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
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
            ]
            : [
                {
                    key: 'color',
                    sortable: true,
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
                },
                {
                    key: 'size',
                    sortable: true,
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
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

    sale: {},
    counters: null,
    storeCounters: null,
    cashiers: null,
    displaySaleDetailsModal: false,
    printReceiptData: Math.random(),
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displaySaleExchangesFilter: false,
    parameters: {
        counter_ids: null,
        cashier_id: null,
        member_id: null,
        date_range: currentDateTime(),
        employee_id: null,
    },
});

const closeModal = () => {
    state.sale = {};
    state.displaySaleDetailsModal = false;
};

const showSaleDetailsModal = (sale) => {
    state.sale = sale;
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
    state.cashiers = null;
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

const printSaleReport = (sale) => {
    if (isPrintRecords(props.exportPermission)) {
        state.sale = sale;

        nextTick(() => {
            state.printReceiptData = Math.random();
        });
    }
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-sale-exchanges/',
        'sale_exchanges.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-sale-exchanges/',
        'sale_exchanges.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
