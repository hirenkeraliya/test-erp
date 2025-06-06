<template>
    <StockTransferLocationIndex
        :statuses="statuses"
        :static-title-statuses="staticTitleStatuses"
        :transfer-types="transferTypes"
        :mime-types="mimeTypes"
        :stock-transfer-types="stockTransferTypes"
        :status="status"
        :stores="stores"
        :warehouses="warehouses"
        :stock-transfer-number="stockTransferNumber"
        :stock-transfer-id="stockTransferId"
        :static-stock-transfer-type="staticStockTransferType"
        :parameters-location-id="ObjectStorage.get('warehouse-manager-warehouse-id')"
        :parameters-transfer-type="dashboardFilterData.transfer_type"
        :parameters-select-status="dashboardFilterData.select_status"
        :dashboard-filter-data="dashboardFilterData"
        :location-types="locationTypes"
        :parameters-location-type="staticLocationTypes.warehouse"
        fetch-stock-transfer-items-url="warehouse_manager.stock_transfers.fetch_stock_transfer_items"
        print-stock-transfer-url="warehouse_manager.stock_transfers.print_stock_transfer"
        stock-transfer-create-url="warehouse_manager.stock_transfers.create"
        stock-transfer-edit-url="warehouse_manager.stock_transfers.edit"
        stock-transfer-edit-request-order-url="warehouse_manager.stock_transfers.edit_request_order"
        stock-transfer-delivery-note-url="warehouse_manager.stock_transfers.delivery_note"
        stock-transfer-shipping-details-url="warehouse_manager.stock_transfers.shipping_details"
        stock-transfer-update-status-url="warehouse_manager.stock_transfers.update_status"
        stock-transfer-discrepancy-url="warehouse_manager.stock_transfers.discrepancy"
        stock-transfer-update-received-date-status-url="warehouse_manager.stock_transfers.update_received_date_and_status"
        stock-transfer-fetch-url="warehouse_manager.stock_transfers.fetch"
        stock-transfer-shipped-or-transit-url="warehouse_manager.stock_transfers.mark_as_shipped_or_transit"
        :redirect-url="route('warehouse_manager.stock_transfers.index')"
        :shipped-types="shippedTypes"
        :shipped-transit="shippedTransit"
        :export-permission="exportPermission"
        :shipped-direct="shippedDirect"
        :static-location-types="staticLocationTypes"
    />
</template>

<script setup>
import StockTransferLocationIndex from '@commonPages/StockTransferLocationIndex.vue';
import ObjectStorage from '@commonServices/storage.js';
import { route } from 'ziggy';

defineProps({
    statuses: {
        type: Object,
        required: true,
    },
    staticTitleStatuses: {
        type: Object,
        required: true,
    },
    transferTypes: {
        type: Object,
        required: true,
    },
    stockTransferTypes: {
        type: Object,
        required: true,
    },
    status: {
        type: Array,
        required: true,
    },
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    stockTransferNumber: {
        type: String,
        default: '',
    },
    stockTransferId: {
        type: String,
        default: '',
    },
    staticStockTransferType: {
        type: Object,
        required: true,
    },
    shippedTypes: {
        type: Object,
        required: true,
    },
    shippedTransit: {
        type: Number,
        required: true,
    },
    shippedDirect: {
        type: Number,
        required: true,
    },
    mimeTypes: {
        type: Object,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    exportPermission: {
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
    }
});
</script>
