<template>
    <PurchaseOrderInvoiceIndex
        :statuses="statuses"
        :status="status"
        :export-permission="exportPermission"
        fetch-url="admin.purchase_order_invoices.fetch"
        create-url="admin.purchase_order_invoices.create"
        cancel-url="admin.purchase_order_invoices.cancel"
        sent-url="admin.purchase_order_invoices.sent"
        paid-url="admin.purchase_order_invoices.paid"
        mark-as-received-url="admin.purchase_order_invoices.mark_as_received"
        edit-url="admin.purchase_order_invoices.edit"
        print-url="admin.purchase_order_invoices.print"
        refresh-purchase-cost-url="admin.purchase_order_invoices.refresh_purchase_cost"
    />
</template>

<script setup>
import PurchaseOrderInvoiceIndex from '@commonPages/PurchaseOrderInvoiceIndex.vue';

defineProps({
    statuses: {
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
});

</script>
