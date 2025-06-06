<template>
    <PageTitle title="Day Close" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <div class="w-full mt-4 sm:mt-0">
            <InfoAlert
                color="primary"
                class="p-4"
            >
                <ol class="list-decimal">
                    <li>This page allows you to close counters and perform day close.</li>
                    <li>
                        If automatic day close is enabled by the admin for your location, the system will try to perform day close as per the specified time. If any of the counters are not closed at that time, day close will not be performed and you need to do it manually from this page.
                    </li>
                    <li>
                        Previous day close details can be checked on
                        <Link
                            href="#"
                            class="text-info"
                        >
                            Day close Report
                        </Link>
                    </li>
                    <li>
                        <b>Closing Balance Calculation:</b><br>
                        Opening balance + Sale amount in Cash + Booking Payment in Cash + Cash In - Booking Payment Refunded in Cash - Cash Out amount - Credit Note Refunded in Cash<br>

                        <b>Note</b> - Only Cash payments are considered for closing balance calculation.
                    </li>
                </ol>
            </InfoAlert>
        </div>

        <div class="w-full sm:w-auto md:w-1/2 mt-4 sm:mt-0 text-left sm:text-right ml-0 sm:ml-2">
            <Tippy
                :content="readyForDayClose() ? 'You cannot perform day close until all counters are closed.' : ''"
            >
                <OutlinePrimaryButton
                    v-if="hasMultipleCounters"
                    text="Day Close"
                    class="shadow-md"
                    type="button"
                    :disabled="readyForDayClose()"
                    @click="locationDayClose()"
                />
            </Tippy>
        </div>
    </div>

    <JSimpleTable
        v-if="hasMultipleCounters"
        :records="dayCloseCounters.data"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportPageCsvRecords"
        :export-excel-records-callback="exportPageExcelRecords"
        :allow-search="true"
    >
        <template #opening_balance="record">
            {{ displayAmountWithCurrencySymbol(record.item.opening_balance) }}
        </template>

        <template #closing_balance="record">
            {{ displayAmountWithCurrencySymbol(record.item.closing_balance) }}
        </template>

        <template #counter_information="data">
            <div class="flex justify-center items-center">
                <PrimaryButton
                    v-if="data.item.closed_at === 'N/A'"
                    text="Close Counter"
                    class="shadow-md"
                    @click="openCounterClosingDetailsModal(data.item.id)"
                />

                <button
                    v-else
                    class="btn btn-info mr-1"
                    @click="openCounterClosingDetailsModal(data.item.id)"
                >
                    <Info
                        class="w-5 h-5"
                        text="Display Closed Counter Details"
                    />
                </button>
            </div>
        </template>
    </JSimpleTable>

    <InfoAlert
        v-else
        color="warning"
        class="p-4 mt-3"
    >
        The day Close feature is available only for those locations that have at least one counter.
    </InfoAlert>

    <CounterClosingDetails
        :modal-show="state.displayCounterClosingDetailsModal"
        :counter-closing-details="state.counterClosingDetails"
        :counter-update-id="state.counterUpdateId"
        @close-modal="state.displayCounterClosingDetailsModal = false"
        @refresh-table="refreshTable"
    />

    <DayClosePrint
        v-if="state.printDayCloseReport"
        :day-close="state.printDayCloseReport"
    />
</template>
<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { Info } from 'lucide-vue-next';
import CounterClosingDetails from '@storeManagerPages/day_close/CounterClosingDetails.vue';
import DayClosePrint from '@storeManagerPages/day_close/partials/DayClosePrint.vue';
import { route } from 'ziggy';
import { reactive, onMounted, nextTick } from 'vue';
import { confirmDialogBox, showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';

const props = defineProps({
    dayCloseCounters: {
        type: Object,
        required: true,
    },
    hasMultipleCounters: {
        type: Boolean,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'counter_name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'created_at',
            label: 'Opened At',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'closed_at',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'opening_balance',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'closing_balance',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'counter_information',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],

    displayCounterClosingDetailsModal: false,
    counterClosingDetails: [],

    displayClosedCounterDetailsModal: false,
    counterUpdateId: null,

    printDayCloseReport: null,
    isCounterOpen: false,
});

const openCounterClosingDetailsModal = (counterUpdateId) => {
    axios.get(route('store_manager.day_close_counters.counter_closing_details', counterUpdateId))
        .then((response) => {
            state.counterUpdateId = counterUpdateId;
            state.counterClosingDetails = response.data.counter_closing_details;
        });

    state.displayCounterClosingDetailsModal = true;
};

const isAnyCounterOpen = () => {
    state.isCounterOpen = false;
    for (const key in props.dayCloseCounters.data) {
        if (props.dayCloseCounters.data[key].closed_at === 'N/A') {
            state.isCounterOpen = true;
            return;
        }
    }
};

const readyForDayClose = () => {
    return state.isCounterOpen || props.dayCloseCounters.data.length <= 0;
};

const locationDayClose = () => {
    if (props.dayCloseCounters.data.length) {
        confirmDialogBox('Are you sure you want to perform the day close?', () => {
            axios.post(route('store_manager.day_close_counters.day_close'))
                .then((response) => {
                    nextTick(() => {
                        state.printDayCloseReport = response.data.location_day_close;
                        state.printDayCloseReport.location_receipt_footer = response.data.location_receipt_footer;
                        state.printDayCloseReport.location_disclaimer = response.data.location_disclaimer;
                    });

                    showSuccessNotification('The day closed successfully.');
                    const timeoutDuration = 1000;

                    setTimeout(() => {
                        refreshTable();
                    }, timeoutDuration);
                }).catch((error) => {
                    showErrorNotification(error.response.data.message);
                });
        });
    }
};

const refreshTable = () => {
    location.reload();
};

const exportPageCsvRecords = (params) => {
    return exportRecords(
        'export-day-close/',
        'day-close.csv',
        params,
        props.exportPermission
    );
};

const exportPageExcelRecords = (params) => {
    return exportRecords(
        'export-day-close/',
        'day-close.xlsx',
        params,
        props.exportPermission
    );
};

onMounted(() => {
    isAnyCounterOpen();
});
</script>
