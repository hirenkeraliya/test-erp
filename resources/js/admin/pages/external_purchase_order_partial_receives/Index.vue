<template>
    <ExternalPurchaseOrderReceiveIndex
        :external-purchase-order="externalPurchaseOrder"
        :statuses="statuses"
        fetch-url="admin.external_purchase_order_receives.fetch"
        create-partial-receive-url="admin.external_purchase_order_receives.create"
        edit-external-partial-receive-url="admin.external_purchase_order_receives.edit"
        fetch-external-purchase-order-receive-items-url="admin.external_purchase_order_receives.fetch_external_purchase_order_receive_items"
        approve-partial-receive-url="admin.external_purchase_order_receives.completed"
        add-grn-url="admin.external_purchase_order_receives.add_grn"
        cancel-partial-receive-url="admin.external_purchase_order_receives.mark_as_cancel"
        :has-partial-receive-items="hasPartialReceiveItems"
        export-url="/admin/export-external-purchase-order-partial-receive-items/"
        print-external-purchase-order-receive-url="admin.external_purchase_order_receives.print"
    />
</template>

<script setup>
import ExternalPurchaseOrderReceiveIndex from '@commonPages/ExternalPurchaseOrderReceiveIndex.vue';

defineProps({
    externalPurchaseOrder: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    hasPartialReceiveItems: {
        type: Boolean,
        default: false,
        required: true,
    },
});
</script>
