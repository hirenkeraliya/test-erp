<template>
    <div class="bg-white rounded-xl p-3 pt-5">
        <Tippy
            tag="h2"
            class="pl-4 mr-5 text-lg font-medium truncate text-left"
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
            :id="chartId"
            ref="chartContainer"
            class="chart"
            :option="getOption()"
            autoresize
            :loading="data.length === 0"
            @click="handleChartClick($event)"
        />
    </div>
</template>
<script setup>
import { getRandomPastelColor, formatLabelForChart, formatYAxisLabelForChart, getPastelColors } from '@commonServices/helper';
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
import { computed, nextTick, onMounted, ref } from 'vue';

import { usePage } from '@inertiajs/vue3';

const allowDifferentColorInChart = computed(() => usePage().props.allow_different_color_in_chart);

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
    chartHeight: {
        type: String,
        default: '60vh',
    },
    titleClass: {
        type: String,
        default: 'text-left'
    },
    legendDataPosition: {
        type: String,
        default: 'bottom'
    },
    filters: {
        type: Object,
        default: null,
    },
});


const chartContainer = ref(null);

const startExport = () => {
    const chartInstance = chartContainer.value?.chart;
    if (chartInstance) {
        const filterText = Object.entries(props.filters || {})
            .map(([key, filter]) => {
                return `${key.charAt(0).toUpperCase() + key.slice(1)}: ${filter.name !== undefined ? filter.name : 'All'}`;
            })
            .join(' | ');

        chartInstance.setOption({
            title: {
                subtext: filterText,
            },
        });
        const barOrLineChartDelay = 100;
        setTimeout(() => {
            const url = chartInstance.getDataURL({
                type: 'png',
                pixelRatio: 2,
                backgroundColor: '#fff',
            });

            const link = document.createElement('a');
            link.href = url;
            link.download = `${props.dataSetLabel}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            chartInstance.setOption({
                title: {
                    subtext: '',
                },
            });
        }, barOrLineChartDelay);
    }
};

onMounted(async () => {
    await nextTick();
    const chartInstance = chartContainer.value?.chart;
    if (chartInstance) {
        chartInstance.setOption(getOption(), true);
    }
});

const getOption = () => {
    const backgroundColor = props.backgroundColor
        ? (allowDifferentColorInChart.value ? getRandomPastelColor() : getPastelColors())
        : props.newBackgroundColor;

    const borderRadiusTopLeft = 4;
    const borderRadiusTopRight = 4;

    return {
        title: {
            text: props.titleOfChart,
            left: 'center',
            right: 'center',
        },
        toolbox: {
            show: true,
            orient: 'vertical',
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
                mySaveAsImage: {
                    show: true,
                    title: 'Save as Image',
                    icon: 'path://M12 16 L16 12 L15 11 L12 14 L9 11 L8 12 L12 16 Z M4 14 L4 18 C4 19 5 20 6 20 L18 20 C19 20 20 19 20 18 L20 14 M12 0 L12 12',
                    onclick: function () {
                        startExport();
                    },
                },
            }
        },
        dataZoom: [
            {
                type: 'inside'
            }
        ],
        legend: {
            data: props.legendData ? [props.legendData] : [props.dataSetLabel],
            top: props.legendDataPosition,
        },
        xAxis: {
            data: props.labels,
            axisTick: {
                alignWithLabel: true
            },
            triggerEvent: true
        },
        yAxis: {
            type: 'value',
            axisLabel: {
                formatter: function (value) {
                    return formatYAxisLabelForChart(value);
                },
            },
            min: 0
        },
        rotate: {
            min: -90,
            max: 90
        },
        series: [
            {
                type: 'bar',
                data: props.data,
                name: props.legendData ?? props.dataSetLabel,
                label: {
                    position: 'insideBottom',
                    distance: 10,
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
                    top: 10,
                    borderRadius: [borderRadiusTopLeft, borderRadiusTopRight, 0, 0],
                },
                color: backgroundColor
            },
        ],
        color: backgroundColor,
    };
};

const handleChartClick = (params) => {
    if (params.componentType === 'series') {
        emits('handle-chart-click', params);
    }
};

const updateChartSize = () => {
    const chartContainer = document.getElementById(props.chartId);

    if (chartContainer) {
        chartContainer.style.height = props.chartHeight;
    }
};

onMounted(() => {
    updateChartSize();
});
</script>

<style scoped>
.chart {
    height: 60vh;
}
</style>
