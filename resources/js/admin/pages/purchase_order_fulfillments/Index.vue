<template>
    <PurchaseOrderFulfillmentIndex
        :ful-fillment-statuses="fulFillmentStatuses"
        :purchase-order="purchaseOrder"
        :static-details="staticDetails"
        :status="status"
        :export-permission="exportPermission"
        :has-purchase-order-items="hasPurchaseOrderItems"
        index-url="admin.purchase_orders.index"
        fetch-url="admin.purchase_order_fulfillments.fetch"
        shipped-url="admin.purchase_order_fulfillments.shipped"
        mark-as-received-url="admin.purchase_order_fulfillments.mark_as_received"
        mark-as-cancel-url="admin.purchase_order_fulfillments.mark_as_cancel"
        mark-as-open-url="admin.purchase_order_fulfillments.mark_as_open"
        delivery-note-url="admin.purchase_order_fulfillments.delivery_note"
        discrepancy-closed-delivery-order-url="admin.purchase_order_fulfillments.discrepancy_closed_delivery_order"
        edit-url="admin.purchase_order_fulfillments.edit"
        print-url="admin.purchase_order_fulfillments.print"
        shipping-details-url="admin.purchase_order_fulfillments.shipping_details"
        fetch-purchase-order-fulfillment-items-url="admin.purchase_order_fulfillments.fetch_purchase_order_fulfillment_items"
        export-url="/admin/export-purchase-order-fulfillment-items/"
    />
</template>

<script setup>
import PurchaseOrderFulfillmentIndex from '@commonPages/PurchaseOrderFulfillmentIndex.vue';

defineProps({
    fulFillmentStatuses: {
        type: Object,
        required: true,
    },
    purchaseOrder: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    status: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    hasPurchaseOrderItems: {
        type: Boolean,
        default: false,
        required: true,
    },
});

</script>
