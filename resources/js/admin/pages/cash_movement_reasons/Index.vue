<template>
    <PageTitle title="Cash Flow Codes" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cash Flow Codes
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.cash_movement_reasons.create')">
                <PrimaryButton
                    text="Add New Cash Movement Reason"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.cash_movement_reasons.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by reason"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <span v-if="! staticCashMovementReasons.includes(data.item.id)">
                    <Link
                        class="flex items-center mr-3"
                        :href="route('admin.cash_movement_reasons.edit', data.item.id)"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" />
                        Edit
                    </Link>
                </span>

                <Tippy
                    v-else
                    class="flex justify-center items-center"
                    content="The reason for this cash movement is set as the system default and cannot be modified."
                >
                    <Info class="text-cyan-400" />
                </Tippy>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, Info } from 'lucide-vue-next';
import { route } from 'ziggy';
import { exportRecords } from '@commonServices/helper';

const props = defineProps({
    staticCashMovementReasons: {
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
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'reason',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'type',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }
    ],
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-cash-movement-reasons/',
        'cash-movement-reasons.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-cash-movement-reasons/',
        'cash-movement-reasons.xlsx',
        params,
        props.exportPermission
    );
};
</script>
