<template>
    <ExternalPurchaseOrderManage
        :purchase-plan="purchasePlan"
        :external-purchase-order="externalPurchaseOrder"
        store-external-purchase-order-url="admin.external_purchase_orders.store"
        update-external-purchase-order-url="admin.external_purchase_orders.update"
        get-external-purchase-order-index-url="admin.external_purchase_orders.index"
        :transfer-items="transferItems"
    />
</template>

<script setup>
import ExternalPurchaseOrderManage from '@commonPages/ExternalPurchaseOrderManage.vue';

defineProps({
    purchasePlan: {
        type: Object,
        required: true
    },
    externalPurchaseOrder: {
        type: Object,
        default: () => { },
    },
    transferItems: {
        type: Object,
        default: () => {},
    },
});

</script>
