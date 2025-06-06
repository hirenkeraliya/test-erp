<template>
    <PageTitle title="Complimentary Setup" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Complimentary Setup
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.complimentary_item_reasons.create')">
                <PrimaryButton
                    text="Add New Complimentary"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <InfoAlert
        color="primary"
        class="mb-3 mt-5"
    >
        Please
        <a
            href="/images/discount_applicable_flow.png"
            class="underline"
            target="_blank"
        >
            click here
        </a>
        To observe how the discounts are distributed among various promotions and other functionalities of the POS.
    </InfoAlert>

    <JTable
        :fetch-url="route('admin.complimentary_item_reasons.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by reason"
    >
        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.complimentary_item_reasons.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>

        <template #usage="data">
            <div class="flex justify-end">
                <span>{{ data.item.total_used_counts }}</span>
                <Tippy
                    :content="displayAmountWithCurrencySymbol(data.item.total_discount_amount)"
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="18"
                    />
                </Tippy>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Info } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { exportRecords, displayAmountWithCurrencySymbol } from '@commonServices/helper';

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
            sortable: true
        }, {
            key: 'usage',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ]
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-complimentary-item-reasons/',
        'complimentary-item-reasons.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-complimentary-item-reasons/',
        'complimentary-item-reasons.xlsx',
        params,
        props.exportPermission
    );
};
</script>
