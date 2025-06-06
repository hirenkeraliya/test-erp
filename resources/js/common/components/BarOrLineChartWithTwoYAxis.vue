<template>
    <div class="bg-white rounded-xl p-3 pt-5">
        <Tippy
            tag="h2"
            class="pl-4 mr-5 text-lg font-medium truncate text-center"
            :content="chartInfo"
        >
            <p
                :class="titleClass"
                class="inline-block pb-5 text-xl font-medium"
            >
                {{ titleOfChart }}
            </p>

            <Info
                v-if="chartInfo"
                class="ml-1 text-primary inline-block"
                :size="15"
            />
        </Tippy>

        <v-chart
            class="chart"
            :option="getOption()"
            autoresize
            :loading="data.length === 0"
            @click="handleChartClick($event)"
        />
    </div>
</template>

<script setup>
import { getPastelColors, formatLabelForChart } from '@commonServices/helper';
import { Info } from 'lucide-vue-next';
import VChart from 'vue-echarts';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { BarChart, LineChart } from 'echarts/charts';
import {
    TooltipComponent,
    LegendComponent,
    ToolboxComponent,
    GridComponent,
    DataZoomComponent,
} from 'echarts/components';

use([
    CanvasRenderer,
    GridComponent,
    TooltipComponent,
    LegendComponent,
    ToolboxComponent,
    BarChart,
    LineChart,
    DataZoomComponent,
]);

const emits = defineEmits([
    'handle-chart-click',
]);

const props = defineProps({
    chartId: {
        type: String,
        required: true,
    },
    titleOfChart: {
        type: String,
        required: true,
    },
    titleClass: {
        type: String,
        default: '',
    },
    data: {
        type: Array,
        default: () => [],
    },
    dataSetLabel: {
        type: String,
        default: '',
    },
    labels: {
        type: Array,
        default: () => [],
    },
    yAxis: {
        type: Array,
        default: () => [],
    },
    backgroundColor: {
        type: Boolean,
        default: true,
    },
    newBackgroundColor: {
        type: Array,
        default: () => ['#c1c1c1'],
    },
    legendData: {
        type: String,
        default: null,
    },
    chartInfo: {
        type: String,
        default: '',
    },
});

const getOption = () => {
    const backgroundColor = props.backgroundColor
        ? getPastelColors()
        : props.newBackgroundColor;

    return {
        toolbox: {
            show: true,
            orient: 'horizontal',
            feature: {
                magicType: {
                    type: ['line', 'bar'],
                    option: {
                        line: {
                            smooth: true,
                            label: {
                                show: true,
                                fontSize: 16,
                                fontWeight: 'bold',
                                formatter: function (value) {
                                    return formatLabelForChart(value.value);
                                }
                            },
                            symbol: 'pin',
                            symbolSize: 20,
                            lineStyle: {
                                width: 5
                            },
                        }
                    }
                },
                restore: {},
                saveAsImage: {
                    type: 'png',
                    name: props.dataSetLabel
                }
            }
        },
        dataZoom: [
            {
                type: 'inside'
            }
        ],
        legend: {
            data: props.legendData ? [props.legendData] : [props.dataSetLabel]
        },
        xAxis: {
            data: props.labels,
            axisTick: {
                alignWithLabel: true
            },
            triggerEvent: true
        },
        yAxis: props.yAxis,
        rotate: {
            min: -90,
            max: 90
        },
        grid: {
            left: '3%',
            right: '4%',
            height: '80%',
            containLabel: true
        },
        series: props.data,
        color: backgroundColor,
    };
};

const handleChartClick = (params) => {
    if (params.componentType === 'series') {
        emits('handle-chart-click', params);
    }
};
</script>

<style scoped>
.chart {
    height: 60vh;
}
</style>
