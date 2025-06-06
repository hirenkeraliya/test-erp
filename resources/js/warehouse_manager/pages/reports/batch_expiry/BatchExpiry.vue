<template>
    <BatchExpiryReport
        :fetch-url="route('warehouse_manager.batch_expiry.fetch_batch_expiry')"
        :fetch-categories="route('warehouse_manager.categories.get_filtered_categories')"
        :fetch-brands="route('warehouse_manager.brands.get_filtered_brands')"
        :fetch-tags="route('warehouse_manager.tags.get_filtered_tags')"
        local-storage-key="warehouse-manager-batch-expiry-reports-columns"
        :export-permission="exportPermission"
    />
</template>

<script setup>

import BatchExpiryReport from '@commonPages/BatchExpiryReport.vue';
import { route } from 'ziggy';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
