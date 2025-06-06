<template>
    <PageTitle title="Sale Targets" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Targets
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.sale_targets.create')">
                <PrimaryButton
                    text="Add New Sale Target"
                    class="shadow-md"
                />
            </Link>
        </div>
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
                v-if="staticTargetTypes.storeWise === state.parameters.target_type"
            >
                <JMultiSelect
                    :records="locations"
                    input-label="Locations"
                    :required="true"
                    validation-field-name="location_ids"
                    :selected-records="state.locations"
                    @update:selected-records="updateLocationId"
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
                v-if="staticTargetTypes.promoterWise === state.parameters.target_type || staticTargetTypes.storeWise === state.parameters.target_type"
                class="px-3 mt-8"
            >
                <PrimaryButton
                    type="button"
                    text="Select all"
                    class="w-auto sm:w-24 md:w-1/1 mr-4"
                    @click="selectAllLocationsAndPromoters"
                />

                <OutlinePrimaryButton
                    v-if="state.locations.length > 0 || state.promoters.length"
                    type="button"
                    text="Clear All"
                    class="w-auto sm:w-24 md:w-1/1 mt-2"
                    @click="clearAllLocationsAndPromoters"
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
        :fetch-url="route('admin.sale_targets.fetch')"
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

        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(data.item.id, $event)"
                />
            </div>
        </template>
        <template #action="data">
            <div
                class="flex justify-center items-center"
            >
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.sale_targets.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    v-if="! data.item.re_generate_target"
                    class="flex items-center mr-3"
                    title="Regenerate Sale Targets Achieved"
                    @click="regenerateSaleTarget(data.item.id)"
                >
                    <RefreshCw class="w-4 h-4 mr-2" />
                    Regenerate
                </Link>

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
import { CheckSquare, List, RefreshCw, View } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords, displayAmountWithCurrencySymbol } from '@commonServices/helper';
import JSwitch from '@commonComponents/JSwitch.vue';
import { router } from '@inertiajs/vue3';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import TimeFrameDetails from '@commonPages/TimeFrameDetails.vue';
import { showSuccessNotification } from '@commonServices/notifier';
import SaleTargetView from '@commonPages/SaleTargetView.vue';
import axios from 'axios';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
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
    locations: {
        type: Array,
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
});

const state = reactive({
    columns: [
        {
            key: 'name',
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'amount_type',
            label: 'Type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'percentage',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'target_type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'time_interval_type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
        {
            key: 'info',
            bodyClass: 'text-center',
            headerClass: 'text-center',
            isDisplay: true,
        }
    ],

    columnsForTimeframeDetails: [
        {
            key: 'target_label',
            sortable: true
        },
        {
            key: 'start_date',
            sortable: true
        }, {
            key: 'end_date',
            sortable: true
        }
    ],

    refreshTableData: Math.random(),
    displaySaleTargetFilter: false,
    locations: [],
    promoters: [],
    displaySaleTargetTimeframeModal: false,
    saleTargetTimeframe: [],
    selectedSaleTarget: [],
    displayViewModal: false,

    parameters: {
        target_type: null,
        select_status: null,
        time_interval_type: null,
        location_ids: [],
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
    state.parameters.location_ids = [];
    state.parameters.promoter_ids = [];
    refreshTable();
};

const updateSelectedStatus = (status) => {
    state.parameters.select_status = status;
    refreshTable();
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
    state.parameters.time_interval_type = timeIntervalType;
    refreshTable();
};

const setStatus = (saleTargetId, status) => {
    router.post(route('admin.sale_targets.set_status', [saleTargetId, status ? 1 : 0]));
};

const regenerateSaleTarget = (saleTargetId) => {
    router.put(route('admin.sale_targets.re_generate_target', saleTargetId));
    showSuccessNotification('Your Sale Target Achieved Regenerate request has been sent successfully. The Regeneration process will now commence in the background. Kindly allow some time for the process to complete');
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

const updateLocationId = (locationIds) => {
    state.promoters = [];
    state.parameters.promoter_ids = [];

    state.locations = locationIds;
    state.parameters.location_ids = state.locations.map((location) => {
        return location.id;
    });
    refreshTable();
};

const updatePromoterId = (promoterIds) => {
    state.locations = [];
    state.parameters.location_ids = [];

    state.promoters = promoterIds;
    state.parameters.promoter_ids = state.promoters.map((promoter) => {
        return promoter.id;
    });
    refreshTable();
};

const selectAllLocationsAndPromoters = () => {
    if (props.staticTargetTypes.storeWise === state.parameters.target_type) {
        updateLocationId(props.locations);
    }
    if (props.staticTargetTypes.promoterWise === state.parameters.target_type) {
        updatePromoterId(props.promoters);
    }
};

const clearAllLocationsAndPromoters = () => {
    state.locations = [];
    state.parameters.location_ids = [];
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
    axios.get(route('admin.sale_targets.fetch_sale_target', saleTargetId))
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
