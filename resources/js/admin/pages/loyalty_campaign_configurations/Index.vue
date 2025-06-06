<template>
    <PageTitle title="Loyalty Campaigns" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Loyalty Campaign Configurations
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.loyalty_campaign_configurations.create')">
                <PrimaryButton
                    text="Add New"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.loyalty_campaign_configurations.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        search-title="Search by  description, amount, or points"
    >
        <template #minimum_purchase_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.minimum_purchase_amount) }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.loyalty_campaign_configurations.edit', data.item.id)"
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
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';

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
            key: 'description',
            label: 'Title',
            sortable: true
        }, {
            key: 'loyalty_campaign_type',
            label: 'Campaign Type',
            sortable: true
        }, {
            key: 'expiration_type',
            label: 'Expire By',
            sortable: true
        }, {
            key: 'minimum_purchase_amount',
            label: 'Minimum Purchase',
            bodyClass: 'text-right',
            headerClass: 'text-right'
        }, {
            key: 'point_earned',
            bodyClass: 'text-right',
            label: 'Points Earned',
            headerClass: 'text-right'
        }, {
            key: 'include_tax',
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
        'export-loyalty-campaign-configurations/',
        'loyalty-campaign-configurations.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-loyalty-campaign-configurations/',
        'loyalty-campaign-configurations.xlsx',
        params,
        props.exportPermission
    );
};
</script>
