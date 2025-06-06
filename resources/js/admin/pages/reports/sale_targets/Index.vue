<template>
    <PageTitle title="Sale Targets" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Achieved Targets
        </h2>
    </div>

    <div
        v-if="state.displaySaleTargetFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.target_type"
                    :records="targetTypes"
                    placeholder="Please select target"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Target Type"
                    @update:selected-record="updateTargetType"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.time_interval_type"
                    :records="timeframeTypes"
                    placeholder="Please select time interval"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Time Interval Type"
                    @update:selected-record="updateTimeIntervalType"
                />
            </div>

            <div v-if="staticTargetTypes.storeWise === state.parameters.target_type">
                <JMultiSelect
                    :selected-records="state.locations"
                    :records="locations"
                    placeholder="Please select location"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateLocations"
                />
            </div>

            <div v-if="staticTargetTypes.promoterWise === state.parameters.target_type">
                <JMultiSelect
                    :selected-records="state.promoters"
                    :records="promoters"
                    placeholder="Please select Promoters"
                    input-label="Promoters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updatePromoters"
                />
            </div>

            <div
                v-if="
                    state.parameters.time_interval_type === staticTimeframeTypes.customPeriod ||
                        state.parameters.time_interval_type === staticTimeframeTypes.daily
                "
            >
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Date Range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
                />
            </div>

            <div
                v-if="state.parameters.time_interval_type === staticTimeframeTypes.yearly"
            >
                <JYearPicker
                    :input-value="state.parameters.year"
                    input-label="Year"
                    validation-field-name="year"
                    :required="true"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateYear($event)"
                />
            </div>

            <div
                v-if="state.parameters.time_interval_type === staticTimeframeTypes.weekly"
            >
                <JWeekPicker
                    :input-value="state.parameters.week"
                    :required="true"
                    input-label="Week"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateWeekly($event)"
                />
            </div>

            <div
                v-if="state.parameters.time_interval_type === staticTimeframeTypes.monthly"
            >
                <JMonthPicker
                    :input-value="state.parameters.month"
                    :required="true"
                    input-label="Month"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateMonthly($event)"
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
        :fetch-url="route('admin.sale_achieved_targets.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by Target value and Achieved Value"
    >
        <template #extra-header-data>
            <p class="text-lg font-bold mr-2 -mt-2">
                <OutlinePrimaryButton
                    text="Filters"
                    class="mt-2 text-sm shadow-md"
                    @click="state.displaySaleTargetFilter = !state.displaySaleTargetFilter"
                />
            </p>
        </template>

        <template #target_name="data">
            <strong>Name: </strong> {{ data.item.target_name }} <br>
            <strong>Type: </strong> {{ data.item.target_table_type }}
        </template>

        <template #time_interval_type="data">
            <div class="leading-normal">
                <strong>Type: </strong> {{ data.item.time_interval_type }} <br>
                {{ data.item.date }}<br>
            </div>
        </template>

        <template #target_value="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.target_value)) }}
        </template>

        <template #achieved_value="data">
            {{ displayAmountWithCurrencySymbol(numberFormat(data.item.achieved_value)) }}
        </template>

        <template #status="data">
            <JBadge
                :label="data.item.status"
                :type="data.item.status === 100 ? 'success' : 'danger'"
                class="mb-1 lg:mb-1 xl:mb-0"
            />
        </template>

        <template #action="data">
            <Info
                @click="showSaleTargetDetailsModal(data.item.id)"
            />
        </template>
    </JTable>

    <SaleTargetSalesDetails
        v-if="state.sales && state.sale_returns && state.displaySaleTargetDetailsModal"
        fetch-sale-url="admin.sales.index"
        fetch-sale-return-url="admin.sale_returns.index"
        :sale-target-show="state.displaySaleTargetDetailsModal"
        :sales="state.sales"
        :sale-returns="state.sale_returns"
        @update:hide-sale-target-modal="hideSaleTargetModel"
    />
