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
        :fetch-url="route('store_manager.credit_notes.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-credit-notes-reports-columns"
        search-title="Search by credit note id, member, or cashier"
    >
        <template #total_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.total_amount) }}
        </template>

        <template #available_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.available_amount) }}
        </template>

        <template #status="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    :label="record.item.status"
                    :type="getStatusColor(record.item.status_id)"
                />
            </div>
        </template>

        <template #info="record">
            <div class="flex justify-center items-center">
                <Info
                    v-if="record.item.available_amount != record.item.total_amount"
                    class="text-cyan-400 cursor-pointer"
                    @click="showCreditNoteModal(record.item)"
                />
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayCreditNotesFilter = !state.displayCreditNotesFilter"
                />
            </p>
        </template>
    </JTable>

    <CreditNoteDetails
        :modal-show="state.displayCreditNoteModal"
        :selected-credit-note="state.selectedCreditNote"
        @close-modal="state.displayCreditNoteModal = false"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol, exportRecords, currentDate } from '@commonServices/helper';
import JBadge from '@commonComponents/JBadge.vue';
import CreditNoteDetails from '@storeManagerPages/sales_management/partials/CreditNoteDetails.vue';
import { Info } from 'lucide-vue-next';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    statuses: {
        type: Object,
        required: true,
    },
    cashiers: {
        type: Array,
        required: true,
    },
    creditNoteStatuses: {
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
    creditNoteId: {
        type: Number,
        default: 0,
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
            key: 'id',
            isDisplay: true,
        }, {
            key: 'receipt_id',
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
            key: 'created_at',
            label: 'Date & Time',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'member',
            isDisplay: true,
        }, {
            key: 'expiry_date',
            label: 'Expiry Date',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'total_amount',
            label: 'Amount',
            class: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'available_amount',
            label: 'Available',
            class: 'text-right',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'status',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'info',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    displayCreditNoteModal: false,
    selectedCreditNote: [],
    cashiers: null,
    counters: null,
    refreshTableData: Math.random(),
    selectedMember: null,
    displayCreditNotesFilter: false,
    parameters: {
        counter_ids: null,
        cashier_id: null,
        member_id: null,
        date_range: [currentDate(), currentDate()],
        status_id: null,
        credit_note_id: props.creditNoteId,
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.counter_ids = null;
    state.parameters.cashier_id = null;
    state.parameters.member_id = null;
    state.parameters.status_id = null;
    state.selectedMember = null;
    state.counters = null;
    refreshTable();
};
const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateMember = (selectMember) => {
    state.selectedMember = selectMember;
    if (selectMember !== null) {
        state.parameters.member_id = selectMember.id;
    }
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

const updateCashierId = (cashierId) => {
    state.parameters.cashier_id = parseInt(cashierId);
    refreshTable();
};
const updateStatusId = (statusId) => {
    state.parameters.status_id = parseInt(statusId);
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

const getStatusColor = (statusId) => {
    if (statusId === props.statuses.expired) {
        return 'danger';
    }

    if (statusId === props.statuses.used) {
        return 'primary';
    }

    if (statusId === props.statuses.refunded) {
        return 'warning';
    }

    return 'success';
};

const showCreditNoteModal = (creditNote) => {
    state.displayCreditNoteModal = true;
    state.selectedCreditNote = creditNote;
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-credit-notes/',
        'credit_notes.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-credit-notes/',
        'credit_notes.xlsx',
        params,
        props.exportPermission,
        columns
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
