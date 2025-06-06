<template>
    <ExternalPurchaseOrderIndex
        :purchase-plan="purchasePlan"
        :statuses="statuses"
        :external-purchase-order-statuses="externalPurchaseOrderStatuses"
        fetch-url="admin.external_purchase_orders.fetch"
        create-external-purchase-order-url="admin.external_purchase_orders.create"
        edit-external-purchase-order-url="admin.external_purchase_orders.edit"
        cancel-external-purchase-order-url="admin.external_purchase_orders.mark_as_cancel"
        approve-external-purchase-order-url="admin.external_purchase_orders.mark_as_approve"
        fetch-external-purchase-order-items-url="admin.external_purchase_orders.fetch_external_purchase_order_items"
        :has-purchase-order-items="hasPurchaseOrderItems"
        export-url="/admin/export-external-purchase-order-items/"
        print-external-purchase-order-url="admin.external_purchase_orders.print"
        external-purchase-order-receive-url="admin.external_purchase_order_receives.index"
    />
</template>

<script setup>
import ExternalPurchaseOrderIndex from '@commonPages/ExternalPurchaseOrderIndex.vue';

defineProps({
    purchasePlan: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    externalPurchaseOrderStatuses: {
        type: Object,
        required: true,
    },
    hasPurchaseOrderItems: {
        type: Boolean,
        default: false,
        required: true,
    },
});
</script>