</template>

<script setup>
import { displayAmountWithCurrencySymbol, numberFormat, exportRecords } from '@commonServices/helper';
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import JBadge from '@commonComponents/JBadge.vue';
import JMonthPicker from '@commonComponents/JMonthPicker.vue';
import JWeekPicker from '@commonComponents/JWeekPicker.vue';
import JYearPicker from '@commonComponents/JYearPicker.vue';
import SaleTargetSalesDetails from '@commonPages/SaleTargetSalesDetails.vue';
import { Info } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    promoters: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    targetTypes: {
        type: Object,
        required: true,
    },
    staticTargetTypes: {
        type: Object,
        required: true,
    },
    timeframeTypes: {
        type: Object,
        required: true,
    },
    staticTimeframeTypes: {
        type: Object,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
    filterData: {
        type: Object,
        required: true,
    },
});


const state = reactive({
    columns: [
        {
            key: 'name',
            label: 'Sale Target Name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'target_name',
            label: 'Target Details',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'time_interval_type',
            label: 'Time Interval Details',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'target_value',
            label: 'Target',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'achieved_value',
            label: 'Achieved',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'status',
            label: 'Achieved Ratio (%)',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'action',
            label: 'Action',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ],
    refreshTableData: Math.random(),
    promoters: [],
    locations: [],
    sales: [],
    sale_returns: [],
    displaySaleTargetFilter: false,
    displaySaleTargetDetailsModal: false,
    parameters: {
        target_type: props.filterData.target_type,
        time_interval_type: props.filterData.time_interval_type,
        promoter_ids: [],
        location_ids: [],
        date_range: props.filterData.day,
        year: props.filterData.year,
        month: props.filterData.month,
        week: props.filterData.week,
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.target_type = null;
    state.parameters.time_interval_type = null;
    state.parameters.promoter_ids = null;
    state.locations = null;
    state.promoters = null;
    state.parameters.location_ids = [];
    state.parameters.date_range = [];
    state.parameters.year = null;
    state.parameters.week = [];
    state.parameters.month = null;
    refreshTable();
};

const updatePromoters = (promoters) => {
    state.promoters = promoters;

    const promoterIds = promoters.map((promoter) => {
        return promoter.id;
    });
    state.parameters.promoter_ids = promoterIds;
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

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateYear = (date) => {
    state.parameters.year = date;
    refreshTable();
};

const updateWeekly = (date) => {
    state.parameters.week = date;
    refreshTable();
};

const updateMonthly = (date) => {
    state.parameters.month = date;
    refreshTable();
};

const exportCsvRecords = (parameters) => {
    return exportRecords(
        'export-sale-achieved-target/',
        'sale_target_achieved.csv',
        parameters,
        props.exportPermission
    );
};

const exportExcelRecords = (parameters) => {
    return exportRecords(
        'export-sale-achieved-target/',
        'sale_target_achieved.xlsx',
        parameters,
        props.exportPermission
    );
};

const updateTargetType = (targetType) => {
    state.promoters = [];
    state.parameters.promoter_ids = [];
    state.locations = [];
    state.parameters.location_ids = [];

    state.parameters.target_type = targetType;
    refreshTable();
};

const updateTimeIntervalType = (timeIntervalType) => {
    state.parameters.date_range = [];
    state.parameters.year = null;
    state.parameters.week = [];
    state.parameters.month = null;

    state.parameters.time_interval_type = timeIntervalType;
    refreshTable();
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);

const showSaleTargetDetailsModal = (saleAchievedTargetId) => {
    axios.get(route('admin.sale_achieved_targets.fetch_sales_and_returns_for_sale_achieved_target', saleAchievedTargetId))
        .then((response) => {
            state.sales = response.data.sales;
            state.sale_returns = response.data.sale_returns;
        });

    state.displaySaleTargetDetailsModal = true;
};

const hideSaleTargetModel = () => {
    state.displaySaleTargetDetailsModal = false;
};
</script>
