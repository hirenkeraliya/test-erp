<template>
    <PurchaseOrderFulfillmentManage
        :delivery-order-url="route('store_manager.purchase_order_fulfillments.delivery_order', purchaseOrderId)"
        :add-shipping-details-url="route('store_manager.purchase_order_fulfillments.add_shipping_details', purchaseOrderId)"
        update-url="store_manager.purchase_order_fulfillments.update"
        print-box-sticker-url="store_manager.purchase_order_fulfillments.print_box_sticker"
        :transfer-items="transferItems"
        :purchase-order-fulfillment="purchaseOrderFulfillment"
        :package-types="packageTypes"
    />
</template>

<script setup>
import { route } from 'ziggy';
import PurchaseOrderFulfillmentManage from '@commonPages/PurchaseOrderFulfillmentManage.vue';

defineProps({
    transferItems: {
        type: Object,
        default: () => {},
    },
    purchaseOrderId: {
        type: Number,
        required: true,
    },
    purchaseOrderFulfillment: {
        type: Object,
        default: () => {},
    },
    packageTypes: {
        type: Object,
        required: true,
    },
});

</script>
