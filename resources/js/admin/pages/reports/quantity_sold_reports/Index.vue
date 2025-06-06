<template>
    <PageTitle title="Quantity Sold Report" />

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        Quantity Sold Report
                    </h2>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-4">
                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-4 xl:col-span-4 bg-gray-50 p-5 rounded border">
                            <JTabs
                                :records="state.locationTypes"
                                :selected-record="state.parameters.location_type"
                                @update:selected-record="updateLocationType"
                            />
                            <TabPanel
                                v-if="state.parameters.location_type === 'Store'"
                                class="active"
                            >
                                <FormSelectBox
                                    :selected-record="state.parameters.region_id"
                                    :records="regions"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    placeholder="Please select Region"
                                    @update:selected-record="updateRegionIdForLocations"
                                />
                                <FormSelectBox
                                    :selected-record="state.parameters.location_id"
                                    :records="state.parameters.region_id !== 0 ? state.locations : locations"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    placeholder="Please select Store"
                                    @update:selected-record="updateLocationId"
                                />
                            </TabPanel>

                            <TabPanel
                                v-if="state.parameters.location_type === 'Region'"
                                class="active"
                            >
                                <FormSelectBox
                                    :selected-record="state.parameters.region_id"
                                    :records="regions"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    placeholder="Please select Region"
                                    @update:selected-record="updateRegionId"
                                />
                            </TabPanel>
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-4 xl:col-span-4">
                            <JDatePicker
                                :range-picker="true"
                                :input-value="state.parameters.date_range"
                                input-label="Date Range"
                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                @update:input-value="updateDate($event)"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-4 xl:col-span-4 bg-gray-50 p-5 rounded border mt-3 sm:mt-0">
                            <JTabs
                                :records="state.locationTypes"
                                :selected-record="state.parameters.compare_location_type"
                                @update:selected-record="updateCompareLocationType"
                            />
                            <TabPanel
                                v-if="state.parameters.compare_location_type === 'Store'"
                                class="active"
                            >
                                <FormSelectBox
                                    :selected-record="state.parameters.compare_region_id"
                                    :records="regions"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    placeholder="Please select Region"
                                    @update:selected-record="updateCompareRegionIdForLocations"
                                />

                                <FormSelectBox
                                    :selected-record="state.parameters.compare_location_id"
                                    :records="state.parameters.compare_region_id !== 0 ? state.locations : locations"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    placeholder="Please select Store"
                                    @update:selected-record="updateCompareLocationId"
                                />
                            </TabPanel>

                            <TabPanel
                                v-if="state.parameters.compare_location_type === 'Region'"
                                class="active"
                            >
                                <FormSelectBox
                                    :selected-record="state.parameters.compare_region_id"
                                    :records="regions"
                                    label-class="block font-medium text-base text-primary-p3 mb-2"
                                    placeholder="Please select Region"
                                    @update:selected-record="updateCompareRegionId"
                                />
                            </TabPanel>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-4">
                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <FormSelectBox
                                :selected-record="state.parameters.report_type"
                                :records="reportTypes"
                                label-class="block font-medium text-base text-primary-p3 mb-2"
                                input-label="Report Type"
                                :required="true"
                                @update:selected-record="updateReportType"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <FormAjaxSelect
                                :selected-record="state.selectedArticleNumber"
                                :search-records="searchArticleNumber"
                                :multi-select="true"
                                track-by="article_number"
                                label="article_number"
                                input-label="Article Number"
                                placeholder="Please type the article number of the product to search."
                                @update:selected-record="selectArticleNumbers"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <FormAjaxSelect
                                :selected-record="state.selectedCategories"
                                :search-records="searchCategory"
                                :multi-select="true"
                                input-label="Categories"
                                placeholder="Please type the name of the category to search."
                                @update:selected-record="selectCategories"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <FormAjaxSelect
                                :selected-record="state.selectedBrands"
                                :search-records="searchBrand"
                                :multi-select="true"
                                input-label="Brands"
                                placeholder="Please type the name of the brand to search."
                                @update:selected-record="selectBrands"
                            />
                        </div>

                        <AttributesFilters
                            v-if="pageProps.product_variant"
                            :attributes="attributes"
                            :custom-class="'input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3'"
                            @update-params="updateParams($event, params)"
                        />

                        <div 
                            v-if="!pageProps.product_variant"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                :selected-record="state.selectedSizes"
                                :search-records="searchSize"
                                :multi-select="true"
                                input-label="Sizes"
                                placeholder="Please type the name of the size to search."
                                @update:selected-record="selectSizes"
                            />
                        </div>

                        <div 
                            v-if="!pageProps.product_variant"
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                :selected-record="state.selectedColors"
                                :search-records="searchColor"
                                :multi-select="true"
                                input-label="Colors"
                                placeholder="Please type the name of the color to search."
                                @update:selected-record="selectColors"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <FormAjaxSelect
                                :selected-record="state.selectedDepartments"
                                :search-records="searchDepartment"
                                :multi-select="true"
                                input-label="Department"
                                placeholder="Please type the name of the department to search."
                                @update:selected-record="selectDepartments"
                            />
                        </div>

                        <div
                            v-if="!pageProps.product_variant" 
                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                        >
                            <FormAjaxSelect
                                :selected-record="state.selectedStyles"
                                :search-records="searchStyle"
                                :multi-select="true"
                                input-label="Styles"
                                placeholder="Please type the name of the style to search."
                                @update:selected-record="selectStyles"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <FormAjaxSelect
                                :selected-record="state.selectedTags"
                                :search-records="searchTag"
                                :multi-select="true"
                                input-label="Tags"
                                placeholder="Please type the name of the tag to search."
                                @update:selected-record="selectTags"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <JSwitch
                                :is-checked="state.parameters.separate_column_sorting"
                                input-label="Apply Different Sorting?"
                                class="mt-0 sm:mt-1 md:mt-1 lg:mt-9"
                                @update:is-checked="handleSeparateColumnSorting"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                            <PrimaryButton
                                v-if="isAnyFilterApplied()"
                                text="Clear Filters"
                                class="flex flex-col sm:flex-row mt-2 sm:mt-0 w-32"
                                @click="clearFilters()"
                            />
                        </div>
                    </div>

                    <div class="mt-6 mb-3 flex justify-between">
                        <div>
                            <select
                                v-if="state.products.length !== 0"
                                class="w-20 form-select box mr-auto"
                                :value="state.parameters.per_page"
                                @input="updatePerPage"
                            >
                                <option
                                    v-for="perPageRecordLimit in state.perPageRecordLimits"
                                    :key="'per-page-record-limit-' + perPageRecordLimit"
                                    :value="perPageRecordLimit"
                                >
                                    {{ perPageRecordLimit }}
                                </option>
                            </select>
                        </div>

                        <div
                            v-if="!checkAllFieldsAreAttachedOrNot()"
                        >
                            <PrimaryButton
                                type="button"
                                text="Print"
                                class="w-20 h-9 mr-2"
                                @click="printQuantitySoldReport()"
                            />

                            <PrimaryButton
                                type="button"
                                text="Excel"
                                class="w-20 h-9 mr-2"
                                @click="exportExcelRecords()"
                            />

                            <PrimaryButton
                                type="button"
                                text="Csv"
                                class="w-20 h-9"
                                @click="exportCsvRecords()"
                            />
                        </div>
                    </div>

                    <div
                        v-if="!checkAllFieldsAreAttachedOrNot()"
                        class="grid grid-cols-12 gap-0 sm:gap-8"
                    >
                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0">
                            <div
                                v-if="state.LocationName || state.regionName"
                                class="py-2 pl-4 bg-gray-200 font-medium text-base mb-4 rounded block xl:flex items-center justify-between"
                            >
                                {{ state.LocationName ?? state.regionName }}

                                <div class="lg:ml-0 xl:ml-2 lg:mt-2 xl:mt-0">
                                    <JBadge
                                        v-if="state.totalSumAndCounts.quantity"
                                        :label="'Quantity: ' + truncateDecimal(state.totalSumAndCounts.quantity)"
                                        class="mr-0 mb-2 lg:mb-0"
                                    />

                                    <JBadge
                                        v-if="state.totalSumAndCounts.amount"
                                        :label="'Amount: ' + displayAmountWithCurrencySymbol(state.totalSumAndCounts.amount)"
                                        class="mr-0"
                                    />
                                </div>
                            </div>
                            <QuantitySoldReportTable
                                :columns="state.columns"
                                :records="state.products ?? []"
                                :sort-direction="state.parameters.sort_direction"
                                :sort-by="state.parameters.sort_by"
                                :current-page="state.parameters.page"
                                :per-page="state.parameters.per_page"
                                :is-data-fetching="state.isDataFetching"
                                table-classes="table -mt-2 table-report"
                                :allow-pagination-and-sorting="false"
                                row-classes="border-b border-slate-200"
                                @update:sort-by="updateSortBy"
                            >
                                <template #qty_sold="data">
                                    {{ truncateDecimal(data.item.qty_sold) }}
                                </template>
                                <template #amount_sold="data">
                                    {{ displayAmountWithCurrencySymbol(data.item.amount_sold) }}
                                </template>
                            </QuantitySoldReportTable>
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border">
                            <div
                                v-if="state.compareLocationName || state.compareRegionName"
                                class="py-2 pl-4 bg-gray-200 font-medium text-base mb-4 rounded block xl:flex items-center justify-between"
                            >
                                {{ state.compareLocationName ?? state.compareRegionName }}

                                <div class="lg:ml-0 xl:ml-2 lg:mt-2 xl:mt-0">
                                    <JBadge
                                        v-if="state.totalSumAndCounts.compare_quantity"
                                        :label="'Quantity: ' + truncateDecimal(state.totalSumAndCounts.compare_quantity)"
                                        class="mb-2 lg:mb-0"
                                    />

                                    <JBadge
                                        v-if="state.totalSumAndCounts.compare_amount"
                                        :label="'Amount: ' + displayAmountWithCurrencySymbol(state.totalSumAndCounts.compare_amount)"
                                    />
                                </div>
                            </div>
                            <QuantitySoldReportTable
                                :columns="state.compareColumns"
                                :records="state.parameters.separate_column_sorting ? state.comparedProducts ?? [] : state.products ?? []"
                                :sort-direction="state.parameters.separate_column_sorting ? state.parameters.compare_sort_direction : state.parameters.sort_direction"
                                :sort-by="state.parameters.separate_column_sorting ? state.parameters.compare_sort_by : state.parameters.sort_by"
                                :current-page="state.parameters.page"
                                :per-page="state.parameters.per_page"
                                :is-data-fetching="state.isDataFetching"
                                table-classes="table -mt-2 table-report"
                                :allow-pagination-and-sorting="false"
                                row-classes="border-b border-slate-200"
                                @update:sort-by="updateCompareSortBy"
                            >
                                <template #compare_qty_sold="data">
                                    {{ truncateDecimal(data.item.compare_qty_sold) }}
                                </template>
                                <template #compare_sold_amount="data">
                                    {{ displayAmountWithCurrencySymbol(data.item.compare_sold_amount) }}
                                </template>
                            </QuantitySoldReportTable>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="!checkAllFieldsAreAttachedOrNot()"
                class="grid grid-cols-12 gap-0 sm:gap-6 mt-3 z-0 relative"
            >
                <div class="block sm:flex flex-wrap items-center col-span-12 intro-y sm:flex-row sm:flex-nowrap">
                    <JPagination
                        :current-page="state.parameters.page"
                        :per-page="state.parameters.per_page"
                        :total-records="state.totalRecords"
                        @update:current-page="changeCurrentPage"
                    />

                    <div class="ml-auto block text-slate-500 mt-2 sm:mt-0">
                        Showing {{ getFromRecordNumber() }} to {{ getToRecordNumber() }} of {{ state.totalRecords }} entries
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import JPagination from '@commonComponents/JPagination.vue';
import { reactive, computed } from 'vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import axios from 'axios';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { truncateDecimal, displayAmountWithCurrencySymbol, currentDate, printReport, exportRecords } from '@commonServices/helper';
import JTabs from '@commonComponents/JTabs.vue';
import { TabPanel } from '@commonVendor/tab';
import QuantitySoldReportTable from '@adminPages/reports/quantity_sold_reports/QuantitySoldReportTable.vue';
import { route } from 'ziggy';
import JBadge from '@commonComponents/JBadge.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { usePage } from '@inertiajs/vue3';
import AttributesFilters from '@commonComponents/AttributesFilters.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    regions: {
        type: Array,
        required: true,
    },
    reportTypes: {
        type: Array,
        required: true,
    },
    staticReportTypes: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => { },
    },
});

