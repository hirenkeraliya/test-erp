<template>
    <PageTitle title="Sale Targets" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Targets
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

            <div
                v-if="staticTargetTypes.promoterWise === state.parameters.target_type"
            >
                <JMultiSelect
                    :selected-records="state.promoters"
                    :records="promoters"
                    input-label="Promoters"
                    :required="true"
                    validation-field-name="promoter_ids"
                    @update:selected-records="updatePromoterId"
                />
            </div>

            <div
                v-if="staticTargetTypes.promoterWise === state.parameters.target_type"
                class="px-3 mt-8"
            >
                <PrimaryButton
                    type="button"
                    text="Select all"
                    class="w-auto sm:w-24 md:w-1/1 mr-4"
                    @click="selectAllPromoters"
                />

                <OutlinePrimaryButton
                    v-if="state.promoters.length > 0"
                    type="button"
                    text="Clear All"
                    class="w-auto sm:w-24 md:w-1/1 mt-2"
                    @click="clearAllPromoters"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.time_interval_type"
                    :records="timeIntervalTypes"
                    placeholder="Please select time interval type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Time interval type"
                    @update:selected-record="updateTimeIntervalType"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.select_status"
                    :records="status"
                    placeholder="Please select status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    @update:selected-record="updateSelectedStatus($event)"
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
        :fetch-url="route('store_manager.sale_targets.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name"
    >
        <template #amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.amount) }}
        </template>
        <template #info="record">
            <div class="flex items-center justify-center cursor-pointer">
                <List
                    @click="showSaleDetailsModal(record.item.sale_target_timeframe_details)"
                />
            </div>
        </template>
        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displaySaleTargetFilter = !state.displaySaleTargetFilter"
                />
            </p>
        </template>
        <template #action="data">
            <div
                class="flex justify-center items-center"
            >
                <div
                    class="cursor-pointer"
                    @click="showViewModal(data.item.id)"
                >
                    <div
                        class="flex items-center mr-3"
                    >
                        <View class="w-4 h-4 mr-2" />
                        View
                    </div>
                </div>
            </div>
        </template>
    </JTable>

    <TimeFrameDetails
        :modal-show="state.displaySaleTargetTimeframeModal"
        :sale-target-timeframe="state.saleTargetTimeframe"
        :columns-for-timeframe-details="state.columnsForTimeframeDetails"
        @close-modal="closeModal"
    />

    <SaleTargetView
        :modal-show="state.displayViewModal"
        :sale-target-details="state.selectedSaleTarget"
        @close-modal="state.displayViewModal = false"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { List, View } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords, displayAmountWithCurrencySymbol } from '@commonServices/helper';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import TimeFrameDetails from '@commonPages/TimeFrameDetails.vue';
import SaleTargetView from '@commonPages/SaleTargetView.vue';
import axios from 'axios';

const props = defineProps({
    status: {
        type: Object,
        required: true,
    },
    targetTypes: {
        type: Object,
        required: true,
    },
    timeIntervalTypes: {
        type: Object,
        required: true,
    },
    promoters: {
        type: Array,
        required: true,
    },
    staticTargetTypes: {
        type: Object,
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
            key: 'name',
        }, {
            key: 'amount',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'target_type',
        }, {
            key: 'time_interval_type',
        },
        {
            key: 'action',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        },
        {
            key: 'info',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    columnsForTimeframeDetails: [
        {
            key: 'target_label',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        {
            key: 'start_date',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'end_date',
            sortable: true,
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }
    ],

    refreshTableData: Math.random(),
    displaySaleTargetFilter: false,
    promoters: [],
    displaySaleTargetTimeframeModal: false,
    saleTargetTimeframe: [],
    selectedSaleTarget: [],
    displayViewModal: false,

    parameters: {
        target_type: null,
        select_status: null,
        time_interval_type: null,
        promoter_ids: [],
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.select_status = null;
    state.parameters.target_type = null;
    state.parameters.time_interval_type = null;
    refreshTable();
};

const updateSelectedStatus = (status) => {
    state.parameters.select_status = status;
    refreshTable();
};

const updateTargetType = (targetType) => {
    state.promoters = [];
    state.parameters.promoter_ids = [];

    state.parameters.target_type = targetType;
    refreshTable();
};

const updateTimeIntervalType = (timeIntervalType) => {
    state.parameters.time_interval_type = timeIntervalType;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-sale-targets/',
        'sale_targets.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-sale-targets/',
        'sale_targets.xlsx',
        params,
        props.exportPermission
    );
};

const updatePromoterId = (promoterIds) => {
    state.promoters = promoterIds;
    state.parameters.promoter_ids = state.promoters.map((promoter) => {
        return promoter.id;
    });
    refreshTable();
};

const selectAllPromoters = () => {
    updatePromoterId(props.promoters);
};

const clearAllPromoters = () => {
    state.promoters = [];
    state.parameters.promoter_ids = [];
    refreshTable();
};

const showSaleDetailsModal = (saleTargetTimeframeDetails) => {
    state.saleTargetTimeframe = saleTargetTimeframeDetails;
    state.displaySaleTargetTimeframeModal = true;
};

const showViewModal = (saleTargetId) => {
    state.selectedSaleTarget = [];
    axios.get(route('store_manager.sale_targets.fetch_sale_target', saleTargetId))
        .then((response) => {
            state.selectedSaleTarget = response.data.sale_target_details;
            state.displayViewModal = true;
        });
};

const closeModal = () => {
    state.saleTargetTimeframe = [];
    state.displaySaleTargetTimeframeModal = false;
};

</script>
