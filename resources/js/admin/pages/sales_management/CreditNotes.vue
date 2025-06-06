<template>
    <PageTitle title="Credit Notes" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Credit Notes
        </h2>
    </div>

    <div
        v-if="state.displayCreditNotesFilter"
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
                    :records="
                        state.storeCounters === null ? [] : state.storeCounters
                    "
                    :placeholder="
                        state.parameters.location_ids
                            ? 'Please select Counter'
                            : 'Please select a Location First'
                    "
                    :disabled="null === state.storeCounters"
                    input-label="Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateCounterId"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.cashier_id"
                    :records="
                        state.storeCashiers === null ? [] : state.storeCashiers
                    "
                    :placeholder="
                        state.parameters.location_ids
                            ? 'Please select Cashier'
                            : 'Please select a Location First'
                    "
                    :disabled="null === state.storeCashiers"
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
                <FormSelectBox
                    :selected-record="state.parameters.status_id"
                    :records="creditNoteStatuses"
                    placeholder="Please select Status"
                    input-label="Status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStatusId"
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
                    :selected-record="state.selectedEmployee"
                    :search-records="searchEmployees"
                    placeholder="Employee Name to search..."
                    input-label="Employee"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateEmployee"
                />
            </div>

            <div>
                <JDatePicker
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
        :fetch-url="route('admin.credit_notes.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-credit-notes-reports-columns"
        search-title="Search by credit note id"
    >
        <template #id="data">
            <div class="flex justify-left items-center">
                <span>
                    {{ data.item.id }}
                </span>
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
        <template #total_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.total_amount) }}
        </template>

        <template #available_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.available_amount) }}
        </template>

        <template #status="record">
            <div
                class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0"
            >
                <JBadge
                    :label="record.item.status"
                    :type="getStatusColor(record.item.status_id)"
                />
            </div>
        </template>

        <template #info="record">
            <div class="flex justify-center items-center">
                <Info
                    v-if="
                        record.item.available_amount != record.item.total_amount
                    "
                    class="text-cyan-400 cursor-pointer"
                    @click="showCreditNoteModal(record.item)"
                />
            </div>
        </template>

        <template #action="record">
            <div class="flex items-center justify-center cursor-pointer">
                <Dropdown
                    v-if="
                        checkEInvoicePermission(eInvoiceGeneratePermission) &&
                            allowEInvoice
                    "
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
                                    printCreditNoteDigitalInvoice(
                                        record.item.id
                                    )
                                "
                            >
                                <Printer class="w-5 h-5 mr-2" /> Print E-Invoice
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>

        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    :label="'Available: ' + truncateDecimal(record.data.total_available_amount)"
                />
            </div>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="
                        state.displayCreditNotesFilter =
                            !state.displayCreditNotesFilter
                    "
                />
            </p>
        </template>
    </JTable>

    <EInvoiceFormModal
        v-if="state.displayEInvoiceFormModal"
        :module-id="state.creditNoteId"
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

    <CreditNoteDetails
        :modal-show="state.displayCreditNoteModal"
        :selected-credit-note="state.selectedCreditNote"
        @close-modal="state.displayCreditNoteModal = false"
    />
</template>

<script setup>
import JTable from "@commonComponents/JTable.vue";
import { reactive, onMounted } from "vue";
import { route } from "ziggy";
import {
    displayAmountWithCurrencySymbol,
    exportRecords,
    checkEInvoicePermission,
    printReport,
    truncateDecimal,
} from "@commonServices/helper";
import JBadge from "@commonComponents/JBadge.vue";
import CreditNoteDetails from "@adminPages/sales_management/partials/CreditNoteDetails.vue";
import {
    Info,
    MoreHorizontal,
    Notebook,
    Printer,
    ReceiptText,
} from "lucide-vue-next";
import EInvoiceFormModal from "@commonComponents/EInvoiceFormModal.vue";
import FormSelectBox from "@commonComponents/FormSelectBox.vue";
import JMultiSelect from "@commonComponents/JMultiSelect.vue";
import OutlinePrimaryButton from "@commonComponents/OutlinePrimaryButton.vue";
import JDatePicker from "@commonComponents/JDatePicker.vue";
import axios from "axios";
import FormAjaxSelect from "@commonComponents/FormAjaxSelect.vue";
import { useHelpCenterStore } from "@commonStores/helpCenter";
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from "@commonVendor/dropdown";

