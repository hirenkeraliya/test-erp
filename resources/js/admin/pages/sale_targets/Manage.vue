<template>
    <PageTitle :title="saleTarget ? 'Edit Sale Target' : 'Add Sale Target'" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Targets
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        {{ saleTarget ? 'Edit' : 'Add' }} Sale Target
                    </h2>
                    <SecondaryButton
                        type="button"
                        text="Clear"
                        class="w-24"
                        @click="clearFormData"
                    />
                </div>

                <form @submit.prevent="saveSaleTarget();">
                    <div class="p-5">
                        <div class="text-lg font-medium border-b">
                            Basic Details
                        </div>
                        <div class="grid grid-cols-12 gap-0 sm:gap-8">
                            <div class="input-form col-span-12 sm:col-span-8 md:col-span-8 lg:col-span-6 xl:col-span-4">
                                <FormInput
                                    v-model:input-value="saleTargetForm.name"
                                    input-name="name"
                                    input-label="Name"
                                    :required="true"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center p-5 mt-5 bg-slate-100 border-b border-slate-200/60" />

                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-8">
                            <div class="col-span-12 md:col-span-6">
                                <div class="text-lg font-medium border-b">
                                    Target Section
                                </div>

                                <div class="grid grid-rows-1 gap-0 sm:gap-2">
                                    <div class="input-form col-span-12 w-full md:w-3/4">
                                        <FormSelectBox
                                            :selected-record="saleTargetForm.target_type"
                                            :records="targetTypes"
                                            input-label="Target Type"
                                            validation-field-name="target_type"
                                            :required="true"
                                            @update:selected-record="updateTargetType"
                                        />
                                    </div>

                                    <div
                                        v-if="staticTargetTypes.storeWise === saleTargetForm.target_type"
                                        class="input-form col-span-12 w-full md:w-3/4"
                                    >
                                        <JTabs
                                            :records="state.storeTypes"
                                            :selected-record="saleTargetForm.store_type"
                                            :required="true"
                                            input-label="Location Type"
                                            return-selected-record="id"
                                            label-class="block text-primary-p3 mt-2"
                                            @update:selected-record="updateStoreType"
                                        />
                                    </div>

                                    <div
                                        v-if="staticTargetTypes.storeWise === saleTargetForm.target_type && saleTargetForm.store_type === saleTargetStoreTypes.select"
                                        class="input-form col-span-12 w-full md:w-3/4"
                                    >
                                        <JMultiSelect
                                            :records="regions"
                                            input-label="Regions"
                                            :selected-records="saleTargetForm.regions"
                                            @update:selected-records="updateRegionId"
                                        />

                                        <JMultiSelect
                                            :records="state.filterLocations"
                                            :input-label="saleTargetForm.regions.length > 0 ? 'Regions Locations' : 'All Locations'"
                                            :required="true"
                                            validation-field-name="location_ids"
                                            :selected-records="saleTargetForm.locations"
                                            @update:selected-records="updateLocationId"
                                        />
                                    </div>

                                    <div
                                        v-if="staticTargetTypes.storeWise === saleTargetForm.target_type && saleTargetForm.store_type === saleTargetStoreTypes.upload"
                                        class="input-form col-span-12 flex"
                                    >
                                        <JFileUpload
                                            v-model:input-file="saleTargetForm.upload_locations"
                                            accept=".xlsx, .xls, .ods"
                                            input-label="Upload Locations"
                                            validation-field-name="upload_locations"
                                            @update:input-file="uploadStore($event)"
                                        />

                                        <div>
                                            <JFileDownload
                                                file-path="/files/sale-target-locations-sample-file.xlsx"
                                                input-label="Download Sample File"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        v-if="staticTargetTypes.promoterWise === saleTargetForm.target_type"
                                        class="input-form col-span-12 w-full md:w-3/4"
                                    >
                                        <JTabs
                                            :records="state.promoterTypes"
                                            :selected-record="saleTargetForm.promoter_type"
                                            :required="true"
                                            input-label="Promoter Type"
                                            return-selected-record="id"
                                            label-class="block text-primary-p3 mt-2"
                                            @update:selected-record="updatePromoterType"
                                        />
                                    </div>

                                    <div
                                        v-if="staticTargetTypes.promoterWise === saleTargetForm.target_type && saleTargetForm.promoter_type === saleTargetPromoterTypes.select"
                                        class="input-form col-span-12"
                                    >
                                        <div class="w-full md:w-3/4">
                                            <JMultiSelect
                                                :selected-records="saleTargetForm.promoter_locations"
                                                :records="locations"
                                                input-label="Locations"
                                                @update:selected-records="updatePromoterLocationId"
                                            />
                                        </div>

                                        <JMultiSelect
                                            :selected-records="saleTargetForm.promoters"
                                            :records="state.filterPromoters"
                                            :input-label="saleTargetForm.promoter_locations.length > 0 ? 'Location Wise Promoters' : 'All Promoters'"
                                            :required="true"
                                            validation-field-name="promoter_ids"
                                            @update:selected-records="updatePromoterId"
                                        />
                                    </div>

                                    <div
                                        v-if="staticTargetTypes.promoterWise === saleTargetForm.target_type && saleTargetForm.promoter_type === saleTargetPromoterTypes.upload"
                                        class="input-form col-span-12 flex"
                                    >
                                        <JFileUpload
                                            v-model:input-file="saleTargetForm.upload_promoters"
                                            accept=".xlsx, .xls, .ods"
                                            input-label="Upload Promoters"
                                            validation-field-name="upload_promoters"
                                            @update:input-file="uploadPromoter($event)"
                                        />

                                        <div>
                                            <JFileDownload
                                                file-path="/files/sale-target-promoters-sample-file.xlsx"
                                                input-label="Download Sample File"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        v-if="staticTargetTypes.promoterWise === saleTargetForm.target_type && saleTargetForm.promoter_type === saleTargetPromoterTypes.select || staticTargetTypes.storeWise === saleTargetForm.target_type && saleTargetForm.store_type === saleTargetStoreTypes.select"
                                        class="input-form"
                                    >
                                        <PrimaryButton
                                            type="button"
                                            text="Select all"
                                            class="w-auto sm:w-24 md:w-1/1 mr-2"
                                            @click="selectAllLocationsAndPromoters"
                                        />

                                        <OutlinePrimaryButton
                                            v-if="saleTargetForm.locations.length > 0 || saleTargetForm.promoters.length"
                                            type="button"
                                            text="Clear All"
                                            class="w-auto sm:w-24 md:w-1/1 mt-2"
                                            @click="clearAllLocationsAndPromoters"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 md:col-span-6 md:border-l md:border-gray-300 md:pl-4 lg:pl-14 mt-5 md:mt-0">
                                <div class="text-lg font-medium border-b">
                                    Time Interval Section
                                </div>

                                <div class="grid grid-rows-1 gap-0 sm:gap-2">
                                    <div class="input-form col-span-12 w-full md:w-3/4">
                                        <FormSelectBox
                                            :selected-record="saleTargetForm.time_interval_type"
                                            :records="timeIntervalTypes"
                                            input-label="Time Interval Types"
                                            validation-field-name="time_interval_type"
                                            :required="true"
                                            @update:selected-record="updateTimeIntervalType"
                                        />
                                    </div>

                                    <div class="input-form col-span-12 w-full md:w-3/4">
                                        <JTabs
                                            :records="state.amountTypes"
                                            :selected-record="saleTargetForm.amount_type"
                                            :required="true"
                                            input-label="Amount Type"
                                            return-selected-record="id"
                                            label-class="block text-primary-p3 mt-3"
                                            @update:selected-record="updateAmountType"
                                        />
                                    </div>

                                    <div
                                        v-if="checkTimeFrameIsMonthlyOrWeekly()"
                                        class="input-form col-span-12 w-full md:w-3/4"
                                    >
                                        <TabPanel
                                            :v-if="saleTargetForm.amount_type === saleTargetAmountTypes.amount"
                                        >
                                            <FormInput
                                                v-if="saleTargetForm.amount_type === saleTargetAmountTypes.amount"
                                                v-model:input-value="saleTargetForm.amount"
                                                type="number"
                                                input-name="amount"
                                                input-label="Amount"
                                                label-class="block text-primary-p3"
                                                :input-group-prefix="currencySymbol"
                                            />
                                        </TabPanel>

                                        <TabPanel
                                            :v-if="saleTargetForm.amount_type === saleTargetAmountTypes.percentage"
                                        >
                                            <FormInput
                                                v-if="saleTargetForm.amount_type === saleTargetAmountTypes.percentage"
                                                v-model:input-value="saleTargetForm.percentage"
                                                :readonly="staticTimeIntervalTypes.weekly === saleTargetForm.time_interval_type || staticTimeIntervalTypes.monthly === saleTargetForm.time_interval_type"
                                                type="number"
                                                input-name="percentage"
                                                input-label="Percentage"
                                                input-group-suffix="%"
                                                label-class="block text-primary-p3"
                                                title="When you select a timeframe, our system considers historical data, analyzing past net sales within that period, and adds a specified percentage to provide you with a target value."
                                            />
                                        </TabPanel>
                                    </div>

                                    <div
                                        v-if="staticTimeIntervalTypes.daily === saleTargetForm.time_interval_type || staticTimeIntervalTypes.customPeriod === saleTargetForm.time_interval_type"
                                        class="input-form col-span-12 w-full md:w-3/4"
                                    >
                                        <JDatePicker
                                            :range-picker="true"
                                            :input-value="saleTargetForm.dates"
                                            input-label="Date"
                                            validation-field-name="dates"
                                            :required="true"
                                            @update:input-value="updateDate($event)"
                                        />
                                    </div>

                                    <div
                                        v-if="staticTimeIntervalTypes.monthly === saleTargetForm.time_interval_type"
                                        class="input-form col-span-12 w-full md:w-3/4"
                                    >
                                        <SaleTargetMonthlyTiers
                                            :tiers="saleTargetForm.month_tiers"
                                            :amount-type="saleTargetForm.amount_type"
                                            :sale-target-amount-types="saleTargetAmountTypes"
                                            validation-field-name="month_tiers"
                                            @update:column-details="updateColumnDetails"
                                            @update:tier-value-details="updateTierValueDetails"
                                            @add:new-tier-details="addNewTierDetails"
                                            @remove:tier-details-of="removeTierDetailsOf"
                                        />
                                    </div>

                                    <div
                                        v-if="staticTimeIntervalTypes.weekly === saleTargetForm.time_interval_type"
                                        class="input-form col-span-12"
                                    >
                                        <SaleTargetWeeklyTiers
                                            :tiers="saleTargetForm.week_tiers"
                                            :amount-type="saleTargetForm.amount_type"
                                            :sale-target-amount-types="saleTargetAmountTypes"
                                            validation-field-name="week_tiers"
                                            @update:column-details="updateColumnDetails"
                                            @update:tier-value-details="updateTierValueWeeklyDetails"
                                            @add:new-tier-details="addNewTierWeeklyDetails"
                                            @remove:tier-details-of="removeTierDetailsOfWeekly"
                                        />
                                    </div>

                                    <div
                                        v-if="staticTimeIntervalTypes.yearly === saleTargetForm.time_interval_type"
                                        class="input-form col-span-12 w-1/2"
                                    >
                                        <JYearPicker
                                            :input-value="saleTargetForm.year"
                                            input-label="Year"
                                            validation-field-name="year"
                                            :required="true"
                                            @update:input-value="updateYear($event)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 px-4 pb-5">
                        <Link :href="route('admin.sale_targets.index')">
                            <SecondaryButton
                                type="button"
                                text="Cancel"
                                class="w-24 mr-1"
                            />
                        </Link>

                        <PrimaryButton
                            type="submit"
                            :text="saleTarget ? 'Update' : 'Submit'"
                            class="w-24"
                        />
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script setup>
import SaleTargetMonthlyTiers from '@adminPages/sale_targets/SaleTargetMonthlyTiers.vue';
import SaleTargetWeeklyTiers from '@adminPages/sale_targets/SaleTargetWeeklyTiers.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JFileDownload from '@commonComponents/JFileDownload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JTabs from '@commonComponents/JTabs.vue';
import JYearPicker from '@commonComponents/JYearPicker.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { removeLocalStorage, saveLocalStorage, setLocalStorage } from '@commonServices/helper';
import { showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import { TabPanel } from '@commonVendor/tab';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, reactive, watch } from 'vue';
import XLSX from 'xlsx';
import { route } from 'ziggy';

