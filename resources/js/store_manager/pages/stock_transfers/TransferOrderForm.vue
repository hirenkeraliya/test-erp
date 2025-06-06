<template>
    <StockTransferOrderManage
        v-if="state.selectedLocationId"
        :stores="state.filteredStores"
        :warehouses="warehouses"
        :stock-transfer="stockTransfer"
        :stock-transfer-reasons="stockTransferReasons"
        :stock-transfer-types="stockTransferTypes"
        :transfer-type="transferType"
        :package-types="packageTypes"
        :location-types="locationTypes"
        :selected-location-id="state.selectedLocationId"
        :selected-location-name="state.selectedLocationName + ' (Store)'"
        :cancel-url="route('store_manager.stock_transfers.index')"
        :product-search-url-list="route('store_manager.get_filtered_inventory_products_list')"
        :filtered-category-url="route('store_manager.categories.get_filtered_categories')"
        :filtered-brand-url="route('store_manager.brands.get_filtered_brands')"
        product-article-search-url="store_manager.products.search_by_article_number"
        get-product-url-name="store_manager.get_product"
        product-upc-url="store_manager.products.get_matching_upc_inventory_products_with_derivatives"
        aggregate-average-days-url="store_manager.stock_transfers.aggregate_average_days"
        get-inventory-stocks-url="store_manager.get_inventory_stocks"
        stock-transfer-store-url="store_manager.stock_transfers.store"
        stock-transfer-update-url="store_manager.stock_transfers.update"
        get-filtered-inventory-products-url="store_manager.get_filtered_inventory_products"
        :default-location-type="staticLocationTypes.store"
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
    selectedLocationId: null,
    filteredStores: null,
    selectedLocationName: null,
});

onMounted(() => {
    state.selectedLocationId = ObjectStorage.get('store-manager-store-id');
    state.filteredStores = props.stores.filter((location) => {
        if (location.id === state.selectedLocationId) {
            state.selectedLocationName = location.name;
        }

        return location.id !== state.selectedLocationId;
    });
});
</script>
