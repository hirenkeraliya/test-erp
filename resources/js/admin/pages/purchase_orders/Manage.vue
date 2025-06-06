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
        :selected-location-id="null"
        :default-location-type-id="staticDetails.staticLocationTypes.store"
        :location-types="locationTypes"
        product-file-upload-for-upc-url="admin.products.get_matching_upc_inventory_products_with_derivatives"
        get-filtered-inventory-products-url="admin.get_filtered_inventory_products"
        product-filter-search-url="admin.get_filtered_inventory_products"
        get-product-url-name="admin.get_product"
        cancel-url="admin.purchase_orders.index"
        product-filter-details-search-url="admin.get_filtered_inventory_products_list"
        product-filter-details-category-search-url="admin.categories.get_filtered_categories"
        product-filter-details-brand-search-url="admin.brands.get_filtered_brands"
        advance-product-search-url="admin.products.search_by_article_number_with_stock"
        get-external-location-url="admin.external_locations.get_external_locations"
        update-purchase-order-url="admin.purchase_orders.update"
        store-purchase-order-url="admin.purchase_orders.store"
        get-location-inventory-stocks-url="admin.get_location_inventory_stocks"
        get-purchase-order-index-url="admin.purchase_orders.index"
    />
</template>

<script setup>
import PurchaseOrderManage from '@commonPages/PurchaseOrderManage.vue';

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
