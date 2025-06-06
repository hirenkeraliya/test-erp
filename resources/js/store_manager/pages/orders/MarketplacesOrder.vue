<template>
    <MarketPlaceOrderListing
        :order-types="orderTypes"
        :order-channels="orderChannels"
        :order-types-static-use="orderTypesStaticUse"
        :order-address-static-types="orderAddressStaticTypes"
        :order-status-static-use="orderStatusStaticUse"
        :payment-types="paymentTypes"
        :receipt-number="receiptNumber"
        :date-range="dateRange"
        report-heading-name="Marketplaces Orders"
        fetch-url="store_manager.orders.fetch_marketplaces_orders"
        fetch-order-items-url="store_manager.orders.fetch_order_items_for_ecommerce"
        filter-members-url="store_manager.members.get_filtered_members"
        print-purchase-order-url="store_manager.orders.print_purchase_order"
        store-picking-lists-url="store_manager.order_picking_lists.store"
        accepted-status-url="store_manager.orders.accepted"
        cancelled-status-url="store_manager.orders.cancelled"
        ready-for-pickup-status-url="store_manager.orders.ready_for_pickup"
        print-ninja-van-way-bill-url="store_manager.orders.print_ninja_van_way_bill"
        print-pdf-url="store_manager.orders.print_marketplace_orders"
        fetch-order-address-url="store_manager.orders.fetch_order_address"
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
