<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <div class="content content--top-nav mr-5">
            <PageTitle title="Basket Analysis" />

            <div
                class="grid grid-cols-3  sm:grid-cols-6 lg:grid-cols-12 gap-6 mt-5"
            >
                <div class="col-span-4">
                    <FormSelectBox
                        class="w-full"
                        :selected-record="state.locationId"
                        :records="locations"
                        :placeholder="'Please select Location'"
                        @update:selected-record="updateLocation"
                    />
                </div>

                <div class="col-span-4">
                    <FormSelectBox
                        v-if="state.locationId"
                        class="w-full"
                        :selected-record="state.productId"
                        :records="state.products"
                        :placeholder="'Please select Product'"
                        @update:selected-record="updateProduct"
                    />
                </div>
            </div>

            <div
                class="grid grid-cols-3 sm:grid-cols-12 md:grid-cols-3 lg:grid-cols-12 gap-6 mt-10"
            >
                <div class="col-span-4">
                    <ComboOperationalViewSalesAmount
                        header="Sales"
                        title="This Product"
                        :sale-amount="state.salesTotalThisProduct"
                        :sale-percentage="parseFloat(state.salesThisProductPercentage)"
                        second-title="Other Products"
                        :second-sale-amount="state.salesTotalOtherProduct"
                        :second-sale-percentage="parseFloat(state.salesOtherProductPercentage)"
                        :is-data-fetching="false"
                        :percentage-indicator="false"
                    />
                </div>

                <div class="col-span-4">
                    <ComboOperationalViewSalesAmount
                        header="Orders"
                        title="This Product"
                        :sale-amount="state.orderTotalThisProduct"
                        :sale-percentage="parseFloat(state.orderThisProductPercentage)"
                        second-title="Other Products"
                        :second-sale-amount="state.orderTotalOtherProduct"
                        :second-sale-percentage="parseFloat(state.orderOtherProductPercentage)"
                        :is-data-fetching="false"
                        :percentage-indicator="false"
                    />
                </div>

                <div class="col-span-4">
                    <ComboOperationalViewSalesAmount
                        header="Discount"
                        title="This Product"
                        :sale-amount="state.discountTotalThisProduct"
                        :sale-percentage="parseFloat(state.discountThisProductPercentage)"
                        second-title="Other Products"
                        :second-sale-amount="state.discountTotalOtherProduct"
                        :second-sale-percentage="parseFloat(state.discountOtherProductPercentage)"
                        :is-data-fetching="false"
                        :percentage-indicator="false"
                    />
                </div>
            </div>

            <p class="text-xl font-medium text-center mb-4 mt-10">
                Co-occurrence with other products
            </p>

            <div
                class="grid grid-cols-3 md:grid-cols-3 lg:grid-cols-12 gap-6 md:gap-y-10 lg:gap-6"
            >
                <div class="col-span-4 bg-white rounded-xl p-3 pt-5">
                    <p class="pb-5 text-lg font-medium text-center">
                        Sales
                    </p>

                    <v-chart
                        class="chart"
                        :option="getBarOption1()"
                        autoresize
                        :loading="Object.keys(getBarOption1()).length <= 0"
                    />
                </div>

                <div class="col-span-4 bg-white rounded-xl p-3 pt-5 ">
                    <p class="pb-5 text-lg font-medium text-center">
                        Orders
                    </p>

                    <v-chart
                        class="chart"
                        :option="getBarOption3()"
                        autoresize
                        :loading="Object.keys(getBarOption3()).length <= 0"
                    />
                </div>

                <div class="col-span-4 bg-white rounded-xl p-3 pt-5">
                    <p class="pb-5 text-lg font-medium text-center">
                        Discount
                    </p>

                    <v-chart
                        class="chart"
                        :option="getBarOption2()"
                        autoresize
                        :loading="Object.keys(getBarOption2()).length <= 0"
                    />
                </div>
            </div>

            <div
                class="grid grid-cols-3 lg:grid-cols-12 gap-6 mt-10 md:gap-y-10"
            >
                <div class="col-span-6 bg-white rounded-xl p-3  pt-5 md:p-6 lg:p-3">
                    <v-chart
                        class="chart"
                        :option="state.getOptionsValue"
                        autoresize
                        :loading="Object.keys(state.getOptionsValue).length <= 0"
                    />
                </div>

                <div class="col-span-6 bg-white rounded-xl p-3 pt-5 md:p-6 lg:p-3">
                    <v-chart
                        class="chart"
                        :option="state.getSalesDiscountGraphOption"
                        autoresize
                        :loading="Object.keys(state.getSalesDiscountGraphOption).length <= 0"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import { formatLabelForChart, formatYAxisLabelForChart, formatYAxisLabelForChartWithCurrencySymbol } from '@commonServices/helper';
