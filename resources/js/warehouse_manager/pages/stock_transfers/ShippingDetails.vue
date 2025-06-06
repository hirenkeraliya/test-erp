<template>
    <PageTitle title="Ship Stock Transfer" />

    <ShippingForm
        :stock-transfer-items="stockTransferItems"
        :package-types="packageTypes"
        :stock-transfer-id="stockTransferId"
        submit-shipping-url="warehouse_manager.stock_transfers.update_shipping_details_and_mark_as_approved"
        cancel-url="warehouse_manager.stock_transfers.index"
    />
</template>

<script setup>
import ShippingForm from '@commonComponents/ShippingForm.vue';

defineProps({
    stockTransferItems: {
        type: Object,
        required: true,
    },

    packageTypes: {
        type: Object,
        required: true,
    },

    stockTransferId: {
        type: Number,
        required: true,
    },
});
</script>