const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

const props = defineProps({
    saleTarget: {
        type: Object,
        default: null,
    },
    company: {
        type: Object,
        default: null,
    },
    locations: {
        type: Array,
        required: true,
    },
    regions: {
        type: Array,
        required: true,
    },
    promoters: {
        type: Array,
        required: true,
    },
    targetTypes: {
        type: Array,
        required: true,
    },
    timeIntervalTypes: {
        type: Array,
        required: true,
    },
    staticTargetTypes: {
        type: Object,
        required: true,
    },
    staticTimeIntervalTypes: {
        type: Object,
        required: true,
    },
    saleTargetAmountTypes: {
        type: Object,
        default: null
    },
    saleTargetStoreTypes: {
        type: Object,
        default: null
    },
    saleTargetPromoterTypes: {
        type: Object,
        default: null
    },
    targetType: {
        type: String,
        default: null
    },
    timeIntervalSelection: {
        type: String,
        default: null
    },
});

const state = reactive({
    amountTypes: [
        { id: props.saleTargetAmountTypes.amount, name: 'Amount' },
        { id: props.saleTargetAmountTypes.percentage, name: 'Percentage' },
    ],
    storeTypes: [
        { id: props.saleTargetStoreTypes.select, name: 'Select' },
        { id: props.saleTargetStoreTypes.upload, name: 'Upload' },
    ],
    promoterTypes: [
        { id: props.saleTargetPromoterTypes.select, name: 'Select' },
        { id: props.saleTargetPromoterTypes.upload, name: 'Upload' },
    ],
    filterLocations: props.locations,
    filterPromoters: props.promoters,
});

