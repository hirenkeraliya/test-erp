<template>
    <PageTitle title="Layaway Sales" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Layaway Sales
        </h2>
    </div>

    <div
        v-if="state.displayLayawaySalesFilter"
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
                    :selected-record="state.parameters.e_invoice_submitted"
                    :records="state.eInvoiceFilter"
                    input-label="E Invoice Submitted"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateEInvoice"
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
        :fetch-url="route('admin.layaway_sales.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-layaway-sales-reports-columns"
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
            {{ data.item.location }}
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

        <template #layaway_pending_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.layaway_pending_amount) }}
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
                                @click="printLayawaySaleDigitalInvoice(record.item.id)"
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
                                @click="printLayawaySale(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" /> Layaway Sale Order Form
                            </DropdownItem>

                            <DropdownItem
                                @click="printSaleTaxInvoice(record.item.id)"
                            >
                                <Printer class="w-5 h-5 mr-2" /> Layaway Sale Tax Invoice
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayLayawaySalesFilter = !state.displayLayawaySalesFilter"
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

    <LayawaySaleDetails
        :modal-show="state.displayLayawaySaleDetailsModal"
        :layaway-sale="state.layawaySale"
        :columns-for-layaway-sale-payment-details="state.columnsForLayawaySalePaymentDetails"
        :columns-for-layaway-sale-item-details="state.columnsForLayawaySaleItemDetails"
        :columns-for-layaway-sale-discounts="state.columnsForLayawaySaleDiscounts"
        :columns-for-layaway-sale-mismatches="state.columnsForLayawaySaleMismatches"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { displayAmountWithCurrencySymbol, exportRecords, currentDateTime, printReport, checkEInvoicePermission } from '@commonServices/helper';
import { ReceiptText, Info, List, Printer, MoreHorizontal, Notebook } from 'lucide-vue-next';
import { onMounted, reactive, computed } from 'vue';
import { route } from 'ziggy';
import LayawaySaleDetails from '@adminPages/sales/layaway_sales/LayawaySaleDetails.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import EInvoiceFormModal from '@commonComponents/EInvoiceFormModal.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { usePage } from '@inertiajs/vue3';

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
    moduleType: {
        type: String,
        required: true,
    },
    eInvoiceGeneratePermission: {
        type: String,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    layawaySaleStatusPending: {
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
            key: 'layaway_pending_amount',
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
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    columnsForLayawaySalePaymentDetails: [
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

    columnsForLayawaySaleItemDetails: [
        {
            key: 'upc',
            sortable: true
        }, {
            key: 'product',
            sortable: true
        }, ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                },
            ]
            : [
                {
                    key: 'color',
                    sortable: true,
                },
                {
                    key: 'size',
                    sortable: true,
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

    columnsForLayawaySaleDiscounts: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
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

    columnsForLayawaySaleMismatches: [
        {
            key: 'message',
            label: 'Sale mismatch messages',
            headerClass: 'text-left',
            bodyClass: 'text-left',
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

    layawaySale: {},
    counters: null,
    locations: null,
    cashiers: null,
    storeCounters: null,
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displayLayawaySalesFilter: false,
    displayEInvoiceFormModal: false,
    saleId: null,
    receiptNumber: null,
    sequenceNumber: null,
    memberName: null,
    locationName: null,
    digitalInvoiceSubmitted: null,
    parameters: {
        location_ids: null,
        counter_ids: null,
        cashier_id: null,
        member_id: null,
        date_range: currentDateTime(),
        employee_id: null,
        status_id: props.layawaySaleStatusPending,
        e_invoice_submitted: null,
        offline_sale_id: props.offlineSaleId,
    },
    displayLayawaySaleDetailsModal: false,
});

const closeModal = () => {
    state.displayLayawaySaleDetailsModal = false;
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
    state.parameters.status_id = props.layawaySaleStatusPending;
    if (statusId !== null) {
        state.parameters.status_id = parseInt(statusId);
    }
    refreshTable();
};

const showSaleDetailsModal = (saleId) => {
    state.layawaySale = [];
    axios.get(route('admin.layaway_sales.fetch_layaway_sale_items', saleId))
        .then((response) => {
            state.layawaySale = response.data.layaway_sale_details;
            state.displayLayawaySaleDetailsModal = true;
        });
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-layaway-sales/',
        'layaway_sale.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-layaway-sales/',
        'layaway_sale.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const printLayawaySale = (saleId) => {
    printReport(route('admin.layaway_sales.print_layaway_sale', saleId), props.exportPermission);
};

const printSaleTaxInvoice = (saleId) => {
    printReport(route('admin.layaway_sales.print_sale_tax_invoice', saleId), props.exportPermission);
};

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

const printLayawaySaleDigitalInvoice = (saleId) => {
    printReport(route('admin.layaway_sales.print_layaway_sale_digital_invoice', saleId), props.exportPermission);
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
