<template>
    <div
        class="bg-white rounded-xl p-3 pt-5"
    >
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
            ref="chartContainer"
            class="chart"
            :option="getOption()"
            autoresize
            :loading="datasets.length <= 0"
        />
    </div>
</template>

<script setup>
import { formatLabelForChart, formatYAxisLabelForChart, getRandomPastelColor, getPastelColors } from '@commonServices/helper';
import { BarChart, LineChart } from 'echarts/charts';
import {
    DataZoomComponent,
    GridComponent,
    LegendComponent,
    TitleComponent,
    ToolboxComponent,
    TooltipComponent,
} from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import VChart from 'vue-echarts';
import { Info } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

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
    TitleComponent
]);

const props = defineProps({
    chartId: {
        type: String,
        required: true,
    },
    titleOfChart: {
        type: String,
        default: '',
    },
    titleClass: {
        type: String,
        default: '',
    },
    chartInfo: {
        type: String,
        default: '',
    },
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
    legendData: {
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
    },
    textRotation: {
        type: Number,
        default: 90,
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

        const multiBarOrLineChartDelay = 100;
        setTimeout(() => {
            const url = chartInstance.getDataURL({
                type: 'png',
                pixelRatio: 2,
                backgroundColor: '#fff',
            });

            // Create a temporary link to trigger the download
            const link = document.createElement('a');
            link.href = url;
            link.download = `${props.fileName}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Reset the subtitle after exporting
            chartInstance.setOption({
                title: {
                    subtext: '',
                },
            });
        }, multiBarOrLineChartDelay);
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

    return {
        title: {
            text: props.titleOfChart,
            left: 'center',
            right: 'center',
        },
        tooltip: {
            trigger: 'item',
            valueFormatter: function (value) {
                return formatLabelForChart(value);
            },
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
                type: 'inside',
            }
        ],
        legend: {
            data: props.legendData,
            top: 'bottom',
            type: 'scroll',
        },
        label: {
            show: true,
            fontSize: 16,
            fontWeight: 'bold',
            align: 'left',
            verticalAlign: 'middle',
            position: 'insideBottom',
            rotate: props.textRotation,
            formatter: function (value) {
                return formatLabelForChart(value.value);
            }
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
        yAxis: {
            type: 'value',
            axisLabel: {
                formatter: function (value) {
                    return formatYAxisLabelForChart(value);
                },
            },
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
