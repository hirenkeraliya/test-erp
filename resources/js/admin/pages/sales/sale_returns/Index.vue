<template>
    <PageTitle title="Sale Returns" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Returns
        </h2>
    </div>

    <div
        v-if="state.displaySaleReturnsFilter"
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
                    :records="
                        state.storeCounters === null ? [] : state.storeCounters
                    "
                    input-label="Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    :placeholder="
                        state.parameters.location_ids
                            ? 'Please select Counter'
                            : 'Please select a Location First'
                    "
                    @update:selected-records="updateCounterId"
                />
            </div>
            <div>
                <FormSelectBox
                    :disabled="null === state.cashiers"
                    :selected-record="state.parameters.cashier_id"
                    :records="state.cashiers === null ? [] : state.cashiers"
                    input-label="Cashiers"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    :placeholder="
                        state.parameters.location_ids
                            ? 'Please select Cashier'
                            : 'Please select a Location First'
                    "
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
                <FormInput
                    :input-value="state.parameters.offline_sale_return_id"
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
        :fetch-url="route('admin.sale_returns.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-sales-return-reports-columns"
        search-title="Search by receipt id"
    >
        <template #extra-header-data="record">
            <div
                class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0"
            >
                <JBadge
                    v-if="record.data.total_return_sales"
                    :label="
                        'Return Orders: ' +
                            truncateDecimal(record.data.total_return_sales)
                    "
                    class="mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_units_returned"
                    :label="
                        'Units Returned: ' +
                            truncateDecimal(record.data.total_units_returned)
                    "
                    class="mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_return_amount"
                    :label="
                        'Sale Returns: ' +
                            displayAmountWithCurrencySymbol(
                                record.data.total_return_amount
                            )
                    "
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
                    @click="
                        state.displaySaleReturnsFilter =
                            !state.displaySaleReturnsFilter
                    "
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
                    :content="
                        'There are ' +
                            data.item.sale_mismatches +
                            ' mismatches on this sale return.'
                    "
                >
                    <Info
                        class="text-danger ml-2 cursor-pointer"
                        :size="15"
                        @click="showSaleReturnDetailsModal(data.item.id)"
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

        <template #return_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.return_amount) }}
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

                    <DropdownMenu class="w-60">
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
                                @click="
                                    printSaleReturnDigitalInvoice(
                                        record.item.id
                                    )
                                "
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
        :module-id="state.saleReturnId"
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

    <SaleReturnDetails
        :modal-show="state.displaySaleReturnDetailsModal"
        :sale-return="state.saleReturn"
        :columns-for-sale-return-item-details="
            state.columnsForSaleReturnItemDetails
        "
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
import JTable from "@commonComponents/JTable.vue";
import {
    Info,
    List,
    Printer,
    MoreHorizontal,
    Notebook,
    ReceiptText,
} from "lucide-vue-next";
import { nextTick, onMounted, reactive, computed } from "vue";
import EInvoiceFormModal from "@commonComponents/EInvoiceFormModal.vue";
import { route } from "ziggy";
import {
    displayAmountWithCurrencySymbol,
    exportRecords,
    currentDateTime,
    truncateDecimal,
    isPrintRecords,
    checkEInvoicePermission,
    printReport,
} from "@commonServices/helper";
import SaleReturnDetails from "@adminPages/sales/sale_returns/SaleReturnDetails.vue";
import Receipt from "@commonComponents/Receipt.vue";
import FormSelectBox from "@commonComponents/FormSelectBox.vue";
import OutlinePrimaryButton from "@commonComponents/OutlinePrimaryButton.vue";
import JDateTimePicker from "@commonComponents/JDateTimePicker.vue";
import axios from "axios";
import FormAjaxSelect from "@commonComponents/FormAjaxSelect.vue";
import JBadge from "@commonComponents/JBadge.vue";
import JMultiSelect from "@commonComponents/JMultiSelect.vue";
import FormInput from "@commonComponents/FormInput.vue";
import { router, usePage } from "@inertiajs/vue3";
import { useHelpCenterStore } from "@commonStores/helpCenter";
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from "@commonVendor/dropdown";

