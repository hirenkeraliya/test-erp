<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <div class="content content--top-nav mr-5">
            <PageTitle title="Product" />

            <div class="items-center block my-auto mt-5 2xl:flex xl:block lg:block md:block sm:block intro-y">
                <div class="block sm:flex flex-wrap mr-auto justify-items-start">
                    <div
                        class="sm:flex flex-wrap ml-0 2xl:ml-2 xl:ml-0 lg:ml-0 md:ml-0 sm:ml-0 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-0 sm:mt-0"
                    >
                        <FormSelectBox
                            class="w-full mt-0 mr-2 2xl:w-96 md:w-64 sm:w-56"
                            :selected-record="locationId"
                            :records="locations"
                            :placeholder="'Please select Location'"
                            @update:selected-record="getLocationData"
                        />

                        <FormSelectBox
                            class="w-full mt-0 mr-2 2xl:w-96 md:w-64 sm:w-56"
                            :selected-record="brandId"
                            :records="brands"
                            :placeholder="'Please select Brand'"
                            @update:selected-record="getBrandData"
                        />

                        <JDatePicker
                            class="mr-0 sm:mr-1"
                            :required="true"
                            label-class="hidden"
                            :input-value="date"
                            @update:input-value="updateDate($event)"
                        />
                    </div>

                    <div class="mt-6 sm:mt-0 ml-0 flex w-full mb-3 sm:ml-3 sm:w-auto">
                        <div class="flex">
                            <div>
                                <div class="mt-0.5 text-slate-800 text-center">
                                    Revenue
                                </div>

                                <div
                                    class="text-lg font-medium text-center text-primary dark:text-slate-300 xl:text-xl"
                                >
                                    {{ displayAmountWithCurrencySymbol(totalSales) }}
                                </div>
                            </div>

                            <div
                                class="w-px h-12 mx-4 border border-r border-dashed border-slate-400 dark:border-darkmode-300 xl:mx-5"
                            />

                            <div>
                                <div class="mt-0.5 text-slate-800 text-center">
                                    Units Sold
                                </div>

                                <div
                                    class="text-lg font-medium text-center text-primary dark:text-slate-300 xl:text-xl"
                                >
                                    {{ truncateDecimal(totalUnitsSold) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex">
                    <Tippy
                        content="Refresh Data"
                        class="btn btn-outline-primary"
                        @click="refresh()"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>

                    <p class="ml-2 text-xs">
                        <span class="text-sm font-medium">Last Update:</span><br>{{ lastUpdate }}
                    </p>
                </div>
            </div>

            <div
                v-if="totalCreditSalePendingAmount > 0"
                class="mt-7"
            >
                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div class="grid gap-3 sm:gap-4 lg:grid-cols-2">
                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showPendingCreditSale()"
                        >
                            <div class="mr-2.5">
                                <p class="text-lg">
                                    Credit Sale Pending
                                </p>

                                <Tippy
                                    tag="p"
                                    class="mt-1 flex items-center font-semibold text-lg"
                                    content="Modifying the brand or date will not impact the overall pending amount of credit sales."
                                >
                                    {{ displayAmountWithCurrencySymbol(totalCreditSalePendingAmount) }}

                                    <Info
                                        class="text-cyan-400 ml-1"
                                        :size="18"
                                    />
                                </Tippy>
                            </div>
                            <div
                                class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none"
                            >
                                <PackageX class="w-4 h-4 lg:h-5 lg:w-5 text-indigo-700" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10">
                <MultiBarOrLineChart
                    :chart-id="`monthly-based-bar-or-line-chart`"
                    title-of-chart="Sales by Hour"
                    :datasets="dataSets"
                    :labels="hourlyChartLabel"
                    :legend-data="['Yesterday`s Sales', 'Today`s Sales']"
                    :show-bar-and-line-chart="true"
                    file-name="sales-by-hour-bar-or-line"
                    :filters="filters"
                />
            </div>

            <div class="mt-10">
                <div>
                    <MultiBarOrLineChart
                        :chart-id="`accumulated-hourly-based-multi-line-chart`"
                        title-of-chart="Accumulated Sales by Hour"
                        :datasets="hourlyTotalDataSets"
                        :labels="hourlyChartLabel"
                        :legend-data="['Yesterday`s Sales', 'Today`s Sales']"
                        :show-bar-and-line-chart="true"
                        file-name="accumulated-sales-by-hour-bar-or-line"
                        :filters="filters"
                    />
                </div>
            </div>

            <div class="mt-10">
                <div
                    class="grid grid-cols-1 mt-5 md:h-1/2 2xl:grid-cols-2 xl:grid-cols-4 lg:grid-cols-4 md:grid-cols-1 sm:grid-cols-1 gap-10"
                >
                    <PieChart
                        chart-id="by-categories"
                        title-of-chart="Categories"
                        :section-name="props.storeRevenueDashboardTableFilterTypes.categories.toString()"
                        :labels="!isEmpty(totalSalesByCategory.total_sales) ? totalSalesByCategory.labels : ['No data available']"
                        :data="!isEmpty(totalSalesByCategory.total_sales) ? totalSalesByCategory.total_sales : [10, 20, 30, 40, 50]"
                        :background-color="!isEmpty(totalSalesByCategory.total_sales)"
                        :dataset-label="'Sales(' + currencySymbol + ')'"
                        :filters="filters"
                    />

                    <PieChart
                        chart-id="by-colors"
                        title-of-chart="Colors"
                        :section-name="props.storeRevenueDashboardTableFilterTypes.colors.toString()"
                        :labels="!isEmpty(totalSalesByColor.total_sales) ? totalSalesByColor.labels : ['No data available']"
                        :data="!isEmpty(totalSalesByColor.total_sales) ? totalSalesByColor.total_sales : [10, 20, 30, 40, 50]"
                        :background-color="false"
                        :dataset-label="'Sales(' + currencySymbol + ')'"
                        :filters="filters"
                    />

                    <PieChart
                        chart-id="by-brands"
                        title-of-chart="Brands"
                        :section-name="props.storeRevenueDashboardTableFilterTypes.brands.toString()"
                        :labels="!isEmpty(totalSalesByBrand.total_sales) ? totalSalesByBrand.labels : ['No data available']"
                        :data="!isEmpty(totalSalesByBrand.total_sales) ? totalSalesByBrand.total_sales : [10, 20, 30, 40, 50]"
                        :background-color="!isEmpty(totalSalesByBrand.total_sales)"
                        :dataset-label="'Sales(' + currencySymbol + ')'"
                        :filters="filters"
                    />

                    <PieChart
                        chart-id="by-departments"
                        title-of-chart="Departments"
                        :section-name="props.storeRevenueDashboardTableFilterTypes.departments.toString()"
                        :labels="!isEmpty(totalSalesByDepartment.total_sales) ? totalSalesByDepartment.labels : ['No data available']"
                        :data="!isEmpty(totalSalesByDepartment.total_sales) ? totalSalesByDepartment.total_sales : [10, 20, 30, 40, 50]"
                        :background-color="!isEmpty(totalSalesByDepartment.total_sales)"
                        :dataset-label="'Sales(' + currencySymbol + ')'"
                        :filters="filters"
                    />

                    <PieChart
                        chart-id="by-color-groups"
                        title-of-chart="Color Groups"
                        :section-name="props.storeRevenueDashboardTableFilterTypes.colorGroups.toString()"
                        :labels="!isEmpty(totalSalesByColorGroup.total_sales) ? totalSalesByColorGroup.labels : ['No data available']"
                        :data="!isEmpty(totalSalesByColorGroup.total_sales) ? totalSalesByColorGroup.total_sales : [10, 20, 30, 40, 50]"
                        :background-color="false"
                        :dataset-label="'Sales(' + currencySymbol + ')'"
                        :filters="filters"
                    />

                    <PieChart
                        chart-id="by-sizes"
                        title-of-chart="Sizes"
                        :section-name="props.storeRevenueDashboardTableFilterTypes.sizes.toString()"
                        :labels="!isEmpty(totalSalesBySize.total_sales) ? totalSalesBySize.labels : ['No data available']"
                        :data="!isEmpty(totalSalesBySize.total_sales) ? totalSalesBySize.total_sales : [10, 20, 30, 40, 50]"
                        :background-color="!isEmpty(totalSalesBySize.total_sales)"
                        :dataset-label="'Sales(' + currencySymbol + ')'"
                        :filters="filters"
                    />

                    <PieChart
                        chart-id="by-styles"
                        title-of-chart="Styles"
                        :section-name="props.storeRevenueDashboardTableFilterTypes.styles.toString()"
                        :labels="!isEmpty(totalSalesByStyle.total_sales) ? totalSalesByStyle.labels : ['No data available']"
                        :data="!isEmpty(totalSalesByStyle.total_sales) ? totalSalesByStyle.total_sales : [10, 20, 30, 40, 50]"
                        :background-color="!isEmpty(totalSalesByStyle.total_sales)"
                        :dataset-label="'Sales(' + currencySymbol + ')'"
                        :filters="filters"
                    />
                </div>
            </div>

            <div class="mt-10 bg-white rounded-xl p-3">
                <JSimpleTable
                    :columns="state.columns"
                    :records="getSectionData()"
                    :footer-record="getFooterSectionData()"
                    :allow-pdf-export="true"
                    :allow-csv-export="true"
                    :allow-excel-export="true"
                    :export-pdf-records-callback="downloadPdfStoreRecord"
                    :export-excel-records-callback="exportExcelRecords"
                    :export-csv-records-callback="exportCsvRecords"
                    row-classes="border-b-2 border-slate-300 intro-x"
                    table-classes="table overflow-hidden border-0 border-none rounded-md mb-3"
                >
                    <template #extra-header-data>
                        <div class="block">
                            <button
                                v-for="(sectionValue, sectionName, index) in state.sections"
                                :key="index + sectionValue"
                                type="button"
                                class="ml-1 sm:ml-2 mb-1 sm:mb-1 md:mb-1 focus:ring-0 capitalize"
                                :class="state.selectedSection === sectionValue ? 'btn btn-primary' : 'btn btn-secondary'"
                                @click="state.selectedSection = sectionValue"
                            >
                                {{ sectionName }}
                            </button>
                        </div>
                    </template>

                    <template #name="data">
                        <div
                            class="cursor-pointer"
                            @click="getClickedProductData(data.item.id, data.item.name)"
                        >
                            {{ data.item.name }}
                        </div>
                    </template>

                    <template #total_sales="data">
                        {{ displayAmountWithCurrencySymbol(data.item.total_sales) }}
                    </template>

                    <template #upt="data">
                        {{ truncateDecimal(data.item.upt) }}
                    </template>

                    <template #atv="data">
                        {{ displayAmountWithCurrencySymbol(data.item.atv) }}
                    </template>
                </JSimpleTable>
            </div>
        </div>
    </div>

    <StoreRevenueDetails
        v-if="state.showDetailModal && state.totalSales && state.totalUnitsSold"
        :modal-show="state.showDetailModal"
        :module-title-name="state.moduleTitleName"
        :records="state.records"
        :total-sales="state.totalSales"
        :total-units-sold="state.totalUnitsSold"
        :sub-section-labels="state.subSectionLabels"
        :default-sub-sections-label="state.selectedSubSectionLabels"
        @close-modal="state.showDetailModal = false"
        @get-records-based-on-selected-sections="getRecordsBasedOnSelectedSections($event)"
    />
</template>

<script setup>
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PieChart from '@commonComponents/PieChart.vue';
import MultiBarOrLineChart from '@commonComponents/MultiBarOrLineChart.vue';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { router, usePage } from '@inertiajs/vue3';
import { displayAmountWithCurrencySymbol, truncateDecimal, printReport, exportRecords } from '@commonServices/helper';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import StoreRevenueDetails from '@adminPages/dashboards/StoreRevenueDetails.vue';
import { Info, RefreshCw, PackageX } from 'lucide-vue-next';
import axios from 'axios';
import { useHelpCenterStore } from '@commonStores/helpCenter';
const currencySymbol = computed(() => usePage().props.currency_symbol);

const helpStore = useHelpCenterStore();
const helpInformation = `
    <ul class='list-disc pl-5'>
        <li class='text-justify'>
            All kind of sales and returns are included to achieve this number excluding Void and cancelled layaway sale. We are taking regular, pending layaway, completed layaway, pending credit, completed credit sales, returns, and exchanges. We are showing this data based on the shift date not based on the sale date.
        </li>
    </ul>
`;

helpStore.setHelpData(helpInformation);

const props = defineProps({
    brandsData: {
        type: Array,
        required: true,
    },
    brandFooterData: {
        type: Object,
        required: true,
    },
    colorsData: {
        type: Array,
        required: true,
    },
    colorFooterData: {
        type: Object,
        required: true,
    },
    colorGroupsData: {
        type: Array,
        required: true,
    },
    colorGroupFooterData: {
        type: Object,
        required: true,
    },
    categoriesData: {
        type: Array,
        required: true,
    },
    categoryFooterData: {
        type: Object,
        required: true,
    },
    departmentsData: {
        type: Array,
        required: true,
    },
    departmentFooterData: {
        type: Object,
        required: true,
    },
    sizesData: {
        type: Array,
        required: true,
    },
    stylesData: {
        type: Array,
        required: true,
    },
    sizeFooterData: {
        type: Object,
        required: true,
    },
    styleFooterData: {
        type: Object,
        required: true,
    },
    todayHourlySales: {
        type: Array,
        required: true,
    },
    hourlyChartLabel: {
        type: Array,
        required: true,
    },
    yesterdayHourlySales: {
        type: Array,
        required: true,
    },
    todayHourlyTotalSales: {
        type: Array,
        required: true,
    },
    yesterdayHourlyTotalSales: {
        type: Array,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    },
    brandId: {
        type: Number,
        default: 0,
    },
    locationId: {
        type: Number,
        default: 0,
    },
    totalSalesByColor: {
        type: Object,
        required: true,
    },
    totalSalesByColorGroup: {
        type: Object,
        required: true,
    },
    totalSalesByBrand: {
        type: Object,
        required: true,
    },
    totalSalesByCategory: {
        type: Object,
        required: true,
    },
    totalSalesByDepartment: {
        type: Object,
        required: true,
    },
    totalSalesBySize: {
        type: Object,
        required: true,
    },
    totalSalesByStyle: {
        type: Object,
        required: true,
    },
    totalSales: {
        type: Number,
        required: true,
    },
    totalUnitsSold: {
        type: Number,
        required: true,
    },
    date: {
        type: String,
        required: true,
    },
    storeRevenueDashboardTableFilterTypes: {
        type: Object,
        required: true,
    },
    lastUpdate: {
        type: String,
        required: true,
    },
    totalCreditSalePendingAmount: {
        type: Number,
        required: true,
    },
});

const filters = reactive({
    location: { name: props.locations.find(location => props.locationId === location.id)?.name || 'All' },
    brand: { name: props.brands.find(brand => props.brandId === brand.id)?.name || 'All' },
    date: { name: props.date || null },
});

const getLocationData = (locationId) => {
    router.get(route('admin.store_revenue', { location_id: locationId, date: props.date, brand_id: props.brandId }));
};

const getBrandData = (brandId) => {
    router.get(route('admin.store_revenue', { location_id: props.locationId, date: props.date, brand_id: brandId }));
};

const refresh = () => {
    router.get(route('admin.store_revenue', { location_id: props.locationId, date: props.date, refresh: true }));
};

onMounted(() => {
    if (props.locations.length > 0 && props.locationId < 0) {
        const locationId = props.locations[0].id;
        router.get(route('admin.store_revenue', { location_id: locationId, date: props.date }));
    }
});

const isEmpty = (object) => {
    return Object.keys(object).length === 0;
};

const dataSets = computed(() => {
    return [
        {
            name: 'Yesterday`s Sales',
            type: 'bar',
            data: isNotEmpty(props.yesterdayHourlySales) ? props.yesterdayHourlySales : [0],
        }, {
            name: 'Today`s Sales',
            type: 'bar',
            data: isNotEmpty(props.todayHourlySales) ? props.todayHourlySales : [0],
        }
    ];
});

const hourlyTotalDataSets = computed(() => {
    return [
        {
            name: 'Yesterday`s Sales',
            type: 'bar',
            data: isNotEmpty(props.yesterdayHourlyTotalSales) ? props.yesterdayHourlyTotalSales : [0],
        }, {
            name: 'Today`s Sales',
            type: 'bar',
            data: isNotEmpty(props.todayHourlyTotalSales) ? props.todayHourlyTotalSales : [0],
        }
    ];
});

const state = reactive({
    chartLabels: ['12AM', '01AM', '02AM', '03AM', '04AM', '05AM', '06AM', '07AM', '08AM', '09AM', '10AM', '11AM', '12PM', '01PM', '02PM', '03PM', '04PM', '05PM', '06PM', '07PM', '08PM', '09PM', '10PM', '11PM'],

    columns: [
        {
            key: 'name',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 border-0 border-none bg-slate-200',
            sortable: true,
        },
        {
            key: 'sales_count',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            bodyClass: 'border-b-2 border-slate-300 text-right border-0 border-none bg-slate-200',
            sortable: true,
        },
        {
            key: 'total_sales',
            label: 'Sales',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            bodyClass: 'text-right border-0 border-none bg-slate-200',
            sortable: true,
        },
        {
            key: 'total_units_sold',
            label: 'Units Sold',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            bodyClass: 'text-right border-0 border-none bg-slate-200',
            sortable: true,
        },
        {
            key: 'upt',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            bodyClass: 'text-right border-0 border-none bg-slate-200',
            sortable: true,
            label: 'Unit Per Transaction'
        },
        {
            key: 'atv',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            bodyClass: 'text-right border-0 border-none bg-slate-200',
            sortable: true,
            label: 'Average Transaction Value'
        },
    ],

    sections: props.storeRevenueDashboardTableFilterTypes,

    subSectionLabels: {
        products: 'Products',
        categories: 'Categories',
        locations: 'Locations',
        brands: 'Brands',
        departments: 'Departments',
        colorGroups: 'Color Groups',
        colors: 'Colors',
        sizes: 'Sizes',
        styles: 'Styles',
    },

    records: null,
    modelId: null,
    showDetailModal: false,
    totalSales: null,
    moduleTitleName: null,
    totalUnitsSold: null,

    selectedSection: props.storeRevenueDashboardTableFilterTypes.categories,
    selectedSubSectionLabels: 'Products',
});

const updateDate = (date) => {
    router.get(route('admin.store_revenue', { location_id: props.locationId, date }));
};

const getSectionData = () => {
    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.categories) {
        return props.categoriesData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.brands) {
        return props.brandsData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.colors) {
        return props.colorsData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.colorGroups) {
        return props.colorGroupsData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.sizes) {
        return props.sizesData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.styles) {
        return props.stylesData;
    }
    return props.departmentsData;
};

const getFooterSectionData = () => {
    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.categories) {
        return props.categoryFooterData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.brands) {
        return props.brandFooterData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.colors) {
        return props.colorFooterData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.colorGroups) {
        return props.colorGroupFooterData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.sizes) {
        return props.sizeFooterData;
    }

    if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.styles) {
        return props.styleFooterData;
    }

    return props.departmentFooterData;
};