import { ScatterChart, BarChart } from 'echarts/charts';
import {
    DataZoomComponent,
    GridComponent,
    LegendComponent,
    ToolboxComponent,
    TooltipComponent,
} from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import VChart from 'vue-echarts';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { reactive } from 'vue';
import ComboOperationalViewSalesAmount from '@commonComponents/ComboOperationalViewSalesAmount.vue';

use([
    CanvasRenderer,
    GridComponent,
    TooltipComponent,
    LegendComponent,
    ToolboxComponent,
    DataZoomComponent,
    ScatterChart,
    BarChart,
]);

const xAxisNamePaddingTop = 20;
const yAxisNamePaddingBottom = 25;

const getOption = () => {
    return {
        tooltip: {
            trigger: 'item',
            valueFormatter: function (value) {
                return formatLabelForChart(value);
            },
        },
        grid: {
            left: '10%',
            right: '4%',
            height: '80%',
            bottom: '12%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            name: 'Orders',
            nameLocation: 'middle',
            nameGap: 8,
            boundaryGap: true,
            nameTextStyle: {
                fontWeight: 'bolder',
                color: 'black',
                fontSize: 15,
                padding: [xAxisNamePaddingTop, 0, 0, 0]
            }
        },
        yAxis: {
            type: 'value',
            axisLabel: {
                formatter: function (value) {
                    return formatYAxisLabelForChart(value);
                },
            },
            axisLine: {
                show: true,
            },
            name: 'Sales',
            nameLocation: 'middle',
            nameGap: 8,
            nameTextStyle: {
                fontWeight: 'bolder',
                color: 'black',
                fontSize: 15,
                padding: [0, 0, yAxisNamePaddingBottom, 0]
            },
        },
        series: []
    };
};

const getDiscountOption = () => {
    return {
        tooltip: {
            trigger: 'item',
            valueFormatter: function (value) {
                return formatLabelForChart(value);
            },
        },
        grid: {
            left: '10%',
            right: '4%',
            height: '80%',
            bottom: '12%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            name: 'Orders',
            nameLocation: 'middle',
            nameGap: 8,
            boundaryGap: true,
            nameTextStyle: {
                fontWeight: 'bolder',
                color: 'black',
                fontSize: 15,
                padding: [xAxisNamePaddingTop, 0, 0, 0]
            }
        },
        yAxis: {
            type: 'value',
            axisLabel: {
                formatter: function (value) {
                    return formatYAxisLabelForChart(value);
                },
            },
            axisLine: {
                show: true,
            },
            name: 'Discount',
            nameLocation: 'middle',
            nameGap: 8,
            nameTextStyle: {
                fontWeight: 'bolder',
                color: 'black',
                fontSize: 15,
                padding: [0, 0, yAxisNamePaddingBottom, 0]
            }
        },
        series: []
    };
};

const props = defineProps({
    basketAnalysisData: {
        type: Object,
        required: true,
    },
    locations: {
        type: Object,
        required: true,
    },
    products: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    locationId: null,
    productId: null,
    labelData: [],
    productGraphActualData: [],
    discountGraphActualData: [],
    orderGraphActualData: [],
    salesOrderGraphActualData: [],
    salesDiscountGraphActualData: [],
    products: [],
    getOptionsValue: getOption(),
    getSalesDiscountGraphOption: getDiscountOption(),
    salesTotalThisProduct: 0,
    salesThisProductPercentage: 0,
    salesTotalOtherProduct: 0,
    salesOtherProductPercentage: 0,
    discountTotalThisProduct: 0,
    discountTotalOtherProduct: 0,
    discountThisProductPercentage: 0,
    discountOtherProductPercentage: 0,
    orderTotalThisProduct: 0,
    orderTotalOtherProduct: 0,
    orderThisProductPercentage: 0,
    orderOtherProductPercentage: 0,
});