const state = reactive({
    isDataFetching: false,
    perPageRecordLimits: ['10', '25', '50', '100'],
    locationTypes: [
        { id: 'Store', name: 'Store' },
        { id: 'Region', name: 'Region' },
    ],
    parameters: {
        location_type: 'Store',
        compare_location_type: 'Store',
        location_id: null,
        compare_location_id: null,
        region_id: 0,
        compare_region_id: 0,
        per_page: 10,
        page: 1,
        sort_direction: 'asc',
        sort_by: 'id',
        compare_sort_by: 'id',
        compare_sort_direction: 'asc',
        separate_column_sorting: false,
        date_range: [currentDate(), currentDate()],
        report_type: null,
        article_numbers: [],
        category_ids: [],
        brand_ids: [],
        department_ids: [],
        color_ids: [],
        size_ids: [],
        style_ids: [],
        tag_ids: [],
        attributes: null,
    },
    totalRecords: 0,
    products: [],
    comparedProducts: [],
    LocationName: null,
    compareLocationName: null,
    regionName: null,
    compareRegionName: null,
    totalSumAndCounts: [],
    columns: null,
    compareColumns: null,
    selectedCategories: null,
    selectedDepartments: null,
    selectedSizes: null,
    selectedColors: null,
    selectedStyles: null,
    selectedArticleNumber: null,
    selectedTags: null,
    locations: [],
});

