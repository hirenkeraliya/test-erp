<template>
    <PageTitle title="Import Records" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Import Records
        </h2>
    </div>

    <JTable
        :fetch-url="route('warehouse_manager.import_records.fetch')"
        :columns="state.columns"
        :additional-query-params="state.parameters"
        search-title="Search by file uploaded at, import type, status, records imported, or records failed"
    >
        <template #records_failed="data">
            <div class="my-auto">
                {{ data.item.records_failed }}

                <a
                    v-if="data.item.records_failed !== 0 && data.item.failed_records_file_url && data.item.status === statuses.completed"
                    :href="data.item.failed_records_file_url"
                    class="btn btn-sm btn-primary ml-1"
                    target="_blank"
                >
                    <Download class="w-4 h-4" />
                </a>
            </div>
        </template>

        <template #uploaded_file="data">
            <div class="my-auto">
                <a
                    v-if="data.item.upload_file_url"
                    :href="data.item.upload_file_url"
                    class="btn btn-sm btn-primary"
                    target="_blank"
                >
                    <Download class="w-4 h-4" />
                </a>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { Download } from 'lucide-vue-next';

const props = defineProps({
    importRecordId: {
        type: Number,
        default: null,
    },

    statuses: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'file_uploaded_at',
        },
        {
            key: 'import_type',
        },
        {
            key: 'created_by_type',
            label: 'Created By'
        },
        {
            key: 'staff_id',
        },
        {
            key: 'module_type',
        },
        {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
            sortable: true
        },
        {
            key: 'records_imported',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        },
        {
            key: 'uploaded_file',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
        {
            key: 'records_failed',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ],

    parameters: {
        import_record_id: props.importRecordId
    },
});

</script>
