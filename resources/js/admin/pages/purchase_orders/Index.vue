<template>
    <PurchaseOrderIndex
        :stores="stores"
        :warehouses="warehouses"
        :statuses="statuses"
        :order-types="orderTypes"
        :order-type="orderType"
        :status="status"
        :external-companies="externalCompanies"
        :order-number="orderNumber"
        :export-permission="exportPermission"
        :ful-fillment-statuses="fulFillmentStatuses"
        :dashboard-filter-data="dashboardFilterData"
        :static-location-types="staticLocationTypes"
        :location-types="locationTypes"
        delivery-orders-url="admin.purchase_order_fulfillments.delivery_orders"
        fetch-purchase-order-url="admin.purchase_orders.fetch"
        create-purchase-order-url="admin.purchase_orders.create"
        fetch-purchase-order-items-url="admin.purchase_orders.fetch_purchase_order_items"
        cancel-purchase-order-url="admin.purchase_orders.cancel"
        approve-purchase-order-url="admin.purchase_orders.approve"
        reject-purchase-order-url="admin.purchase_orders.reject"
        open-purchase-order-url="admin.purchase_orders.open"
        edit-purchase-order-url="admin.purchase_orders.edit"
        delivery-order-url="admin.purchase_order_fulfillments.delivery_order"
        print-purchase-order-url="admin.purchase_orders.print"
        get-external-locations-url="admin.external_locations.get_external_locations"
        index-purchase-order-url="admin.purchase_orders.index"
        :is-admin="true"
    />
</template>

<script setup>
import PurchaseOrderIndex from '@commonPages/PurchaseOrderIndex.vue';

defineProps({
    statuses: {
        type: Object,
        required: true,
    },
    orderTypes: {
        type: Object,
        required: true,
    },
    orderType: {
        type: Object,
        required: true,
    },
    status: {
        type: Object,
        required: true,
    },
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    externalCompanies: {
        type: Array,
        required: true,
    },
    orderNumber: {
        type: String,
        default: '',
    },
    exportPermission: {
        type: String,
        required: true,
    },
    fulFillmentStatuses: {
        type: Object,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
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