const updateLocationId = (locationId) => {
    state.parameters.location_id = parseInt(locationId);
    fetchRecords();
};

const updateRegionId = (regionId) => {
    state.parameters.region_id = parseInt(regionId);
    fetchRecords();
};

const updateRegionIdForLocations = (regionId) => {
    state.parameters.region_id = parseInt(regionId);
    state.locations = [];
    axios.post(route('admin.locations.get_locations_of_regions', { region_id: regionId }))
        .then((response) => {
            state.locations = response.data.locations;
        });

    fetchRecords();
};

const updateCompareLocationId = (compareLocationId) => {
    state.parameters.compare_location_id = parseInt(compareLocationId);
    fetchRecords();
};

const updateCompareRegionId = (regionId) => {
    state.parameters.compare_region_id = parseInt(regionId);
    fetchRecords();
};

const updateCompareRegionIdForLocations = (regionId) => {
    state.parameters.compare_region_id = parseInt(regionId);
    state.locations = [];
    axios.post(route('admin.locations.get_locations_of_regions', { region_id: regionId }))
        .then((response) => {
            state.locations = response.data.locations;
        });
    fetchRecords();
};

const changeCurrentPage = (currentPage) => {
    state.parameters.page = currentPage;
    fetchRecords();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    fetchRecords();
};