const saleTargetForm = useForm({
    name: null,
    username: null,
    amount: null,
    percentage: null,
    amount_type: props.saleTargetAmountTypes.amount,
    store_type: props.saleTargetStoreTypes.select,
    promoter_type: props.saleTargetPromoterTypes.select,
    target_type: props.targetType,
    time_interval_type: props.timeIntervalSelection,
    status: true,
    location_ids: [],
    promoter_ids: [],
    locations: [],
    promoters: [],
    promoter_locations: [],
    regions: [],
    dates: [],
    watchEnabled: true,
    month_tiers: [],
    week_tiers: [],
    year: null,
    upload_locations: null,
    upload_promoters: null
});

const updateAmountType = (amountType) => {
    saleTargetForm.amount_type = amountType;
};

const updateStoreType = (storeType) => {
    saleTargetForm.store_type = storeType;
};

const updatePromoterType = (promoterType) => {
    saleTargetForm.promoter_type = promoterType;
};

const addNewTierDetails = () => {
    saleTargetForm.month_tiers.push({ months: null });
};

const updateTierValueDetails = (details) => {
    saleTargetForm.month_tiers[details.key][details.column_name] = details.value;
    calculateMonthlyAmountOrPercentage();
};

const removeTierDetailsOf = (key) => {
    saleTargetForm.month_tiers.splice(key, 1);
    calculateMonthlyAmountOrPercentage();
};

