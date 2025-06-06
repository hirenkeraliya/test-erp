<template>
    <PageTitle title="Promotions" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Promotions
        </h2>

        <div class="w-full sm:w-auto block sm:flex mt-4 sm:mt-0">
            <Link
                v-if="promotionId"
                :href="route('admin.promotions.index')"
            >
                <OutlinePrimaryButton
                    type="button"
                    text="View All Promotions"
                    class="btn-sm h-10 mr-2 mb-2 sm:mb-0"
                />
            </Link>

            <Link :href="route('admin.promotions.fetch_calender')">
                <OutlinePrimaryButton
                    type="button"
                    text="Calender View"
                    class="btn-sm h-10 mr-2 mb-2 sm:mb-0"
                />
            </Link>

            <Link :href="route('admin.promotions.create')">
                <PrimaryButton
                    text="Add New Promotion"
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
        To observe how the discounts are distributed among various promotions and other functionalities of the POS.
    </InfoAlert>

    <div
        v-if="state.displayPromotionsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.locations"
                    :records="locations"
                    placeholder="Please select location"
                    input-label="Location"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateLocations"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.promotion_type"
                    :records="promotionTypes"
                    input-label="PromotionType"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Promotion Type"
                    @update:selected-record="updateTypeId"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.promotion_user_restriction_type"
                    :records="promotionUserRestrictionType"
                    input-label="Promotion User Restriction Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Promotion User Restriction Type"
                    @update:selected-record="updatePromotionUserRestrictionType"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.availability_type"
                    :records="availabilityType"
                    input-label="Availability Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Promotion Availability Type"
                    @update:selected-record="updatePromotionAvailabilityType"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status_value"
                    :records="statuses"
                    input-label="Status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Status"
                    @update:selected-record="updateStatus"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.type"
                    :records="types"
                    input-label="Types"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Promotion Types"
                    @update:selected-record="updatePromotionTypes"
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
        :fetch-url="route('admin.promotions.fetch', {id : promotionId})"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name or promotion type"
    >
        <template #name="data">
            {{ data.item.name }}
            <div
                v-if="data.item.mystery_gift_id != null"
                class="mt-2"
            >
                <span class="bg-orange-200 text-orange-800 text-md font-medium me-2 px-3 py-2 rounded-full">
                    System Generated
                </span>
            </div>
        </template>

        <template #status="data">
            <JSwitch
                input-class="ml-0 mt-0"
                :is-checked="data.item.status"
                class="mt-[0px]"
                @update:is-checked="setStatus(data.item.id, $event)"
            />
        </template>

        <template #action="data">
            <div class="flex items-center justify-center">
                <div
                    v-if="state.parameters.status_value === allStatuses.active && data.item?.mystery_gift_id === null"
                    class="flex justify-center items-center"
                >
                    <Link
                        class="flex items-center mr-3"
                        :href="route('admin.promotions.edit', data.item.id)"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" />
                        Edit
                    </Link>

                    <Link
                        class="flex items-center mr-3"
                        :href="route('admin.promotions.clone', data.item.id)"
                    >
                        <Copy class="w-4 h-4 mr-2" />
                        Clone
                    </Link>
                </div>

                <div
                    class="flex justify-center items-center ml-2 cursor-pointer"
                    @click="showPromotionDetailModal(data.item.id)"
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

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayPromotionsFilter = !state.displayPromotionsFilter"
                />
            </p>
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
    </JTable>

    <PromotionViewDetails
        :modal-show="state.displayPromotionDetailsModal"
        :promotion-details="state.selectedPromotion"
        :item-wise-promotion-type="itemWisePromotionType"
        @close-modal="state.displayPromotionDetailsModal = false"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Copy, View, Info } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords, displayAmountWithCurrencySymbol } from '@commonServices/helper';
import JSwitch from '@commonComponents/JSwitch.vue';
import { router } from '@inertiajs/vue3';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import PromotionViewDetails from '@adminPages/promotions/partials/PromotionViewDetails.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import axios from 'axios';

const props = defineProps({
    itemWisePromotionType: {
        type: Object,
        required: true
    },
    locations: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
    promotionTypes: {
        type: Array,
        required: true,
    },
    promotionUserRestrictionType: {
        type: Array,
        required: true,
    },
    availabilityType: {
        type: Array,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    promotionId: {
        type: Number,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    allStatuses: {
        type: Object,
        required: true,
    },
    allTypes: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'id'
        }, {
            key: 'name',
            sortable: true,
        }, {
            key: 'promotion_type',
            label: 'Promotion Type',
        }, {
            key: 'timeframe_type',
        }, {
            key: 'usage',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    displayPromotionDetailsModal: false,
    selectedPromotion: [],
    refreshTableData: Math.random(),
    displayPromotionsFilter: false,
    displayPromotionsCalendar: false,
    parameters: {
        locations: [],
        location_ids: [],
        status_value: props.allStatuses.active,
        promotion_type: null,
        promotion_user_restriction_type: null,
        availability_type: null,
        type: props.allTypes.manual,
    },
});

const showPromotionDetailModal = (promotionId) => {
    state.selectedPromotion = [];
    axios.get(route('admin.promotions.fetch_promotions_details', promotionId))
        .then((response) => {
            state.selectedPromotion = response.data.promotion_details;
            state.displayPromotionDetailsModal = true;
        });
};

const setStatus = (promotionId, status) => {
    const delayMs = 1000;
    router.post(route('admin.promotions.set_status', [promotionId, status ? 1 : 0]), {}, {
        onSuccess: () => setTimeout(() => {
            refreshTable();
        }, delayMs)
    });
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateLocations = (locations) => {
    state.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });
    state.parameters.location_ids = locationIds;
    refreshTable();
};

const updateStatus = (status) => {
    state.parameters.status_value = parseInt(status);
    refreshTable();
};

const updateTypeId = (typeId) => {
    state.parameters.promotion_type = typeId !== null ? parseInt(typeId) : '';
    refreshTable();
};

const updatePromotionUserRestrictionType = (promotionUserRestrictionType) => {
    state.parameters.promotion_user_restriction_type = parseInt(promotionUserRestrictionType);
    refreshTable();
};

const updatePromotionAvailabilityType = (promotionAvailableType) => {
    state.parameters.availability_type = parseInt(promotionAvailableType);
    refreshTable();
};

const updatePromotionTypes = (promotionTypes) => {
    state.parameters.type = parseInt(promotionTypes);
    refreshTable();
};

const clearAll = () => {
    state.locations = null;
    state.parameters.location_ids = [];
    state.parameters.status_value = props.allStatuses.active;
    state.parameters.promotion_type = null;
    state.parameters.promotion_user_restriction_type = null;
    state.parameters.availability_type = null;
    state.parameters.type = props.allTypes.manual;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-promotions/',
        'promotions.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-promotions/',
        'promotions.xlsx',
        params,
        props.exportPermission
    );
};
</script>
