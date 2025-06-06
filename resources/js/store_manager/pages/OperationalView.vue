<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <PageTitle title="Operational Dashboard" />

        <div class="content content--top-nav mr-5">
            <div class="items-center block my-auto mt-5 2xl:flex xl:block lg:block md:block sm:block intro-y">
                <div class="block sm:flex flex-wrap mr-auto justify-items-start">
                    <div class="sm:flex ml-0 2xl:ml-2 xl:ml-0 lg:ml-0 md:ml-0 sm:ml-0 2xl:mt-0 xl:mt-0 lg:mt-0 md:mt-0 sm:mt-0">
                        <FormSelectBox
                            class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                            :selected-record="state.brandId"
                            :records="brands"
                            :placeholder="'Please select Brand'"
                            @update:selected-record="getBrandData"
                        />

                        <JDatePicker
                            class="mr-2"
                            :required="true"
                            label-class="hidden"
                            :input-value="state.date"
                            @update:input-value="updateDate($event)"
                        />

                        <OutlinePrimaryButton
                            type="button"
                            text="Clear"
                            class="w-20 h-10 btn-sm mt-2"
                            @click="clearAll()"
                        />
                    </div>
                </div>

                <div class="mt-6 sm:mt-0 ml-0 flex w-full mb-3 sm:ml-3 sm:w-auto justify-items-end">
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

            <div class="relative z-0 grid grid-cols-5">
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
                            second-tippy-title="today"
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
                            second-title="Last Year"
                            second-tippy-title="current month"
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
                            second-tippy-title="current Year"
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

            <div class="grid grid-cols-2 mt-10 mb-5 md:grid-cols-12 sm:grid-cols-12">
                <div class="col-span-12 2xl:col-span-6 xl:col-span-6 lg:col-span-6 md:col-span-12 sm:col-span-12 intro-y">
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

                <div class="col-span-12 2xl:col-span-6 xl:col-span-6 lg:col-span-6 md:col-span-12 sm:col-span-12 intro-y">
                    <BarOrLineChart
                        v-if="state.atvChartData.data"
                        title-of-chart="Average Transaction Value (ATV)"
                        chart-info="Formula: Today Sales Amount - Today Return Amount / Today Sales."
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

            <div class="grid grid-cols-12 gap-0 lg:gap-14 mt-5">
                <div class="col-span-12 lg:col-span-6 md:col-span-12 mt-10">
                    <TopPromoters
                        :top-promoters="state.topPromoters ?? []"
                        :date="state.date"
                        type="today"
                        heading="This Month Top 10 Promoters (By sales)"
                        route-url="store_manager.sales_by_promoters.index"
                        :is-data-fetching="state.topPromoters === null"
                    />
                </div>
                <div class="col-span-12 lg:col-span-6 md:col-span-12 mt-10">
                    <TopPromoters
                        :top-promoters="state.thisYearTopPromoters ?? []"
                        :date="state.date"
                        type="yearly"
                        heading="This Year Top 10 Promoters (By sales)"
                        route-url="store_manager.sales_by_promoters.index"
                        :is-data-fetching="state.thisYearTopPromoters === null"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import BarOrLineChart from '@commonComponents/BarOrLineChart.vue';
import ComboOperationalViewSalesAmount from '@commonComponents/ComboOperationalViewSalesAmount.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import MultiBarOrLineChart from '@commonComponents/MultiBarOrLineChart.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import SalesDetails from '@commonComponents/SalesDetails.vue';
import TopPromoters from '@commonComponents/TopPromoters.vue';
import { usePage } from '@inertiajs/vue3';
import DashboardMenu from '@storeManagerPages/DashboardMenu.vue';
import axios from 'axios';
import { RefreshCw } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { route } from 'ziggy';

const currencySymbol = computed(() => usePage().props.currency_symbol);

const props = defineProps({
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
});

const state = reactive({
    date: props.date,
    salesCount: [],
    today: [],
    thisMonth: [],
    thisYear: [],
    revenueChartData: [],
    atvChartData: [],
    uptChartData: [],
    topPromoters: null,
    thisYearTopPromoters: null,
    brandId: props.brandId,
    refresh: false,
    lastUpdate: null,
});

const updateDate = (date) => {
    if (date) {
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

const getBrandData = (brandId) => {
    if (brandId) {
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

const clearData = () => {
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
    state.refresh = true;
    getsSalesCount();
    getsToday();
    getsThisMonth();
    getsThisYear();
    getRevenueChartData();
    getAtvChartData();
    getUptChartData();
    getTopPromoters();
    getThisYearTopPromoters();
};

const getsSalesCount = () => {
    axios.get(route('store_manager.get_operational_sales_count', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.salesCount = response.data.salesCount;
            state.lastUpdate = response.data.lastUpdate;
        });
};

const getsToday = () => {
    axios.get(route('store_manager.get_operational_today_sales', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.today = response.data.today;
        });
};

const getsThisMonth = () => {
    axios.get(route('store_manager.get_operational_this_month_sales', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisMonth = response.data.thisMonth;
        });
};

const getsThisYear = () => {
    axios.get(route('store_manager.get_operational_this_year_sales', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisYear = response.data.thisYear;
        });
};

const getRevenueChartData = () => {
    axios.get(route('store_manager.get_operational_revenue_chart_data', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.revenueChartData = response.data.revenueChartData;
        });
};

const getAtvChartData = () => {
    axios.get(route('store_manager.get_operational_atv_chart_data', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.atvChartData = response.data.atvChartData;
        });
};

const getUptChartData = () => {
    axios.get(route('store_manager.get_operational_upt_chart_data', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.uptChartData = response.data.uptChartData;
        });
};

const getTopPromoters = () => {
    axios.get(route('store_manager.get_operational_top_promoters', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.topPromoters = response.data.topPromoters.length > 0 ? response.data.topPromoters : [];
        });
};

const getThisYearTopPromoters = () => {
    axios.get(route('store_manager.get_operational_this_year_top_promoters', { date: state.date, brand_id: state.brandId, refresh: state.refresh }))
        .then((response) => {
            state.thisYearTopPromoters = response.data.thisYearTopPromoters.length > 0 ? response.data.thisYearTopPromoters : [];
        });
};

const clearAll = () => {
    state.date = props.date;
    state.brandId = props.brandId;
};

updateDate(props.date);
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

const filters = reactive({
    brand: { name: props.brands.find(brand => state.brandId === brand.id)?.name || 'All' },
    date: { name: state.date || null },
});
</script>
