<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <PageTitle title="Intelligence" />

        <div class="content content--top-nav mr-5">
            <div class="grid grid-cols-12 md:grid-cols-6 lg:grid-cols-12 gap-6 mt-10">
                <div class="col-span-12 lg:col-span-4 md:col-span-6">
                    <div class="grid grid-rows-1 gap-3 sm:grid-rows-2 sm:gap-4 lg:grid-rows-2 items-center lg:gap-y-6">
                        <div
                            class="rounded-xl bg-white p-4 flex items-center justify-between cursor-pointer h-28"
                            @click="state.skuModalShow = true"
                        >
                            <div class="mr-2.5">
                                <p class="text-lg text-slate-700">
                                    Low stock SKUs
                                </p>
                                <p class="mt-1 text-lg font-semibold">
                                    58
                                </p>
                            </div>

                            <div
                                class="rounded-full bg-indigo-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-indigo-100 border flex-none"
                            >
                                <ShieldAlert class="w-4 h-4 lg:h-5 lg:w-5 text-indigo-700" />
                            </div>
                        </div>

                        <div class="rounded-xl bg-white p-4 flex items-center justify-between h-28">
                            <div class="mr-2.5">
                                <p class="text-lg text-slate-700">
                                    Revenue at stake
                                </p>
                                <p class="mt-1 text-lg font-semibold flex items-center">
                                    {{ displayAmountWithCurrencySymbol(559643.9) }}
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-red-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-red-100 border flex-none"
                            >
                                <ShieldAlert class="w-4 h-4 lg:h-5 lg:w-5 text-red-700" />
                            </div>
                        </div>

                        <div class="rounded-xl bg-white p-4 flex items-center justify-between h-28">
                            <div class="mr-2.5">
                                <p class="text-lg text-slate-700">
                                    Affected Locations
                                </p>
                                <p class="mt-1 text-lg font-semibold flex items-center">
                                    39
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-red-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-red-100 border flex-none"
                            >
                                <ShieldAlert class="w-4 h-4 lg:h-5 lg:w-5 text-red-700" />
                            </div>
                        </div>

                        <div class="rounded-xl bg-white p-4 flex items-center justify-between h-28">
                            <div class="mr-2.5">
                                <a
                                    :href="route('admin.sell_through_aggregate_reports.index', {sort_direction: 'desc', sort_by: 'balance', report_type: accumulatedStaticReportType.byUpc})"
                                    class="text-lg font-semibold text-slate-700"
                                >
                                    Products that can be put on promotion
                                </a>
                            </div>
                            <div
                                class="rounded-full bg-red-50 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center border-red-100 border flex-none"
                            >
                                <ShieldAlert class="w-4 h-4 lg:h-5 lg:w-5 text-red-700" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-4 md:col-span-6 ">
                    <BarOrLineChartWithTwoYAxis
                        v-if="isNotEmpty(state.expectedToStockOutIn.data)"
                        chart-id="expected-to-stock-out-in"
                        title-of-chart="Expected To Stock Out In (Days)"
                        :data="isNotEmpty(state.expectedToStockOutIn.data) ? state.expectedToStockOutIn.data : [0]"
                        :labels="state.expectedToStockOutIn.labels"
                        :y-axis="state.expectedToStockOutIn.yAxis"
                        :background-color="false"
                        :new-background-color="getBackgroundColor()"
                        @handle-chart-click="handleChartClick"
                    />
                </div>

                <div class="col-span-12 h-80 lg:col-span-4 md:col-span-6 lg:h-full">
                    <MalaysiaMap
                        chart-id="malaysia-map"
                        :data-series="state.malaysiaMapData"
                    />
                </div>
            </div>

            <div
                id="products-table"
                class="bg-white rounded-xl p-5 mt-10"
            >
                <div class="col-span-12">
                    <div class="text-xl font-medium">
                        Demand Forecasting by Locations
                    </div>

                    <JSimpleTable
                        :columns="state.productColumns"
                        :records="state.productRecords"
                        row-classes="border-b-2 border-slate-300 intro-x"
                        table-classes="table overflow-hidden border-0 border-none rounded-md mb-3"
                        :allow-search="true"
                    >
                        <template #location="data">
                            {{ data.item }}
                        </template>

                        <template #salevalue="data">
                            {{ displayAmountWithCurrencySymbol(data.item.salevalue) }}
                        </template>

                        <template #action>
                            <div class="flex items-center justify-center cursor-pointer">
                                <Lightbulb @click="state.productModalShow = true" />

                                <LineChart @click="state.forecastModalShow = true" />
                            </div>
                        </template>
                    </JSimpleTable>
                </div>
            </div>
        </div>
    </div>

    <!-- START SKU modal -->
    <Modal
        size="modal-xl"
        :show="state.skuModalShow"
        @hidden="hideSkuModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Low stock SKUs
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideSkuModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <JSimpleTable
                :columns="state.skuColumns"
                :records="state.skuRecords"
                :allow-search="true"
            >
                <template #retail_price="data">
                    {{ displayAmountWithCurrencySymbol(data.item.retail_price) }}
                </template>

                <template #d1="data">
                    <div class="text-center">
                        {{ data.item['D+1'] }} ({{ displayAmountWithCurrencySymbol(data.item.retail_price) }})
                    </div>
                </template>

                <template #d3="data">
                    <div class="text-center">
                        {{ data.item['D+3'] }} ({{ displayAmountWithCurrencySymbol(data.item.retail_price) }})
                    </div>
                </template>

                <template #d5="data">
                    <div class="text-center">
                        {{ data.item['D+5'] }} ({{ displayAmountWithCurrencySymbol(data.item.retail_price) }})
                    </div>
                </template>

                <template #d7="data">
                    <div class="text-center">
                        {{ data.item['D+7'] }} ({{ displayAmountWithCurrencySymbol(data.item.retail_price) }})
                    </div>
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
    <!-- END SKU modal -->

    <!-- START Product modal -->
    <Modal
        size="modal-xl"
        :show="state.productModalShow"
        @hidden="hideProductModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Acquire Stock For Product: EXL_A/T 69 COTTON/HARD (Location: ARIANI GALLERY ALOR SETAR)
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideProductModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <JSimpleTable
                :columns="state.ideaToAcquireStockColumns"
                :records="state.ideaToAcquireStockRecords"
                :allow-search="true"
            >
                <template #expected_delivery="data">
                    <div class="flex justify-center">
                        {{ data.item.expected_delivery }}

                        <h2
                            class="mr-5 text-lg font-medium truncate pl-4 flex items-center"
                            :title="'Lead Days: ' + data.item.lead_days"
                        >
                            <Info
                                class="ml-1 text-primary"
                                :size="15"
                            />
                        </h2>
                    </div>
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
    <!-- END Product modal -->

    <!-- START Why Forecast modal -->
    <Modal
        size="modal-xl"
        :show="state.forecastModalShow"
        @hidden="hideForecastModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Forecast Drivers
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideForecastModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="px-5 sm:p-10 text-left">
            <div>
                <h1 class="px-5 font-medium text-lg">
                    Price vs Quantity Trend
                </h1>

                <MultiBarOrLineChartWithTwoYAxis
                    chart-id="forecast-line-chart"
                    :datasets="isNotEmpty(state.forecastChart.data) ? state.forecastChart.data : [0]"
                    :labels="state.forecastChart.labels"
                    :y-axis="state.forecastChart.yAxis"
                    :background-color="false"
                    :new-background-color="getBackgroundColor()"
                    file-name="forecast-line-chart"
                />
            </div>

            <div>
                <h1 class="p-6 font-medium text-lg">
                    Category Trends
                </h1>

                <MultiBarOrLineChart
                    chart-id="category-line-chart"
                    :datasets="isNotEmpty(state.categoryChart.data) ? state.categoryChart.data : [0]"
                    :labels="state.categoryChart.labels"
                    :legend-data="state.categoryChart.legendData"
                    :background-color="false"
                    :new-background-color="getBackgroundColor()"
                    file-name="category-line-chart"
                    :text-rotation="0"
                />
            </div>
        </ModalBody>
    </Modal>
    <!-- END Why Forecast modal -->
