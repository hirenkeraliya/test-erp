<template>
    <OrderListing
        :order-types="orderTypes"
        :order-types-static-use="orderTypesStaticUse"
        :payment-types="paymentTypes"
        :store-managers="storeManagers"
        :e-invoice-generate-permission="eInvoiceGeneratePermission"
        :module-type="moduleType"
        :allow-e-invoice="allowEInvoice"
        report-heading-name="B2B Orders"
        fetch-route-name="admin.orders.fetch_b2b_orders"
    />
</template>

<script setup>
import OrderListing from '@adminComponents/OrderListing.vue';

defineProps({
    orderTypes: {
        type: Object,
        required: true
    },
    orderTypesStaticUse: {
        type: Object,
        required: true
    },
    paymentTypes: {
        type: Object,
        required: true
    },
    storeManagers: {
        type: Object,
        required: true
    },
    eInvoiceGeneratePermission: {
        type: String,
        required: true,
    },
    moduleType: {
        type: String,
        required: true,
    },
    allowEInvoice: {
        type: Boolean,
        required: true,
    },
});

</script>
