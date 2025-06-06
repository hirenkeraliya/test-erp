<template>
    <PageTitle title="Top Twenty Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <JMultiSelect
                :selected-records="state.locationIds"
                :records="locations"
                input-label="Locations"
                placeholder="Please select location(s)"
                :required="true"
                @update:selected-records="updateLocationId"
            />
        </div>

        <div class="w-full lg:w-1/2 px-3 mt-2 sm:mt-2 lg:mt-8">
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

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-2">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="topTwentyReportTypes"
                input-label="Report Type"
                placeholder="Please select Report Type"
                :required="true"
            />
        </div>

        <div
            v-if="state.parameters.report_type === topTwentyReportStaticTypes.byAttributes"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-2"
        >
            <FormSelectBox
                :selected-record="state.parameters.attribute_type"
                :records="attributes"
                :required="true"
                input-label="Attribute Types"
                @update:selected-record="updateAttributeId"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-2">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_view_type"
                :records="topTwentyReportViewTypes"
                input-label="Report View Type"
                placeholder="Please select Report View Type"
                :required="true"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.filter_by"
                :records="topTwentyFilters"
                input-label="Filter Type"
                placeholder="Select Filter Type"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === topTwentyFilterStaticDetails.byCounter"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :disabled="0 === state.locationIds.length"
                :selected-records="state.counterIds"
                :records="state.counters === null ? [] : state.counters"
                input-label="Counters"
                :placeholder="!state.locationIds.length ? 'Please select a Location First' : 'Please select Counter(s)'"
                @update:selected-records="updateCounterIds"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === topTwentyFilterStaticDetails.byCashier"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :disabled="0 === state.locationIds.length"
                :selected-records="state.cashierIds"
                :records="state.cashiers"
                input-label="Cashiers"
                :placeholder="!state.locationIds.length ? 'Please Select a Location first' : 'Please select Cashier(s)'"
                @update:selected-records="updateCashierIds"
            />
        </div>

        <div
            v-if="state.parameters.report_type"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JDatePicker
                v-model:input-value="state.parameters.date_range"
                :range-picker="true"
                :required="true"
                input-label="Date Range"
            />
        </div>

        <div
            v-if="state.parameters.report_type && state.parameters.report_type !== topTwentyReportStaticTypes.byProducts && state.parameters.report_type !== topTwentyReportStaticTypes.byMasterProduct"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-10">
                <FormCheckbox
                    :check-value="state.parameters.check_article_number"
                    check-label="By Article Number ?"
                    class="ml-2"
                    @change="updateCheckbox"
                />
                <Tippy
                    class="inline-flex items-center"
                    content="When an article number is selected, the report will be generated based on the parent article number. If no article number is selected, the report will be generated for each UPC individually."
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="16"
                    />
                </Tippy>
            </div>
        </div>

        <div
            v-if="state.parameters.report_type"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-10">
                <FormCheckbox
                    v-model:check-value="state.parameters.combine_stock_by_selected_location"
                    check-label="Combine Stock By Selected Location"
                    class="ml-2"
                />
            </div>
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
            <OutlineDangerButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="clearData"
            />

            <PrimaryButton
                type="button"
                text="PDF"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportSalesCollection"
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
import { reactive } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import { exportRecords, printReport } from '@commonServices/helper';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { Info } from 'lucide-vue-next';

const props = defineProps({
    topTwentyReportTypes: {
        type: Object,
        required: true,
    },
    topTwentyReportStaticTypes: {
        type: Object,
        required: true,
    },
    topTwentyReportViewTypes: {
        type: Object,
        required: true,
    },
    topTwentyReportViewStaticTypes: {
        type: Object,
        required: true,
    },
    topTwentyFilters: {
        type: Object,
        required: true,
    },
    topTwentyFilterStaticDetails: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => { },
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        location_ids: null,
        counter_ids: null,
        cashier_ids: [],
        date_range: null,
        report_type: null,
        report_view_type: null,
        check_article_number: false,
        combine_stock_by_selected_location: false,
        filter_by: null,
        attribute_type: null,
    },

    counterIds: [],
    cashierIds: [],
    locationIds: [],
    counters: [],
    cashiers: [],
});

