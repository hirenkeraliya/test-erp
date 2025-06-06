<template>
    <PageTitle title="Loyalty Campaigns" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Loyalty Campaigns
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.loyalty_campaigns.create')">
                <PrimaryButton
                    text="Add New Loyalty Campaign"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayLoyaltyCampaignsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.start_date"
                    input-label="Start Date"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateStartDate($event)"
                />
            </div>
            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.end_date"
                    input-label="End Date"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateEndDate($event)"
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
        :fetch-url="route('admin.loyalty_campaigns.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by name, spend, or loyalty points"
    >
        <template #minimum_spend_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.minimum_spend_amount) }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.loyalty_campaigns.edit', data.item.id)"
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
                    @click="state.displayLoyaltyCampaignsFilter = !state.displayLoyaltyCampaignsFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare } from 'lucide-vue-next';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const props = defineProps({
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
            key: 'name',
            sortable: true
        }, {
            key: 'minimum_spend_amount',
            bodyClass: 'text-right',
            label: 'Minimum Spend',
            headerClass: 'text-right',
            sortable: true,
        }, {
            key: 'loyalty_points',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'loyalty_point_expiration_days',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'start_date',
        }, {
            key: 'end_date',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
    displayLoyaltyCampaignsFilter: false,
    parameters: {
        start_date: null,
        end_date: null
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.start_date = null;
    state.parameters.end_date = null;
    refreshTable();
};

const updateStartDate = (date) => {
    state.parameters.start_date = date;
    refreshTable();
};

const updateEndDate = (date) => {
    state.parameters.end_date = date;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-loyalty-campaigns/',
        'loyalty-campaigns.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-loyalty-campaigns/',
        'loyalty-campaigns.xlsx',
        params,
        props.exportPermission
    );
};
</script>
