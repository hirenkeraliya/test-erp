<template>
    <PageTitle title="Inventory Report" />

    <InventoryReport
        :fetch-url="route('store_manager.inventory_reports.fetch')"
        :stores="stores"
        :regions="regions"
        :product-collections="productCollections"
        :product-statuses="productStatuses"
        :stock-types="stockTypes"
        :selling-types="sellingTypes"
        :pre-selected-locations="state.selectedLocations"
        :pre-selected-location-ids="state.selectedLocationIds"
        :pre-selected-location-type="staticLocationTypes.store"
        :warehouses="warehouses"
        :export-permission="exportPermission"
        :dashboard-filter-data="dashboardFilterData"
        :fetch-sizes-url="route('store_manager.sizes.get_filtered_sizes')"
        :fetch-colors-url="route('store_manager.colors.get_filtered_colors')"
        :get-filtered-inventory-products="route('store_manager.get_filtered_inventory_products')"
        :fetch-categories="route('store_manager.categories.get_filtered_categories')"
        :fetch-brands="route('store_manager.brands.get_filtered_brands')"
        :fetch-departments="route('store_manager.departments.get_filtered_departments')"
        :fetch-tags="route('store_manager.tags.get_filtered_tags')"
        :fetch-styles="route('store_manager.styles.get_filtered_styles')"
        :fetch-article-numbers="route('store_manager.products.get_filtered_article_number')"
        local-storage-key="store_manager-inventory-reports-columns"
        :redirect-url="route('store_manager.inventory_reports.index')"
        :static-location-types="staticLocationTypes"
        :location-types="locationTypes"
        check-inventory-export-limit-url="store_manager.inventory_reports.check_inventory_export_limit"
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
    selectedLocations: [],
    selectedLocationIds: [ObjectStorage.get('store-manager-store-id')],
});

state.selectedLocations = props.stores.filter((location) => {
    return location.id === ObjectStorage.get('store-manager-store-id');
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
