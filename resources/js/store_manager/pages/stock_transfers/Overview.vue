<template>
    <StockTransfersOverview
        stock-transfer-url="store_manager.stock_transfers.index"
        :get-transfer-order-url="route('store_manager.get_transfer_order')"
        :get-request-order-url="route('store_manager.get_request_order')"
        :get-transfer-in-url="route('store_manager.get_transfer_in')"
        :get-transfer-out-url="route('store_manager.get_transfer_out')"
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
