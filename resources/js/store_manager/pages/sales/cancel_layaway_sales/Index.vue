<template>
    <PageTitle title=" Cancel Layaway Sales" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cancel Layaway Sales
        </h2>
    </div>

    <div
        v-if="state.displayCancelLayawaySalesFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.counters"
                    :records="counters"
                    :placeholder="'Please select Counter'"
                    input-label="Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateCounterId"
                />
            </div>
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.cashier_id"
                    :records="cashiers"
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
        :fetch-url="route('store_manager.cancel_layaway_sales.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-cancel-layaway-sales-reports-columns"
        search-title="Search by receipt id"
    >
        <template #offline_sale_id="data">
            <div class="flex justify-left items-center">
                <span>
                    {{ data.item.offline_sale_id }}
                </span>
                <Tippy
                    v-if="data.item.sale_mismatches > 0"
                    :content="'There are ' + data.item.sale_mismatches + ' mismatches on this layaway sale.'"
                >
                    <Info
                        class="text-danger ml-2 cursor-pointer"
                        :size="15"
                        @click="showSaleDetailsModal(data.item.id)"
                    />
                </Tippy>
            </div>
        </template>

        <template #gross_sales="data">
            {{ displayAmountWithCurrencySymbol(data.item.gross_sales) }}
        </template>

        <template #total_amount_paid="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_amount_paid) }}
        </template>

        <template #layaway_pending_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.layaway_pending_amount) }}
        </template>

        <template #info="record">
            <div class="flex justify-center items-center cursor-pointer">
                <div class="mr-1">
                    <List
                        @click="showSaleDetailsModal(record.item.id)"
                    />
                </div>

                <div>
                    <Printer
                        @click="printCancelLayawaySale(record.item.id)"
                    />
                </div>
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayCancelLayawaySalesFilter = !state.displayCancelLayawaySalesFilter"
                />
            </p>
        </template>
    </JTable>

    <CancelLayawaySaleDetails
        :modal-show="state.displayCancelLayawaySaleDetailsModal"
        :cancel-layaway-sale="state.cancelLayawaySale"
        :columns-for-cancel-layaway-sale-payment-details="state.columnsForCancelLayawaySalePaymentDetails"
        :columns-for-cancel-layaway-sale-item-details="state.columnsForCancelLayawaySaleItemDetails"
        :columns-for-cancel-layaway-sale-discounts="state.columnsForCancelLayawaySaleDiscounts"
        :columns-for-cancel-layaway-sale-mismatches="state.columnsForCancelLayawaySaleMismatches"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { displayAmountWithCurrencySymbol, exportRecords, currentDateTime, printReport } from '@commonServices/helper';
import { Info, List, Printer } from 'lucide-vue-next';
import { onMounted, reactive, computed } from 'vue';
import { route } from 'ziggy';
import CancelLayawaySaleDetails from '@storeManagerPages/sales/cancel_layaway_sales/CancelLayawaySaleDetails.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    counters: {
        type: Array,
        required: true,
    },
    cashiers: {
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
    offlineSaleId: {
        type: String,
        default: '',
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
            sortable: true,
            label: 'Receipt Id',
            isDisplay: true,
        }, {
            key: 'bill_reference_number',
            label: '# Reference',
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
            key: 'authorizer',
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
            isDisplay: true,
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'total_amount_paid',
            label: 'Paid',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'layaway_pending_amount',
            label: 'Due',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'notes',
            label: 'Remarks',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            isDisplay: true,
        }, {
            key: 'info',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    columnsForCancelLayawaySalePaymentDetails: [
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
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }
    ],

    columnsForCancelLayawaySaleItemDetails: [
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

    columnsForCancelLayawaySaleDiscounts: [
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

    columnsForCancelLayawaySaleMismatches: [
        {
            key: 'message',
            label: 'Sale mismatch messages',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }
    ],

    cancelLayawaySale: {},
    counters: null,
    cashiers: null,
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displayCancelLayawaySalesFilter: false,
    parameters: {
        counter_ids: null,
        cashier_id: null,
        member_id: null,
        date_range: currentDateTime(),
        employee_id: null,
        offline_sale_id: props.offlineSaleId,
    },
    displayCancelLayawaySaleDetailsModal: false,
});

const closeModal = () => {
    state.displayCancelLayawaySaleDetailsModal = false;
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

const showSaleDetailsModal = (saleId) => {
    state.cancelLayawaySale = [];
    axios.get(route('store_manager.cancel_layaway_sales.fetch_cancel_layaway_sale_items', saleId))
        .then((response) => {
            state.cancelLayawaySale = response.data.cancel_layaway_sale_details;
            state.displayCancelLayawaySaleDetailsModal = true;
        });
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-cancel-layaway-sales/',
        'cancel_layaway_sale.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-cancel-layaway-sales/',
        'cancel_layaway_sale.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const printCancelLayawaySale = (saleId) => {
    printReport(route('store_manager.cancel_layaway_sales.print_cancel_layaway_sale', saleId), props.exportPermission);
};

onMounted(() => {
    if (props.offlineSaleId) {
        state.parameters.date_range = [];
        refreshTable();
    }
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