const updateColumnDetails = (details) => {
    const columnName = details.column_name;
    saleTargetForm[columnName] = details.value;
};

const addNewTierWeeklyDetails = () => {
    saleTargetForm.week_tiers.push({ weeks: null });
};

const updateTierValueWeeklyDetails = (details) => {
    saleTargetForm.week_tiers[details.key][details.column_name] = details.value;
    calculateWeeklyAmountOrPercentage();
};

const removeTierDetailsOfWeekly = (key) => {
    saleTargetForm.week_tiers.splice(key, 1);
    calculateWeeklyAmountOrPercentage();
};

const calculateWeeklyAmountOrPercentage = () => {
    let amount = 0;
    let percentage = 0;
    saleTargetForm.week_tiers.forEach(weekTier => {
        if (saleTargetForm.amount_type === props.saleTargetAmountTypes.amount && typeof weekTier.amount !== 'undefined') {
            amount += parseFloat(weekTier.amount);
        }

        if (saleTargetForm.amount_type === props.saleTargetAmountTypes.percentage && typeof weekTier.percentage !== 'undefined') {
            percentage += parseFloat(weekTier.percentage);
        }
    });

    saleTargetForm.amount = amount > 0 ? amount : null;
    saleTargetForm.percentage = percentage > 0 ? percentage : null;
};

