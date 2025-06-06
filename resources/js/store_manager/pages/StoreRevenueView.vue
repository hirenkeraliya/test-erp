<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <PageTitle title="Store Revenue Dashboard" />

        <div class="content content--top-nav mr-5">
            <InfoAlert
                color="primary"
                class="mb-3 mt-5"
            >
                All kind of sales and returns are included to achieve this number excluding Void and cancelled layaway sale. We are taking regular, pending layaway, completed layaway, pending credit, completed credit sales, returns, and exchanges. We are showing this data based on the shift date not based on the sale date.
            </InfoAlert>

            <div class="items-center block my-auto mt-5 2xl:flex xl:block lg:block md:block sm:block intro-y">
                <div class="block sm:flex flex-wrap mr-auto justify-items-end">
                    <div class="sm:flex ml-0 2xl:ml-2 xl:ml-0 lg:ml-0 md:ml-0 sm:ml-0 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-0 sm:mt-0">
                        <FormSelectBox
                            class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                            :selected-record="brandId"
                            :records="brands"
                            :placeholder="'Please select Brand'"
                            @update:selected-record="getBrandData"
                        />
                        <JDatePicker
                            class="mr-2"
                            :required="true"
                            label-class="hidden"
                            :input-value="date"
                            @update:input-value="updateDate($event)"
                        />
                    </div>

                    <div class="mt-6 sm:mt-0 ml-0 flex w-full mb-3 sm:ml-0 sm:w-auto">
                        <div class="flex">
                            <div>
                                <div class="mt-0.5 text-slate-800 text-center">
                                    Revenue
                                </div>

                                <div class="text-lg font-medium text-center text-primary dark:text-slate-300 xl:text-xl">
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

                                <div class="text-lg font-medium text-center text-primary dark:text-slate-300 xl:text-xl">
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

            <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-5 bg-slate-200 rounded-xl p-5">
                <div class="col-span-12 lg:col-span-12 md:col-span-12">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-2">
                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-full"
                            @click="showPendingCreditSale()"
                        >
                            <div class="mr-2.5">
                                <p class="text-sm lg:text-lg font-semibold text-slate-700">
                                    Credit Sale Pending
                                </p>
                                <Tippy
                                    tag="p"
                                    class="mt-1 text-sm flex items-center"
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

            <div class="mb-5 mt-5">
                <h2 class="mr-5 text-lg font-medium truncate">
                    Sales by Hour
                </h2>

                <MultiBarOrLineChart
                    :chart-id="`monthly-based-bar-or-line-chart`"
                    :datasets="dataSets"
                    :labels="hourlyChartLabel"
                    :legend-data="['Yesterday`s Sales', 'Today`s Sales']"
                    :show-bar-and-line-chart="true"
                    file-name="sales-by-hour-bar-or-line"
                    :filters="filters"
                />
            </div>

            <div class="col-span-12 pr-0 sm:pr-10 md:pr-10 lg:pr-10 xl:pr-10 2xl:pr-10 sm:col-span-6 xl:col-span-6 intro-y mt-4 sm:mt-0 md:mt-0 lg:mt-0 xl:mt-0 2xl:mt-0 pl-4">
                <div>
                    <h2 class="mr-5 text-lg font-medium truncate">
                        Accumulated Sales by Hour
                    </h2>

                    <MultiBarOrLineChart
                        :chart-id="`accumulated-hourly-based-multi-line-chart`"
                        :datasets="hourlyTotalDataSets"
                        :labels="hourlyChartLabel"
                        :legend-data="['Yesterday`s Sales', 'Today`s Sales']"
                        :show-bar-and-line-chart="true"
                        file-name="accumulated-sales-by-hour-bar-or-line"
                        :filters="filters"
                    />
                </div>
            </div>

            <div class="mt-5">
                <div class="grid grid-cols-1 mt-5 2xl:grid-cols-2 xl:grid-cols-4 lg:grid-cols-4 md:grid-cols-2 sm:grid-cols-1">
                    <div class="mx-10">
                        <h2
                            class="mb-3 font-bold text-center text-pink"
                            :class="!isEmpty(totalSalesByCategory.total_sales) ? 'text-pink-900' : 'text-gray-600'"
                        >
                            Categories
                        </h2>

                        <PieChart
                            chart-id="by-categories"
                            :section-name="props.storeRevenueDashboardTableFilterTypes.categories.toString()"
                            :labels="!isEmpty(totalSalesByCategory.total_sales) ? totalSalesByCategory.labels : ['No data available']"
                            :data="!isEmpty(totalSalesByCategory.total_sales) ? totalSalesByCategory.total_sales : [10, 20, 30, 40, 50]"
                            :background-color="!isEmpty(totalSalesByCategory.total_sales)"
                            :dataset-label="'Sales('+currencySymbol+')'"
                            :filters="filters"
                        />
                    </div>

                    <div class="mx-10">
                        <h2
                            class="mb-3 font-bold text-center text-pink"
                            :class="!isEmpty(totalSalesByColor.total_sales) ? 'text-pink-900' : 'text-gray-600'"
                        >
                            Colors
                        </h2>

                        <PieChart
                            chart-id="by-colors"
                            :section-name="props.storeRevenueDashboardTableFilterTypes.colors.toString()"
                            :labels="!isEmpty(totalSalesByColor.total_sales) ? totalSalesByColor.labels : ['No data available']"
                            :data="!isEmpty(totalSalesByColor.total_sales) ? totalSalesByColor.total_sales : [10, 20, 30, 40, 50]"
                            :background-color="false"
                            :dataset-label="'Sales('+currencySymbol+')'"
                            :filters="filters"
                        />
                    </div>

                    <div class="mx-10">
                        <h2
                            class="mb-3 font-bold text-center text-pink"
                            :class="!isEmpty(totalSalesByBrand.total_sales) ? 'text-pink-900' : 'text-gray-600'"
                        >
                            Brands
                        </h2>

                        <PieChart
                            chart-id="by-brands"
                            :section-name="props.storeRevenueDashboardTableFilterTypes.brands.toString()"
                            :labels="!isEmpty(totalSalesByBrand.total_sales) ? totalSalesByBrand.labels : ['No data available']"
                            :data="!isEmpty(totalSalesByBrand.total_sales) ? totalSalesByBrand.total_sales : [10, 20, 30, 40, 50]"
                            :background-color="!isEmpty(totalSalesByBrand.total_sales)"
                            :dataset-label="'Sales('+currencySymbol+')'"
                            :filters="filters"
                        />
                    </div>

                    <div class="mx-10 mt-4 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-4 sm:mt-4">
                        <h2
                            class="mb-3 font-bold text-center text-pink"
                            :class="!isEmpty(totalSalesByDepartment.total_sales) ? 'text-pink-900' : 'text-gray-600'"
                        >
                            Departments
                        </h2>

                        <PieChart
                            chart-id="by-departments"
                            :section-name="props.storeRevenueDashboardTableFilterTypes.departments.toString()"
                            :labels="!isEmpty(totalSalesByDepartment.total_sales) ? totalSalesByDepartment.labels : ['No data available']"
                            :data="!isEmpty(totalSalesByDepartment.total_sales) ? totalSalesByDepartment.total_sales : [10, 20, 30, 40, 50]"
                            :background-color="!isEmpty(totalSalesByDepartment.total_sales)"
                            :dataset-label="'Sales('+currencySymbol+')'"
                            :width="!isEmpty(totalSalesByDepartment.total_sales) ? 405 : 360"
                            :height="!isEmpty(totalSalesByDepartment.total_sales) ? 280 : 200"
                            :filters="filters"
                        />
                    </div>

                    <div class="mx-10 mt-4 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-4 sm:mt-4">
                        <h2
                            class="mb-3 font-bold text-center text-pink"
                            :class="!isEmpty(totalSalesByColorGroup.total_sales) ? 'text-pink-900' : 'text-gray-600'"
                        >
                            Color Groups
                        </h2>

                        <PieChart
                            chart-id="by-color-groups"
                            :section-name="props.storeRevenueDashboardTableFilterTypes.colorGroups.toString()"
                            :labels="!isEmpty(totalSalesByColorGroup.total_sales) ? totalSalesByColorGroup.labels : ['No data available']"
                            :data="!isEmpty(totalSalesByColorGroup.total_sales) ? totalSalesByColorGroup.total_sales : [10, 20, 30, 40, 50]"
                            :background-color="false"
                            :dataset-label="'Sales('+currencySymbol+')'"
                            :filters="filters"
                        />
                    </div>

                    <div class="mx-10 mt-4 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-4 sm:mt-4">
                        <h2
                            class="mb-3 font-bold text-center text-pink"
                            :class="!isEmpty(totalSalesBySize.total_sales) ? 'text-pink-900' : 'text-gray-600'"
                        >
                            Sizes
                        </h2>

                        <PieChart
                            chart-id="by-sizes"
                            :section-name="props.storeRevenueDashboardTableFilterTypes.sizes.toString()"
                            :labels="!isEmpty(totalSalesBySize.total_sales) ? totalSalesBySize.labels : ['No data available']"
                            :data="!isEmpty(totalSalesBySize.total_sales) ? totalSalesBySize.total_sales : [10, 20, 30, 40, 50]"
                            :background-color="!isEmpty(totalSalesBySize.total_sales)"
                            :dataset-label="'Sales('+currencySymbol+')'"
                            :filters="filters"
                        />
                    </div>

                    <div class="mx-10 mt-4 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-4 sm:mt-4">
                        <h2
                            class="mb-3 font-bold text-center text-pink"
                            :class="!isEmpty(totalSalesByStyle.total_sales) ? 'text-pink-900' : 'text-gray-600'"
                        >
                            Styles
                        </h2>

                        <PieChart
                            chart-id="by-styles"
                            :section-name="props.storeRevenueDashboardTableFilterTypes.styles.toString()"
                            :labels="!isEmpty(totalSalesByStyle.total_sales) ? totalSalesByStyle.labels : ['No data available']"
                            :data="!isEmpty(totalSalesByStyle.total_sales) ? totalSalesByStyle.total_sales : [10, 20, 30, 40, 50]"
                            :background-color="!isEmpty(totalSalesByStyle.total_sales)"
                            :dataset-label="'Sales('+currencySymbol+')'"
                            :filters="filters"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-5">
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
</template>

