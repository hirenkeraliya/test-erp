<template>
    <PageTitle title="Roles &amp; Permissions" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Roles &amp; Permissions
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('super_admin.roles.create')">
                <PrimaryButton
                    text="Add New Role"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('super_admin.roles.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        search-title="Search by name"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.roles.edit_roles_permissions', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.roles.clone', data.item.id)"
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
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { RoleHelpText } from '@commonStores/documentation';

const state = reactive({
    columns: [
        {
            key: 'id',
        }, {
            key: 'name',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(RoleHelpText());
</script>
