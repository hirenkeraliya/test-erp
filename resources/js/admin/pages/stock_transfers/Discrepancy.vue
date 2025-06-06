<template>
    <Discrepancy
        :stock-transfer-items="stockTransferItems"
        :mime-types="mimeTypes"
        :discrepancy-types="discrepancyTypes"
        :stock-transfer-id="stockTransferId"
        :cancel-url="route('admin.stock_transfers.index')"
        update-received-quantities-url="admin.stock_transfers.update_received_quantities"
        close-discrepancy-url="admin.stock_transfers.close_discrepancy"
        add-delivery-note-item-remarks-url="admin.stock_transfers.add_delivery_note_item_remarks"
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
