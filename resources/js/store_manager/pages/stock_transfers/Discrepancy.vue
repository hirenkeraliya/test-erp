<template>
    <Discrepancy
        :stock-transfer-items="stockTransferItems"
        :discrepancy-types="discrepancyTypes"
        :mime-types="mimeTypes"
        :stock-transfer-id="stockTransferId"
        :cancel-url="route('store_manager.stock_transfers.index')"
        update-received-quantities-url="store_manager.stock_transfers.update_received_quantities"
        close-discrepancy-url="store_manager.stock_transfers.close_discrepancy"
        add-delivery-note-item-remarks-url="store_manager.stock_transfers.add_delivery_note_item_remarks"
    />
</template>

<script setup>
import Discrepancy from '@commonComponents/Discrepancy.vue';
import { route } from 'ziggy';

defineProps({
    stockTransferItems: {
        type: Object,
        required: true,
    },
    discrepancyTypes: {
        type: Object,
        required: true,
    },
    stockTransferId: {
        type: Number,
        required: true,
    },
    mimeTypes: {
        type: Object,
        required: true,
    },
});
</script>
