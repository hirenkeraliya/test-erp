<template>
    <PageTitle title="External Connections" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            External Connections
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('super_admin.external_connections.create')">
                <PrimaryButton
                    text="Add New External Connection"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('super_admin.external_connections.fetch')"
        :columns="state.columns"
        search-title="Search by name or url"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.external_connections.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
                <Tippy
                    content="Refresh Data"
                    class="btn btn-outline-primary"
                    @click="syncData(data.item.id)"
                >
                    <RefreshCw class="text-primary w-5" />
                </Tippy>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import { CheckSquare, RefreshCw } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showSuccessNotification } from '@commonServices/notifier';

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true
        }, {
            key: 'url',
            sortable: true
        },
        {
            key: 'approved_at',
            sortable: true
        }, {
            key: 'rejected_at',
            sortable: true,
        }, {
            key: 'status',
            sortable: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ]
});

const syncData = (externalId) => {
    axios.get(route('super_admin.external_connections.sync_data', externalId))
        .then(() => {
            showSuccessNotification('We are actively processing your request to synchronize External Connections data in the background. Kindly await the completion of this process.');
        });
};
</script>
