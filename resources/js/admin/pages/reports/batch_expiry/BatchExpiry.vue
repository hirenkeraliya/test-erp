<template>
    <BatchExpiryReport
        :export-permission="exportPermission"
        :fetch-url="route('admin.batch_expiry.fetch_batch_expiry')"
        :fetch-categories="route('admin.categories.get_filtered_categories')"
        :fetch-brands="route('admin.brands.get_filtered_brands')"
        :fetch-tags="route('admin.tags.get_filtered_tags')"
        local-storage-key="admin-batch-expiry-reports-columns"
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
