<template>
    <StockTransfersOverview
        stock-transfer-url="warehouse_manager.stock_transfers.index"
        :get-transfer-order-url="route('warehouse_manager.get_transfer_order')"
        :get-request-order-url="route('warehouse_manager.get_request_order')"
        :get-transfer-in-url="route('warehouse_manager.get_transfer_in')"
        :get-transfer-out-url="route('warehouse_manager.get_transfer_out')"
        :transfer-types="transferTypes"
    />
</template>

<script setup>
import StockTransfersOverview from '@commonPages/StockTransfersOverview.vue';
import { route } from 'ziggy';

defineProps({
    transferTypes: {
        type: Object,
        required: true,
    },
});
</script>
