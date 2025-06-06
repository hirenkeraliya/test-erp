<template>
    <PageTitle title="Booking Payments Report" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Booking Payments Report
        </h2>
    </div>

    <div
        v-if="state.displayBookingPaymentsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
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
                    :records="bookingPaymentStatuses"
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
        :fetch-url="route('store_manager.booking_payments.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-booking-payments-reports-columns"
        search-title="Search by offline id, member, amount or available "
    >
        <template #location="data">
            {{ data.item.location }}
        </template>

        <template #available_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.available_amount) }}
        </template>

        <template #total_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_amount) }}
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayBookingPaymentsFilter = !state.displayBookingPaymentsFilter"
                />
            </p>
        </template>

        <template #info="data">
            <div class="flex items-center justify-center cursor-pointer">
                <div class="mr-1">
                    <List
                        @click="showBookingPaymentDetailsModal(data.item.id)"
                    />
                </div>
                <div>
                    <Printer
                        @click="printBookingPayment(data.item.id)"
                    />
                </div>
            </div>
        </template>

        <template #offline_id="data">
            <div class="flex items-center justify-left">
                <span>
                    {{ data.item.offline_id }}
                </span>
                <Tippy
                    v-if="data.item.mismatches"
                    :content="'There are ' + data.item.mismatches + ' mismatches on this booking payment.'"
                >
                    <Info
                        class="ml-2 cursor-pointer text-danger"
                        :size="15"
                        @click="showBookingPaymentDetailsModal(data.item.id)"
                    />
                </Tippy>
            </div>
        </template>
    </JTable>

    <BookingPaymentDetails
        :modal-show="state.displayBookingPaymentDetailsModal"
        :booking-payments="state.bookingPayment"
        :columns-for-payment-details="state.columnsForPaymentDetails"
        :columns-for-booking-payments-details="state.columnsForBookingPaymentDetails"
        :columns-for-mismatches="state.columnsForMismatches"
        :columns-for-refund="state.columnsForRefund"
        :columns-for-uses="state.columnsForUses"
        :columns-for-void-uses="state.columnsForVoidUses"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive, onMounted } from 'vue';
import { route } from 'ziggy';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { displayAmountWithCurrencySymbol, exportRecords, currentDate, printReport } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import axios from 'axios';
import BookingPaymentDetails from '@storeManagerPages/reports/booking_payments/BookingPaymentDetails.vue';
import { Info, List, Printer } from 'lucide-vue-next';
import { useHelpCenterStore } from '@commonStores/helpCenter';
const props = defineProps({
    bookingPaymentStatuses: {
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
    receiptId: {
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
        },{
            key: 'offline_id',
            label: 'Receipt Id',
            isDisplay: true,
        }, {
            key: 'location',
            isDisplay: true,
        }, {
            key: 'counter',
            isDisplay: true,
        }, {
            key: 'authorizer',
            isDisplay: true,
        }, {
            key: 'cashier',
            isDisplay: true,
        }, {
            key: 'happened_at',
            label: 'Date Time',
            isDisplay: true,
        }, {
            key: 'member',
            isDisplay: true,
        }, {
            key: 'total_amount',
            isDisplay: true,
            label: 'Paid',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'available_amount',
            isDisplay: true,
            label: 'Balance',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'remarks',
            isDisplay: true,
        }, {
            key: 'bill_reference_number',
            label: '# Reference',
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

    columnsForPaymentDetails: [
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

    columnsForBookingPaymentDetails: [
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
        }, {
            key: 'color',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'size',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }
    ],

    columnsForMismatches: [
        {
            key: 'message',
            label: 'Mismatch messages',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }
    ],

    columnsForRefund: [
        {
            key: 'payment_type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'amount',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        },
    ],

    columnsForUses: [
        {
            key: 'sale_payment',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        },
    ],

    columnsForVoidUses: [
        {
            key: 'void_sale',
            bodyClass: 'text-left',
            headerClass: 'text-left',

            sortable: true
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        },
    ],

    bookingPayment: {},
    displayBookingPaymentDetailsModal: false,
    refreshTableData: Math.random(),
    selectedMember: null,
    displayBookingPaymentsFilter: false,
    parameters: {
        date_range: [currentDate(), currentDate()],
        status_id: null,
        receipt_id: props.receiptId,
    },
});
const refreshTable = () => {
    state.refreshTableData = Math.random();
};
const clearAll = () => {
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.member_id = null;
    state.selectedMember = null;
    state.parameters.status_id = null;
    refreshTable();
};

const updateStatusId = (statusId) => {
    state.parameters.status_id = parseInt(statusId);
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
const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-booking-payments/',
        'booking_payments.csv',
        params,
        props.exportPermission,
        columns
    );
};
const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-booking-payments/',
        'booking_payments.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const showBookingPaymentDetailsModal = (bookingPaymentId) => {
    state.bookingPayment = [];
    axios.get(route('store_manager.booking_payments.fetch_booking_payments_details', bookingPaymentId))
        .then((response) => {
            state.bookingPayment = response.data.bookingPayment_details;
        });

    state.displayBookingPaymentDetailsModal = true;
};

const closeModal = () => {
    state.bookingPayment = {};
    state.displayBookingPaymentDetailsModal = false;
};

const printBookingPayment = (bookingPaymentId) => {
    printReport(route('store_manager.booking_payments.print_booking_payment', bookingPaymentId), props.exportPermission);
};

onMounted(() => {
    if (props.receiptId) {
        state.parameters.date_range = [];
        refreshTable();
    }
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
