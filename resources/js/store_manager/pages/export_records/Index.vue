<template>
    <ExportRecord
        export-record-fetch-url="store_manager.export_records.fetch"
        export-record-export-url="export-export-records/"
        :export-record-id="exportRecordId"
        :export-types="exportTypes"
        :statuses="statuses"
        :static-statuses="staticStatuses"
        :export-permission="exportPermission"
        :export-filter-data="exportFilterData"
        :columns="state.columns"
    />
</template>

<script setup>
import ExportRecord from '@commonPages/ExportRecord.vue';
import { reactive } from 'vue';

defineProps({
    exportRecordId: {
        type: Number,
        default: null,
    },
    exportTypes: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    staticStatuses: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    exportFilterData: {
        type: Number,
        default: null,
    },
});

const state = reactive({
    columns: [
        {
            key: 'file_exported_at',
        },
        {
            key: 'export_type',
        },
        {
            key: 'staff_id',
        },
        {
            key: 'module_type',
        },
        {
            key: 'status',
            sortable: true
        },
        {
            key: 'total_exported_records',
            label: 'Exported Records',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        },
        {
            key: 'exported_file',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
    ],
});
</script>
