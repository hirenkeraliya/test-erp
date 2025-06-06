<template>
    <PageTitle title="Cashback" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cashback
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.cashbacks.create')">
                <PrimaryButton
                    text="New Campaign"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <InfoAlert
        color="primary"
        class="mb-3 mt-5"
    >
        Please
        <a
            href="/images/discount_applicable_flow.png"
            class="underline"
            target="_blank"
        >
            click here
        </a>
        to explore how discounts are applied/stacked in promotion .
    </InfoAlert>

    <div
        v-if="state.displayCashbackFilter"
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
        :fetch-url="route('admin.cashbacks.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by name, exclude by type, flat, or minimum spend"
    >
        <template #minimum_spend_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.minimum_spend_amount) }}
        </template>

        <template #discount_type_id="data">
            {{ data.item.discount_type }}
        </template>

        <template #usage="data">
            <div class="flex justify-end">
                <span>{{ data.item.total_used_counts }}</span>
                <Tippy
                    :content="displayAmountWithCurrencySymbol(data.item.total_discount_amount)"
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="18"
                    />
                </Tippy>
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.cashbacks.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayCashbackFilter = !state.displayCashbackFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, Info } from 'lucide-vue-next';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    locations: {
        type: Array,
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
            key: 'id',
            sortable: true
        }, {
            key: 'exclude_by_type',
            label: 'Exclude By',
            sortable: true
        }, {
            key: 'name',
            sortable: true
        }, {
            key: 'discount_type_id',
            label: 'Discount',
            sortable: true
        }, {
            key: 'discount_value',
            sortable: true,
            label: 'Value',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'minimum_spend_amount',
            sortable: true,
            label: 'Minimum Spend',
            headerClass: 'text-right',
            bodyClass: 'text-right w-60',
        }, {
            key: 'usage',
            headerClass: 'text-right',
            label: 'Redeemed',
            bodyClass: 'text-right',
        }, {
            key: 'start_date',
        }, {
            key: 'end_date',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
    locations: [],
    displayCashbackFilter: false,
    parameters: {
        date_range: null,
        location_ids: [],
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = null;
    state.locations = null;
    state.parameters.location_ids = [];
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
    state.parameters.location_ids = locationIds;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-cashbacks/',
        'cashbacks.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-cashbacks/',
        'cashbacks.xlsx',
        params,
        props.exportPermission
    );
};
</script>
