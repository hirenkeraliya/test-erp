<template>
    <PurchasePlanIndex
        :stores="stores"
        :warehouses="warehouses"
        :vendors="vendors"
        :statuses="statuses"
        :plan-statuses="planStatuses"
        :export-permission="exportPermission"
        :static-location-types="staticLocationTypes"
        :location-types="locationTypes"
        fetch-purchase-plan-url="admin.purchase_plans.fetch"
        create-purchase-plan-url="admin.purchase_plans.create"
        edit-purchase-plan-url="admin.purchase_plans.edit"
        print-purchase-plan-url="admin.purchase_plans.print"
        index-purchase-plan-url="admin.purchase_plans.index"
        fetch-purchase-plan-items-url="admin.purchase_plans.fetch_purchase_plan_items"
        cancel-purchase-plan-url="admin.purchase_plans.cancel"
        approve-purchase-plan-url="admin.purchase_plans.approve"
        external-purchase-order-url="admin.external_purchase_orders.index"
        :is-admin="true"
    />
</template>

<script setup>
import PurchasePlanIndex from '@commonPages/PurchasePlanIndex.vue';

defineProps({
    statuses: {
        type: Object,
        required: true,
    },
    planStatuses: {
        type: Object,
        required: true,
    },
    stores: {
        type: Array,
        required: true,
    },
    vendors: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    }
});
</script>