<script setup>
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import MultiBarOrLineChart from '@commonComponents/MultiBarOrLineChart.vue';
import PieChart from '@commonComponents/PieChart.vue';
import { displayAmountWithCurrencySymbol, exportRecords, printReport, truncateDecimal } from '@commonServices/helper';
import { router, usePage } from '@inertiajs/vue3';
import DashboardMenu from '@storeManagerPages/DashboardMenu.vue';
import { Info, PackageX, RefreshCw } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { route } from 'ziggy';

const currencySymbol = computed(() => usePage().props.currency_symbol);

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
    brands: {
        type: Array,
        required: true,
    },
    brandId: {
        type: Number,
        default: 0,
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
    brand: { name: props.brands.find(brand => props.brandId === brand.id)?.name || 'All' },
    date: { name: props.date || null },
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

    selectedSection: props.storeRevenueDashboardTableFilterTypes.categories,
});

const updateDate = (date) => {
    router.get(route('store_manager.store_revenue', { date }));
};

const getBrandData = (brandId) => {
    router.get(route('store_manager.store_revenue', { location_id: props.locationId, date: props.date, brand_id: brandId }));
};

const refresh = () => {
    router.get(route('store_manager.store_revenue', { location_id: props.locationId, date: props.date, refresh: true }));
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

const isNotEmpty = (object) => {
    if (typeof (object) === 'object') {
        return Object.keys(object).length !== 0;
    }
};

const showPendingCreditSale = () => {
    router.get(route('store_manager.credit_sales.index'));
};

const downloadPdfStoreRecord = () => {
    printReport(route('store_manager.print_store_revenue', { date: props.date, brand_id: props.brandId, type: state.selectedSection }));
};

const exportCsvRecords = () => {
    return exportRecords(
        'export-store-revenue/',
        'location-revenue.csv',
        { date: props.date, brand_id: props.brandId, type: state.selectedSection }
    );
};

const exportExcelRecords = () => {
    return exportRecords(
        'export-store-revenue/',
        'location-revenue.xlsx',
        { date: props.date, brand_id: props.brandId, type: state.selectedSection }
    );
};

</script>
