<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <div class="content content--top-nav mr-5">
            <PageTitle title="Performance" />
            <div>
                <SalesPerformanceDashboard
                    :static-sale-target-time-interval="staticSaleTargetTimeInterval"
                    :sale-target-types="saleTargetTypes"
                    :static-sale-target-types="staticSaleTargetTypes"
                    :sale-target-time-interval="saleTargetTimeInterval"
                    :sale-target="saleTarget"
                    :sale-target-id="state.saleTargetId"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import SalesPerformanceDashboard from '@commonPages/SalesPerformanceDashboard.vue';
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import { reactive } from 'vue';

defineProps({
    saleTargetTimeInterval: {
        type: Array,
        required: true,
    },
    staticSaleTargetTimeInterval: {
        type: Object,
        required: true,
    },
    staticSaleTargetTypes: {
        type: Object,
        required: true,
    },
    saleTargetTypes: {
        type: Array,
        required: true,
    },
    saleTarget: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    cardData : null,
    chartData : null,
    saleTargetId : 0,
});

</script>
