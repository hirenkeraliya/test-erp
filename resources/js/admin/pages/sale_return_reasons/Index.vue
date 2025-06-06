<template>
    <PageTitle title="Return Codes" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Return Codes
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.sale_return_reasons.create')">
                <PrimaryButton
                    text="Add New Sale Return Reason"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.sale_return_reasons.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by reason"
    >
        <template #put_back_in_inventory="record">
            {{ record.item.put_back_in_inventory ? "Yes" : "No" }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    :href="route('admin.sale_return_reasons.edit', data.item.id)"
                    class="flex items-center mr-3"
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
            key: 'reason',
        }, {
            key: 'type',
        }, {
            key: 'put_back_in_inventory',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }
    ],
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-sale-return-reasons/',
        'sale-return-reasons.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-sale-return-reasons/',
        'sale-return-reasons.xlsx',
        params,
        props.exportPermission
    );
};
</script>