</template>

<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import BarOrLineChartWithTwoYAxis from '@commonComponents/BarOrLineChartWithTwoYAxis.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import MalaysiaMap from '@commonComponents/MalaysiaMap.vue';
import MultiBarOrLineChart from '@commonComponents/MultiBarOrLineChart.vue';
import MultiBarOrLineChartWithTwoYAxis from '@commonComponents/MultiBarOrLineChartWithTwoYAxis.vue';
import { displayAmountWithCurrencySymbol, formatLabelForChart, formatYAxisLabelForChartWithCurrencySymbol } from '@commonServices/helper';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';
import { Info, Lightbulb, LineChart, ShieldAlert, X } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';

const getBackgroundColor = () => {
    return [
        '#a4b6dd', '#d89105', '#c094cc', '#ac5949', '#a2d0c0',
    ];
};

const props = defineProps({
    accumulatedStaticReportType: {
        type: Object,
        required: true,
    },
    lowStockSKU: {
        type: Object,
        required: true,
    },
    d1: {
        type: Array,
        required: true,
    },
    d3: {
        type: Array,
        required: true,
    },
    d5: {
        type: Array,
        required: true,
    },
    d7: {
        type: Array,
        required: true,
    }
});

const currentDate = new Date();
const options = { day: '2-digit', month: 'short', year: 'numeric' };

