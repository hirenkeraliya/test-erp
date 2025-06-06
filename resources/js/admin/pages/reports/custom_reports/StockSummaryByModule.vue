<template>
    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_by"
                :records="stockSummaryByModuleReportBy"
                input-label="Report By"
                :required="true"
                placeholder="Report By"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                <FormSelectBox
                    v-model:selected-record="state.parameters.report_type"
                    :records="stockSummaryByModuleReportType"
                    input-label="Report Type"
                    :required="true"
                    placeholder="Report Type"
                />
            </div>
        </div>

        <div
            v-if="state.parameters.report_type && state.parameters.report_by"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.location_ids"
                :records="allLocations"
                input-label="Locations"
                placeholder="Please select store"
                @update:selected-records="updateLocation"
            />
        </div>

        <div
            v-if="state.parameters.report_type && state.parameters.report_by"
            class="w-full lg:w-1/2 px-3 mt-2 sm:mt-2 lg:mt-8"
        >
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
    </div>

    <div
        v-if="state.parameters.report_type && state.parameters.report_by"
        class="grid grid-cols-12 gap-0 sm:gap-6"
    >
        <div
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JDatePicker
                v-model:input-value="state.parameters.date_range"
                :range-picker="true"
                :required="true"
                input-label="Date Filter"
            />
        </div>

        <div
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormAjaxSelect
                :selected-record="state.selectArticleNumbers"
                :search-records="searchArticleNumber"
                track-by="article_number"
                label="article_number"
                input-label="Article Number"
                label-class=""
                :multi-select="true"
                placeholder="Please type the article number of the product to search."
                @update:selected-record="selectArticleNumbers"
            />
        </div>

        <div
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.brandIds"
                :records="brands"
                input-label="Brands"
                placeholder="Please select brand(s)"
                @update:selected-records="updateBrandId"
            />
        </div>

        <div
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.departmentIds"
                :records="departments"
                input-label="Departments"
                :placeholder="'Please select Department(s)'"
                @update:selected-records="updateDepartmentIds"
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
                type="button"
                text="PDF"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportStockSummaryByModule"
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
import { showErrorNotification } from '@commonServices/notifier';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { route } from 'ziggy';
import { exportRecords, printReport } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';

const props = defineProps({
    allLocations: {
        type: Array,
        required: true,
    },
    stockSummaryByModuleReportBy: {
        type: Object,
        required: true,
    },
    stockSummaryByModuleReportType: {
        type: Object,
        required: true,
    },
    brands: {
        type: Object,
        required: true,
    },
    departments: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        report_by: null,
        report_type: null,
        location_ids: null,
        date_range: null,
        article_number: null,
        brand_ids: null,
        department_ids: null,
    },
    allLocations: [],
    brandIds: [],
    departmentIds: [],
    selectArticleNumbers: [],
    errorMessage: null,
});

const clearData = () => {
    emits('update:clear-button');
};

const validationCheck = () => {
    state.errorMessage = null;

    if (state.parameters.date_range === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    if (state.parameters.report_by === null) {
        return true;
    }

    return false;
};

const exportStockSummaryByModule = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, report type and a date before proceeding.');
        return;
    }

    printReport(route('admin.custom_reports.print_stock_summary_by_module', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, status and a date before proceeding..');
        return;
    }

    return exportRecords(
        'export-stock-summary-by-module/',
        'stock-summary-by-module-report.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, status and a date before proceeding..');
        return;
    }

    return exportRecords(
        'export-stock-summary-by-module/',
        'stock-summary-by-module-report.csv',
        state.parameters
    );
};

const updateLocation = (allLocations) => {
    state.location_ids = allLocations;
    state.parameters.location_ids = allLocations.map((location) => location.id);
};

const selectAllLocations = () => {
    updateLocation(props.allLocations);
    state.displayClearButton = true;
    return;
};

const clearAllLocations = () => {
    state.location_ids = [];
    state.parameters.location_ids = null;
    state.displayClearButton = false;
};

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    const minSearchLength = 3;

    if (searchText.length >= minSearchLength) {
        axios.post(route('admin.products.get_filtered_article_number'), filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectArticleNumbers = selectedNumbers;
    state.parameters.article_number = state.selectArticleNumbers.map((number) => number.article_number);
};

const updateBrandId = (brandIds) => {
    state.brandIds = brandIds;
    state.parameters.brand_ids = state.brandIds.map((brand) => {
        return brand.id;
    });
};

const updateDepartmentIds = (departmentIds) => {
    state.departmentIds = departmentIds;
    state.parameters.department_ids = state.departmentIds.map((department) => {
        return department.id;
    });
};
</script>
