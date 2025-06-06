<template>
    <PageTitle title="Cashier Groups" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Cashier Groups
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('store_manager.cashier_groups.create')">
                <PrimaryButton
                    text="Add New Cashier Group"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('store_manager.cashier_groups.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name or price override limit percentage"
    >
        <template #price_override_limit_percentage_for_item="data">
            {{ data.item.price_override_limit_percentage_for_item ? displayAmountWithPercentageSymbol(data.item.price_override_limit_percentage_for_item): 'N/A' }}
        </template>
        <template #price_override_limit_percentage_for_cart="data">
            {{ displayAmountWithPercentageSymbol(data.item.price_override_limit_percentage_for_cart) }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('store_manager.cashier_groups.edit', data.item.id)"
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
import { displayAmountWithPercentageSymbol, exportRecords } from '@commonServices/helper';

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
            key: 'price_override_limit_percentage_for_item',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }, {
            key: 'price_override_limit_percentage_for_cart',
            headerClass: 'text-right',
            bodyClass: 'text-right',
            sortable: true
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ]
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-cashier-groups/',
        'cashier-groups.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-cashier-groups/',
        'cashier-groups.xlsx',
        params,
        props.exportPermission
    );
};
</script>
