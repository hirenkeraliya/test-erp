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
            ref="chartContainer"
            class="chart"
            :option="getOption()"
            autoresize
            :loading="Object.keys(getOption()).length <= 0"
        />
    </div>
</template>

<script setup>
import { getPastelColors, formatLabelForChart } from '@commonServices/helper';
import VChart from 'vue-echarts';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart } from 'echarts/charts';
import { Info } from 'lucide-vue-next';
import {
    TooltipComponent,
    LegendComponent,
    ToolboxComponent,
    TitleComponent,
} from 'echarts/components';
import XkcdColor from '@commonVendor/corporaColorXkcd';
import { nextTick, onMounted, ref } from 'vue';

use([
    TitleComponent,
    ToolboxComponent,
    CanvasRenderer,
    PieChart,
    TooltipComponent,
    LegendComponent,
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
    sectionName: {
        type: String,
        default: '',
    },
    labels: {
        type: Array,
        required: true,
    },
    data: {
        type: Array,
        required: true,
    },
    datasetLabel: {
        type: String,
        default: ''
    },
    backgroundColor: {
        type: Boolean,
        default: true,
    },
    filters: {
        type: Object,
        default: null,
    },
});

const chartContainer = ref(null);

const startExport = () => {
    const chartInstance = chartContainer.value?.chart;
    const chartWidthDivisor = 35;
    const chartHeightDivisor = 35;
    const minFontSize = 7;

    if (chartInstance) {
        const chartWidth = chartInstance.getWidth();
        const chartHeight = chartInstance.getHeight();

        const dynamicFontSize = Math.max(Math.min(chartWidth / chartWidthDivisor, chartHeight / chartHeightDivisor), minFontSize);

        const filterText = Object.entries(props.filters || {})
            .map(([key, filter]) => {
                return `${key.charAt(0).toUpperCase() + key.slice(1)}: ${filter.name !== undefined ? filter.name : 'All'}`;
            })
            .join(' | ');

        chartInstance.setOption({
            title: {
                subtext: filterText,
                subtextStyle: {
                    fontSize: dynamicFontSize,
                },
            },
            toolbox: {
                show: false,
            },
        });

        const pieChartDelay = 100;
        setTimeout(() => {
            const url = chartInstance.getDataURL({
                type: 'png',
                pixelRatio: 3,
                backgroundColor: '#fff',
            });

            const link = document.createElement('a');
            link.href = url;
            link.download = `${props.sectionName}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            chartInstance.setOption({
                title: {
                    subtext: '',
                },
                toolbox: {
                    show: true,
                },
            });
        }, pieChartDelay);
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
    const numbers = props.data;
    const labels = props.labels;
    const preparedRecords = [];
    for (const key in labels) {
        preparedRecords.push({ name: labels[key], value: numbers[key] });
    }

    const backgroundColor = props.backgroundColor
        ? getPastelColors()
        : preparedHexColors(labels);

    return {
        title: {
            text: props.titleOfChart,
            left: 'center',
            right: 'center',
        },
        tooltip: {
            valueFormatter: function (value) {
                return formatLabelForChart(value);
            }
        },
        legend: {
            data: props.labels,
            top: 'bottom',
            type: 'scroll',
        },
        toolbox: {
            id: props.chartId,
            show: true,
            showTitle: true,
            feature: {
                mySaveAsImage: {
                    show: true,
                    title: 'Save as Image',
                    icon: 'path://M12 16 L16 12 L15 11 L12 14 L9 11 L8 12 L12 16 Z M4 14 L4 18 C4 19 5 20 6 20 L18 20 C19 20 20 19 20 18 L20 14 M12 0 L12 12',
                    onclick: function () {
                        startExport();
                    },
                },
            },
            top: 20,
            orient: 'vertical',
        },
        series: [
            {
                top: 20,
                scale: true,
                scaleSize: 20,
                type: 'pie',
                data: preparedRecords,
                label: {
                    fontSize: 13,
                    fontWeight: 'bold',
                    formatter: function (value) {
                        return formatLabelForChart(value.value);
                    }
                },
                itemStyle: {
                    shadowBlur: 5,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                },
                emphasis: {
                    focus: 'self',
                    itemStyle: {
                        opacity: 1,
                        shadowBlur: 30,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.7)',
                        borderWidth: 1,
                        borderColor: 'rgba(0, 0, 0, 0.3)',
                    },
                    blurScope: 'global',
                },
                blur: {
                    itemStyle: {
                        shadowBlur: 4,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(255,255,255, 0.5)',
                        opacity: 0.5, // Modify this to control blur intensity
                    },
                },
                color: backgroundColor,
            },
        ],
    };
};

const preparedHexColors = (labels) => {
    const hexColors = [];

    for (const key in labels) {
        const colorName = labels[key].toLowerCase();

        const color = XkcdColor.find(c => c.color.toLowerCase() === colorName);

        hexColors.push(color ? color.hex.toUpperCase() : '#c1c1c1');
    }
    return hexColors;
};
</script>

<style scoped>
.chart {
    height: 60vh;
}
</style>