const updateLocationType = (locationType) => {
    state.parameters.location_type = locationType;
    state.parameters.location_id = null;
    state.parameters.region_id = null;
    fetchRecords();
};

const updateCompareLocationType = (locationType) => {
    state.parameters.compare_location_type = locationType;
    state.parameters.compare_location_id = null;
    state.parameters.compare_region_id = null;
    fetchRecords();
};

const updatePerPage = (event) => {
    state.parameters.per_page = parseInt(event.target.value);
    state.parameters.page = 1;
    fetchRecords();
};

const fetchRecords = () => {
    if (checkAllFieldsAreAttachedOrNot()) {
        return;
    }

    state.isDataFetching = true;
    axios.get(route('admin.quantity_sold_reports.fetch', state.parameters)).then((response) => {
        state.products = response.data.products;
        state.comparedProducts = response.data.compared_products;
        state.totalRecords = response.data.total_records;
        state.LocationName = response.data.location_name;
        state.compareLocationName = response.data.compare_location_name;
        state.regionName = response.data.region_name;
        state.compareRegionName = response.data.compare_region_name;
        state.totalSumAndCounts = response.data.total_sum_and_counts;
        state.isDataFetching = false;
    }).catch(() => {
        state.isDataFetching = false;
    });
};

const getFromRecordNumber = () => {
    return (state.parameters.per_page * state.parameters.page) - state.parameters.per_page + 1;
};

