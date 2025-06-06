<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <div class="content content--top-nav mr-5">
            <PageTitle title="Company" />

            <div class="items-center block my-auto 2xl:flex xl:block lg:block md:block sm:block intro-y mt-5">
                <FormSelectBox
                    class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                    :selected-record="state.brand_id"
                    :records="brands"
                    :placeholder="'Please select Brand'"
                    @update:selected-record="getBrandData"
                />

                <div class="flex flex-wrap ml-auto mt-3 lg:mt-0 justify-start lg:justify-end">
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

            <div class="grid grid-cols-12 gap-0 sm:gap-8 mt-3 lg:mt-10">
                <div class="z-0 col-span-12 2xl:col-span-5 xl:col-span-7 lg:col-span-12 md:col-span-12">
                    <SalesCount :sales-count="state.salesCount" />
                </div>

                <div class="col-span-12 2xl:col-span-3 xl:col-span-5 lg:col-span-12 md:col-span-12">
                    <div class="grid grid-cols-1">
                        <div
                            :class="['relative zoom-in', 'before:content-[\'\'] before:w-[90%] before:shadow-[0px_3px_20px_#0000000b] before:bg-slate-50 before:h-full before:mt-3 before:absolute before:rounded-md before:mx-auto before:inset-x-0']"
                        >
                            <div v-if="state.salesCount.length === 0">
                                <div class="animated-background !h-[148px] !rounded-xl" />
                            </div>

                            <div
                                v-else
                                class="p-5 box mt-5 sm:mt-0"
                            >
                                <div class="flex">
                                    <div class="mr-2">
                                        <div class="text-base text-slate-500 md:text-sm">
                                            Today's Sales {{ state.salesCount.todayDate }}
                                        </div>
                                        <Tippy
                                            tag="div"
                                            class="mt-1 mb-8 text-lg font-medium leading-8 2xl:text-2xl md:text-lg sm:text-lg flex items-center"
                                            content="Formula: Today Sale - Today Return. Percentage will be displayed based on today vs yesterday comparison."
                                        >
                                            {{ displayAmountWithCurrencySymbol(state.salesCount.todayTotalSaleData) }}
                                            <Info
                                                class="ml-1 text-primary"
                                                :size="15"
                                            />
                                        </Tippy>
                                    </div>
                                    <div class="ml-auto">
                                        <SalePercentageTippy
                                            :sale-percentage="state.salesCount.todayTotalSalePercentage"
                                            content="yesterday"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <SalesByRegions
                            :brand-id="state.brand_id"
                            :refresh-sales-by-regions="state.refreshSalesByRegions"
                        />
                    </div>
                </div>

                <div class="col-span-12 2xl:col-span-4 xl:col-span-12 lg:col-span-12 md:col-span-12 mt-5 sm:mt-0">
                    <YearlySalesData :yearly-sales-data="state.yearlySalesData" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-0 sm:gap-4 mt-10">
                <div class="lg:col-span-3 md:col-span-3 sm:col-span-1">
                    <MultiBarOrLineChart
                        chart-id="brand-wise-hourly-based-line-chart"
                        title-of-chart="Ranking By Brands"
                        :datasets="isNotEmpty(state.brandWiseData.data) ? state.brandWiseData.data : [0]"
                        :labels="state.brandWiseData.labels"
                        :legend-data="state.brandWiseData.legendData"
                        :background-color="isNotEmpty(state.brandWiseData.data)"
                        file-name="brand-wise-hourly-based-bar"
                        :show-bar-and-line-chart="true"
                        :filters="filters"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:gap-4 mt-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 bg-white rounded-xl p-3 pt-5 md:mb-6">
                    <TabGroup class="md:ml-0 md:pl-0 mb-5">
                        <TabList class="block sm:nav nav-pills bg-slate-200 rounded-md p-1 items-center">
                            <Tab
                                class="w-full py-2 px-2 leading-none active"
                                tag="button"
                            >
                                Month
                            </Tab>
                            <Tab
                                class="w-full py-2 px-2 leading-none"
                                tag="button"
                            >
                                Quarter
                            </Tab>
                        </TabList>

                        <TabPanels class="mt-5 float-clean">
                            <TabPanel class="w-full active">
                                <JMonthPicker
                                    first-div-class="mt-0"
                                    :input-value="state.monthYear"
                                    @update:input-value="updateDate($event)"
                                />
                            </TabPanel>

                            <TabPanel class="w-full leading-relaxed">
                                <div class="w-full flex justify-end">
                                    <div
                                        v-for="(quarter) in 4"
                                        :key="quarter"
                                    >
                                        <button
                                            class="btn btn-sm ml-2"
                                            :class="state.quarterSelected === quarter ? 'btn-primary' : 'btn-outline-primary'"
                                            @click="getStyleDataByQuarter(quarter)"
                                        >
                                            Quarter {{ quarter }}
                                        </button>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </TabGroup>
                </div>

                <MultiBarOrLineChart
                    chart-id="style-wise-hourly-based-bar-chart"
                    title-of-chart="Ranking By Styles"
                    :datasets="isNotEmpty(state.styleWiseData.data) ? state.styleWiseData.data : [0]"
                    :labels="state.styleWiseData.labels"
                    :legend-data="state.styleWiseData.legendData"
                    :background-color="isNotEmpty(state.styleWiseData.data)"
                    file-name="style-wise-hourly-based-bar"
                    :show-bar-and-line-chart="true"
                    :filters="filters"
                />
            </div>
        </div>
    </div>