const boundaryGapEnd = 0.01;

const getBarOption1 = () => {
    return {
        tooltip: {
            show: true,
            formatter: function (params) {
                return params.data;
            }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '8%',
        },
        xAxis: {
            type: 'value',
            boundaryGap: [0, boundaryGapEnd],
            axisLabel: {
                formatter: function (value) {
                    return formatYAxisLabelForChartWithCurrencySymbol(value);
                },
            },
        },
        yAxis: {
            type: 'category',
            show: false,
            data: state.labelData,
        },
        series: [
            {
                name: 'co-occurrence with other products',
                type: 'bar',
                barWidth: 50,
                data: state.productGraphActualData,
                itemStyle: {
                    color: '#61a0a8',
                },
                label: {
                    show: true,
                    position: 'insideLeft',
                    formatter: function (params) {
                        return params.name;
                    }
                }
            },
        ]
    };
};

const getBarOption2 = () => {
    return {
        tooltip: {
            show: true,
            formatter: function (params) {
                return params.data;
            }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '8%',
        },
        xAxis: {
            type: 'value',
            boundaryGap: [0, boundaryGapEnd],
            axisLabel: {
                formatter: function (value) {
                    return formatYAxisLabelForChartWithCurrencySymbol(value);
                },
            },
        },
        yAxis: {
            type: 'category',
            show: false,
            data: state.labelData
        },
        series: [
            {
                name: 'co-occurrence with other Discount',
                type: 'bar',
                barCategoryGap: '100%',
                barWidth: 50,
                data: state.discountGraphActualData,
                itemStyle: {
                    color: '#d48265',
                },
                label: {
                    show: true,
                    position: 'insideLeft',
                    formatter: function (params) {
                        return params.name;
                    }
                }
            },
        ]
    };
};

const getBarOption3 = () => {
    return {
        tooltip: {
            show: true,
            formatter: function (params) {
                return params.data;
            }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '8%',
        },
        xAxis: {
            type: 'value',
            boundaryGap: [0, boundaryGapEnd]
        },
        yAxis: {
            type: 'category',
            data: state.labelData,
            show: false,
        },
        series: [
            {
                name: 'co-occurrence with other order',
                type: 'bar',
                barCategoryGap: '100%',
                barWidth: 50,
                data: state.orderGraphActualData,
                itemStyle: {
                    color: '#c23531'
                },
                label: {
                    show: true,
                    position: 'insideLeft',
                    formatter: function (params) {
                        return params.name;
                    }
                }
            },
        ]
    };
};

const clearData = () => {
    state.labelData = [];
    state.productGraphActualData = [];
    state.discountGraphActualData = [];
    state.orderGraphActualData = [];
    state.salesOrderGraphActualData = [];
    state.salesDiscountGraphActualData = [];
    state.salesTotalThisProduct = 0;
    state.salesThisProductPercentage = 0;
    state.salesTotalOtherProduct = 0;
    state.salesOtherProductPercentage = 0;
    state.discountTotalThisProduct = 0;
    state.discountTotalOtherProduct = 0;
    state.discountThisProductPercentage = 0;
    state.discountOtherProductPercentage = 0;
    state.orderTotalThisProduct = 0;
    state.orderTotalOtherProduct = 0;
    state.orderThisProductPercentage = 0;
    state.orderOtherProductPercentage = 0;
};

const updateLocation = (locationId) => {
    clearData();
    state.locationId = locationId;
    state.productId = null;
    const selectedLocationData = props.basketAnalysisData[state.locationId];
    state.products = selectedLocationData.map((item) => {
        return { id: item.product_id, name: item.product_name };
    }).filter(item => item !== null);
};

const updateProduct = (productId) => {
    clearData();
    state.productId = productId;
    updateGraphData();
};

const decimalPlaces = 2;