const updateSortBy = (sortBy) => {
    state.parameters.sort_by = sortBy;
    state.parameters.sort_direction = state.parameters.sort_direction === 'asc' ? 'desc' : 'asc';
    fetchRecords();
};

const updateCompareSortBy = (sortBy) => {
    if (!state.parameters.separate_column_sorting) {
        updateSortBy(sortBy);
        return;
    }

    state.parameters.compare_sort_by = sortBy;
    state.parameters.compare_sort_direction = state.parameters.compare_sort_direction === 'asc' ? 'desc' : 'asc';
    fetchRecords();
};

const handleSeparateColumnSorting = (value) => {
    state.parameters.separate_column_sorting = value;
    fetchRecords();
};

const updateReportType = (reportType) => {
    state.parameters.report_type = reportType;
    columns();
    fetchRecords();
};

const getToRecordNumber = () => {
    const toRecordNumber = state.parameters.per_page * state.parameters.page;

    if (toRecordNumber > state.totalRecords) {
        return state.totalRecords;
    }

    return toRecordNumber;
};

const checkAllFieldsAreAttachedOrNot = () => {
    if (state.parameters.location_type === 'Store' && state.parameters.compare_location_type === 'Store') {
        return state.parameters.location_id === null || state.parameters.compare_location_id === null || state.parameters.date_range === null || state.parameters.report_type === null;
    }

    if (state.parameters.location_type === 'Store' && state.parameters.compare_location_type === 'Region') {
        return state.parameters.location_id === null || state.parameters.compare_region_id === null || state.parameters.date_range === null || state.parameters.report_type === null;
    }

    if (state.parameters.location_type === 'Region' && state.parameters.compare_location_type === 'Region') {
        return state.parameters.region_id === null || state.parameters.compare_region_id === null || state.parameters.date_range === null || state.parameters.report_type === null;
    }

    if (state.parameters.location_type === 'Region' && state.parameters.compare_location_type === 'Store') {
        return state.parameters.region_id === null || state.parameters.compare_location_id === null || state.parameters.date_range === null || state.parameters.report_type === null;
    }
};

const minSearchLength = 3;

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(route('admin.products.get_filtered_article_number'), filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectedArticleNumber = selectedNumbers;
    if (selectedNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    }
    state.parameters.page = 1;
    fetchRecords();
};

const columns = () => {
    const columns = [
        {
            key: 'product',
            sortable: true,
            label: 'Name',
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                },
            ]
            : [
                {
                    key: 'color',
                    sortable: true,
                },
                {
                    key: 'size',
                    sortable: true,
                },
            ]),
        {
            key: 'qty_sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        },
        {
            key: 'amount_sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        },
    ];

    const comparedColumns = [
        {
            key: 'product',
            sortable: true,
            label: 'Name',
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                },
            ]
            : [
                {
                    key: 'color',
                    sortable: true,
                },
                {
                    key: 'size',
                    sortable: true,
                },
            ]),
        {
            key: 'compare_qty_sold',
            sortable: true,
            label: 'Qty Sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'compare_sold_amount',
            sortable: true,
            label: 'Amount Sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ];

    if (props.staticReportTypes.byUpc === state.parameters.report_type) {
        state.columns = columns;
        state.compareColumns = comparedColumns;

        state.columns.splice(1, 0,
            {
                key: 'upc',
                sortable: true,
                label: 'Upc',
            }
        );

        state.compareColumns.splice(1, 0,
            {
                key: 'upc',
                sortable: true,
                label: 'Upc',
            }
        );
    }

    if (props.staticReportTypes.byParentArticleNumber === state.parameters.report_type) {
        state.columns = columns;
        state.compareColumns = comparedColumns;

        state.columns.splice(1, 0,
            {
                key: 'article_number',
                sortable: true,
            }
        );

        state.compareColumns.splice(1, 0,
            {
                key: 'article_number',
                sortable: true,
            }
        );
    }
};

const searchStyle = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(route('admin.styles.get_filtered_styles'), filterData).then((response) => {
            componentState.records = response.data.styles;
            componentState.isLoading = false;
        });
    }
};

