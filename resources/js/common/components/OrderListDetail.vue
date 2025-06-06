<template>
    <JSimpleTable
        :columns="state.orderColumns"
        :records="orders"
    />
</template>
<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { reactive } from 'vue';

defineProps({
    orders: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    orderColumns: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'receipt_number',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'bill_reference_number',
            label: '# Reference',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'channel',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'member',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
});

</script>
