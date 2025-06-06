<template>
    <PageTitle title="Cash Movements" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cash Movements
        </h2>
    </div>

    <div
        v-if="state.displayCashMovementsFilter"
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
                    :selected-records="state.selectedCounters"
                    :records="state.counters === null ? [] : state.counters"
                    :placeholder="state.parameters.location_ids ? 'Please select Counter' : 'Please select a Location First'"
                    :disabled="null === state.counters"
                    input-label="Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateCounterId"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.cash_movement_type"
                    :records="cashMovementType"
                    placeholder="Please select Type"
                    input-label="Cash Movement Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateTypeId"
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
        :fetch-url="route('admin.cash_movements.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-cash-movements-reports-columns"
        search-title="Search by counter, location, authorizer, reason, other reason, or amount"
    >
        <template #amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.amount) }}
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayCashMovementsFilter = !state.displayCashMovementsFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol, exportRecords, currentDate } from '@commonServices/helper';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import axios from 'axios';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    cashMovementType: {
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
    columns: [
        {
            key: 'id',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'counter_name',
            label: 'Counter',
            isDisplay: true,
        }, {
            key: 'location',
            isDisplay: true,
        }, {
            key: 'authorizer',
            isDisplay: true,
        }, {
            key: 'type',
            isDisplay: true,
        }, {
            key: 'happened_at',
            label: 'Date & Time',
            isDisplay: true,
        }, {
            key: 'cash_movement_reason',
            label: 'Reason',
            isDisplay: true,
        }, {
            key: 'other_reason',
            isDisplay: true,
        }, {
            key: 'remarks',
            isDisplay: true,
        }, {
            key: 'amount',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }
    ],
    counters: null,
    locations: null,
    selectedCounters: null,
    refreshTableData: Math.random(),
    displayCashMovementsFilter: false,
    parameters: {
        location_ids: null,
        counter_ids: null,
        date_range: [currentDate(), currentDate()],
        cash_movement_type: null,
    },
});
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
                state.counters = response.data.counters;
            });

        refreshTable();

        return;
    }

    clearAll();
};

const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.date_range = [currentDate(), currentDate()];
    state.parameters.counter_ids = null;
    state.counters = null;
    state.locations = null;
    state.selectedCounters = null;
    state.parameters.cash_movement_type = null;
    refreshTable();
};
const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateCounterId = (counters) => {
    state.selectedCounters = counters;

    const counterIds = counters.map((counter) => {
        return counter.id;
    });
    state.parameters.counter_ids = counterIds;
    refreshTable();
};

const updateTypeId = (typeId) => {
    state.parameters.cash_movement_type = null;
    if (typeId !== null) {
        state.parameters.cash_movement_type = parseInt(typeId);
    }
    refreshTable();
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-cash-movements/',
        'cash_movement.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-cash-movements/',
        'cash_movement.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
