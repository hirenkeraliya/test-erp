<template>
    <PurchaseOrderInvoiceEdit
        url="admin.purchase_order_invoices.index"
        update-invoice-id-url="admin.purchase_order_invoices.update_invoice_id"
        remove-update-invoice-id-url="admin.purchase_order_invoices.remove_update_invoice_id"
        :purchase-order-fulfillments="purchaseOrderFulfillments"
        :purchase-order-invoice-id="purchaseOrderInvoiceId"
    />
</template>

<script setup>
import PurchaseOrderInvoiceEdit from '@commonPages/PurchaseOrderInvoiceEdit.vue';

defineProps({
    purchaseOrderFulfillments: {
        type: Array,
        required: true,
    },
    purchaseOrderInvoiceId: {
        type: Number,
        required: true,
    }
});

</script>
