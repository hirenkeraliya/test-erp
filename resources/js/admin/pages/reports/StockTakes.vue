<template>
    <PageTitle title="Stock Takes" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Takes
        </h2>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.stock_takes.fetch_stock_takes')"
        :allow-column-customization="true"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportListPageCsvRecords"
        :export-excel-records-callback="exportListPageExcelRecords"
        local-storage-key="admin-stock-takes-reports-columns"
        search-title="Search by store or warehouse, requested manager or submitted manager"
    >
        <template #action="data">
            <ExportDropDown
                class="mr-3"
                :allow-csv-export="true"
                :allow-excel-export="true"
                @update:export-csv-file="exportCsvRecord(data.item.id)"
                @update:export-excel-file="exportExcelRecord(data.item.id)"
            />
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import ExportDropDown from '@commonComponents/ExportDropDown.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { exportRecords } from '@commonServices/helper';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'requested_manager',
            label: 'Requested By',
            isDisplay: true,
        }, {
            key: 'location',
            label: 'Location',
            isDisplay: true,
        }, {
            key: 'submitted_manager',
            label: 'Submitted By',
            isDisplay: true,
        }, {
            key: 'submitted_at',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'compare_stock_date',
            label: 'Comparison Date',
            isDisplay: true,
        }, {
            key: 'action',
            isDisplay: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
});

const exportCsvRecord = (stockTakeId, params) => {
    return exportRecords(
        'export-stock-take-products/' + stockTakeId + '/',
        'stock-take-products-' + stockTakeId + '.csv',
        params
    );
};

const exportExcelRecord = (stockTakeId, params) => {
    return exportRecords(
        'export-stock-take-products/' + stockTakeId + '/',
        'stock-take-products-' + stockTakeId + '.xlsx',
        params
    );
};

const exportListPageCsvRecords = (params, columns) => {
    return exportRecords(
        'export-stock-takes/',
        'stock-takes.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportListPageExcelRecords = (params, columns) => {
    return exportRecords(
        'export-stock-takes/',
        'stock-takes.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
