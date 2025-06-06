<template>
    <PageTitle title="Employee Groups" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Employee Groups
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.employee_groups.create')">
                <PrimaryButton
                    text="Add New Employee Group"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.employee_groups.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name or code"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.employee_groups.edit', data.item.id)"
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
import { CheckSquare } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords } from '@commonServices/helper';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { EmployeeGroupHelpText } from '@commonStores/documentation';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true
        }, {
            key: 'code',
            sortable: true
        }, {
            key: 'purchase_limit_type_id',
            label: 'Purchase Limit Type',
            sortable: true,
        }, {
            key: 'item_purchase_limit',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'limit_reset_type_id',
            label: 'Limit Reset Type',
            sortable: true
        }, {
            key: 'limit_reset',
            label: 'Limit Reset Timeline',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }
    ]
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-employee-groups/',
        'employee-groups.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-employee-groups/',
        'employee-groups.xlsx',
        params,
        props.exportPermission
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(EmployeeGroupHelpText());
</script>
