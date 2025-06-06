<template>
    <div v-if="sellThroughReportFilterValidationCheck(parameters, isLocationCompulsorySelection)">
        <PrimaryButton
            text="Print"
            @click="exportAccumulatedSalesThrough"
        />
    </div>
</template>

<script setup>
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { printReport, sellThroughReportFilterValidationCheck } from '@commonServices/helper';
import { reactive, watch } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    parameters: {
        type: Object,
        required: true,
    },
    refreshTableData: {
        type: Number,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    displayProductFilter: {
        type: Boolean,
        required: true,
    },
    isLocationCompulsorySelection: {
        type: Boolean,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
        },
        {
            key: 'received',
            bodyClass: 'text-center'
        },
        {
            key: 'sold',
            bodyClass: 'text-center'
        },
        {
            key: 'online_sold',
            bodyClass: 'text-center',
            sortable: true,
            isDisplay: false,
        },
        {
            key: 'net_sale_amount',
            label: 'Net Sale',
            bodyClass: 'text-center',
            sortable: true,
            isDisplay: false,
        },
        {
            key: 'online_sale_amount',
            label: 'Online Net Sale',
            bodyClass: 'text-center',
            sortable: true,
            isDisplay: false,
        },
        {
            key: 'remaining',
            bodyClass: 'text-center',
            label: 'Balance'
        },
        {
            key: 'sell_through',
            bodyClass: 'text-center',
            label: 'Sell Through (%)'
        },
    ],

    refreshTableData: Math.random(),
});

watch(() => props.refreshTableData, () => {
    refreshTable();
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const exportAccumulatedSalesThrough = () => {
    printReport(route('admin.sell_through_aggregate_reports.print_details', props.parameters), props.exportPermission);
};
</script>
