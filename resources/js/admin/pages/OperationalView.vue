<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <PageTitle title="Orders" />

        <div class="content content--top-nav mr-5">
            <template v-if="!hasPermission('dashboard_operational')">
                <h2 class="mr-auto mt-5 text-xl font-semibold text-danger">
                    You don't have Permission.
                </h2>
            </template>

            <div v-else>
                <div class="items-center block my-auto mt-5 lg:mt-2 2xl:flex xl:block lg:block md:block sm:block intro-y">
                    <div class="block sm:flex flex-wrap mr-auto justify-items-start">
                        <div
                            class="sm:flex ml-0 2xl:ml-2 xl:ml-0 lg:ml-0 md:ml-0 md:flex-wrap sm:ml-0 2xl:mt-3 xl:mt-3 lg:mt-3 md:mt-0 sm:mt-0"
                        >
                            <FormSelectBox
                                class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                                :selected-record="state.locationId"
                                :records="locations"
                                :placeholder="'Please select Location'"
                                @update:selected-record="getLocationData"
                            />

                            <FormSelectBox
                                class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                                :selected-record="state.brandId"
                                :records="brands"
                                :placeholder="'Please select Brand'"
                                @update:selected-record="getBrandData"
                            />

                            <JDatePicker
                                class="sm:mr-2"
                                :required="true"
                                label-class="hidden"
                                :input-value="state.date"
                                @update:input-value="updateDate($event)"
                            />
                        </div>
                    </div>

                    <div class="mt-6 lg:mt-3 ml-0 flex w-full mb-3 md:mb-0 2xl:mb-3 sm:ml-3 sm:w-auto">
                        <Tippy
                            content="Refresh Data"
                            class="btn btn-outline-primary"
                            @click="refresh()"
                        >
                            <RefreshCw class="text-primary w-5" />
                        </Tippy>
                        <p class="ml-2 text-xs">
                            <span class="text-sm font-medium">Last Update:</span><br>{{ state.lastUpdate }}
                        </p>
                    </div>
                </div>

                <div class="relative z-0 grid grid-cols-5 mb-2">
                    <div class="col-span-12 mt-2">
                        <div class="grid grid-cols-12 gap-6 mt-5">
                            <ComboOperationalViewSalesAmount
                                title="Today"
                                tippy-title="yesterday"
                                :sale-amount="state.salesCount.todayTotalSaleAmount"
                                :sale-percentage="state.salesCount.todayTotalSalePercentage"
                                first-number-info="Formula: Today Sale Amount - Today Return Amount. Percentage will be displayed based on today vs yesterday comparison."
                                second-number-info="Formula: Last Year This Date Sale Amount - Last Year This Date Return Amount. Percentage will be displayed based on last year this date vs today."
                                second-title="Last Year This Date"
                                second-tippy-title="Last Year This Date"
                                :second-sale-amount="state.salesCount.previousYearTodaySaleAmount"
                                :second-sale-percentage="state.salesCount.previousYearTodaySalePercentage"
                                :is-data-fetching="state.salesCount.length === 0"
                            />

                            <ComboOperationalViewSalesAmount
                                title="MTD (Month to date)"
                                tippy-title="last month"
                                :sale-amount="state.salesCount.mtdTotalSaleAmount"
                                :sale-percentage="state.salesCount.mtdTotalSalePercentage"
                                first-number-info="Formula: Current Month Sales - Current Month Sale Returns. Percentage will be displayed based on current month till date vs previous month till date."
                                second-number-info="Formula: Last Year Current Month Sales - Last Year Current Month Sale Returns. Percentage will be displayed based on last year current month till date vs current month till date."
                                second-title="Last Year MTD"
                                second-tippy-title="Last Year This month"
                                :second-sale-amount="state.salesCount.previousYearMonthSaleAmount"
                                :second-sale-percentage="state.salesCount.previousYearMonthSalePercentage"
                                :is-data-fetching="state.salesCount.length === 0"
                            />

                            <ComboOperationalViewSalesAmount
                                title="YTD Sales"
                                tippy-title="last year"
                                :sale-amount="state.salesCount.ytdTotalSaleAmount"
                                :sale-percentage="state.salesCount.ytdTotalSalePercentage"
                                first-number-info="Formula: Current Year Sales Till Date - Current Year Sale Returns Till Date. Percentage will be displayed based on current year till date vs last year till date."
                                second-number-info="Formula: Last Year Till Date Sales - Last Year Till Date Sale Returns. Percentage will be displayed based on last year till date vs current year till date."
                                second-title="Last YTD Sales"
                                second-tippy-title="Last Year"
                                :second-sale-amount="state.salesCount.previousYearTillTodaySaleAmount"
                                :second-sale-percentage="state.salesCount.previousYearTillTodaySalePercentage"
                                :is-data-fetching="state.salesCount.length === 0"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 lg:grid-cols-6 gap-0 lg:gap-x-14 mt-10">
                    <div class="col-span-12 lg:col-span-6 md:col-span-12 lg:h-full lg:pr-7">
                        <div class="grid grid-cols-12 lg:grid-flow-row-dense gap-6 h-full md:w-full">
                            <SalesDetails
                                class="col-span-12 2xl:col-span-4 xl:col-span-4 lg:col-span-3 md:col-span-12 sm:col-span-12"
                                title="Today"
                                total-receipt-info="Formula: Today Sales"
                                total-unit-sold-info="Formula: Today Units Sold - Today Units Returned."
                                upt-info="Formula: Today Units Sold - Today Units Returned / Today Sales."
                                atv-info="Formula: Today Sales Amount - Today Return Amount / Today Sales."
                                :sales-date="state.today"
                            />

                            <SalesDetails
                                class="col-span-12 2xl:col-span-4 xl:col-span-4 lg:col-span-3 md:col-span-12 sm:col-span-12"
                                title="This Month"
                                total-receipt-info="Formula: Current Month Sales"
                                total-unit-sold-info="Formula: Current Month Units Sold - Current Month Units Returned."
                                upt-info="Formula: Current Month Units Sold - Current Month Units Returned / Current Month Sales."
                                atv-info="Formula: Current Month Sales Amount - Current Month Return Amount / Current Month Sales."
                                :sales-date="state.thisMonth"
                            />

                            <SalesDetails
                                title="This Year"
                                total-receipt-info="Formula: Current Year Sales"
                                total-unit-sold-info="Formula: Current Year Units Sold - Current Year Units Returned."
                                upt-info="Formula: Current Year Units Sold - Current Year Units Returned / Current Year Sales."
                                atv-info="Formula: Current Year Sales Amount - Current Year Return Amount / Current Year Sales."
                                class="col-span-12 2xl:col-span-4 xl:col-span-4 lg:col-span-3 md:col-span-12 sm:col-span-12"
                                :sales-date="state.thisYear"
                            />
                        </div>
                    </div>

                    <div class="col-span-12 lg:col-span-6 md:col-span-12 mt-10">
                        <MultiBarOrLineChart
                            chart-id="Revenue"
                            chart-info="Formula: Today Sale Amount - Today Return Amount By Each Month"
                            title-of-chart="Revenue"
                            :datasets="dataSets"
                            :labels="state.revenueChartData.labels"
                            :legend-data="['Current Year', 'Last Year']"
                            :show-bar-and-line-chart="true"
                            file-name="revenue"
                            :filters="filters"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4 md:gap-y-10 mt-10 lg:gap-14 lg:mt-10">
                    <div class="col-span-12 lg:col-span-6 md:col-span-12">
                        <BarOrLineChart
                            v-if="state.uptChartData.data"
                            title-of-chart="Units Per Transaction (UPT)"
                            chart-info="Formula: Today Units Sold - Today Units Returned / Today Sales."
                            chart-id="units-per-transaction-bar-or-line"
                            :data="isNotEmpty(state.uptChartData.data) ? state.uptChartData.data : [0]"
                            data-set-label="Units Per Transaction"
                            :labels="state.uptChartData.labels"
                            :background-color="isNotEmpty(state.uptChartData.data)"
                            legend-data="Units Per Transaction"
                            :filters="filters"
                        />
                    </div>

                    <div class="col-span-12 lg:col-span-6 md:col-span-12">
                        <BarOrLineChart
                            v-if="state.atvChartData.data"
                            title-of-chart="Average Transaction Value (ATV)"
                            chart-info="Formula: Today Sales - Today Return / Today Sales."
                            chart-id="average-transaction-value-bar-or-line"
                            :data="isNotEmpty(state.atvChartData.data) ? state.atvChartData.data : [0]"
                            :data-set-label="'Average Transaction Value(' + currencySymbol + ') '"
                            :labels="state.atvChartData.labels"
                            :background-color="isNotEmpty(state.atvChartData.data)"
                            legend-data="Average Transaction Value"
                            :filters="filters"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-y-6 lg:gap-14 mt-10">
                    <div class="col-span-12 lg:col-span-6 md:col-span-12">
                        <TopPromoters
                            :top-promoters="state.topPromoters ?? []"
                            :location-id="state.locationId"
                            :date="state.date"
                            type="today"
                            heading="This Month Top 10 Promoters (By sales)"
                            route-url="admin.sales_by_promoters.index"
                            :is-data-fetching="state.topPromoters === null"
                        />
                    </div>

                    <div class="col-span-12 lg:col-span-6 md:col-span-12">
                        <TopPromoters
                            :top-promoters="state.thisYearTopPromoters ?? []"
                            :location-id="state.locationId"
                            :date="state.date"
                            type="yearly"
                            heading="This Year Top 10 Promoters (By sales)"
                            route-url="admin.sales_by_promoters.index"
                            :is-data-fetching="state.thisYearTopPromoters === null"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import BarOrLineChart from '@commonComponents/BarOrLineChart.vue';
