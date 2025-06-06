<template>
    <MarketPlaceOrderListing
        :order-types="orderTypes"
        :order-channels="orderChannels"
        :order-types-static-use="orderTypesStaticUse"
        :order-address-static-types="orderAddressStaticTypes"
        :order-status-static-use="orderStatusStaticUse"
        :payment-types="paymentTypes"
        :e-invoice-generate-permission="eInvoiceGeneratePermission"
        :module-type="moduleType"
        :allow-e-invoice="allowEInvoice"
        :show-invoice="true"
        :receipt-number="receiptNumber"
        :date-range="dateRange"
        report-heading-name="Marketplaces Orders"
        fetch-url="admin.orders.fetch_marketplaces_orders"
        fetch-order-items-url="admin.orders.fetch_order_items_for_ecommerce"
        filter-members-url="admin.members.get_filtered_members"
        print-purchase-order-url="admin.orders.print_purchase_order"
        store-picking-lists-url="admin.order_picking_lists.store"
        accepted-status-url="admin.orders.accepted"
        cancelled-status-url="admin.orders.cancelled"
        print-digital-invoice-url="admin.orders.print_b2b_order_digital_invoice"
        ready-for-pickup-status-url="admin.orders.ready_for_pickup"
        print-ninja-van-way-bill-url="admin.orders.print_ninja_van_way_bill"
        print-pdf-url="admin.orders.print_marketplace_orders"
        fetch-order-address-url="admin.orders.fetch_order_address"
    />
</template>

<script setup>
import MarketPlaceOrderListing from '@commonComponents/MarketPlaceOrderListing.vue';

defineProps({
    orderTypes: {
        type: Object,
        required: true
    },
    orderChannels: {
        type: Object,
        required: true
    },
    orderTypesStaticUse: {
        type: Object,
        required: true
    },
    orderAddressStaticTypes: {
        type: Object,
        required: true
    },
    orderStatusStaticUse: {
        type: Object,
        required: true
    },
    paymentTypes: {
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
    receiptNumber: {
        type: String,
        default: null,
    },
    dateRange: {
        type: Array,
        default: null,
    }
});

</script>
