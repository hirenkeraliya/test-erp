<template>
    <PageTitle title="Payment Type Report" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Payment Type Report
        </h2>

        <InfoAlert
            color="primary"
            class="mb-3 mt-5"
        >
            Only regular, complete credit and complete layaway sales are considered for the payment types report.
        </InfoAlert>
    </div>

    <div
        v-if="state.displayPaymentTypesFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.payment_type_id"
                    :records="paymentTypes"
                    placeholder="Please select payment type"
                    input-label="PaymentTypes"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updatePaymentTypeId"
                />
            </div>

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
                    :records="state.storeCounters === null ? [] : state.storeCounters"
                    :placeholder="state.parameters.location_ids ? 'Please select Counter' : 'Please select a Location First'"
                    :disabled="null === state.storeCounters"
                    input-label="Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateCounterId"
                />
            </div>

            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.date"
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
        :fetch-url="route('admin.payment_type_report.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by Payment Type"
    >
        <template #id="data">
            {{ data.item.id }}
        </template>

        <template #total_transactions="data">
            {{ data.item.total_transactions }}
            <Info
                class="ml-2 text-primary"
                :size="15"
                @click="showTransactionDetailsModal(data.item.id)"
            />
        </template>

        <template #total_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_amount) }}
        </template>

        <template #extra-header-data="data">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    :label="`Total Amount: ${displayAmountWithCurrencySymbol(data.data.total_amount_badge || 0)}`"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
                <JBadge
                    :label="`Total Transactions: ${data.data.total_transactions_badge || 0}`"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />
            </div>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayPaymentTypesFilter = !state.displayPaymentTypesFilter"
                />
            </p>
        </template>
    </JTable>

    <Modal
        size="modal-xl"
        :show="state.transactionModalShow"
        @hidden="hideTransactionModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Transactions
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideTransactionModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <JSimpleTable
                :columns="state.transactionDetailColumns"
                :records="state.transactionDetailRecords"
                :allow-search="true"
            >
                <template #receipt_id="data">
                    <a
                        v-if="data.item.url"
                        class="text-blue-700 underline font-bold"
                        :href="data.item.url"
                        target="_blank"
                    >
                        {{ data.item.receipt_id }}
                    </a>

                    <p v-else>
                        {{ data.item.receipt_id }}
                    </p>
                </template>

                <template #amount="data">
                    {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import JBadge from '@commonComponents/JBadge.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import axios from 'axios';
import { exportRecords, displayAmountWithCurrencySymbol, currentDate } from '@commonServices/helper';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { Info, X } from 'lucide-vue-next';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    paymentTypes: {
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
    parameters: {
        payment_type_id: null,
        location_ids: null,
        counter_ids: null,
        date: [currentDate(), currentDate()]
    },

    columns: [
        {
            key: 'id',
            label: 'number',
            sortable: true
        },
        {
            key: 'name',
            label: 'Payment Type',
            sortable: true
        },
        {
            key: 'total_transactions',
            label: 'Transactions',
            bodyClass: 'text-right flex justify-end items-center',
            headerClass: 'text-right',
            sortable: true
        },
        {
            key: 'total_amount',
            bodyClass: 'text-right',
            label: 'Amount',
            headerClass: 'text-right',
            sortable: true
        },
    ],

    transactionDetailColumns: [
        {
            key: 'receipt_id',
            label: 'Receipt Number',
            sortable: true
        },
        {
            key: 'payment_type',
            label: 'Payment Type',
            sortable: true
        },
        {
            key: 'amount',
            label: 'Amount',
            bodyClass: 'text-right flex justify-end items-center',
            headerClass: 'text-right',
            sortable: true
        },
    ],

    counters: null,
    locations: null,
    storeCounters: null,
    records: [],
    displayPaymentTypesFilter: false,
    refreshTableData: Math.random(),
    transactionModalShow: false,
    transactionDetailRecords: [],
});

const hideTransactionModal = () => {
    state.transactionModalShow = false;
    state.transactionDetailRecords = [];
};

const showTransactionDetailsModal = (id) => {
    state.parameters.id = id;
    axios.get(route('admin.payment_type_report.fetch_transactions', state.parameters))
        .then((response) => {
            state.transactionDetailRecords = response.data.transaction_details;
        });
    state.parameters.id = null;
    state.transactionModalShow = true;
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateLocations = (locations) => {
    state.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });

    if (locationIds.length) {
        state.parameters.location_ids = locationIds;
        state.parameters.counter_ids = null;

        axios.post(route('admin.counters.get_counters_of_locations', { locations_ids: locationIds }))
            .then((response) => {
                state.storeCounters = response.data.counters;
            });

        refreshTable();

        return;
    }

    clearAll();
};

const updatePaymentTypeId = (paymentTypeId) => {
    state.parameters.payment_type_id = null;
    if (paymentTypeId !== null) {
        state.parameters.payment_type_id = parseInt(paymentTypeId);
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

const clearAll = () => {
    state.parameters.location_ids = '';
    state.parameters.counter_ids = '';
    state.parameters.payment_type_id = '';
    state.parameters.date = [currentDate(), currentDate()];
    state.counters = null;
    state.locations = null;
    state.storeCounters = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date = date;
    refreshTable();
};

const exportCsvRecords = () => {
    return exportRecords(
        'export-payment-types/',
        'payment_types.csv',
        state.parameters,
        props.exportPermission
    );
};

const exportExcelRecords = () => {
    return exportRecords(
        'export-payment-types/',
        'payment_types.xlsx',
        state.parameters,
        props.exportPermission
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
