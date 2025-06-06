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
                class="w-24 h-10 btn-sm"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.sales.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-sales-reports-columns"
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
                    @click="printSaleReport(record.item.id)"
                />

                <List
                    @click="showSaleDetailsModal(record.item.id)"
                />
            </div>
        </template>
        <template #action="record">
            <div class="flex items-center justify-center cursor-pointer">
                <Dropdown
                    v-if="checkEInvoicePermission(eInvoiceGeneratePermission) && allowEInvoice"
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
                                @click="printSaleDigitalInvoice(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" /> Print Invoice
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
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
import JTable from '@commonComponents/JTable.vue';
import { displayAmountWithCurrencySymbol, exportRecords, currentDateTime, truncateDecimal, isPrintRecords, checkEInvoicePermission, printReport } from '@commonServices/helper';
import { ReceiptText, Info, List, Printer, MoreHorizontal, Notebook } from 'lucide-vue-next';
import { nextTick, onMounted, reactive, computed } from 'vue';
import { route } from 'ziggy';
import SaleDetails from '@adminPages/sales/SaleDetails.vue';
import Receipt from '@commonComponents/Receipt.vue';
import EInvoiceFormModal from '@commonComponents/EInvoiceFormModal.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { router, usePage } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    offlineSaleId: {
        type: String,
        default: '',
    },
    exportPermission: {
        type: String,
        required: true,
    },
    eInvoiceGeneratePermission: {
        type: String,
        required: true,
    },
    moduleType: {
        type: String,
        required: true,
    },
    allowEInvoice: {
        type: Boolean,
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
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'bill_reference_number',
            label: '# Reference',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'location',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'counter',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'cashier',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'happened_at',
            label: 'Date & Time',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'member',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
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
            key: 'notes',
            label: 'Remarks',
            isDisplay: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'info',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
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
            sortable: true
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
            headerClass: 'text-right'
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
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'discount_type',
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
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
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
    locations: null,
    storeCounters: null,
    cashiers: null,
    displaySaleDetailsModal: false,
    displayEInvoiceFormModal: false,
    printReceiptData: Math.random(),
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displaySalesFilter: false,
    isClear: false,
    saleId: null,
    receiptNumber: null,
    sequenceNumber: null,
    memberName: null,
    locationName: null,
    digitalInvoiceSubmitted: null,
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
    parameters: {
        location_ids: null,
        counter_ids: null,
        cashier_id: null,
        member_id: null,
        employee_id: null,
        date_range: currentDateTime(),
        offline_sale_id: props.offlineSaleId,
        e_invoice_submitted: null,
    },
});

const closeModal = () => {
    state.sale = {};
    state.displaySaleDetailsModal = false;
};

const hideEInvoiceFormModal = () => {
    state.displayEInvoiceFormModal = false;
};

const showSaleDetailsModal = (saleId) => {
    state.sale = [];
    axios.get(route('admin.sales.fetch_sale_items', saleId))
        .then((response) => {
            state.sale = response.data.sale_details;
        });

    state.displaySaleDetailsModal = true;
};

const showEInvoiceFormModal = (sale) => {
    state.saleId = sale.id;
    state.displayEInvoiceFormModal = true;
    state.sequenceNumber = sale.digital_invoice_number;
    state.receiptNumber = sale.offline_sale_id;
    state.memberName = sale.member;
    state.locationName = sale.location.name;
    state.digitalInvoiceSubmitted = sale.digital_invoice_submitted;
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.date_range = currentDateTime();
    state.parameters.counter_ids = null;
    state.parameters.cashier_id = null;
    state.parameters.member_id = null;
    state.parameters.employee_id = null;
    state.parameters.offline_sale_id = null;
    state.parameters.e_invoice_submitted = null;
    state.selectedMember = null;
    state.selectedEmployee = null;
    state.counters = null;
    state.locations = null;
    state.storeCounters = null;
    state.cashiers = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
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

const updateEInvoice = (value) => {
    state.parameters.e_invoice_submitted = value;
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

const printSaleReport = (saleId) => {
    if (isPrintRecords(props.exportPermission)) {
        state.sale = [];
        axios.get(route('admin.sales.fetch_sale_items', saleId))
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

const receiptId = (offlineSaleId) => {
    state.parameters.offline_sale_id = offlineSaleId;
    refreshTable();
};

const printSaleDigitalInvoice = (saleId) => {
    printReport(route('admin.sales.print_sale_digital_invoice', saleId), props.exportPermission);
};

const refreshPage = () => {
    router.get(route('admin.sales.index'));
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
