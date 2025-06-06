<template>
    <PageTitle title="Promoter Commission Report" />

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
                v-show="state.parameters.location_ids !== null"
                v-model:selected-record="state.parameters.report_type"
                :records="promoterCommissionReports"
                input-label="Report Type"
                :required="true"
                placeholder="Report Type"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="block sm:flex items-center">
                <FormSelectBox
                    v-show="state.parameters.report_type !== null"
                    :selected-record="state.parameters.filter_by"
                    :records="promoterCommissionFilters"
                    input-label="Filter By"
                    placeholder="Filter By"
                    class="w-full"
                    @update:selected-record="updateTheFilterBy"
                />
                <div
                    v-if="state.parameters.filter_by"
                    class="ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                >
                    <PrimaryButton
                        type="button"
                        text="Clear"
                        class="btn-sm w-24 h-10"
                        @click="clearFilters"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="state.parameters.filter_by === promoterCommissionFilterStaticDetails.byBrand"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.brandIds"
                :records="state.brands"
                input-label="Brands"
                placeholder="Please select brand(s)"
                :required="true"
                @update:selected-records="updateBrandId"
            />
        </div>

        <div
            v-if="state.departments && state.parameters.filter_by === promoterCommissionFilterStaticDetails.byDepartment"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.departmentIds"
                :records="state.departments"
                input-label="Departments"
                :placeholder="'Please select Department(s)'"
                :required="true"
                @update:selected-records="updateDepartmentIds"
            />
        </div>

        <div
            v-if="state.promoterGroups && state.parameters.filter_by === promoterCommissionFilterStaticDetails.byPromoterGroup"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.promoterGroupIds"
                :records="state.promoterGroups"
                input-label="PromoterGroups"
                :placeholder="'Please select PromoterGroup(s)'"
                :required="true"
                @update:selected-records="updatePromoterGroupIds"
            />
        </div>

        <div
            v-if="state.parameters.report_type"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMonthPicker
                :input-value="state.monthYear"
                :required="true"
                input-label="Month"
                @update:input-value="updateDate($event)"
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
                @click="exportPDFPromoterCommissionReport"
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
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JMonthPicker from '@commonComponents/JMonthPicker.vue';
import { exportRecords, printReport } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const props = defineProps({
    promoterCommissionFilters: {
        type: Object,
        required: true,
    },
    promoterCommissionFilterStaticDetails: {
        type: Object,
        required: true,
    },
    promoterCommissionReports: {
        type: Object,
        required: true,
    },
    promoterCommissionReportStaticDetails: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        location_ids: null,
        month_range: null,
        filter_by: null,
        brand_ids: [],
        department_ids: [],
        report_type: null,
        group_ids: []
    },

    brands: [],
    brandIds: [],
    departments: null,
    departmentIds: null,
    promoterGroups: null,
    promoterGroupIds: null
});

const updateBrandId = (brandIds) => {
    state.brandIds = brandIds;
    state.parameters.department_ids = [];
    state.parameters.group_ids = [];
    state.parameters.brand_ids = [];
    if (state.brandIds.length) {
        state.parameters.brand_ids = state.brandIds.map((brand) => {
            return brand.id;
        });
    }
};

const updateDate = (date) => {
    state.parameters.month_range = null;
    state.monthYear = date;

    if (date === null) {
        return;
    }

    const monthData = Object.values(state.monthYear);
    monthData[0] += 1;
    state.parameters.month_range = monthData;
};

const clearFilters = () => {
    state.parameters.filter_by = null;
    state.parameters.group_ids = [];
    state.parameters.brand_ids = [];
    state.parameters.department_ids = [];
};

const selectAllLocations = () => {
    updateLocationId(props.locations);
    state.displayClearButton = true;
};

const clearAllLocations = () => {
    state.locationIds = [];
    state.brands = [];
    state.brandIds = [];
    state.departmentIds = [];
    state.promoterGroupIds = [];
    state.displayClearButton = false;
    state.parameters.location_ids = null;
    state.parameters.date_range = null;
    state.parameters.report_type = null;
    clearFilters();
};

const validationCheck = () => {
    if (state.parameters.location_ids === null) {
        return true;
    }

    if (state.parameters.month_range === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    return false;
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

const exportPDFPromoterCommissionReport = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, location and a month before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.promoterCommissionFilterStaticDetails.byDepartment && state.parameters.department_ids.length === 0) {
        showErrorNotification('Please choose a department before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.promoterCommissionFilterStaticDetails.byBrand && state.parameters.brand_ids.length === 0) {
        showErrorNotification('Please select a brand before proceeding.');
        return;
    }

    printReport(route('admin.custom_reports.print_promoter_commission_report', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, location and a month before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.promoterCommissionFilterStaticDetails.byDepartment && state.parameters.department_ids.length === 0) {
        showErrorNotification('Please choose a department before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.promoterCommissionFilterStaticDetails.byBrand && state.parameters.brand_ids.length === 0) {
        showErrorNotification('Please select a brand before proceeding.');
        return;
    }

    return exportRecords(
        'export-promoter-commission-report/',
        'promoter-commission.xlsx',
        state.parameters
    );
};
const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, location and a month before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.promoterCommissionFilterStaticDetails.byDepartment && state.parameters.department_ids.length === 0) {
        showErrorNotification('Please choose a department before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.promoterCommissionFilterStaticDetails.byBrand && state.parameters.brand_ids.length === 0) {
        showErrorNotification('Please select a brand before proceeding.');
        return;
    }

    return exportRecords(
        'export-promoter-commission-report/',
        'promoter-commission.csv',
        state.parameters
    );
};

const updatePromoterGroupIds = (promoterGroupIds) => {
    state.promoterGroupIds = promoterGroupIds;
    state.parameters.brand_ids = [];
    state.parameters.department_ids = [];
    state.parameters.group_ids = state.promoterGroupIds.map((promoterGroup) => {
        return promoterGroup.id;
    });
};

const updateDepartmentIds = (departmentIds) => {
    state.departmentIds = departmentIds;
    state.parameters.brand_ids = [];
    state.parameters.group_ids = [];
    state.parameters.department_ids = state.departmentIds.map((department) => {
        return department.id;
    });
};

const clearData = () => {
    emits('update:clear-button');
};

const updateTheFilterBy = (filterBy) => {
    state.parameters.filter_by = filterBy;
    state.brands = [];
    state.departments = [];
    state.promoterGroups = [];

    if (filterBy === props.promoterCommissionFilterStaticDetails.byBrand) {
        axios.post(route('admin.brands.get_brands'))
            .then((response) => {
                state.brands = response.data.brands;
            });
    }

    if (filterBy === props.promoterCommissionFilterStaticDetails.byDepartment) {
        axios.get(route('admin.departments.get_departments_list'))
            .then((response) => {
                state.departments = response.data.departments;
            });
    }

    if (filterBy === props.promoterCommissionFilterStaticDetails.byPromoterGroup) {
        axios.get(route('admin.promoter_groups.get_promoter_groups_list'))
            .then((response) => {
                state.promoterGroups = response.data.promoterGroups;
            });
    }
};
</script>
