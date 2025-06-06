<template>
    <PurchaseOrderManage
        :stores="stores"
        :warehouses="warehouses"
        :external-stores="externalStores"
        :external-warehouses="externalWarehouses"
        :external-companies="externalCompanies"
        :order-types="orderTypes"
        :static-details="staticDetails"
        :purchase-order="purchaseOrder"
        :default-order-type="defaultOrderType"
        :created-by-company-id="createdByCompanyId"
        :default-location-type-id="staticDetails.staticLocationTypes.warehouse"
        :selected-location-id="ObjectStorage.get('warehouse-manager-warehouse-id')"
        :location-types="locationTypes"
        product-file-upload-for-upc-url="warehouse_manager.products.get_matching_upc_inventory_products_with_derivatives"
        get-filtered-inventory-products-url="warehouse_manager.get_filtered_inventory_products"
        product-filter-search-url="warehouse_manager.get_filtered_inventory_products"
        get-product-url-name="warehouse_manager.get_product"
        cancel-url="warehouse_manager.purchase_orders.index"
        product-filter-details-search-url="warehouse_manager.get_filtered_inventory_products_list"
        product-filter-details-category-search-url="warehouse_manager.categories.get_filtered_categories"
        product-filter-details-brand-search-url="warehouse_manager.brands.get_filtered_brands"
        advance-product-search-url="warehouse_manager.products.search_by_article_number_with_stock"
        get-external-location-url="warehouse_manager.external_locations.get_external_locations"
        update-purchase-order-url="warehouse_manager.purchase_orders.update"
        store-purchase-order-url="warehouse_manager.purchase_orders.store"
        get-location-inventory-stocks-url="warehouse_manager.get_location_inventory_stocks"
        get-purchase-order-index-url="warehouse_manager.purchase_orders.index"
    />
</template>

<script setup>
import PurchaseOrderManage from '@commonPages/PurchaseOrderManage.vue';
import ObjectStorage from '@commonServices/storage.js';

defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    locationTypes: {
        type: Array,
        required: true,
    },
    externalStores: {
        type: Array,
        default: () => [],
    },
    externalWarehouses: {
        type: Array,
        default: () => [],
    },
    externalCompanies: {
        type: Array,
        required: true,
    },
    orderTypes: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    purchaseOrder: {
        type: Object,
        default: () => {},
    },
    defaultOrderType: {
        type: Number,
        required: true,
    },
    createdByCompanyId: {
        type: Number,
        default: null,
    },
});
</script>
