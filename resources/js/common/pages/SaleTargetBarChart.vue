<template>
    <div
        v-if="isLoading"
    >
        <div class="cp">
            <div class="animated-background !h-[550px] !rounded-xl" />
        </div>
    </div>
    <div v-else>
        <div
            v-if="props.labels.length !== 0"
            class="w-full h-96"
        >
            <v-chart
                class="chart"
                :option="chartOption"
                autoresize
                :loading="labels.length <= 0"
                @click="handleChartClick($event)"
            />
        </div>
        <div
            v-else
            class="w-full text-center"
        >
            <p class="inline-block text-xl font-light capitalize">
                Oops... Nothing to see here.
            </p>
        </div>
    </div>
</template>

<script setup>
import { use } from "echarts/core";
import { CanvasRenderer } from "echarts/renderers";
import { BarChart, LineChart } from "echarts/charts";
import {
    TitleComponent,
    TooltipComponent,
    ToolboxComponent,
    LegendComponent,
    GridComponent,
} from "echarts/components";
import VChart from "vue-echarts";
import { computed, ref, watch } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { formatLabelForChart, formatYAxisLabelForChart, getRandomPastelColor, getPastelColors } from '@commonServices/helper';
import { isEmpty } from "lodash";

use([
    CanvasRenderer,
    BarChart,
    LineChart,
    TitleComponent,
    TooltipComponent,
    ToolboxComponent,
    LegendComponent,
    GridComponent,
]);

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    isLoading: {
        type: Boolean,
        required: false,
        default: false,
    },
    datasets: {
        type: Array,
        required: true,
    },
    showBarAndLineChart: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['bar-click']);

const allowDifferentColorInChart = computed(() => usePage().props.allow_different_color_in_chart);

const backgroundColor = props.backgroundColor
    ? (allowDifferentColorInChart.value ? getRandomPastelColor() : getPastelColors())
    : props.newBackgroundColor;

const currentTargetIndex = 0;
const currentSaleIndex = 1;
const previousTargetIndex = 2;
const previousSaleIndex = 3;
const chartOption = ref({
    toolbox: {
        show: true,
        orient: 'vertical',
        feature: {
            magicType: {
                show: props.showBarAndLineChart,
                type: ['line', 'bar'],
                option: {
                    line: {
                        smooth: true,
                        symbol: 'pin',
                        symbolSize: 20,
                        lineStyle: {
                            width: 5
                        },
                        label: {
                            rotate: 0,
                            show: true,
                            fontSize: 16,
                            fontWeight: 'bold',
                        }
                    },
                    bar: {
                        label: {
                            show: true,
                            fontSize: 16,
                            fontWeight: 'bold',
                            align: 'left',
                            verticalAlign: 'middle',
                            position: 'insideBottom',
                            rotate: 90,
                        }
                    }
                }
            },
            saveAsImage: {
                type: 'png',
                name: props.fileName
            }
        }
    },
    legend: {
        top: '10%',
    },
    grid: {
        left: '0%',
        right: '0%',
        bottom: '15%',
        containLabel: true
    },
    xAxis: {
        type: 'category',
        data: !isEmpty(props.labels) ? props.labels : ['blank'],
        boundaryGap: true,
    },
    yAxis: {
        type: 'value',
        axisLabel: {
            formatter: function (value) {
                return formatYAxisLabelForChart(value);
            },
        },
    },
    label: {
        show: true,
        fontSize: 16,
        fontWeight: 'bold',
        align: 'left',
        verticalAlign: 'middle',
        position: 'insideBottom',
        rotate: 90,
        formatter: function (value) {
            return formatLabelForChart(value.value);
        }
    },
    series: [
        {
            name: 'Current Target',
            type: 'bar',
            data: props.datasets[currentTargetIndex],
            itemStyle: {
                color: backgroundColor
            }
        },
        {
            name: 'Current Sales',
            type: 'bar',
            data: props.datasets[currentSaleIndex],
            itemStyle: {
                color: backgroundColor
            }
        },
        {
            name: 'Previous Target',
            type: 'bar',
            data: props.datasets[previousTargetIndex],
            itemStyle: {
                color: backgroundColor
            }
        },
        {
            name: 'Previous Sales',
            type: 'bar',
            data: props.datasets[previousSaleIndex],
            itemStyle: {
                color: backgroundColor
            }
        },
    ]
});

const handleChartClick = (params) => {
    if (params.componentType === 'series') {
        emit('bar-click', params);
    }
};

watch(() => props.datasets, (newDatasets) => {
    chartOption.value.series = [
        {
            name: 'Current Target',
            type: 'bar',
            data: newDatasets[currentTargetIndex],
            itemStyle: {
                color: backgroundColor
            }
        },
        {
            name: 'Current Sales',
            type: 'bar',
            data: newDatasets[currentSaleIndex],
            itemStyle: {
                color: backgroundColor
            }
        },
        {
            name: 'Previous Target',
            type: 'bar',
            data: newDatasets[previousTargetIndex],
            itemStyle: {
                color: backgroundColor
            }
        },
        {
            name: 'Previous Sales',
            type: 'bar',
            data: newDatasets[previousSaleIndex],
            itemStyle: {
                color: backgroundColor
            }
        },
    ];
});

watch(() => props.labels, (newLabels) => {
    chartOption.value.xAxis.data = newLabels;
});
</script>

<style scoped>
.chart {
    height: 100%;
}
</style>