const threeDays = 3;
const fiveDays = 5;
const sixDays = 6;

currentDate.setDate(currentDate.getDate() + threeDays);
const futureDays1 = currentDate.toLocaleDateString('en-GB', options);

currentDate.setDate(currentDate.getDate() + fiveDays);
const futureDays2 = currentDate.toLocaleDateString('en-GB', options);

currentDate.setDate(currentDate.getDate() + sixDays);
const futureDays3 = currentDate.toLocaleDateString('en-GB', options);

const state = reactive({
    skuModalShow: false,
    forecastModalShow: false,
    productModalShow: false,
    selectedProduct: null,
    productTitle: '1',
    skuColumns: [
        {
            key: 'location name',
            label: 'Location',
            bodyClass: 'text-center'
        },
        {
            key: 'product name',
            label: 'SKU',
            bodyClass: 'text-center'
        },
        {
            key: 'retail_price',
            label: 'Price',
            bodyClass: 'text-right'
        },
        {
            key: 'stock',
            bodyClass: 'text-center'
        },
        {
            key: 'd1',
            label: 'D + 1',
            bodyClass: 'text-center'
        },
        {
            key: 'd3',
            label: 'D + 3',
            bodyClass: 'text-center'
        },
        {
            key: 'd5',
            label: 'D + 5',
            bodyClass: 'text-center'
        },
        {
            key: 'd7',
            label: 'D + 7',
            bodyClass: 'text-center'
        },
    ],
    skuRecords: props.lowStockSKU,
    productColumns: [
        {
            key: 'location name',
            label: 'Location',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'product name',
            label: 'Product Name',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'upc',
            label: 'UPC',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'stock',
            label: 'Qty',
            headerClass: 'border-0 border-none bg-slate-300 text-center',
            bodyClass: 'border-b-2 border-slate-300 text-center border-0 border-none bg-slate-200',
        }, {
            key: 'salevalue',
            label: 'Revenue',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            bodyClass: 'border-b-2 border-slate-300 text-right border-0 border-none bg-slate-200',
        }, {
            key: 'D+1',
            label: 'D + 1',
            headerClass: 'border-0 border-none bg-slate-300 text-center',
            bodyClass: 'border-b-2 border-slate-300 text-center border-0 border-none bg-slate-200',
        }, {
            key: 'D+3',
            label: 'D + 3',
            headerClass: 'border-0 border-none bg-slate-300 text-center',
            bodyClass: 'border-b-2 border-slate-300 text-center border-0 border-none bg-slate-200',
        }, {
            key: 'D+5',
            label: 'D + 5',
            headerClass: 'border-0 border-none bg-slate-300 text-center',
            bodyClass: 'border-b-2 border-slate-300 text-center border-0 border-none bg-slate-200',
        }, {
            key: 'D+7',
            label: 'D + 7',
            headerClass: 'border-0 border-none bg-slate-300 text-center',
            bodyClass: 'border-b-2 border-slate-300 text-center border-0 border-none bg-slate-200',
        }, {
            key: 'action',
            headerClass: 'border-0 border-none bg-slate-300 text-center',
            bodyClass: 'border-b-2 border-slate-300 text-center border-0 border-none bg-slate-200',
        },
    ],
    productRecords: props.d1,
    ideaToAcquireStockColumns: [
        {
            key: 'location',
            bodyClass: 'text-left',
            headerClass: 'text-left'
        }, {
            key: 'type',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }, {
            key: 'total_stock_transfer',
            label: 'Stock Transfers In Last 6 Months',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }, {
            key: 'expected_delivery',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        },
    ],
    ideaToAcquireStockRecords: [
        {
            location: 'PACK MAKERS SDN BHD',
            type: 'STORE',
            total_stock_transfer: 100,
            expected_delivery: futureDays1,
            lead_days: '4',
        },
        {
            location: 'ARIANI GALLERY BANGI',
            type: 'STORE',
            total_stock_transfer: 50,
            expected_delivery: futureDays2,
            lead_days: '3',
        },
        {
            location: 'ARIANI GALLERY KOTA KINABALU',
            type: 'STORE',
            total_stock_transfer: 80,
            expected_delivery: futureDays1,
            lead_days: '1',
        },
        {
            location: 'TAILOR CALLA BATIK',
            type: 'STORE',
            total_stock_transfer: 20,
            expected_delivery: futureDays3,
            lead_days: '1',
        },
    ],
    expectedToStockOutIn: {
        data: [
            {
                data: [
                    {
                        value: 457,
                        itemStyle: {
                            color: '#8c1515',
                        }
                    },
                    {
                        value: 632,
                        itemStyle: {
                            color: '#b32424',
                        }
                    },
                    {
                        value: 680,
                        itemStyle: {
                            color: '#cc3333',
                        }
                    },
                    {
                        value: 804,
                        itemStyle: {
                            color: '#e64c4c',
                        }
                    },
                ],
                name: 'SKU',
                smooth: 0.6,
                type: 'bar',
                label: {
                    position: 'insideBottom',
                    distance: 15,
                    align: 'left',
                    verticalAlign: 'middle',
                    show: true,
                    fontSize: 16,
                    fontWeight: 'bold',
                    rotate: 90,
                    formatter: function (value) {
                        return formatLabelForChart(value.value);
                    }
                },
            },
            {
                data: ['314204.4', '438582.5', '469153.8', '559643.9'],
                name: 'Revenue',
                yAxisIndex: 1,
                smooth: 0.6,
                type: 'line',
                label: {
                    position: 'insideBottom',
                    distance: 10,
                    align: 'left',
                    verticalAlign: 'middle',
                    show: true,
                    color: '#111',
                    fontSize: 14,
                    fontWeight: 'bold',
                    rotate: 45,
                    formatter: function (value) {
                        return formatYAxisLabelForChartWithCurrencySymbol(value.value);
                    }
                },
            },
        ],
        yAxis: [
            {
                name: 'SKU',
                type: 'value',
                axisLabel: {
                    formatter: function (value) {
                        return value;
                    },
                },
                min: 0
            },
            {
                name: 'Revenue',
                type: 'value',
                axisLabel: {
                    formatter: function (value) {
                        return formatYAxisLabelForChartWithCurrencySymbol(value);
                    },
                },
                min: 0
            },
        ],
        labels: ['D + 1', 'D + 3', 'D + 5', 'D + 7']
    },
    forecastChart: {
        data: [
            {
                data: ['5', '9', '9', '19', '16', '17', '14', '15', '16'],
                name: 'Sale Quantity',
                smooth: 0.6,
                type: 'bar',
                label: {
                    position: 'insideBottom',
                    distance: 15,
                    align: 'left',
                    verticalAlign: 'middle',
                    show: true,
                    fontSize: 16,
                    fontWeight: 'bold',
                    rotate: 90,
                    formatter: function (value) {
                        return formatLabelForChart(value.value);
                    }
                },
            },
            {
                data: ['63', '75.05', '75', '79', '73.07', '79', '77.02', '79', 0],
                name: 'Price',
                yAxisIndex: 1,
                smooth: 0.6,
                type: 'line',
                label: {
                    position: 'insideBottom',
                    distance: 10,
                    align: 'left',
                    verticalAlign: 'middle',
                    show: true,
                    color: '#111',
                    fontSize: 14,
                    fontWeight: 'bold',
                    rotate: 45,
                    formatter: function (value) {
                        return formatYAxisLabelForChartWithCurrencySymbol(value.value);
                    }
                },
            },
        ],
        yAxis: [
            {
                name: 'Sale Quantity',
                type: 'value',
            },
            {
                name: 'Price',
                type: 'value',
                axisLabel: {
                    formatter: function (value) {
                        return formatYAxisLabelForChartWithCurrencySymbol(value);
                    },
                },
            },
        ],
        labels: ['19 Feb', '26 Feb', '04 Mar', '11 Mar', '18 Mar', '25 Mar', '01 Apr', '08 Apr', '15 Apr'],
    },
    categoryChart: {
        data: [
            {
                name: 'INNER',
                type: 'line',
                data: ['28', '32', '29', '23', '73', '38', '32', '36', '25'],
            }, {
                name: 'RING',
                type: 'line',
                data: ['13', '38', '18', '42', '28', '22', '39', '31', '27']
            }, {
                name: 'SCARF SARONG PLAIN DIAMOND',
                type: 'line',
                data: ['13', '43', '24', '27', '34', '26', '27', '36', '9']
            }, {
                name: 'SCARF SARONG PLAIN WITHOUT DIAMOND',
                type: 'line',
                data: ['20', '56', '62', '48', '47', '36', '43', '30', '17']
            }, {
                name: 'SCARF SARONG PRINTED WITHOUT DIAMOND',
                type: 'line',
                data: ['6', '16', '14', '30', '87', '119', '132', '77', '53']
            }, {
                name: 'SEJADAH',
                type: 'line',
                data: ['6', '4', '11', '7', '10', '8', '19', '13', '25']
            }, {
                name: 'SHAWL PLAIN WITHOUT DIAMOND',
                type: 'line',
                data: ['0', '8', '4', '8', '5', '10', '11', '5', '7']
            }, {
                name: 'SHAWL PRINTED DIAMOND',
                type: 'line',
                data: ['8', '13', '9', '7', '2', '10', '10', '11', '7']
            }, {
                name: 'SQUARE PLAIN WITHOUT DIAMOND',
                type: 'line',
                data: ['26', '84', '128', '79', '112', '94', '146', '81', '58']
            }, {
                name: 'SQUARE PRINTED DIAMOND',
                type: 'line',
                data: ['6', '38', '27', '28', '33', '39', '38', '24', '18']
            }, {
                name: 'SQUARE PRINTED WITHOUT DIAMOND',
                type: 'line',
                data: ['192', '531', '465', '502', '719', '777', '929', '524', '328']
            },
        ],
        legendData: ['INNER', 'RING', 'SCARF SARONG PLAIN DIAMOND', 'SCARF SARONG PLAIN WITHOUT DIAMOND', 'SCARF SARONG PRINTED WITHOUT DIAMOND', 'SEJADAH', 'SHAWL PLAIN WITHOUT DIAMOND', 'SHAWL PRINTED DIAMOND', 'SQUARE PLAIN WITHOUT DIAMOND', 'SQUARE PRINTED DIAMOND', 'SQUARE PRINTED WITHOUT DIAMOND'],
        labels: ['19 Feb', '26 Feb', '04 Mar', '11 Mar', '18 Mar', '25 Mar', '01 Apr', '08 Apr', '15 Apr'],
    },
    products: [
        {
            id: 1,
            name: 'KIDS RAQEEMA BAJU KURUNG'
        },
        {
            id: 2,
            name: 'PLAIN CARD'
        },
        {
            id: 3,
            name: 'JUMBO RM 9'
        },
        {
            id: 4,
            name: 'TAG MERAH '
        },
        {
            id: 5,
            name: 'PAPERBAG ARIANI PINK 2023'
        },
        {
            id: 6,
            name: 'INSTANT 169 NOURISH PLAIN DIAMOND'
        },
        {
            id: 7,
            name: 'INSTANT 149 CHIFFON NAYLA DIAMOND'
        },
        {
            id: 8,
            name: 'SAMPUL RAYA ARIANI 2023'
        },
        {
            id: 9,
            name: 'HANGER ARIANI LUXE 2023'
        },
        {
            id: 10,
            name: 'INSTANT 239 ELYSE DIAMOND'
        },
    ],
    malaysiaMapData: [{
        map: 'Malaysia',
        name: '',
        data: [
            {
                name: 'Kedah',
                value: 3
            }, {
                name: 'Kelantan',
                value: 3
            }, {
                name: 'Terengganu',
                value: 2
            }, {
                name: 'Sabah',
                value: 1
            }, {
                name: 'Pahang',
                value: 1
            }, {
                name: 'Perak',
                value: 1
            }, {
                name: 'Johor',
                value: 4
            }, {
                name: 'Selangor',
                value: 21
            }, {
                name: 'Negeri Sembilan',
                value: 2
            }, {
                name: 'Malacca',
                value: 1
            },
        ],
        type: 'map',
        roam: true
    }]
});

const hideSkuModal = () => {
    state.skuModalShow = false;
};

const hideProductModal = () => {
    state.productModalShow = false;
};

const hideForecastModal = () => {
    state.forecastModalShow = false;
};

const isNotEmpty = (object) => {
    if (typeof (object) === 'object') {
        return Object.keys(object).length !== 0;
    }
};

const handleChartClick = (params) => {
    if (params.name === 'D + 1') {
        state.productTitle = '1';
        state.productRecords = props.d1;
    }

    if (params.name === 'D + 3') {
        state.productTitle = '3';
        state.productRecords = props.d3;
    }

    if (params.name === 'D + 5') {
        state.productTitle = '5';
        state.productRecords = props.d5;
    }

    if (params.name === 'D + 7') {
        state.productTitle = '7';
        state.productRecords = props.d7;
    }

    const targetElement = document.querySelector('#products-table');
    if (targetElement) {
        targetElement.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>
