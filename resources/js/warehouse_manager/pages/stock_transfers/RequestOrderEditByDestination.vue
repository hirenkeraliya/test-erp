<template>
    <RequestOrderEdit
        :stock-transfer="stockTransfer"
        :product-search-list-url="route('warehouse_manager.get_filtered_inventory_products_list')"
        :filtered-category-url="route('warehouse_manager.categories.get_filtered_categories')"
        :filtered-brand-url="route('warehouse_manager.brands.get_filtered_brands')"
        :get-inventory-stocks="route('warehouse_manager.get_inventory_stocks')"
        :cancel-url="route('warehouse_manager.stock_transfers.index')"
        :product-search-url="route('warehouse_manager.get_filtered_inventory_products')"
        product-upc-url="warehouse_manager.products.get_matching_upc_inventory_products"
        get-product-url-name="warehouse_manager.get_product"
        product-article-search-url="warehouse_manager.products.search_by_article_number"
        stock-transfers-update-request-order="warehouse_manager.stock_transfers.update_request_order"
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
