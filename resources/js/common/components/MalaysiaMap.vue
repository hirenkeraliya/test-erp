<template>
    <div class="bg-white rounded-xl p-3 pt-5 h-full">
        <Tippy
            tag="h2"
            class="pl-4 mr-5 text-lg font-medium truncate text-center"
        >
            <p class="inline-block pb-5 text-xl font-medium">
                Malaysia
            </p>
        </Tippy>

        <div
            :id="chartId"
            style="width: 100%; height: 80%;"
        />
    </div>
</template>

<script setup>
import { malaysiaMapConfigurations } from '@commonVendor/malaysiaMap.js';
import * as echarts from 'echarts';
import { onMounted } from 'vue';

const props = defineProps({
    chartId: {
        type: String,
        required: true,
    },
    dataSeries: {
        type: Array,
        required: true,
    }
});

onMounted(() => {
    echarts.registerMap('Malaysia', malaysiaMapConfigurations());

    const chartElement = echarts.init(document.getElementById(props.chartId));

    chartElement.setOption({
        tooltip: {
            trigger: 'item',
            formatter: function (params) {
                return `
                    ${params.name}<br />
                    Affected Locations: ${isNaN(params.value) ? 0 : params.value}
                `;
            }
        },
        legend: {
            top: 'top',
            type: 'scroll',
            left: 'center',
            show: true,
            orient: 'horizontal',
            selectedMode: 'multiple',
        },
        series: props.dataSeries,
    });
});

</script>
