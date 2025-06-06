<template>
    <OrderListing
        :order-types="orderTypes"
        :order-types-static-use="orderTypesStaticUse"
        :order-return-reasons="orderReturnReasons"
        :cancel-order-reasons="cancelOrderReasons"
        :payment-types="paymentTypes"
        :export-permission="exportPermission"
        :locations="locations"
        :location-id="locationId"
        report-heading-name="B2B Orders"
        fetch-url="store_manager.orders.fetch_b2b_orders"
    >
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('store_manager.orders.create')">
                <PrimaryButton
                    text="Add New Order"
                    class="shadow-md"
                />
            </Link>
        </div>
    </OrderListing>
</template>

<script setup>
import OrderListing from '@storeManagerComponents/OrderListing.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { route } from 'ziggy';

defineProps({
    orderTypes: {
        type: Object,
        required: true
    },
    orderTypesStaticUse: {
        type: Object,
        required: true
    },
    orderReturnReasons: {
        type: Object,
        required: true
    },
    cancelOrderReasons: {
        type: Object,
        required: true
    },
    paymentTypes: {
        type: Object,
        required: true
    },
    exportPermission: {
        type: String,
        required: true,
    },
    locations: {
        type: Object,
        required: true
    },
    locationId: {
        type: Number,
        default: 0,
    },
});

</script>
