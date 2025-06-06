<template>
    <StockTransferOrderManage
        v-if="state.selectedWarehouseId"
        :stores="stores"
        :warehouses="state.filteredWarehouses"
        :stock-transfer="stockTransfer"
        :stock-transfer-reasons="stockTransferReasons"
        :stock-transfer-types="stockTransferTypes"
        :transfer-type="transferType"
        :package-types="packageTypes"
        :location-types="locationTypes"
        :selected-location-id="state.selectedWarehouseId"
        :selected-location-name="state.selectedWarehouseName + ' (Warehouse)'"
        :cancel-url="route('warehouse_manager.stock_transfers.index')"
        :product-search-url-list="route('warehouse_manager.get_filtered_inventory_products_list')"
        :filtered-category-url="route('warehouse_manager.categories.get_filtered_categories')"
        :filtered-brand-url="route('warehouse_manager.brands.get_filtered_brands')"
        product-article-search-url="warehouse_manager.products.search_by_article_number"
        get-product-url-name="warehouse_manager.get_product"
        product-upc-url="warehouse_manager.products.get_matching_upc_inventory_products_with_derivatives"
        aggregate-average-days-url="warehouse_manager.stock_transfers.aggregate_average_days"
        get-inventory-stocks-url="warehouse_manager.get_inventory_stocks"
        stock-transfer-store-url="warehouse_manager.stock_transfers.store"
        stock-transfer-update-url="warehouse_manager.stock_transfers.update"
        get-filtered-inventory-products-url="warehouse_manager.get_filtered_inventory_products"
        :default-location-type="staticLocationTypes.warehouse"
        :static-location-types="staticLocationTypes"
    />
</template>
<script setup>
import { route } from 'ziggy';
import { onMounted, reactive } from 'vue';
import StockTransferOrderManage from '@commonPages/StockTransferOrderManage.vue';
import ObjectStorage from '@commonServices/storage.js';

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    stockTransfer: {
        type: Object,
        default: () => {},
    },
    stockTransferReasons: {
        type: Object,
        required: true,
    },
    stockTransferTypes: {
        type: Object,
        required: true,
    },
    transferType: {
        type: String,
        default: null,
    },
    packageTypes: {
        type: Object,
        default: () => {},
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

const state = reactive({
    selectedWarehouseId: null,
    filteredWarehouses: null,
    selectedWarehouseName: null,
});

onMounted(() => {
    state.selectedWarehouseId = ObjectStorage.get('warehouse-manager-warehouse-id');
    state.filteredWarehouses = props.warehouses.filter((warehouse) => {
        if (warehouse.id === state.selectedWarehouseId) {
            state.selectedWarehouseName = warehouse.name;
        }

        return warehouse.id !== state.selectedWarehouseId;
    });
});
</script>