const pageProps = computed(() => usePage().props);

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    offlineSaleReturnId: {
        type: String,
        default: "",
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
    helpCenterMessages: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: "digital_invoice_number",
            label: "Sequence#",
            isDisplay: true,
            headerClass: "text-left",
            bodyClass: "text-left",
        },
        {
            key: "offline_sale_return_id",
            label: "Receipt Id",
            isDisplay: true,
        },
        {
            key: "original_receipt_id",
            isDisplay: true,
        },
        {
            key: "location",
            isDisplay: true,
        },
        {
            key: "counter",
            isDisplay: true,
        },
        {
            key: "cashier",
            isDisplay: true,
        },
        {
            key: "happened_at",
            label: "Date & Time",
            isDisplay: true,
        },
        {
            key: "member",
            isDisplay: true,
        },
        {
            key: "return_amount",
            bodyClass: "text-right",
            isDisplay: true,
            label: "Return",
        },
        {
            key: "info",
            isDisplay: true,
            headerClass: "text-center",
            bodyClass: "text-center",
        },
        {
            key: "action",
            isDisplay: true,
            headerClass: "text-center",
            bodyClass: "text-center",
        },
    ],

    columnsForSaleReturnItemDetails: [
        {
            key: "upc",
            sortable: true,
        },
        {
            key: "product",
            sortable: true,
        },        
        ...(pageProps.value.product_variant
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
            key: "put_back_in_inventory",
            sortable: true,
        },
        {
            key: "sale_return_reason",
            sortable: true,
        },
        {
            key: "quantity",
            bodyClass: "text-center",
            sortable: true,
        },
        {
            key: "unit_price",
            bodyClass: "text-right",
            sortable: true,
        },
        {
            key: "subtotal",
            bodyClass: "text-right",
            sortable: true,
        },
        {
            key: "total_discount_amount",
            label: 'Discount',
            bodyClass: "text-right",
            sortable: true,
        },
        {
            key: "total_tax_amount",
            label: 'Tax',
            bodyClass: "text-right",
            sortable: true,
        },
        {
            key: "total_price_paid",
            label: 'Price Paid',
            bodyClass: "text-right",
            sortable: true,
        },
    ],

    columnsForSaleMismatches: [
        {
            key: "message",
            label: "Sale mismatch messages",
        },
    ],

    saleReturn: {},
    counters: null,
    locations: null,
    cashiers: null,
    storeCounters: null,
    displaySaleReturnDetailsModal: false,
    printReceiptData: Math.random(),
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displaySaleReturnsFilter: false,
    isClear: false,
    displayEInvoiceFormModal: false,
    saleReturnId: null,
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
        offline_sale_return_id: props.offlineSaleReturnId,
        e_invoice_submitted: null,
    },
    eInvoiceFilter: [
        {
            id: "1",
            name: "Yes",
        },
        {
            id: "0",
            name: "No",
        },
    ],
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateEInvoice = (value) => {
    state.parameters.e_invoice_submitted = value;
    refreshTable();
};

const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.date_range = currentDateTime();
    state.parameters.counter_ids = null;
    state.parameters.cashier_id = null;
    state.parameters.member_id = null;
    state.parameters.employee_id = null;
    state.selectedMember = null;
    state.selectedEmployee = null;
    state.counters = null;
    state.locations = null;
    state.storeCounters = null;
    state.cashiers = null;
    state.parameters.offline_sale_return_id = null;
    state.parameters.e_invoice_submitted = null;
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

const hideEInvoiceFormModal = () => {
    state.displayEInvoiceFormModal = false;
};

const showEInvoiceFormModal = (saleReturn) => {
    state.saleReturnId = saleReturn.id;
    state.displayEInvoiceFormModal = true;
    state.sequenceNumber = saleReturn.digital_invoice_number;
    state.receiptNumber = saleReturn.offline_sale_return_id;
    state.memberName = saleReturn.member;
    state.locationName = saleReturn.location.name;
    state.digitalInvoiceSubmitted = saleReturn.digital_invoice_submitted;
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

        axios
            .post(
                route("admin.counters.get_counters_of_locations", {
                    locations_ids: locationIds,
                })
            )
            .then((response) => {
                state.storeCounters = response.data.counters;
            });

        axios
            .post(
                route("admin.cashiers.get_cashiers_of_stores", {
                    location_ids: locationIds,
                })
            )
            .then((response) => {
                state.cashiers = response.data.cashiers;
            });

        refreshTable();

        return;
    }

    clearAll();
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
    axios
        .get(route("admin.members.get_filtered_members", filterData))
        .then((response) => {
            componentState.records = response.data.members;
            componentState.isLoading = false;
        });
};

const searchEmployees = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios
        .get(route("admin.employees.get_filtered_employees", filterData))
        .then((response) => {
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
    axios
        .get(route("admin.sale_returns.fetch_sale_return_items", saleReturnId))
        .then((response) => {
            state.saleReturn = response.data.sale_return_details;
            state.displaySaleReturnDetailsModal = true;
        });
};

const printSaleReport = (saleReturnId) => {
    if (isPrintRecords(props.exportPermission)) {
        state.saleReturn = [];
        axios
            .get(
                route(
                    "admin.sale_returns.fetch_sale_return_items",
                    saleReturnId
                )
            )
            .then((response) => {
                state.saleReturn = response.data.sale_return_details;
                nextTick(() => {
                    state.printReceiptData = Math.random();
                });
            });
    }
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        "export-sale-returns/",
        "sale_return.csv",
        params,
        props.exportPermission,
        columns
    );
};
const exportExcelRecords = (params, columns) => {
    return exportRecords(
        "export-sale-returns/",
        "sale_return.xlsx",
        params,
        props.exportPermission,
        columns
    );
};

const receiptId = (offlineSaleReturnId) => {
    state.parameters.offline_sale_return_id = offlineSaleReturnId;
    refreshTable();
};

const refreshPage = () => {
    router.get(route("admin.sale_returns.index"));
};

const printSaleReturnDigitalInvoice = (saleReturnId) => {
    printReport(
        route(
            "admin.sale_returns.print_sale_return_digital_invoice",
            saleReturnId
        ),
        props.exportPermission
    );
};

onMounted(() => {
    if (props.offlineSaleReturnId) {
        state.isClear = true;
        state.displaySaleReturnsFilter = true;
        refreshTable();
    }
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
