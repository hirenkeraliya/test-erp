<template>
    <RequestOrderEdit
        :stock-transfer="stockTransfer"
        :product-search-list-url="route('admin.get_filtered_inventory_products_list')"
        :filtered-category-url="route('admin.categories.get_filtered_categories')"
        :filtered-brand-url="route('admin.brands.get_filtered_brands')"
        :get-inventory-stocks="route('admin.get_inventory_stocks')"
        :cancel-url="route('admin.stock_transfers.index')"
        :product-search-url="route('admin.get_filtered_inventory_products')"
        product-upc-url="admin.products.get_matching_upc_inventory_products_with_derivatives"
        get-product-url-name="admin.get_product"
        product-article-search-url="admin.products.search_by_article_number"
        stock-transfers-update-request-order="admin.stock_transfers.update_request_order"
    />
</template>

<script setup>
import RequestOrderEdit from '@commonComponents/RequestOrderEdit.vue';
import { route } from 'ziggy';

defineProps({
    stockTransfer: {
        type: Object,
        default: () => {},
    },
});
</script>