const updateLocationId = (locationIds) => {
    state.locationIds = locationIds;
    state.parameters.location_ids = null;
    if (state.locationIds.length) {
        state.cashierIds = [];

        state.parameters.location_ids = state.locationIds.map((location) => {
            return location.id;
        });

        axios.post(route('admin.counters.get_counters_of_locations'), { locations_ids: state.parameters.location_ids })
            .then((response) => {
                state.counters = response.data.counters;
            });

        axios.post(route('admin.cashiers.get_cashiers_of_stores'), { location_ids: state.parameters.location_ids })
            .then((response) => {
                state.cashiers = response.data.cashiers;
            });
    }
};

const updateAttributeId = (attributeType) => {
    state.parameters.attribute_type = attributeType === null ? null : parseInt(attributeType);
};

const updateCounterIds = (counterIds) => {
    state.counterIds = counterIds;
    state.parameters.counter_ids = state.counterIds.map((counter) => {
        return counter.id;
    });
};

const updateCashierIds = (cashierIds) => {
    state.cashierIds = cashierIds;

    state.parameters.cashier_ids = state.cashierIds.map((cashier) => {
        return cashier.id;
    });
};

const updateCheckbox = () => {
    state.parameters.check_article_number = !state.parameters.check_article_number;
};

const selectAllLocations = () => {
    updateLocationId(props.locations);
    state.displayClearButton = true;
};

const clearAllLocations = () => {
    state.counters = [];
    state.cashiers = [];
    state.locationIds = [];
    state.counterIds = [];
    state.cashierIds = [];
    state.displayClearButton = false;
    state.parameters.location_ids = null;
    state.parameters.counter_ids = null;
    state.parameters.date_range = null;
    state.parameters.cashier_ids = null;
    state.parameters.report_type = null;
    state.parameters.filter_by = null;
    state.parameters.attribute_type = null;
    state.parameters.check_article_number = false;
    state.parameters.combine_stock_by_selected_location = false;
};

const validationCheck = () => {
    if (state.parameters.location_ids === null) {
        return true;
    }

    if (state.parameters.date_range === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    if (state.parameters.report_type === props.topTwentyReportStaticTypes.byAttributes && state.parameters.attribute_type === null) {
        return true;
    }

    if (state.parameters.report_view_type === null) {
        return true;
    }

    return false;
};

const exportSalesCollection = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a location, report type and a date before proceeding.');
        return;
    }

    state.parameters.cashier_ids = state.cashierIds.map((cashier) => {
        return cashier.id;
    });

    if (state.parameters.check_article_number === true) {
        state.parameters.check_article_number = 1;
    } else if (state.parameters.check_article_number === false) {
        state.parameters.check_article_number = 0;
    }

    if (state.parameters.combine_stock_by_selected_location === true) {
        state.parameters.combine_stock_by_selected_location = 1;
    } else if (state.parameters.combine_stock_by_selected_location === false) {
        state.parameters.combine_stock_by_selected_location = 0;
    }

    printReport(route('admin.custom_reports.print_top_twenty', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a location, report type and a date before proceeding..');
        return;
    }

    if (state.parameters.check_article_number === true) {
        state.parameters.check_article_number = 1;
    } else if (state.parameters.check_article_number === false) {
        state.parameters.check_article_number = 0;
    }

    if (state.parameters.combine_stock_by_selected_location === true) {
        state.parameters.combine_stock_by_selected_location = 1;
    } else if (state.parameters.combine_stock_by_selected_location === false) {
        state.parameters.combine_stock_by_selected_location = 0;
    }

    return exportRecords(
        'export-top-twenty/',
        'top-twenty.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a location, report type and a date before proceeding..');
        return;
    }

    if (state.parameters.check_article_number === true) {
        state.parameters.check_article_number = 1;
    } else if (state.parameters.check_article_number === false) {
        state.parameters.check_article_number = 0;
    }

    if (state.parameters.combine_stock_by_selected_location === true) {
        state.parameters.combine_stock_by_selected_location = 1;
    } else if (state.parameters.combine_stock_by_selected_location === false) {
        state.parameters.combine_stock_by_selected_location = 0;
    }

    return exportRecords(
        'export-top-twenty/',
        'top-twenty.csv',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};
</script>