</template>
<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import SalesByRegions from '@adminPages/dashboards/SalesByRegions.vue';
import SalesCount from '@adminPages/dashboards/SalesCount.vue';
import YearlySalesData from '@adminPages/dashboards/YearlySalesData.vue';
import JMonthPicker from '@commonComponents/JMonthPicker.vue';
import MultiBarOrLineChart from '@commonComponents/MultiBarOrLineChart.vue';
import SalePercentageTippy from '@commonComponents/SalePercentageTippy.vue';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@commonVendor/tab';
import axios from 'axios';
import { Info, RefreshCw } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const helpStore = useHelpCenterStore();
const helpInformation = `
    <ul class='list-disc pl-5'>
        <li class='text-justify'>
            All kind of sales and returns are included to achieve this number excluding Void and cancelled layaway sale. We are taking regular, pending layaway, completed layaway, pending credit, completed credit sales, returns, and exchanges. We are showing this data based on the shift date not based on the sale date.
        </li>
    </ul>
`;

helpStore.setHelpData(helpInformation);

const date = new Date();

const defaultMonth = {
    month: date.getMonth(),
    year: date.getFullYear()
};

const props = defineProps({
    brands: {
        type: Object,
        required: true
    },
    brandId: {
        type: Number,
        default: 0
    }
});

const filters = reactive({
    brand: { name: props.brands.find(brand => props.brandId === brand.id)?.name || 'All' },
});

const state = reactive({
    chartLabels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    salesCount: [],
    yearlySalesData: [],
    brandWiseData: {
        data: [],
        labels: [],
    },
    styleWiseData: {
        data: [],
        labels: [],
    },
    quarterSelected: null,
    monthYear: defaultMonth,
    month_range: [defaultMonth.month + 1, defaultMonth.year],
    lastUpdate: null,
    brand_id: props.brandId,
    refreshSalesByRegions: Math.random(),
});

const clearData = () => {
    state.salesCount = [];
    state.yearlySalesData = [];
    state.brandWiseData = {
        data: [],
        labels: [],
    };
    state.styleWiseData = {
        data: [],
        labels: [],
    };
};

const getData = () => {
    clearData();
    state.refreshSalesByRegions = Math.random();
    axios.get(route('admin.get_business_view_data', { monthRange: state.month_range, brand_id: state.brand_id }))
        .then((response) => {
            state.salesCount = response.data.salesCount;
            state.yearlySalesData = response.data.yearlySalesData;
            state.brandWiseData = response.data.brandWiseData;
            state.styleWiseData = response.data.styleWiseData;
            state.lastUpdate = response.data.lastUpdate;
        });
};

const isNotEmpty = (object) => {
    if (typeof (object) === 'object') {
        return Object.keys(object).length !== 0;
    }
};

const getStyleDataByQuarter = (quarter) => {
    state.quarterSelected = parseInt(quarter);
    state.styleWiseData = {
        data: [],
        labels: [],
        legendData: [],
    };
    axios.get(route('admin.get_style_chart_data', { quarter, brand_id: state.brand_id })).then((response) => {
        state.styleWiseData = response.data.styleWiseData;
    });
};

const refresh = () => {
    clearData();
    axios.get(route('admin.get_business_view_data', { refresh: true }));
    location.reload();
};

getData();

const updateDate = (date) => {
    state.quarterSelected = null;
    state.month_range = null;
    state.monthYear = date;

    if (date === null) {
        return;
    }

    const monthData = Object.values(state.monthYear);
    monthData[0] += 1;
    state.month_range = monthData;

    state.styleWiseData = {
        data: [],
        labels: [],
        legendData: [],
    };

    axios.get(route('admin.get_style_chart_data', { monthRange: state.month_range, brand_id: state.brand_id })).then((response) => {
        state.styleWiseData = response.data.styleWiseData;
    });
};

const getBrandData = (brandId) => {
    state.brand_id = brandId;
    getData();
};
</script>
