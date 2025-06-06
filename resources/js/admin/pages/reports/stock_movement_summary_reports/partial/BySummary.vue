<template>
    <div v-if="stockMovementSummaryReportFilterValidationCheck(parameters)">
        <PrimaryButton
            text="Print"
            @click="exportAccumulatedStockMovement"
        />
    </div>
</template>

<script setup>
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { printReport, stockMovementSummaryReportFilterValidationCheck } from '@commonServices/helper';
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
    }
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

const exportAccumulatedStockMovement = () => {
    printReport(route('admin.stock_movement_summary_reports.print_details', props.parameters), props.exportPermission);
};
</script>
