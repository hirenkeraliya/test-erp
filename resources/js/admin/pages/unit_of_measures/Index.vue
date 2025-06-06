<template>
    <PageTitle title="Unit of Measures (UOM)" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Unit of Measures (UOM)
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.unit_of_measures.create')">
                <PrimaryButton
                    text="Add New Unit of Measure"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.unit_of_measures.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.unit_of_measure_derivatives.index', data.item.id)"
                >
                    <List class="w-4 h-4 mr-1" />
                    Derivatives
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.unit_of_measures.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <button
                    class="flex items-center mr-3"
                    @click="deleteRecord(data.item.id)"
                >
                    <Archive class="w-4 h-4 mr-1" />
                    Archive
                </button>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, List, Archive } from 'lucide-vue-next';
import { route } from 'ziggy';
import { exportRecords } from '@commonServices/helper';
import { router } from '@inertiajs/vue3';
import { confirmDialogBox } from '@commonServices/notifier';


const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    refreshTableData: Math.random(),
    columns: [
        {
            key: 'id',
            sortable: true
        }, {
            key: 'name',
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
        'export-unit-of-measures/',
        'unit-of-measures.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-unit-of-measures/',
        'unit-of-measures.xlsx',
        params,
        props.exportPermission
    );
};

const deleteRecord = (unitOfMeasureId) => {
    const message = 'Are you sure want to delete?';
    confirmDialogBox(message, () => {
        router.post(route('admin.unit_of_measures.delete', unitOfMeasureId), {}, {
            onSuccess: () => refreshTable()
        });
    });
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

</script>
