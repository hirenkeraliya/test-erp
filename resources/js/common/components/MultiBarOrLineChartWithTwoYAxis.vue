<template>
    <v-chart
        class="chart"
        :option="getOption()"
        autoresize
        :loading="Object.keys(getOption()).length <= 0"
    />
</template>

<script setup>
import { formatLabelForChart, getPastelColors } from '@commonServices/helper';
import { BarChart, LineChart } from 'echarts/charts';
import {
    AxisPointerComponent,
    DataZoomComponent,
    GridComponent,
    LegendComponent,
    ToolboxComponent,
    TooltipComponent
} from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import VChart from 'vue-echarts';

use([
    CanvasRenderer,
    GridComponent,
    TooltipComponent,
    LegendComponent,
    ToolboxComponent,
    BarChart,
    LineChart,
    DataZoomComponent,
    AxisPointerComponent
]);

const props = defineProps({
    fileName: {
        type: String,
        required: true,
    },
    datasets: {
        type: Object,
        default: () => {},
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
    showBarAndLineChart: {
        type: Boolean,
        default: false,
    }
});

const getOption = () => {
    const backgroundColor = props.backgroundColor
        ? getPastelColors()
        : props.newBackgroundColor;

    return {
        tooltip: {
            trigger: 'item',
        },
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
                restore: {
                    show: props.showBarAndLineChart,
                },
                saveAsImage: {
                    type: 'png',
                    name: props.fileName
                }
            }
        },
        yAxis: props.yAxis,
        label: {
            show: true,
            fontSize: 16,
            fontWeight: 'bold',
            align: 'left',
            verticalAlign: 'middle',
            position: 'insideBottom',
            rotate: 0,
            formatter: function (value) {
                return formatLabelForChart(value.value);
            }
        },
        legend: {
            top: 'bottom',
            type: 'scroll',
        },
        grid: {
            left: '3%',
            right: '4%',
            height: '80%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: true,
            data: props.labels,
        },
        color: backgroundColor,
        series: props.datasets
    };
};
</script>

<style scoped>
.chart {
    height: 60vh;
}
</style>
