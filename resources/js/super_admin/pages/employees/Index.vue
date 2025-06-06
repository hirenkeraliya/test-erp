<template>
    <PageTitle title="Employees" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Employees
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('super_admin.employees.create')">
                <PrimaryButton
                    text="Add New Employee"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('super_admin.employees.fetch')"
        :columns="state.columns"
    >
        <template #first_name="data">
            {{ data.item.first_name }} {{ data.item.last_name }}
        </template>

        <template #company="record">
            {{ record.item.company.name }}
        </template>

        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(data.item.id, $event)"
                />

                <Tippy
                    :content="'If this employee is assigned an Admin account, he cannot login if the status is inactive.'"
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="15"
                    />
                </Tippy>
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('super_admin.employees.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Info } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { router } from '@inertiajs/vue3';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { EmployeeHelpText } from '@commonStores/documentation';

const state = reactive({
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'first_name',
            label: 'Name',
            sortable: true
        }, {
            key: 'email',
            sortable: true
        }, {
            key: 'mobile_number',
            sortable: true
        }, {
            key: 'company',
        }, {
            key: 'staff_id',
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ]
});

const setStatus = (employeeId, status) => {
    router.post(route('super_admin.employees.set_status', [employeeId, status ? 1 : 0]));
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(EmployeeHelpText());
</script>