import MultiBarOrLineChart from '@commonComponents/MultiBarOrLineChart.vue';
import ComboOperationalViewSalesAmount from '@commonComponents/ComboOperationalViewSalesAmount.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import SalesDetails from '@commonComponents/SalesDetails.vue';
import TopPromoters from '@commonComponents/TopPromoters.vue';
import { hasPermission } from '@commonServices/helper';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { RefreshCw } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { route } from 'ziggy';

const helpStore = useHelpCenterStore();
const helpInformation = `
    <ul class='list-disc pl-5'>
        <li class='text-justify'>
            All kinds of sales and returns are included to achieve this number, excluding void and cancelled layaway sales. We are taking regular, pending layaway, completed layaway, pending credit, completed credit sales, returns, and exchanges. We are showing this data based on the shift date, not based on the sale date.
        </li>
    </ul>
`;

helpStore.setHelpData(helpInformation);

const props = defineProps({
    locationId: {
        type: Number,
        default: 0,
    },
    date: {
        type: String,
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
});

const filters = reactive({
    location: { name: props.locations.find(location => props.locationId === location.id)?.name || 'All' },
    brand: { name: props.brands.find(brand => props.brandId === brand.id)?.name || 'All' },
    date: { name: props.date || null },
});

const state = reactive({
    date: props.date,
    locationId: props.locationId,
    brandId: props.brandId,
    salesCount: [],
    today: [],
    thisMonth: [],
    thisYear: [],
    revenueChartData: [],
    atvChartData: [],
    uptChartData: [],
    topPromoters: null,
    thisYearTopPromoters: null,
    refresh: false,
    lastUpdate: null,
});

const getLocationData = (locationId) => {
    if (hasPermission('dashboard_operational')) {
        state.locationId = locationId;
        getsSalesCount();
        getsToday();
        getsThisMonth();
        getsThisYear();
        getRevenueChartData();
        getAtvChartData();
        getUptChartData();
        getTopPromoters();
        getThisYearTopPromoters();
    }
};

const getBrandData = (brandId) => {
    if (hasPermission('dashboard_operational')) {
        clearData();
        state.brandId = brandId;
        getsSalesCount();
        getsToday();
        getsThisMonth();
        getsThisYear();
        getRevenueChartData();
        getAtvChartData();
        getUptChartData();
        getTopPromoters();
        getThisYearTopPromoters();
    }
};

const updateDate = (date) => {
    if (hasPermission('dashboard_operational') && date) {
        clearData();
        state.date = date;
        getsSalesCount();
        getsToday();
        getsThisMonth();
        getsThisYear();
        getRevenueChartData();
        getAtvChartData();
        getUptChartData();
        getTopPromoters();
        getThisYearTopPromoters();
    }
};

const getsSalesCount = () => {
    axios.get(route('admin.get_operational_sales_count', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.salesCount = response.data.salesCount;
            state.lastUpdate = response.data.lastUpdate;
        });
};

const getsToday = () => {
    axios.get(route('admin.get_operational_today_sales', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.today = response.data.today;
        });
};

const getsThisMonth = () => {
    axios.get(route('admin.get_operational_this_month_sales', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisMonth = response.data.thisMonth;
        });
};

const getsThisYear = () => {
    axios.get(route('admin.get_operational_this_year_sales', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisYear = response.data.thisYear;
        });
};

const getRevenueChartData = () => {
    axios.get(route('admin.get_operational_revenue_chart_data', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.revenueChartData = response.data.revenueChartData;
        });
};

const getAtvChartData = () => {
    axios.get(route('admin.get_operational_atv_chart_data', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.atvChartData = response.data.atvChartData;
        });
};

const getUptChartData = () => {
    axios.get(route('admin.get_operational_upt_chart_data', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.uptChartData = response.data.uptChartData;
        });
};

const getTopPromoters = () => {
    axios.get(route('admin.get_operational_top_promoters', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.topPromoters = response.data.topPromoters.length > 0 ? response.data.topPromoters : [];
        });
};

const getThisYearTopPromoters = () => {
    axios.get(route('admin.get_operational_this_year_top_promoters', { location_id: state.locationId, date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisYearTopPromoters = response.data.thisYearTopPromoters.length > 0 ? response.data.thisYearTopPromoters : [];
        });
};

const clearData = () => {
    state.refresh = true;
    state.salesCount = [];
    state.today = [];
    state.thisMonth = [];
    state.thisYear = [];
    state.revenueChartData = [];
    state.atvChartData = [];
    state.uptChartData = [];
    state.topPromoters = null;
    state.thisYearTopPromoters = null;
};

const refresh = () => {
    if (hasPermission('dashboard_operational')) {
        clearData();
        getsSalesCount();
        getsToday();
        getsThisMonth();
        getsThisYear();
        getRevenueChartData();
        getAtvChartData();
        getUptChartData();
        getTopPromoters();
        getThisYearTopPromoters();
    }
};

const currencySymbol = computed(() => usePage().props.currency_symbol);
getLocationData(0);
getBrandData(0);

const isNotEmpty = (object) => {
    if (typeof (object) === 'object') {
        return Object.keys(object).length !== 0;
    }
};

const dataSets = computed(() => {
    return [
        {
            name: 'Current Year',
            type: 'bar',
            data: isNotEmpty(state.revenueChartData.current_year_data) ? state.revenueChartData.current_year_data : [0],
        }, {
            name: 'Last Year',
            type: 'bar',
            data: isNotEmpty(state.revenueChartData.last_year_data) ? state.revenueChartData.last_year_data : [0],
        }
    ];
});
</script>
