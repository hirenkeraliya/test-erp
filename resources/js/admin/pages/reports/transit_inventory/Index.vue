<template>
    <PageTitle title="Transit Inventory Report" />

    <TransitInventoryReport
        :fetch-url="route('admin.transit_inventory_reports.fetch')"
        :stores="stores"
        :product-collections="productCollections"
        :warehouses="warehouses"
        :filter-data="filterData"
        :export-permission="exportPermission"
        :location-types="locationTypes"
        :static-location-types="staticLocationTypes"
        get-filtered-product-url="admin.get_filtered_products"
        local-storage-key="admin-transit-inventory-reports-columns"
        :redirect-url="route('admin.transit_inventory_reports.index')"
    />
</template>

<script setup>
import { route } from 'ziggy';
import TransitInventoryReport from '@commonPages/TransitInventoryReport.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { TransitInventoryReportHelpText } from '@commonStores/documentation';

defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    filterData: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    locationTypes: {
        type: Array,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    },
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(TransitInventoryReportHelpText());
</script>