const getClickedProductData = async (modelId, modelName) => {
    state.modelId = modelId;
    state.selectedSubSectionLabels = state.subSectionLabels.products;
    axios.get(route('admin.products.get_product_sales_summary', {
        locationId: props.locationId === 0 ? null : props.locationId,
        date: props.date,
        id: modelId,
        type: state.selectedSection
    })).then((response) => {
        state.records = null;
        state.moduleTitleName = modelName;
        state.totalSales = null;
        state.totalUnitsSold = null;

        state.records = response.data.products;
        state.totalSales = response.data.total_sales;
        state.totalUnitsSold = response.data.total_units_sold;
        state.showDetailModal = true;
    });
};

const getRecordsBasedOnSelectedSections = (detailSection) => {
    state.selectedSubSectionLabels = detailSection;
    const locationId = props.locationId === 0 ? null : props.locationId;

    if (detailSection === state.subSectionLabels.categories) {
        if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.categories) {
            const category = props.categoriesData.find((category) => category.id === state.modelId);
            state.records = [];
            state.records.push(
                {
                    name: category.name,
                    total_sales: state.totalSales,
                    total_units_sold: state.totalUnitsSold,
                },
            );

            return;
        }

        axios.get(route('admin.categories.get_category_sales_summary', {
            locationId,
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = null;

            state.records = response.data.categories;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }

    if (detailSection === state.subSectionLabels.locations) {
        if (props.locationId !== 0) {
            const location = props.locations.find((location) => location.id === props.locationId);
            state.records = [];
            state.records.push(
                {
                    name: location.name,
                    total_sales: state.totalSales,
                    total_units_sold: state.totalUnitsSold,
                },
            );

            return;
        }

        axios.get(route('admin.locations.get_location_sales_summary', {
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = response.data.locations;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }

    if (detailSection === state.subSectionLabels.products) {
        axios.get(route('admin.products.get_product_sales_summary', {
            locationId,
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = null;

            state.records = response.data.products;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }

    if (detailSection === state.subSectionLabels.brands) {
        if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.brands) {
            const brand = props.brandsData.find((brand) => brand.id === state.modelId);
            state.records = [];
            state.records.push(
                {
                    name: brand.name,
                    total_sales: state.totalSales,
                    total_units_sold: state.totalUnitsSold,
                },
            );

            return;
        }

        axios.get(route('admin.brands.get_brand_sales_summary', {
            locationId,
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = null;

            state.records = response.data.brands;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }

    if (detailSection === state.subSectionLabels.departments) {
        if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.departments) {
            const department = props.departmentsData.find((department) => department.id === state.modelId);
            state.records = [];
            state.records.push(
                {
                    name: department.name,
                    total_sales: state.totalSales,
                    total_units_sold: state.totalUnitsSold,
                },
            );

            return;
        }

        axios.get(route('admin.departments.get_department_sales_summary', {
            locationId,
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = null;

            state.records = response.data.departments;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }

    if (detailSection === state.subSectionLabels.colorGroups) {
        if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.colorGroups) {
            const colorGroup = props.colorGroupsData.find((colorGroup) => colorGroup.id === state.modelId);
            state.records = [];
            state.records.push(
                {
                    name: colorGroup.name,
                    total_sales: state.totalSales,
                    total_units_sold: state.totalUnitsSold,
                },
            );

            return;
        }

        axios.get(route('admin.color_groups.get_color_group_sales_summary', {
            locationId,
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = null;

            state.records = response.data.color_groups;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }

    if (detailSection === state.subSectionLabels.colors) {
        if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.colors) {
            const color = props.colorsData.find((color) => color.id === state.modelId);
            state.records = [];
            state.records.push(
                {
                    name: color.name,
                    total_sales: state.totalSales,
                    total_units_sold: state.totalUnitsSold,
                },
            );

            return;
        }

        axios.get(route('admin.colors.get_color_sales_summary', {
            locationId,
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = null;

            state.records = response.data.colors;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }

    if (detailSection === state.subSectionLabels.sizes) {
        if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.sizes) {
            const size = props.sizesData.find((size) => size.id === state.modelId);
            state.records = [];
            state.records.push(
                {
                    name: size.name,
                    total_sales: state.totalSales,
                    total_units_sold: state.totalUnitsSold,
                },
            );

            return;
        }

        axios.get(route('admin.sizes.get_size_sales_summary', {
            locationId,
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = null;

            state.records = response.data.sizes;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }

    if (detailSection === state.subSectionLabels.styles) {
        if (state.selectedSection === props.storeRevenueDashboardTableFilterTypes.styles) {
            const style = props.stylesData.find((style) => style.id === state.modelId);
            state.records = [];
            state.records.push(
                {
                    name: style.name,
                    total_sales: state.totalSales,
                    total_units_sold: state.totalUnitsSold,
                },
            );

            return;
        }

        axios.get(route('admin.styles.get_style_sales_summary', {
            locationId,
            date: props.date,
            id: state.modelId,
            type: state.selectedSection
        })).then((response) => {
            state.records = null;

            state.records = response.data.styles;
            state.totalSales = response.data.total_sales;
            state.totalUnitsSold = response.data.total_units_sold;
        });
    }
};

const isNotEmpty = (object) => {
    if (typeof (object) === 'object') {
        return Object.keys(object).length !== 0;
    }
};

const showPendingCreditSale = () => {
    router.get(route('admin.credit_sales.index', { location_id: props.locationId }));
};

const downloadPdfStoreRecord = () => {
    printReport(route('admin.print_store_revenue', { date: props.date, brand_id: props.brandId, type: state.selectedSection, location_id: props.locationId }));
};

const exportCsvRecords = () => {
    return exportRecords(
        'export-store-revenue/',
        'location-revenue.csv',
        { date: props.date, brand_id: props.brandId, type: state.selectedSection, location_id: props.locationId }
    );
};

const exportExcelRecords = () => {
    return exportRecords(
        'export-store-revenue/',
        'location-revenue.xlsx',
        { date: props.date, brand_id: props.brandId, type: state.selectedSection, location_id: props.locationId }
    );
};
</script>
