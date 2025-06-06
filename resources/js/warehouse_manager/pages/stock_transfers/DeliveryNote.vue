<template>
    <DeliveryNote
        :stock-transfer-items="stockTransferItems"
        :stock-transfer-id="stockTransferId"
        :statuses="statuses"
        :discrepancy-types="discrepancyTypes"
        :mime-types="mimeTypes"
        :package-types="packageTypes"
        :stock-transfer-locations="stockTransferLocations"
        :cancel-url="route('warehouse_manager.stock_transfers.index')"
        update-additional-items-url="warehouse_manager.stock_transfers.update_additional_items"
        redirect-url="warehouse_manager.stock_transfers.delivery_note"
        inventory-stock-url="warehouse_manager.get_inventory_stocks"
        update-received-quantities-url="warehouse_manager.stock_transfers.update_received_quantities"
        update-status-url="warehouse_manager.stock_transfers.update_status"
        remove-discrepancy-proof-url="warehouse_manager.stock_transfers.remove_discrepancy_proof"
        discrepancy-proof-url="warehouse_manager.stock_transfers.discrepancy_proof"
        close-stock-transfer-url="warehouse_manager.stock_transfers.close_stock_transfer"
        set-received-same-quantities-url="warehouse_manager.stock_transfers.set_received_same_quantities"
        remove-additional-item-url="warehouse_manager.stock_transfers.remove_additional_item"
        :product-search-url="route('warehouse_manager.get_filtered_inventory_products')"
        :product-list-search-url="route('warehouse_manager.get_filtered_inventory_products_list')"
        :category-search-url="route('warehouse_manager.categories.get_filtered_categories')"
        :brand-search-url="route('warehouse_manager.brands.get_filtered_brands')"
        get-product-url-name="warehouse_manager.get_product"
        add-delivery-note-item-remarks-url="warehouse_manager.stock_transfers.add_delivery_note_item_remarks"
    />
</template>

<script setup>
import DeliveryNote from '@commonComponents/DeliveryNote.vue';
import { route } from 'ziggy';

defineProps({
    stockTransferItems: {
        type: Object,
        required: true,
    },
    stockTransferId: {
        type: Number,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    discrepancyTypes: {
        type: Object,
        required: true,
    },
    packageTypes: {
        type: Object,
        default: () => {},
    },
    stockTransferLocations: {
        type: Object,
        default: () => {},
    },
    mimeTypes: {
        type: Object,
        required: true,
    },
});
</script>
