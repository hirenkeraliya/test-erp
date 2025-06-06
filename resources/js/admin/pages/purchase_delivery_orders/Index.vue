<template>
    <DeliveryOrders
        :fulfillment-statuses="fulFillmentStatuses"
        :static-details="staticDetails"
        :status="status"
        :order-type="orderType"
        :order-number="orderNumber"
        :export-permission="exportPermission"
        :purchase-orders-filter-data="purchaseOrdersFilterData"
        :fetch-url="route('admin.purchase_order_fulfillments.fetch_delivery_orders')"
        open-purchase-order-fulfillment-items-url="admin.purchase_order_fulfillments.fetch_purchase_order_fulfillment_items"
        mark-as-shipped-url="admin.purchase_order_fulfillments.shipped"
        mark-as-received-url="admin.purchase_order_fulfillments.mark_as_received"
        mark-as-cancel-url="admin.purchase_order_fulfillments.mark_as_cancel"
        delivery-note-url="admin.purchase_order_fulfillments.delivery_note"
        mark-as-open-url="admin.purchase_order_fulfillments.mark_as_open"
        mark-as-closed-url="admin.purchase_order_fulfillments.discrepancy_closed_delivery_order"
        edit-url="admin.purchase_order_fulfillments.edit"
        print-purchase-order-fulfillment-url="admin.purchase_order_fulfillments.print"
        export-records-url="/admin/export-purchase-order-fulfillment-items/"
    />
</template>

<script setup>
import DeliveryOrders from '@commonPages/DeliveryOrders.vue';
import { route } from 'ziggy';

defineProps({
    fulFillmentStatuses: {
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
    orderType: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    purchaseOrdersFilterData: {
        type: Object,
        required: true,
    },
    orderNumber: {
        type: String,
        default: null,
    },
});
</script>
