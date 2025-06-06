<template>
    <InfoAlert
        v-if="state.parameters.report_type_id === seasonalReportStaticTypes.bySummary || state.parameters.report_type_id === seasonalReportStaticTypes.byComparison"
        color="primary"
    >
        <span class="flex">
            Balance To Achieve = Sale Season - Compare Sale Season
        </span>
        <span class="flex">
            Achievement (%) = ((Sale Season - Compare Sale Season) / Sale Season) * 100
        </span>
    </InfoAlert>

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type_id"
                :records="seasonalReportTypes"
                input-label="ReportType"
                :required="true"
                placeholder="Please select report type"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.sale_season_id"
                :records="saleSeasons"
                input-label="Sale Season"
                :required="true"
                placeholder="Please select sale season"
                @update:selected-record="setRangeForSaleSeasonDateRange(state.parameters.sale_season_id); updateSaleSeason(state.parameters.sale_season_id);"
            />
        </div>

        <div
            v-if="state.parameters.sale_season_id && (state.parameters.report_type_id === seasonalReportStaticTypes.byComparison || state.parameters.report_type_id === seasonalReportStaticTypes.bySummary)"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JDatePicker
                v-model:input-value="state.parameters.sale_season_date_range"
                :range-picker="true"
                :range="true"
                :min-date="state.parameters.sale_season_date_range[0]"
                :max-date="state.parameters.sale_season_date_range[1]"
                input-name="sale_season_date_range"
                input-label="Sale Season Date Range"
            />
        </div>

        <div
            v-if="state.parameters.report_type_id === seasonalReportStaticTypes.bySummary || state.parameters.report_type_id === seasonalReportStaticTypes.byComparison"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                v-model:selected-record="state.parameters.compare_sale_season_id"
                :records="state.saleSeasons"
                input-label="Compare Sale Season"
                :required="true"
                placeholder="Please select compare sale season"
                @update:selected-record="setRangeForCompareSaleSeasonDateRange(state.parameters.compare_sale_season_id)"
            />
        </div>

        <div
            v-if="state.parameters.sale_season_compare_date_range && (state.parameters.report_type_id === seasonalReportStaticTypes.byComparison || state.parameters.report_type_id === seasonalReportStaticTypes.bySummary)"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JDatePicker
                v-model:input-value="state.parameters.sale_season_compare_date_range"
                :range-picker="true"
                :range="true"
                :min-date="state.parameters.sale_season_compare_date_range[0]"
                :max-date="state.parameters.sale_season_compare_date_range[1]"
                input-name="sale_season_date_range"
                input-label="Sale Compare Season Date Range"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <JMultiSelect
                :selected-records="state.locationIds"
                :records="locations"
                input-label="Locations"
                placeholder="Please select location(s)"
                @update:selected-records="updateLocationId"
            />
        </div>

        <div class="w-full lg:w-1/2 px-0 mt-2 sm:mt-2 lg:mt-8">
            <PrimaryButton
                type="button"
                text="Select all"
                class="w-auto sm:w-24 md:w-1/1"
                @click="selectAllLocations"
            />

            <OutlinePrimaryButton
                v-if="state.displayClearButton"
                type="button"
                text="Clear All"
                class="w-auto sm:w-24 md:w-1/1 mt-2"
                @click="clearAllLocations"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mx-4">
            <JMultiSelect
                :selected-records="state.brandIds"
                :records="brands"
                input-label="Brands"
                placeholder="Please select brand(s)"
                @update:selected-records="updateBrandId"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
            <OutlineDangerButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="clearData"
            />

            <PrimaryButton
                v-if="state.parameters.report_type_id !== seasonalReportStaticTypes.byComparison"
                type="button"
                text="PDF"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="printSaleHour"
            />

            <PrimaryButton
                type="button"
                text="Excel"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportExcelRecord"
            />

            <PrimaryButton
                type="button"
                text="CSV"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportCsvRecord"
            />
        </div>
    </div>
</template>

<script setup>
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords, printReport } from '@commonServices/helper';
import { reactive } from 'vue';
import { route } from 'ziggy';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { showErrorNotification } from '@commonServices/notifier';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    },
    saleSeasons: {
        type: Array,
        required: true,
    },
    seasonalReportTypes: {
        type: Array,
        required: true,
    },
    seasonalReportStaticTypes: {
        type: Object,
        required: true,
    }
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    locationIds: [],
    saleSeasons: props.saleSeasons,
    parameters: {
        location_ids: null,
        brand_ids: null,
        report_type_id: null,
        sale_season_id: null,
        compare_sale_season_id: null,
        sale_season_date_range: [],
        sale_season_compare_date_range: null,
    },
    displayClearButton: false
});

const clearData = () => {
    emits('update:clear-button');
};

const printSaleHour = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and sale season');
        return;
    }

    if (checkErrorNotification()) {
        return;
    }

    printReport(route('admin.custom_reports.seasonal_sales_print', state.parameters));
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and sale season');
        return;
    }

    if (checkErrorNotification()) {
        return;
    }

    return exportRecords(
        'export-seasonal-sales/',
        'seasonal-sales-report.csv',
        state.parameters
    );
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and sale season');
        return;
    }

    if (checkErrorNotification()) {
        return;
    }

    return exportRecords(
        'export-seasonal-sales/',
        'seasonal-sales-report.xlsx',
        state.parameters
    );
};

const updateLocationId = (locationIds) => {
    state.locationIds = locationIds;
    state.parameters.location_ids = null;

    if (state.locationIds.length) {
        state.parameters.location_ids = state.locationIds.map((location) => {
            return location.id;
        });
    }
};

const updateBrandId = (brandIds) => {
    state.brandIds = brandIds;
    state.parameters.brand_ids = null;

    if (state.brandIds.length) {
        state.parameters.brand_ids = state.brandIds.map((brand) => {
            return brand.id;
        });
    }
};

const validationCheck = () => {
    if (state.parameters.report_type_id === null) {
        return true;
    }

    if (state.parameters.sale_season_id === null) {
        return true;
    }

    return false;
};

const updateSaleSeason = (saleSeasonId) => {
    state.parameters.compare_sale_season_id = null;
    state.saleSeasons = props.saleSeasons;
    state.saleSeasons = state.saleSeasons.filter(season => season.id !== saleSeasonId);
};

const setRangeForSaleSeasonDateRange = (saleSeasonId) => {
    const selectedSeason = state.saleSeasons.find(season => season.id === saleSeasonId);
    if (selectedSeason) {
        state.parameters.sale_season_date_range = [selectedSeason.start_date, selectedSeason.end_date];
    } else {
        state.parameters.sale_season_date_range = null;
    }
};

const setRangeForCompareSaleSeasonDateRange = (saleSeasonId) => {
    const selectedSeason = state.saleSeasons.find(season => season.id === saleSeasonId);
    if (selectedSeason) {
        state.parameters.sale_season_compare_date_range = [selectedSeason.start_date, selectedSeason.end_date];
    } else {
        state.parameters.sale_season_compare_date_range = null;
    }
};

const checkErrorNotification = () => {
    if (state.parameters.compare_sale_season_id === null && (state.parameters.report_type_id === props.seasonalReportStaticTypes.bySummary || state.parameters.report_type_id === props.seasonalReportStaticTypes.byComparison)) {
        showErrorNotification('Please select a compare sale season');
        return true;
    }
    return false;
};

const selectAllLocations = () => {
    updateLocationId(props.locations);
    state.displayClearButton = true;
};

const clearAllLocations = () => {
    state.locationIds = [];
    state.parameters.location_ids = null;
    state.displayClearButton = false;
};
</script>
