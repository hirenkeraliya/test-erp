<template>
    <ExternalPurchaseOrderReceiveManage
        :external-purchase-order="externalPurchaseOrder"
        store-external-purchase-order-receive-url="admin.external_purchase_order_receives.store"
        update-external-purchase-order-receive-url="admin.external_purchase_order_receives.update"
        update-external-purchase-order-url="admin.external_purchase_orders.update"
        get-external-purchase-order-receive-index-url="admin.external_purchase_order_receives.index"
        :transfer-items="transferItems"
        :external-purchase-order-partial-receive="externalPurchaseOrderPartialReceive"
    />
</template>

<script setup>
import ExternalPurchaseOrderReceiveManage from '@commonPages/ExternalPurchaseOrderReceiveManage.vue';

defineProps({
    externalPurchaseOrder: {
        type: Object,
        default: () => { },
    },
    transferItems: {
        type: Object,
        default: () => {},
    },
    externalPurchaseOrderPartialReceive: {
        type: Object,
        default: () => { },
    },
});

</script>
