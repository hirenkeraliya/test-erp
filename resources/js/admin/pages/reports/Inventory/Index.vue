<template>
    <PageTitle title="Inventory Report" />

    <InventoryReport
        :fetch-url="route('admin.inventory_reports.fetch')"
        :stores="stores"
        :warehouses="warehouses"
        :product-collections="productCollections"
        :pre-selected-location-type="staticLocationTypes.store"
        :regions="regions"
        :product-statuses="productStatuses"
        :stock-types="stockTypes"
        :selling-types="sellingTypes"
        :dashboard-filter-data="dashboardFilterData"
        :export-permission="exportPermission"
        :fetch-sizes-url="route('admin.sizes.get_filtered_sizes')"
        :fetch-colors-url="route('admin.colors.get_filtered_colors')"
        :get-filtered-inventory-products="route('admin.get_filtered_inventory_products')"
        :fetch-categories="route('admin.categories.get_filtered_categories')"
        :fetch-brands="route('admin.brands.get_filtered_brands')"
        :fetch-departments="route('admin.departments.get_filtered_departments')"
        :fetch-tags="route('admin.tags.get_filtered_tags')"
        :fetch-styles="route('admin.styles.get_filtered_styles')"
        :fetch-article-numbers="route('admin.products.get_filtered_article_number')"
        local-storage-key="admin-inventory-reports-columns"
        :redirect-url="route('admin.inventory_reports.index')"
        :static-location-types="staticLocationTypes"
        :location-types="locationTypes"
        :sale-channels="saleChannels"
        :has-pending-sync-transaction="hasPendingSyncTransaction"
        check-inventory-export-limit-url="admin.inventory_reports.check_inventory_export_limit"
        :attributes="attributes"
    />
</template>

<script setup>
import InventoryReport from '@commonPages/InventoryReport.vue';
import { route } from 'ziggy';
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
    regions: {
        type: Array,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    stockTypes: {
        type: Array,
        required: true,
    },
    sellingTypes: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    productStatuses: {
        type: Array,
        required: true,
    },
    helpCenterMessages: {
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
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => {},
    },
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
