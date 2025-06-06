<template>
    <PageTitle title="SizeGroups" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Size Groups
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.size_groups.create')">
                <PrimaryButton
                    text="Add New Size Group"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.size_groups.fetch')"
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
                    :href="route('admin.size_groups.edit', data.item.id)"
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
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare } from 'lucide-vue-next';
import { route } from 'ziggy';
import { exportRecords } from '@commonServices/helper';

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
            key: 'name',
            sortable: true
        }, {
            key: 'code',
            sortable: true
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-size-groups/',
        'size-groups.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-size-groups/',
        'size-groups.xlsx',
        params,
        props.exportPermission
    );
};
</script>
