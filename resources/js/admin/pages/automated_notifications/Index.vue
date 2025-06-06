<template>
    <PageTitle title="Automated Notifications" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Automated Notifications
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.automated_notifications.create')">
                <PrimaryButton
                    text="Add New Automated Notification"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.automated_notifications.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by type or timeframe"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.automated_notifications.edit', data.item.id)"
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
            key: 'type',
            sortable: true
        }, {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'description',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'timeframe_type',
            sortable: true
        },{
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-automated-notifications/',
        'automated-notifications.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-automated-notifications/',
        'automated-notifications.xlsx',
        params,
        props.exportPermission
    );
};
</script>
