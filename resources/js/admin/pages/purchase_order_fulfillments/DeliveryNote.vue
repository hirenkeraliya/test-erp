<template>
    <PurchaseOrderFulfillmentDeliveryNote
        :transfer-items="transferItems"
        :purchase-order-product-ids="purchaseOrderProductIds"
        :purchase-order-fulfillment-id="purchaseOrderFulfillmentId"
        :location-id="locationId"
        :external-location-id="externalLocationId"
        :statuses="statuses"
        :partially-receive-statuses="partiallyReceiveStatuses"
        :discrepancy-types="discrepancyTypes"
        :package-types="packageTypes"
        :stock-transfer-locations="stockTransferLocations"
        :delivery-order-url="route('admin.purchase_order_fulfillments.delivery_order', purchaseOrderId)"
        get-filtered-inventory-products-url="admin.get_filtered_inventory_products"
        get-product-url-name="admin.get_product"
        get-filtered-inventory-products-list-url="admin.get_filtered_inventory_products_list"
        get-filtered-categories-url="admin.categories.get_filtered_categories"
        get-filtered-brands-url="admin.brands.get_filtered_brands"
        purchase-order-fulfillment-delivery-note-item-remarks-url="admin.purchase_order_fulfillments.purchase_order_fulfillment_delivery_note_item_remarks"
        update-received-quantities-url="admin.purchase_order_fulfillments.update_received_quantities"
        update-batch-details-url="admin.purchase_order_fulfillments.update_batch_details"
        delete-batch-detail-url="admin.purchase_order_fulfillments.delete_batch_details"
        discrepancy-url="admin.purchase_order_fulfillments.discrepancy"
        closed-url="admin.purchase_order_fulfillments.closed"
        set-received-same-quantities-url="admin.purchase_order_fulfillments.set_received_same_quantities"
        discrepancy-proof-url="admin.purchase_order_fulfillments.discrepancy_proof"
        remove-discrepancy-proof-url="admin.purchase_order_fulfillments.remove_discrepancy_proof"
        remove-additional-item-url="admin.purchase_order_fulfillments.remove_additional_item"
        get-location-inventory-stocks-url="admin.get_location_inventory_stocks"
        update-additional-items-url="admin.purchase_order_fulfillments.update_additional_items"
        delivery-note-url="admin.purchase_order_fulfillments.delivery_note"
        partial-receive-url="admin.purchase_order_fulfillments.partial_receive"
        fetch-partially-receive-fulfillment-url="admin.purchase_order_fulfillments.fetch_partially_receive_fulfillment"
        fetch-partially-receive-fulfillment-items="admin.purchase_order_fulfillments.fetch_partially_receive_fulfillment_items"
        mark-as-approved-url="admin.purchase_order_fulfillments.partial_receive_approved"
        mark-as-completed-url="admin.purchase_order_fulfillments.partial_receive_completed"
        mark-as-cancelled-url="admin.purchase_order_fulfillments.partial_receive_cancelled"
    />
</template>

<script setup>
import { route } from 'ziggy';
import PurchaseOrderFulfillmentDeliveryNote from '@commonPages/PurchaseOrderFulfillmentDeliveryNote.vue';

defineProps({
    transferItems: {
        type: Object,
        required: true,
    },
    purchaseOrderProductIds: {
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
    locationId: {
        type: Number,
        required: true,
    },
    externalLocationId: {
        type: Number,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    partiallyReceiveStatuses: {
        type: Object,
        required: true,
    },
    discrepancyTypes: {
        type: Object,
        required: true,
    },
    packageTypes: {
        type: Object,
        default: () => {},
    },
    stockTransferLocations: {
        type: Object,
        default: () => {},
    },
});

</script>
