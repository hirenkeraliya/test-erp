<template>
    <PageTitle title="Rewards" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Rewards
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.rewards.create')">
                <PrimaryButton
                    text="Add New"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.rewards.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by title, type, target_type, minimum point, or maximum point"
    >
        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(data.item.id, $event)"
                />
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.rewards.edit', data.item.id)"
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
import { router } from '@inertiajs/vue3';
import JSwitch from '@commonComponents/JSwitch.vue';

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
            key: 'title',
            sortable: true
        }, {
            key: 'type',
            sortable: true
        }, {
            key: 'status',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),

});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-rewards/',
        'rewards.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-rewards/',
        'rewards.xlsx',
        params,
        props.exportPermission
    );
};

const setStatus = (rewardId, status) => {
    router.post(route('admin.rewards.set_status', [rewardId, status ? 1 : 0]));
};
</script>
