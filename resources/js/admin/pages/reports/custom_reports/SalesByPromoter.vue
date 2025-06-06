<template>
    <PageTitle title="Sales By Promoters Report" />

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
            <JMultiSelect
                :selected-records="state.promoterIds"
                :records="state.promoters"
                input-label="Promoters"
                placeholder="Please select Promoter(s)"
                @update:selected-records="updatePromoterId"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-show="state.parameters.location_ids !== null"
                v-model:selected-record="state.parameters.report_type"
                :records="salesByPromoterReports"
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
                    :records="salesByPromoterFilters"
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
            v-if="state.parameters.filter_by === salesByPromoterFilterStaticDetails.byBrands"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.brandIds"
                :records="state.brands"
                input-label="Brands"
                placeholder="Please select brand(s)"
                @update:selected-records="updateBrandId"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === salesByPromoterFilterStaticDetails.byDepartments"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.departmentIds"
                :records="state.departments"
                input-label="Departments"
                :placeholder="'Please select Department(s)'"
                @update:selected-records="updateDepartmentIds"
            />
        </div>

        <div
            v-if="state.categories && state.parameters.filter_by === salesByPromoterFilterStaticDetails.byCategories"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.categoryIds"
                :records="state.categories"
                input-label="Categories"
                :placeholder="'Please select Categories'"
                @update:selected-records="updateCategoryIds"
            />
        </div>

        <div
            v-if="state.promoterGroups && state.parameters.filter_by === salesByPromoterFilterStaticDetails.byPromoterGroup"
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
            <JDatePicker
                v-model:input-value="state.parameters.date_range"
                :range-picker="true"
                :required="true"
                input-label="Date Range"
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
                @click="exportPDFSalesByPromoter"
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
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { exportRecords, printReport } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const props = defineProps({
    salesByPromoterFilters: {
        type: Object,
        required: true,
    },
    salesByPromoterFilterStaticDetails: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    salesByPromoterReports: {
        type: Object,
        required: true,
    },
    salesByPromoterReportStaticDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        location_ids: null,
        date_range: null,
        filter_by: null,
        brand_ids: null,
        department_ids: null,
        promoter_ids: null,
        category_ids: null,
        group_ids: null,
        report_type: null,
    },

    brands: [],
    brandIds: [],
    promoters: [],
    promoterIds: [],
    locationIds: [],
    departments: null,
    departmentIds: null,
    categories: null,
    categoryIds: null,
    promoterGroups: null,
    promoterGroupIds: null
});

const updateLocationId = (locationIds) => {
    if (locationIds.length === 0) {
        state.promoters = [];
        state.promoterIds = [];
    }
    state.locationIds = locationIds;

    state.parameters.location_ids = state.locationIds.map((location) => {
        return location.id;
    });

    getPromoters();
};

const updatePromoterId = (promoterIds) => {
    state.promoterIds = promoterIds;

    state.parameters.promoter_ids = state.promoterIds.map((promoter) => {
        return promoter.id;
    });
};

const updatePromoterGroupIds = (promoterGroupIds) => {
    state.promoterGroupIds = promoterGroupIds;
    state.parameters.brand_ids = [];
    state.parameters.department_ids = [];
    state.parameters.category_ids = [];
    state.parameters.group_ids = state.promoterGroupIds.map((promoterGroup) => {
        return promoterGroup.id;
    });
};

const updateBrandId = (brandIds) => {
    state.brandIds = brandIds;
    state.parameters.department_ids = [];
    state.parameters.group_ids = [];
    state.parameters.category_ids = [];

    state.parameters.brand_ids = state.brandIds.map((brand) => {
        return brand.id;
    });
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
    state.promoters = [];
    state.promoterIds = [];
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

    if (state.parameters.date_range === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    return false;
};

const exportPDFSalesByPromoter = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, location and a date before proceeding.');
        return;
    }

    printReport(route('admin.custom_reports.print_sales_by_promoter', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, location and a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sales-by-promoter/',
        'sales-by-promoter.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, location and a date before proceeding.');
        return;
    }

    return exportRecords(
        'export-sales-by-promoter/',
        'sales-by-promoter.csv',
        state.parameters
    );
};

const updateDepartmentIds = (departmentIds) => {
    state.departmentIds = departmentIds;
    state.parameters.brand_ids = [];
    state.parameters.group_ids = [];
    state.parameters.category_ids = [];
    state.parameters.department_ids = state.departmentIds.map((department) => {
        return department.id;
    });
};

const updateCategoryIds = (categoryIds) => {
    state.categoryIds = categoryIds;
    state.parameters.brand_ids = [];
    state.parameters.group_ids = [];
    state.parameters.department_ids = [];
    state.parameters.category_ids = state.categoryIds.map((category) => {
        return category.id;
    });
};

const getPromoters = () => {
    state.promoters = [];
    if (state.parameters.location_ids) {
        axios.get(route('admin.promoters.get_active_promoters_by_location_ids',
            { location_ids: state.parameters.location_ids }
        ))
            .then((response) => {
                state.promoters = response.data.promoters;
            });
    }
};

const clearData = () => {
    emits('update:clear-button');
};

const clearFilters = () => {
    state.parameters.filter_by = null;
    state.parameters.group_ids = [];
    state.parameters.brand_ids = [];
    state.parameters.department_ids = [];
    state.parameters.category_ids = [];
};

const updateTheFilterBy = (filterBy) => {
    state.parameters.filter_by = filterBy;
    state.brands = [];
    state.departments = [];
    state.categories = [];
    state.promoterGroups = [];

    if (filterBy === props.salesByPromoterFilterStaticDetails.byBrands) {
        axios.post(route('admin.brands.get_brands'))
            .then((response) => {
                state.brands = response.data.brands;
            });
    }

    if (filterBy === props.salesByPromoterFilterStaticDetails.byDepartments) {
        axios.get(route('admin.departments.get_departments_list'))
            .then((response) => {
                state.departments = response.data.departments;
            });
    }

    if (filterBy === props.salesByPromoterFilterStaticDetails.byCategories) {
        axios.get(route('admin.categories.get_parent_categories'))
            .then((response) => {
                state.categories = response.data.categories;
            });
    }

    if (filterBy === props.salesByPromoterFilterStaticDetails.byPromoterGroup) {
        axios.get(route('admin.promoter_groups.get_promoter_groups_list'))
            .then((response) => {
                state.promoterGroups = response.data.promoterGroups;
            });
    }
};
</script>
