<template>
    <PageTitle title="Reserved Inventory Report" />

    <ReservedInventoryReport
        :fetch-url="route('warehouse_manager.reserved_inventory_reports.fetch')"
        :stores="stores"
        :product-collections="productCollections"
        :warehouses="warehouses"
        :filter-data="filterData"
        :export-permission="exportPermission"
        get-filtered-product-url="warehouse_manager.get_filtered_products"
        local-storage-key="warehouse-manager-reserved-inventory-reports-columns"
        :redirect-url="route('warehouse_manager.reserved_inventory_reports.index')"
        :location-types="locationTypes"
        :static-location-types="staticLocationTypes"
    />
</template>

<script setup>
import { route } from 'ziggy';
import ReservedInventoryReport from '@commonPages/ReservedInventoryReport.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
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
    helpCenterMessages: {
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
helpStore.setHelpData(props.helpCenterMessages);
</script>
