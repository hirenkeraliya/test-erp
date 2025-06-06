<template>
    <PurchaseOrderFulfillmentDiscrepancy
        :transfer-items="transferItems"
        :discrepancy-types="discrepancyTypes"
        :purchase-order-fulfillment-id="purchaseOrderFulfillmentId"
        :delivery-order-url="route('store_manager.purchase_order_fulfillments.delivery_order', purchaseOrderId)"
        update-discrepancy-batch-details-url="store_manager.purchase_order_fulfillments.update_discrepancy_batch_details"
        delete-batch-detail-url="store_manager.purchase_order_fulfillments.delete_batch_details"
        purchase-order-fulfillment-delivery-note-item-remarks-url="store_manager.purchase_order_fulfillments.purchase_order_fulfillment_delivery_note_item_remarks"
        update-received-quantities-url="store_manager.purchase_order_fulfillments.update_received_quantities"
        closed-discrepancy-url="store_manager.purchase_order_fulfillments.closed_discrepancy"
    />
</template>

<script setup>
import { route } from 'ziggy';
import PurchaseOrderFulfillmentDiscrepancy from '@commonPages/PurchaseOrderFulfillmentDiscrepancy.vue';

defineProps({
    transferItems: {
        type: Object,
        required: true,
    },
    discrepancyTypes: {
        type: Object,
        required: true,
    },
    purchaseOrderFulfillmentId: {
        type: Number,
        required: true,
    },
    purchaseOrderId: {
        type: Number,
        required: true,
    },
});

</script>
