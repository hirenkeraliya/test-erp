<template>
    <PageTitle title="Memberships" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Memberships
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.memberships.create')">
                <PrimaryButton
                    text="Add New Membership"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <InfoAlert
        color="primary"
        class="mb-3 mt-5"
    >
        1. Memberships of members/employees are assigned/upgraded automatically based on their lifetime spend amount.<br>
        2. And while redeeming the loyalty points, the system looks at their current membership to decide the ratio or loyalty points to {{ currencySymbol }}.
    </InfoAlert>

    <JTable
        :fetch-url="route('admin.memberships.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :search-title="'Search by name, lifetime value, or points per '+currencySymbol+'1'"
    >
        <template #lifetime_value="data">
            {{ displayAmountWithCurrencySymbol(data.item.lifetime_value) }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.memberships.edit', data.item.id)"
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
import { computed, reactive } from 'vue';
import { CheckSquare } from 'lucide-vue-next';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';
import { route } from 'ziggy';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { usePage } from '@inertiajs/vue3';
const currencySymbol = computed(() => usePage().props.currency_symbol);

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
            key: 'lifetime_value',
            label: 'Minimum Lifetime Value',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        }, {
            key: 'loyalty_points_per_currency_unit',
            label: 'Loyalty Points Per Currency unit (' + currencySymbol.value + ')',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'min_loyalty_points_for_redemption',
            label: 'Minimum Loyalty Points For Redemption',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'max_loyalty_points_for_redemption',
            label: 'Maximum Loyalty Points For Redemption',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-memberships/',
        'memberships.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-memberships/',
        'memberships.xlsx',
        params,
        props.exportPermission
    );
};
</script>
