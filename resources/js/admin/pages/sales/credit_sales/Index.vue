<template>
    <PageTitle title="Credit Sales" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Credit Sales
        </h2>
    </div>

    <div
        v-if="state.displayCreditSalesFilter"
        class="px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.locations"
                    :records="locations"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select location"
                    @update:selected-records="updateLocations"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.counters"
                    :disabled="null === state.storeCounters"
                    :records="state.storeCounters === null ? [] : state.storeCounters"
                    :placeholder="state.parameters.location_ids ? 'Please select Counter' : 'Please select a Location First'"
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
                    input-label="Cashiers"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateCashierId"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.e_invoice_submitted"
                    :records="state.eInvoiceFilter"
                    input-label="E Invoice Submitted"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateEInvoice"
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
                <FormSelectBox
                    :selected-record="state.parameters.status_id"
                    :records="statuses"
                    placeholder="Please select Status"
                    input-label="Status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStatusId"
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
        :fetch-url="route('admin.credit_sales.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-credit-sales-reports-columns"
        search-title="Search by receipt id"
    >
        <template #offline_sale_id="data">
            <div class="flex justify-left items-center">
                <span>
                    {{ data.item.offline_sale_id }}
                </span>
                <Tippy
                    v-if="data.item.sale_mismatches > 0"
                    :content="'There are ' + data.item.sale_mismatches + ' mismatches on this credit sale.'"
                >
                    <Info
                        class="text-danger ml-2 cursor-pointer"
                        :size="15"
                        @click="showSaleDetailsModal(data.item.id)"
                    />
                </Tippy>
                <Tippy
                    v-if="data.item.digital_invoice_submitted"
                    :content="'E-Invoice generated'"
                >
                    <ReceiptText
                        class="ml-2 cursor-pointer text-info"
                        :size="15"
                    />
                </Tippy>
            </div>
        </template>

        <template #gross_sales="data">
            {{ displayAmountWithCurrencySymbol(data.item.gross_sales) }}
        </template>

        <template #net_sales="data">
            {{ displayAmountWithCurrencySymbol(data.item.net_sales) }}
        </template>

        <template #total_amount_paid="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_amount_paid) }}
        </template>

        <template #credit_pending_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.credit_pending_amount) }}
        </template>

        <template #action="record">
            <div class="flex items-center justify-center cursor-pointer">
                <Dropdown
                    v-if="checkEInvoicePermission(eInvoiceGeneratePermission)"
                    class="flex items-center mr-3"
                >
                    <DropdownToggle
                        tag="a"
                        class="w-5 h-5 block"
                        href="javascript:;"
                    >
                        <MoreHorizontal class="w-5 h-5 text-slate-500" />
                    </DropdownToggle>

                    <DropdownMenu
                        class="w-60"
                    >
                        <DropdownContent>
                            <DropdownItem
                                class="flex items-center mr-3"
                                @click="showEInvoiceFormModal(record.item)"
                            >
                                <Notebook class="w-4 h-4 mr-1" />
                                E-Invoice Generation
                            </DropdownItem>
                            <DropdownItem
                                v-if="record.item.digital_invoice_submitted"
                                @click="printCreditSaleDigitalInvoice(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" /> Print Invoice
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>

        <template #info="record">
            <div class="flex justify-center items-center cursor-pointer">
                <div class="mr-1">
                    <List
                        @click="showSaleDetailsModal(record.item.id)"
                    />
                </div>

                <Dropdown
                    class="dropdown"
                >
                    <DropdownToggle
                        tag="a"
                        class="w-5 h-5 block"
                        href="javascript:;"
                    >
                        <MoreHorizontal class="w-5 h-5 text-slate-500" />
                    </DropdownToggle>

                    <DropdownMenu
                        class="w-60"
                    >
                        <DropdownContent>
                            <DropdownItem
                                @click="printCreditSale(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" /> Credit Sale Order Form
                            </DropdownItem>

                            <DropdownItem
                                @click="printCreditSaleTaxInvoice(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" /> Credit Sale Tax Invoice
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
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
                    @click="state.displayCreditSalesFilter = !state.displayCreditSalesFilter"
                />
            </p>
        </template>
    </JTable>

    <EInvoiceFormModal
        v-if="state.displayEInvoiceFormModal"
        :module-id="state.saleId"
        :module-type="moduleType"
        :receipt-number="state.receiptNumber"
        :sequence-number="state.sequenceNumber"
        :member-name="state.memberName"
        :location-name="state.locationName"
        :digital-invoice-submitted="state.digitalInvoiceSubmitted"
        :display-e-invoice-form-modal="state.displayEInvoiceFormModal"
        @update:hide-e-invoice-modal="hideEInvoiceFormModal"
        @refresh:table-refresh="refreshTable"
    />

    <CreditSaleDetails
        :modal-show="state.displayCreditSaleDetailsModal"
        :credit-sale="state.creditSale"
        :columns-for-credit-sale-payment-details="state.columnsForCreditSalePaymentDetails"
        :columns-for-credit-sale-item-details="state.columnsForCreditSaleItemDetails"
        :columns-for-credit-sale-discounts="state.columnsForCreditSaleDiscounts"
        :columns-for-credit-sale-mismatches="state.columnsForCreditSaleMismatches"
        @close-modal="closeModal"
    />
</template>

