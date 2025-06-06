<template>
    <PageTitle title="States" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            States
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.states.create')">
                <PrimaryButton
                    text="Add New State"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.states.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name"
    >
        <template #country="data">
            <span>{{ data.item.country ? data.item.country.name : 'N/A' }}</span>
        </template>
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.states.edit', data.item.id)"
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
            key: 'country',
            sortable: true
        }, {
            key: 'name',
            sortable: true
        }, {
            key: 'country_code',
            sortable: true
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ]
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-states/',
        'states.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-states/',
        'states.xlsx',
        params,
        props.exportPermission
    );
};
</script>
