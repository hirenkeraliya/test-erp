<template>
    <PageTitle title="Employees" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Employees
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('store_manager.employees.create')">
                <PrimaryButton
                    text="Add New Employee"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('store_manager.employees.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by first name, email, or mobile number"
    >
        <template #first_name="data">
            {{ data.item.first_name }} {{ data.item.last_name }}
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
                    :content="'If this employee is assigned an Admin, Store Manager, Warehouse Manager, Promoter or Cashier account, he cannot login if the status is inactive.'"
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
                    :href="route('store_manager.employees.edit', data.item.id)"
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
import { exportRecords } from '@commonServices/helper';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { EmployeeHelpText } from '@commonStores/documentation';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
});

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
            key: 'card_number',
            sortable: true
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
    router.post(route('store_manager.employees.set_status', [employeeId, status ? 1 : 0]));
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-employees/',
        'employees.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-employees/',
        'employees.xlsx',
        params,
        props.exportPermission
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(EmployeeHelpText());
</script>
