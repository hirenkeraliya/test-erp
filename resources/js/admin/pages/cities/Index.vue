<template>
    <PageTitle title="Cities" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cities
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.cities.create')">
                <PrimaryButton
                    text="Add New City"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.cities.fetch')"
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

        <template #state="data">
            <span>{{ data.item.state ? data.item.state.name : 'N/A' }}</span>
        </template>
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.cities.edit', data.item.id)"
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
            key: 'state',
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
        'export-cities/',
        'cities.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-cities/',
        'cities.xlsx',
        params,
        props.exportPermission
    );
};
</script>