const selectStyles = (selectedStyles) => {
    state.selectedStyles = selectedStyles;
    if (selectedStyles !== null) {
        state.parameters.style_ids = selectedStyles.map(function (selectedStyle) {
            return selectedStyle.id;
        });
    }
    fetchRecords();
};

const searchCategory = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('admin.categories.get_filtered_categories'), filterData).then((response) => {
        componentState.records = response.data.categories;
        componentState.isLoading = false;
    });
};

const searchBrand = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('admin.brands.get_filtered_brands'), filterData).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const searchSize = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.sizes.get_filtered_sizes'), filterData).then((response) => {
        componentState.records = response.data.sizes;
        componentState.isLoading = false;
    });
};

const searchColor = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.colors.get_filtered_colors'), filterData).then((response) => {
        componentState.records = response.data.colors;
        componentState.isLoading = false;
    });
};

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.departments.get_filtered_departments'), filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const selectCategories = (selectedCategories) => {
    state.selectedCategories = selectedCategories;
    if (selectedCategories !== null) {
        state.parameters.category_ids = selectedCategories.map(function (category) {
            return category.id;
        });
    }
    fetchRecords();
};

const selectBrands = (selectedBrands) => {
    state.selectedBrands = selectedBrands;
    if (selectedBrands !== null) {
        state.parameters.brand_ids = selectedBrands.map(function (brand) {
            return brand.id;
        });
    }
    fetchRecords();
};

const selectSizes = (selectedSizes) => {
    state.selectedSizes = selectedSizes;
    if (selectedSizes !== null) {
        state.parameters.size_ids = selectedSizes.map(function (size) {
            return size.id;
        });
    }
    fetchRecords();
};

const selectColors = (selectedColors) => {
    state.selectedColors = selectedColors;
    if (selectedColors !== null) {
        state.parameters.color_ids = selectedColors.map(function (color) {
            return color.id;
        });
    }
    fetchRecords();
};

const selectDepartments = (selectedDepartments) => {
    state.selectedDepartments = selectedDepartments;
    if (selectedDepartments !== null) {
        state.parameters.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    }
    fetchRecords();
};

const clearFilters = () => {
    state.parameters.article_numbers = [];
    state.parameters.category_ids = [];
    state.parameters.brand_ids = [];
    state.parameters.department_ids = [];
    state.parameters.color_ids = [];
    state.parameters.size_ids = [];
    state.parameters.style_ids = [];
    state.parameters.tag_ids = [];
    state.parameters.attributes = null;
    state.selectedCategories = null;
    state.selectedDepartments = null;
    state.selectedSizes = null;
    state.selectedColors = null;
    state.selectedStyles = null;
    state.selectedArticleNumber = null;
    state.selectedTags = null;
    state.selectedTags = null;
    fetchRecords();
};

const isAnyFilterApplied = () => {
    if (
        state.parameters.article_numbers.length > 0 ||
        state.parameters.category_ids.length > 0 ||
        state.parameters.brand_ids.length > 0 ||
        state.parameters.department_ids.length > 0 ||
        state.parameters.color_ids.length > 0 ||
        state.parameters.size_ids.length > 0 ||
        state.parameters.style_ids.length > 0
    ) {
        return true;
    }

    return false;
};

fetchRecords();

const searchTag = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.tags.get_filtered_tags'), filterData).then((response) => {
        componentState.records = response.data.tags;
        componentState.isLoading = false;
    });
};

const selectTags = (selectedTags) => {
    state.selectedTags = selectedTags;
    if (selectedTags !== null) {
        state.parameters.tag_ids = selectedTags.map(function (tag) {
            return tag.id;
        });
    }
    fetchRecords();
};

const printQuantitySoldReport = () => {
    printReport(route('admin.quantity_sold_reports.print', state.parameters), props.exportPermission);
};

const exportCsvRecords = () => {
    return exportRecords(
        'export-quantity-sold-report/',
        'quantity-sold-report.csv',
        state.parameters,
        props.exportPermission
    );
};

const exportExcelRecords = () => {
    return exportRecords(
        'export-quantity-sold-report/',
        'quantity-sold-report.xlsx',
        state.parameters,
        props.exportPermission
    );
};

const updateParams = (params) => {
    state.parameters.attributes = params;    
    fetchRecords();
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