const calculateMonthlyAmountOrPercentage = () => {
    let amount = 0;
    let percentage = 0;
    saleTargetForm.month_tiers.forEach(weekTier => {
        if (saleTargetForm.amount_type === props.saleTargetAmountTypes.amount && typeof weekTier.amount !== 'undefined') {
            amount += parseFloat(weekTier.amount);
        }

        if (saleTargetForm.amount_type === props.saleTargetAmountTypes.percentage && typeof weekTier.percentage !== 'undefined') {
            percentage += parseFloat(weekTier.percentage);
        }
    });

    saleTargetForm.amount = amount > 0 ? amount : null;
    saleTargetForm.percentage = percentage > 0 ? percentage : null;
};

const saveSaleTarget = () => {
    if (props.saleTargetAmountTypes.amount === saleTargetForm.amount_type) {
        saleTargetForm.percentage = null;
    }

    if (props.saleTargetAmountTypes.percentage === saleTargetForm.amount_type) {
        saleTargetForm.amount = null;
    }

    saleTargetForm.watchEnabled = false;
    removeLocalStorage('saleTarget');

    if (props.saleTarget) {
        saleTargetForm.put(route('admin.sale_targets.update', props.saleTarget.id));
        return;
    }
    saleTargetForm.post(route('admin.sale_targets.store'));
};

const updateTargetType = (targetType) => {
    saleTargetForm.target_type = targetType;
    if (props.staticTargetTypes.storeWise === targetType) {
        saleTargetForm.promoter_ids = [];
    }

    if (props.staticTargetTypes.promoterWise === targetType) {
        saleTargetForm.location_ids = [];
    }
    saleTargetForm.promoter_ids = [];
    saleTargetForm.location_ids = [];
};

const updateTimeIntervalType = (timeIntervalType) => {
    saleTargetForm.time_interval_type = timeIntervalType;

    if (props.staticTimeIntervalTypes.daily === timeIntervalType || props.staticTimeIntervalTypes.customPeriod === timeIntervalType) {
        saleTargetForm.dates = [];
        saleTargetForm.month_tiers = [];
        saleTargetForm.week_tiers = [];
        saleTargetForm.year = null;
    }
    if (props.staticTimeIntervalTypes.weekly === timeIntervalType) {
        saleTargetForm.dates = [];
        saleTargetForm.month_tiers = [];
        saleTargetForm.year = null;
        addNewTierWeeklyDetails();
    }
    if (props.staticTimeIntervalTypes.monthly === timeIntervalType) {
        saleTargetForm.dates = [];
        saleTargetForm.week_tiers = [];
        saleTargetForm.year = null;
        addNewTierDetails();
    }
    if (props.staticTimeIntervalTypes.yearly === timeIntervalType) {
        saleTargetForm.dates = [];
        saleTargetForm.week_tiers = [];
        saleTargetForm.month_tiers = [];
    }
};

const updateLocationId = (locationIds) => {
    saleTargetForm.promoters = [];
    saleTargetForm.promoter_ids = [];

    saleTargetForm.locations = locationIds;
    saleTargetForm.location_ids = saleTargetForm.locations.map((location) => {
        return location.id;
    });
};

const updateRegionId = (regionIds) => {
    saleTargetForm.locations = [];
    saleTargetForm.location_ids = [];
    saleTargetForm.regions = regionIds;
    state.filterLocations = props.locations;
    if (saleTargetForm.regions.length > 0) {
        const regionIds = [];
        saleTargetForm.regions.forEach(region => {
            regionIds.push(region.id);
        });
        state.filterLocations = state.filterLocations.filter(record => regionIds.includes(record.region_id));
    }
};

const updatePromoterId = (promoterIds) => {
    saleTargetForm.locations = [];
    saleTargetForm.location_ids = [];

    saleTargetForm.promoters = promoterIds;
    saleTargetForm.promoter_ids = saleTargetForm.promoters.map((promoter) => {
        return promoter.id;
    });
};

const updatePromoterLocationId = (locationIds) => {
    saleTargetForm.promoters = [];
    saleTargetForm.promoter_ids = [];

    saleTargetForm.promoter_locations = locationIds;
    state.filterPromoters = props.promoters;

    if (saleTargetForm.promoter_locations.length > 0) {
        const locationIds = [];

        saleTargetForm.promoter_locations.forEach(location => {
            locationIds.push(location.id);
        });

        axios.get(route('admin.promoters.get_promoters_by_location_ids',
            { location_ids: locationIds }
        ))
            .then((response) => {
                state.filterPromoters = response.data.promoters;
            });
    }
};

