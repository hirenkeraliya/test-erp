<template>
    <PageTitle title="General Sales Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-2">
            <FormSelectBox
                :selected-record="state.parameters.e_invoice_submitted"
                :records="eInvoiceFilters"
                input-label="To Exclude By E-Invoice Generated"
                @update:selected-record="updateEInvoice"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                :selected-record="state.parameters.report_type"
                :records="generalSalesReports"
                input-label="Report Type"
                :required="true"
                placeholder="Report Type"
                @update:selected-record="updateTheReportType"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="block sm:flex items-center">
                <FormSelectBox
                    v-show="state.parameters.report_type !== null"
                    :selected-record="state.parameters.filter_by"
                    :records="generalSalesFilters"
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
            v-if="state.promoters && state.parameters.filter_by === generalSalesFilterStaticDetails.byPromoter"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.promoterIds"
                :records="state.promoters"
                input-label="Promoters"
                :placeholder="'Please select Promoter(s)'"
                :required="true"
                @update:selected-records="updatePromoterIds"
            />
        </div>

        <div
            v-if="state.brands && state.parameters.filter_by === generalSalesFilterStaticDetails.byBrand"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.brandIds"
                :records="state.brands"
                input-label="Brands"
                :placeholder="'Please select brand(s)'"
                :required="true"
                @update:selected-records="updateBrandIds"
            />
        </div>

        <div
            v-if="state.departments && state.parameters.filter_by === generalSalesFilterStaticDetails.byDepartment"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.departmentIds"
                :records="state.departments"
                input-label="Departments"
                :placeholder="'Please select department(s)'"
                :required="true"
                @update:selected-records="updateDepartmentIds"
            />
        </div>

        <div
            v-if="state.counters && state.parameters.filter_by === generalSalesFilterStaticDetails.byCounter"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.counterIds"
                :records="state.counters"
                input-label="Counters"
                placeholder="Please select Counter(s)"
                :required="true"
                @update:selected-records="updateCounterIds"
            />
        </div>

        <div
            v-if="state.parameters.report_type"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JDatePicker
                v-if="generalSalesReportStaticDetails.byCurrentDayVsPreviousDay === state.parameters.report_type"
                v-model:input-value="state.parameters.date"
                :range-picker="false"
                :required="true"
                input-label="Date"
            />

            <JDatePicker
                v-else
                v-model:input-value="state.parameters.date_range"
                :range-picker="true"
                :required="true"
                input-label="Date Range"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-10">
            <strong>Exclude products with no price?</strong>
            <FormCheckbox
                :check-value="state.parameters.exclude_products_with_no_price"
                class="ml-2"
                @change="updateCheckbox"
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
                @click="exportPDFGeneralSalesReport"
            />

            <PrimaryButton
                type="button"
                text="Excel"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportExcelGeneralSalesReport"
            />

            <PrimaryButton
                type="button"
                text="CSV"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportCsvGeneralSalesReport"
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
import { exportRecords, printReport } from '@commonServices/helper';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';