const props = defineProps({
    statuses: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    creditNoteStatuses: {
        type: Array,
        required: true,
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
    creditNoteId: {
        type: Number,
        default: 0,
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
            key: "id",
            isDisplay: true,
        },
        {
            key: "receipt_id",
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
            key: "created_at",
            label: "Date & Time",
            sortable: true,
            isDisplay: true,
        },
        {
            key: "member",
            isDisplay: true,
        },
        {
            key: "expiry_date",
            label: "Expiry Date",
            sortable: true,
            isDisplay: true,
        },
        {
            key: "total_amount",
            label: "Amount",
            class: "text-right",
            bodyClass: "text-right",
            headerClass: "text-right",
            sortable: true,
            isDisplay: true,
        },
        {
            key: "available_amount",
            label: "Available",
            class: "text-right",
            bodyClass: "text-right",
            headerClass: "text-right",
            sortable: true,
            isDisplay: true,
        },
        {
            key: "status",
            sortable: true,
            isDisplay: true,
            bodyClass: "text-center",
            headerClass: "text-center",
        },
        {
            key: "info",
            isDisplay: true,
            bodyClass: "text-center",
            headerClass: "text-center",
        },
        {
            key: "action",
            isDisplay: true,
            headerClass: "text-center",
            bodyClass: "text-center",
        },
    ],
    displayCreditNoteModal: false,
    selectedCreditNote: [],
    storeCashiers: null,
    storeCounters: null,
    cashiers: null,
    counters: null,
    locations: null,
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedEmployee: null,
    displayCreditNotesFilter: false,
    displayEInvoiceFormModal: false,
    creditNoteId: null,
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
        date_range: null,
        status_id: null,
        employee_id: null,
        e_invoice_submitted: null,
        credit_note_id: props.creditNoteId,
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

const clearAll = () => {
    state.parameters.date_range = null;
    state.parameters.location_ids = null;
    state.parameters.counter_ids = null;
    state.parameters.cashier_id = null;
    state.parameters.member_id = null;
    state.parameters.status_id = null;
    state.parameters.employee_id = null;
    state.parameters.e_invoice_submitted = null;
    state.selectedMember = null;
    state.selectedEmployee = null;
    state.storeCounters = null;
    state.storeCashiers = null;
    state.counters = null;
    state.locations = null;

    refreshTable();
};
const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateEInvoice = (value) => {
    state.parameters.e_invoice_submitted = value;
    refreshTable();
};

const updateMember = (selectMember) => {
    state.selectedMember = selectMember;
    state.parameters.member_id = null;
    if (selectMember !== null) {
        state.parameters.member_id = selectMember.id;
    }
    refreshTable();
};

const hideEInvoiceFormModal = () => {
    state.displayEInvoiceFormModal = false;
};

const showEInvoiceFormModal = (creditNote) => {
    state.creditNoteId = creditNote.id;
    state.displayEInvoiceFormModal = true;
    state.sequenceNumber = creditNote.digital_invoice_number;
    state.receiptNumber = creditNote.receipt_id;
    state.memberName = creditNote.member;
    state.locationName = creditNote.location;
    state.digitalInvoiceSubmitted = creditNote.digital_invoice_submitted;
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
                state.storeCashiers = response.data.cashiers;
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
const updateStatusId = (statusId) => {
    state.parameters.status_id = null;
    if (statusId !== null) {
        state.parameters.status_id = parseInt(statusId);
    }
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
    state.parameters.employee_id = null;
    if (selectEmployee !== null) {
        state.parameters.employee_id = selectEmployee.id;
    }
    refreshTable();
};

const getStatusColor = (statusId) => {
    if (statusId === props.statuses.expired) {
        return "danger";
    }

    if (statusId === props.statuses.used) {
        return "primary";
    }

    if (statusId === props.statuses.refunded) {
        return "warning";
    }

    return "success";
};

const showCreditNoteModal = (creditNote) => {
    state.displayCreditNoteModal = true;
    state.selectedCreditNote = creditNote;
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        "export-credit-notes/",
        "credit_notes.csv",
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        "export-credit-notes/",
        "credit_notes.xlsx",
        params,
        props.exportPermission,
        columns
    );
};

const printCreditNoteDigitalInvoice = (creditNoteId) => {
    printReport(
        route(
            "admin.credit_notes.print_credit_notes_digital_invoice",
            creditNoteId
        ),
        props.exportPermission
    );
};

onMounted(() => {
    if (props.creditNoteId) {
        state.parameters.date_range = [];
        refreshTable();
    }
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