<script setup>
import CreditSaleDetails from '@adminPages/sales/credit_sales/CreditSaleDetails.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JTable from '@commonComponents/JTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { currentDateTime, displayAmountWithCurrencySymbol, exportRecords, printReport, checkEInvoicePermission } from '@commonServices/helper';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ReceiptText, Info, List, MoreHorizontal, Printer, Notebook } from 'lucide-vue-next';
import { onMounted, reactive, computed } from 'vue';
import EInvoiceFormModal from '@commonComponents/EInvoiceFormModal.vue';
import { route } from 'ziggy';

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
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
    moduleType: {
        type: String,
        required: true,
    },
    eInvoiceGeneratePermission: {
        type: String,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    creditSaleStatusPending: {
        type: Number,
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
            key: 'status',
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
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'net_sales',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'total_amount_paid',
            label: 'Paid',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'credit_pending_amount',
            label: 'Due',
            bodyClass: 'text-right',
            headerClass: 'text-right',
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
        }, {
            key: 'action',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    eInvoiceFilter: [
        {
            id: '1',
            name: 'Yes',
        },
        {
            id: '0',
            name: 'No',
        },
    ],

    columnsForCreditSalePaymentDetails: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'payment_type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }
    ],

    columnsForCreditSaleItemDetails: [
        {
            key: 'upc',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'product',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
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
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'unit_price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'subtotal',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'total_discount_amount',
            label: 'Discount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'total_tax_amount',
            label: 'Tax',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'total_price_paid',
            label: 'Price Paid',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }
    ],

    columnsForCreditSaleDiscounts: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'discount_type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }
    ],

    columnsForCreditSaleMismatches: [
        {
            key: 'message',
            label: 'Sale mismatch messages',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }
    ],

    creditSale: {},
    counters: null,
    locations: props.dashboardFilterData.selectedLocations,
    cashiers: null,
    storeCounters: null,
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displayCreditSalesFilter: false,
    displayEInvoiceFormModal: false,
    saleId: null,
    receiptNumber: null,
    sequenceNumber: null,
    memberName: null,
    locationName: null,
    digitalInvoiceSubmitted: null,
    isClear: false,
    parameters: {
        location_ids: null,
        counter_ids: null,
        cashier_id: null,
        member_id: null,
        date_range: currentDateTime(),
        employee_id: null,
        status_id: props.creditSaleStatusPending,
        e_invoice_submitted: null,
        offline_sale_id: props.offlineSaleId,
    },
    displayCreditSaleDetailsModal: false,
});

const closeModal = () => {
    state.displayCreditSaleDetailsModal = false;
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const refreshPage = () => {
    router.get(route('admin.credit_sales.index'));
};

const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.date_range = currentDateTime();
    state.parameters.counter_ids = null;
    state.parameters.cashier_id = null;
    state.parameters.member_id = null;
    state.parameters.employee_id = null;
    state.parameters.e_invoice_submitted = null;
    state.selectedMember = null;
    state.selectedEmployee = null;
    state.counters = null;
    state.locations = null;
    state.cashiers = null;
    state.storeCounters = null;
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

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateEInvoice = (value) => {
    state.parameters.e_invoice_submitted = value;
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
    axios.get(route('admin.members.get_filtered_members', filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

const searchEmployees = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.get(route('admin.employees.get_filtered_employees', filterData)).then((response) => {
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

const updateStatusId = (statusId) => {
    state.parameters.status_id = props.creditSaleStatusPending;
    if (statusId !== null) {
        state.parameters.status_id = parseInt(statusId);
    }
    refreshTable();
};

const showSaleDetailsModal = (saleId) => {
    state.creditSale = [];
    axios.get(route('admin.credit_sales.fetch_credit_sale_items', saleId))
        .then((response) => {
            state.creditSale = response.data.credit_sale_details;
            state.displayCreditSaleDetailsModal = true;
        });
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-credit-sales/',
        'credit_sale.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-credit-sales/',
        'credit_sale.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const printCreditSale = (saleId) => {
    printReport(route('admin.credit_sales.print_credit_sale', saleId), props.exportPermission);
};

const printCreditSaleTaxInvoice = (saleId) => {
    printReport(route('admin.credit_sales.print_credit_sale_tax_invoice', saleId), props.exportPermission);
};

onMounted(() => {
    if (props.dashboardFilterData.location_ids || props.dashboardFilterData.location_ids === null) {
        state.isClear = true;
        state.parameters.date_range = null;
        state.displayCreditSalesFilter = true;
        refreshTable();
    }

    if (props.offlineSaleId) {
        state.parameters.date_range = [];
        refreshTable();
    }
});

const showEInvoiceFormModal = (sale) => {
    state.saleId = sale.id;
    state.displayEInvoiceFormModal = true;
    state.sequenceNumber = sale.digital_invoice_number;
    state.receiptNumber = sale.offline_sale_id;
    state.memberName = sale.member;
    state.locationName = sale.location;
    state.digitalInvoiceSubmitted = sale.digital_invoice_submitted;
};

const hideEInvoiceFormModal = () => {
    state.displayEInvoiceFormModal = false;
};

const printCreditSaleDigitalInvoice = (saleId) => {
    printReport(route('admin.credit_sales.print_credit_sale_digital_invoice', saleId), props.exportPermission);
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