const updateGraphData = () => {
    let totalSalesAmount = 0;
    let totalDiscountAmount = 0;
    let totalOrder = 0;
    for (const key in props.basketAnalysisData) {
        if (state.locationId === parseInt(key) && state.productId) {
            const keyInt = parseInt(key);

            state.labelData = props.basketAnalysisData[keyInt].map((item) => {
                if (parseInt(state.productId) === parseInt(item.product_id)) {
                    return item.comparison_product_name;
                }
                return null;
            }).filter(item => item !== null);

            state.productGraphActualData = props.basketAnalysisData[keyInt].map((item) => {
                if (parseInt(state.productId) === parseInt(item.product_id)) {
                    state.salesTotalThisProduct += parseFloat(item.sales_amount);
                    return item.sales_amount;
                }
                state.salesTotalOtherProduct += parseFloat(item.sales_amount);
                return null;
            }).filter(item => item !== null);

            totalSalesAmount = state.salesTotalThisProduct + state.salesTotalOtherProduct;
            state.salesThisProductPercentage = calculatePercentage(state.salesTotalThisProduct, totalSalesAmount).toFixed(decimalPlaces);
            state.salesOtherProductPercentage = calculatePercentage(state.salesTotalOtherProduct, totalSalesAmount).toFixed(decimalPlaces);

            state.discountGraphActualData = props.basketAnalysisData[keyInt].map((item) => {
                if (parseInt(state.productId) === parseInt(item.product_id)) {
                    state.discountTotalThisProduct += parseFloat(item.discount);
                    return item.discount;
                }
                state.discountTotalOtherProduct += parseFloat(item.discount);
                return null;
            }).filter(item => item !== null);

            totalDiscountAmount = state.discountTotalThisProduct + state.discountTotalOtherProduct;
            state.discountThisProductPercentage = calculatePercentage(state.discountTotalThisProduct, totalDiscountAmount).toFixed(decimalPlaces);
            state.discountOtherProductPercentage = calculatePercentage(state.discountTotalOtherProduct, totalDiscountAmount).toFixed(decimalPlaces);

            state.orderGraphActualData = props.basketAnalysisData[keyInt].map((item) => {
                if (parseInt(state.productId) === parseInt(item.product_id)) {
                    state.orderTotalThisProduct += parseFloat(item.sale_orders);
                    return item.sale_orders;
                }
                state.orderTotalOtherProduct += parseFloat(item.sale_orders);
                return null;
            }).filter(item => item !== null);

            totalOrder = state.orderTotalThisProduct + state.orderTotalOtherProduct;
            state.orderThisProductPercentage = calculatePercentage(state.orderTotalThisProduct, totalOrder).toFixed(decimalPlaces);
            state.orderOtherProductPercentage = calculatePercentage(state.orderTotalOtherProduct, totalOrder).toFixed(decimalPlaces);

            state.salesOrderGraphActualData = props.basketAnalysisData[keyInt].map((item) => {
                if (parseInt(state.productId) === parseInt(item.product_id)) {
                    return {
                        type: 'scatter',
                        name: item.comparison_product_name,
                        data: [
                            [item.sale_orders, item.sales_amount]
                        ],
                    };
                }
                return null;
            }).filter(item => item !== null);

            state.getOptionsValue.legend = { data: state.salesOrderGraphActualData.map(item => item.name) };
            state.getOptionsValue.series = state.salesOrderGraphActualData;

            state.salesDiscountGraphActualData = props.basketAnalysisData[keyInt].map((item) => {
                if (parseInt(state.productId) === parseInt(item.product_id)) {
                    return {
                        type: 'scatter',
                        name: item.comparison_product_name,
                        data: [
                            [item.sale_orders, item.discount]
                        ],
                    };
                }
                return null;
            }).filter(item => item !== null);

            state.getSalesDiscountGraphOption.legend = { data: state.salesDiscountGraphActualData.map(item => item.name) };
            state.getSalesDiscountGraphOption.series = state.salesDiscountGraphActualData;
        }
    }
};

const calculatePercentage = (amount1, amount2) => {
    const percentageFactor = 100;
    return (amount1 / amount2) * percentageFactor;
};

</script>

<style scoped>
.chart {
    height: 60vh;
}
.h-20{
    height: 20px;
}
.h-60{
    height: 60px;
}
.p-5{
    padding: 5px;
}
.offset-8 {
  grid-column-start: span 8; /* Start spanning from the next column */
}
</style>
