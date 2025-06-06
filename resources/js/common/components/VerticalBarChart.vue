<template>
    <div class="bg-white rounded-xl p-3 pt-5">
        <v-chart
            class="chart"
            :option="getOption()"
            autoresize
            :loading="Object.keys(getOption()).length <= 0"
        />
    </div>
</template>
<script setup>
import { getPastelColors, formatLabelForChart, formatYAxisLabelForChart } from '@commonServices/helper';
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
    TitleComponent,
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
    TitleComponent
]);

const props = defineProps({
    chartId: {
        type: String,
        required: true,
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
    backgroundColor: {
        type: Boolean,
        default: true,
    },
    legendData: {
        type: String,
        default: null,
    },
    chartTitle: {
        type: String,
        default: null,
    }
});

const getOption = () => {
    const backgroundColor = props.backgroundColor
        ? getPastelColors()
        : ['#c1c1c1'];

    const borderRadius = 4;

    return {
        title: {
            text: props.chartTitle,
            left: 'center'
        },
        legend: {
            data: props.legendData ? [props.legendData] : [props.dataSetLabel],
            top: 'bottom'
        },
        xAxis: {
            data: props.labels,
            axisTick: {
                alignWithLabel: true
            },
        },
        yAxis: {
            type: 'value',
            axisLabel: {
                formatter: function (value) {
                    return formatYAxisLabelForChart(value);
                },
            },
        },
        rotate: {
            min: -90,
            max: 90
        },
        toolbox: {
            show: true,
            orient: 'vertical',
            feature: {
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
        series: [
            {
                type: 'bar',
                data: props.data,
                name: props.legendData ?? props.dataSetLabel,
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
                itemStyle: {
                    borderRadius: [borderRadius, borderRadius, 0, 0],
                },
                color: backgroundColor
            },
        ]
    };
};
</script>

<style scoped>
.chart {
    height: 60vh;
}
</style>
