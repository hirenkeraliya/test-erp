<template>
    <PageTitle title="Inventory Report" />

    <InventoryReport
        :fetch-url="route('warehouse_manager.inventory_reports.fetch')"
        :stores="stores"
        :regions="regions"
        :warehouses="warehouses"
        :product-statuses="productStatuses"
        :product-collections="productCollections"
        :pre-selected-locations="state.selectedWarehouses"
        :pre-selected-location-ids="state.selectedWarehouseIds"
        :pre-selected-location-type="staticLocationTypes.store"
        :dashboard-filter-data="dashboardFilterData"
        :fetch-sizes-url="route('warehouse_manager.sizes.get_filtered_sizes')"
        :fetch-colors-url="route('warehouse_manager.colors.get_filtered_colors')"
        :get-filtered-inventory-products="route('warehouse_manager.get_filtered_inventory_products')"
        :fetch-categories="route('warehouse_manager.categories.get_filtered_categories')"
        :fetch-brands="route('warehouse_manager.brands.get_filtered_brands')"
        :fetch-departments="route('warehouse_manager.departments.get_filtered_departments')"
        :fetch-tags="route('warehouse_manager.tags.get_filtered_tags')"
        :fetch-styles="route('warehouse_manager.styles.get_filtered_styles')"
        :fetch-article-numbers="route('warehouse_manager.products.get_filtered_article_number')"
        local-storage-key="warehouse_manager-inventory-reports-columns"
        :redirect-url="route('warehouse_manager.inventory_reports.index')"
        :stock-types="stockTypes"
        :selling-types="sellingTypes"
        :export-permission="exportPermission"
        :static-location-types="staticLocationTypes"
        :location-types="locationTypes"
        check-inventory-export-limit-url="warehouse_manager.inventory_reports.check_inventory_export_limit"
        :attributes="attributes"
    />
</template>

<script setup>
import InventoryReport from '@commonPages/InventoryReport.vue';
import { route } from 'ziggy';
import ObjectStorage from '@commonServices/storage.js';
import { reactive } from 'vue';
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
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    regions: {
        type: Array,
        required: true,
    },
    stockTypes: {
        type: Array,
        required: true,
    },
    productStatuses: {
        type: Array,
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
    sellingTypes: {
        type: Array,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => {},
    },
});

const state = reactive({
    selectedWarehouses: [],
    selectedWarehouseIds: [ObjectStorage.get('warehouse-manager-warehouse-id')],
});

state.selectedWarehouses = props.warehouses.filter((warehouse) => {
    return warehouse.id === ObjectStorage.get('warehouse-manager-warehouse-id');
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
