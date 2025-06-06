<template>
    <PurchasePlanManage
        :stores="stores"
        :warehouses="warehouses"
        :vendors="vendors"
        :purchase-plan="purchasePlan"
        :static-details="staticDetails"
        :selected-location-id="null"
        :default-location-type-id="staticDetails.staticLocationTypes.store"
        :location-types="locationTypes"
        product-file-upload-for-upc-url="admin.products.get_matching_upc_inventory_products_with_derivatives"
        get-filtered-inventory-products-url="admin.get_filtered_inventory_products"
        product-filter-search-url="admin.get_filtered_inventory_products"
        get-product-url-name="admin.get_product"
        cancel-url="admin.purchase_plans.index"
        product-filter-details-search-url="admin.get_filtered_inventory_products_list"
        product-filter-details-category-search-url="admin.categories.get_filtered_categories"
        product-filter-details-brand-search-url="admin.brands.get_filtered_brands"
        advance-product-search-url="admin.products.search_by_article_number_for_purchase_plan"
        update-purchase-plan-url="admin.purchase_plans.update"
        store-purchase-plan-url="admin.purchase_plans.store"
        get-location-inventory-stocks-url="admin.get_location_inventory_stocks"
        get-purchase-plan-index-url="admin.purchase_plans.index"
    />
</template>

<script setup>
import PurchasePlanManage from '@commonPages/PurchasePlanManage.vue';

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
    staticDetails: {
        type: Object,
        required: true,
    },
    purchasePlan: {
        type: Object,
        default: () => {},
    },
    vendors: {
        type: Array,
        required: true,
    },
});

</script>
