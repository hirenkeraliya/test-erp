<template>
    <PageTitle title="Admins" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Admins
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('super_admin.admins.create')">
                <PrimaryButton
                    text="Add New Admin"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('super_admin.admins.fetch_admins')"
        :columns="state.columns"
    >
        <template #name="record">
            {{ record.item.employee.first_name }} {{ record.item.employee.last_name }}
        </template>

        <template #company="record">
            {{ record.item.employee.company.name }}
        </template>

        <template #email="record">
            {{ record.item.employee.email }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.admins.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.admins.change_password', data.item.id)"
                >
                    <Unlock class="w-4 h-4 mr-1" />
                    Change Password
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, Unlock } from 'lucide-vue-next';
import { route } from 'ziggy';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { AdminHelpText } from '@commonStores/documentation';

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'name',
        }, {
            key: 'company',
        }, {
            key: 'username',
            sortable: true
        }, {
            key: 'email',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ]
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(AdminHelpText());
</script>
