<template>
    <PurchaseOrderInvoiceManage
        url="store_manager.purchase_order_invoices.index"
        store-url="store_manager.purchase_order_invoices.store"
        details-url="store_manager.purchase_order_invoices.fulfillment_details"
        :purchase-orders="purchaseOrders"
    />
</template>

<script setup>
import PurchaseOrderInvoiceManage from '@commonPages/PurchaseOrderInvoiceManage.vue';

defineProps({
    purchaseOrders: {
        type: Array,
        required: true,
    }
});

</script>
