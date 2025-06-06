<template>
    <PageTitle title="Roles &amp; Permissions" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Warehouse Manager Permissions
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.warehouse_manager_roles.create')">
                <PrimaryButton
                    text="New Permission"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.warehouse_manager_roles.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        search-title="Search by name"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.warehouse_manager_roles.edit_roles_permissions', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.warehouse_manager_roles.clone', data.item.id)"
                >
                    <Copy class="w-4 h-4 mr-2" />
                    Clone
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Copy } from 'lucide-vue-next';

const state = reactive({
    columns: [
        {
            key: 'id',
        }, {
            key: 'name',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }
    ],
    refreshTableData: Math.random(),
});
</script>