const selectAllLocationsAndPromoters = () => {
    if (props.staticTargetTypes.storeWise === saleTargetForm.target_type) {
        updateLocationId(state.filterLocations ?? props.locations);
    }
    if (props.staticTargetTypes.promoterWise === saleTargetForm.target_type) {
        updatePromoterId(state.filterPromoters ?? props.promoters);
    }
};

const clearAllLocationsAndPromoters = () => {
    saleTargetForm.locations = [];
    saleTargetForm.location_ids = [];
    saleTargetForm.promoters = [];
    saleTargetForm.promoter_ids = [];
};

const updateDate = (selectedDate) => {
    saleTargetForm.dates = selectedDate;
};

const updateYear = (selectedYear) => {
    saleTargetForm.year = selectedYear;
};

onMounted(() => {
    if (props.saleTarget) {
        removeLocalStorage('saleTarget');
        // https://github.com/inertiajs/inertia/issues/854#issuecomment-1084587544
        Object.assign(saleTargetForm, props.saleTarget);
    } else {
        setLocalStorage('saleTarget', saleTargetForm);
    }
});

const checkSaveLocalStorage = () => {
    if (!props.saleTarget) {
        saveLocalStorage('saleTarget', saleTargetForm);
    }
};

const clearFormData = () => {
    saleTargetForm.reset();
};

watch(saleTargetForm, () => {
    if (saleTargetForm.watchEnabled) {
        checkSaveLocalStorage();
    }
}, { deep: true });

const uploadStore = (files) => {
    const reader = new FileReader();

    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const records = JSON.parse(JSON.stringify(XLSX.utils.sheet_to_json(worksheet, {
            blankRows: false,
            defval: null,
        })).replace(/"\s+|\s+"/g, '"'));

        const storeNames = [];

        records.forEach(record => {
            if ('name' in record) {
                storeNames.push(record.name.toString());
            }
        });

        if (storeNames.length === 0) {
            showErrorNotification('Locations not found.');
        } else {
            axios.post(route('admin.locations.get_locations_of_locations_name'), { names: storeNames })
                .then((response) => {
                    if (response.data.locations.length > 0) {
                        saleTargetForm.locations = response.data.locations;
                        saleTargetForm.location_ids = saleTargetForm.locations.map((location) => {
                            return location.id;
                        });

                        showSuccessNotification('Location names uploaded and selected successfully.');
                    } else {
                        showErrorNotification('Unable to find location names in the location.');
                    }
                }).catch((error) => {
                    showErrorNotification(error);
                });
        }
    };

    reader.readAsArrayBuffer(files);

    saleTargetForm.store_type = props.saleTargetStoreTypes.select;
    saleTargetForm.upload_locations = null;
};

const uploadPromoter = (files) => {
    const reader = new FileReader();

    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const records = JSON.parse(JSON.stringify(XLSX.utils.sheet_to_json(worksheet, {
            blankRows: false,
            defval: null,
        })).replace(/"\s+|\s+"/g, '"'));

        const staffIds = [];

        records.forEach(record => {
            if ('staff_id' in record) {
                staffIds.push(record.staff_id.toString());
            }
        });

        if (staffIds.length === 0) {
            showErrorNotification('Promoter not found.');
        } else {
            axios.post(route('admin.promoters.get_promoters_of_staff_ids'), { staffIds })
                .then((response) => {
                    if (response.data.promoters.length > 0) {
                        saleTargetForm.promoters = response.data.promoters;
                        saleTargetForm.promoter_ids = saleTargetForm.promoters.map((promoter) => {
                            return promoter.id;
                        });

                        showSuccessNotification('Promoter names uploaded and selected successfully.');
                    } else {
                        showErrorNotification('Unable to find promoter names in the promoter.');
                    }
                }).catch((error) => {
                    showErrorNotification(error);
                });
        }
    };

    reader.readAsArrayBuffer(files);

    saleTargetForm.promoter_type = props.saleTargetPromoterTypes.select;
    saleTargetForm.upload_promoters = null;
};

const checkTimeFrameIsMonthlyOrWeekly = () => {
    if (props.staticTimeIntervalTypes.weekly === saleTargetForm.time_interval_type) {
        return false;
    }

    if (props.staticTimeIntervalTypes.monthly === saleTargetForm.time_interval_type) {
        return false;
    }

    return true;
};
</script>