const props = defineProps({
    generalSalesFilters: {
        type: Object,
        required: true,
    },
    eInvoiceFilters: {
        type: Object,
        required: true,
    },
    generalSalesReports: {
        type: Object,
        required: true,
    },
    generalSalesFilterStaticDetails: {
        type: Object,
        required: true,
    },
    generalSalesReportStaticDetails: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const clearFilters = () => {
    state.parameters.filter_by = null;
    state.parameters.promoter_ids = [];
    state.parameters.brand_ids = [];
    state.parameters.department_ids = [];
};

const state = reactive({
    parameters: {
        date_range: null,
        filter_by: null,
        report_type: null,
        promoter_ids: [],
        brand_ids: [],
        department_ids: [],
        counter_ids: [],
        exclude_products_with_no_price: 1,
        e_invoice_submitted: null,
    },

    promoters: null,
    promoterIds: null,
    brands: null,
    brandIds: null,
    departments: null,
    departmentIds: null,
    counters: [],
    counterIds: [],
});

const validationCheck = () => {
    if (
        props.generalSalesReportStaticDetails.byCurrentDayVsPreviousDay !== state.parameters.report_type &&
        state.parameters.date_range === null
    ) {
        return 'Please select a report type and a date before proceeding';
    }

    if (props.generalSalesReportStaticDetails.byCurrentDayVsPreviousDay === state.parameters.report_type && state.parameters.date === null) {
        return 'Please select a date before continuing';
    }

    if (
        state.parameters.filter_by === props.generalSalesFilterStaticDetails.byPromoter &&
        Object.keys(state.parameters.promoter_ids).length === 0
    ) {
        return 'Please choose a promoter before continuing.';
    }

    if (
        state.parameters.filter_by === props.generalSalesFilterStaticDetails.byBrand &&
        Object.keys(state.parameters.brand_ids).length === 0
    ) {
        return 'Please choose a brand before continuing.';
    }

    if (
        state.parameters.filter_by === props.generalSalesFilterStaticDetails.byDepartment &&
        Object.keys(state.parameters.department_ids).length === 0
    ) {
        return 'Please choose a department before continuing.';
    }

    if (
        state.parameters.filter_by === props.generalSalesFilterStaticDetails.byCounter &&
        Object.keys(state.parameters.counter_ids).length === 0
    ) {
        return 'Please choose a counter before continuing.';
    }

    return null;
};

const exportPDFGeneralSalesReport = () => {
    const error = validationCheck();
    if (error) {
        showErrorNotification(error);
        return;
    }

    printReport(route('store_manager.custom_reports.print_general_sales_report', state.parameters));
};

const exportCsvGeneralSalesReport = () => {
    const error = validationCheck();
    if (error) {
        showErrorNotification(error);
        return;
    }

    return exportRecords(
        'export-general-sales-report/',
        'general-sales-report.csv',
        state.parameters
    );
};

const updateEInvoice = (value) => {
    state.parameters.e_invoice_submitted = value;
};

const exportExcelGeneralSalesReport = () => {
    const error = validationCheck();
    if (error) {
        showErrorNotification(error);
        return;
    }

    return exportRecords(
        'export-general-sales-report/',
        'general-sales-report.xlsx',
        state.parameters
    );
};

const updatePromoterIds = (promoterIds) => {
    state.promoterIds = promoterIds;
    state.parameters.promoter_ids = state.promoterIds.map((promoter) => {
        return promoter.id;
    });
};

const updateBrandIds = (brandIds) => {
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

const clearData = () => {
    emits('update:clear-button');
};

const updateCheckbox = () => {
    state.parameters.exclude_products_with_no_price = !state.parameters.exclude_products_with_no_price;
};

const updateTheFilterBy = (filterBy) => {
    state.parameters.filter_by = filterBy;
    state.parameters.promoter_ids = [];
    state.promoterIds = [];
    state.parameters.brand_ids = [];
    state.brandIds = [];
    state.parameters.department_ids = [];
    state.departmentIds = []; ;

    if (filterBy === props.generalSalesFilterStaticDetails.byPromoter) {
        axios.get(route('store_manager.promoters.get_store_promoters'))
            .then((response) => {
                state.promoters = response.data.promoters;
            });
    }

    if (filterBy === props.generalSalesFilterStaticDetails.byBrand) {
        axios.post(route('store_manager.brands.get_brands'))
            .then((response) => {
                state.brands = response.data.brands;
            });
    }

    if (filterBy === props.generalSalesFilterStaticDetails.byDepartment) {
        axios.get(route('store_manager.departments.get_departments_list'))
            .then((response) => {
                state.departments = response.data.departments;
            });
    }

    if (filterBy === props.generalSalesFilterStaticDetails.byCounter) {
        axios.get(route('store_manager.counters.get_location_counters'))
            .then((response) => {
                state.counters = response.data.counters;
            });
    }
};

const updateCounterIds = (counterIds) => {
    state.counterIds = counterIds;
    state.parameters.counter_ids = state.counterIds.map((counter) => {
        return counter.id;
    });
};

const updateTheReportType = (reportType) => {
    state.parameters.date_range = null;
    state.parameters.date = null;

    state.parameters.report_type = reportType;
};
</script>
