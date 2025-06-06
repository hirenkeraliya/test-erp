<template>
    <PageTitle title="Users" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Users
        </h2>

        <div class="w-full sm:w-auto flex mt-1 sm:mt-0">
            <Link :href="route('admin.users.create')">
                <PrimaryButton
                    text="Add New User"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayEmployeeFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.employees"
                    :records="employees"
                    input-label="Employees"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Employees"
                    @update:selected-records="updateEmployees"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10"
                @click="clearAll()"
            />
        </div>
    </div>
    <JTable
        :fetch-url="route('admin.users.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by username"
    >
        <template #employee="data">
            <div>
                {{ data.item.employee ? data.item.employee.first_name : 'N/A' }}
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.users.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.users.change_password', data.item.id)"
                >
                    <Unlock class="w-4 h-4 mr-1" />
                    Change Password
                </Link>
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayEmployeeFilter = !state.displayEmployeeFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, Unlock } from 'lucide-vue-next';
import { route } from 'ziggy';
import { exportRecords } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    employees: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'username',
            sortable: true,
        }, {
            key: 'employee',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },{
            key: 'type',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
    employees: [],
    displayEmployeeFilter: false,

    parameters: {
        employee_ids: [],
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.employees = null;
    state.parameters.employee_ids = [];
    refreshTable();
};

const updateEmployees = (employees) => {
    state.employees = employees;

    const groupIds = employees.map((colorGroup) => {
        return colorGroup.id;
    });
    state.parameters.employee_ids = groupIds;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-users/',
        'users.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-users/',
        'users.xlsx',
        params,
        props.exportPermission
    );
};
</script>
