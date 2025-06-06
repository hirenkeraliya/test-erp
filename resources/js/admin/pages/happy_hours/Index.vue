<template>
    <div v-if="companyAllowHappyHourDiscount">
        <PageTitle title="Happy Hours" />

        <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
            <h2 class="text-lg font-medium mr-auto">
                Happy Hours
            </h2>

            <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
                <Link :href="route('admin.happy_hours.create')">
                    <PrimaryButton
                        text="Add New Happy Hour"
                        class="shadow-md"
                    />
                </Link>
            </div>
        </div>

        <JTable
            v-model:columns="state.columns"
            :fetch-url="route('admin.happy_hours.fetch')"
            :refresh-table-data="state.refreshTableData"
            :additional-query-params="state.parameters"
            :allow-csv-export="true"
            :allow-excel-export="true"
            :export-csv-records-callback="exportCsvRecords"
            :export-excel-records-callback="exportExcelRecords"
            local-storage-key="admin-happy-hours-columns"
            search-title="Search by name, new price"
        >
            <template #offline_id="record">
                <span v-if="record.item.offline_ids.length">
                    <span v-html="objectArrayToString(record.item.offline_ids)" />
                </span>
                <span v-else>
                    N/A
                </span>
            </template>

            <template #location="record">
                {{ record.item.location }}
            </template>

            <template #new_price="record">
                {{ displayAmountWithCurrencySymbol(record.item.new_price) }}
            </template>

            <template #authorizer_name="record">
                <span v-if="record.item.authorizer_names.length">
                    <span v-html="objectArrayToString(record.item.authorizer_names)" />
                </span>
                <span v-else>
                    N/A
                </span>
            </template>

            <template #happened_at="record">
                <span v-if="record.item.happened_at_dates.length">
                    <span v-html="objectArrayToString(record.item.happened_at_dates)" />
                </span>
                <span v-else>
                    N/A
                </span>
            </template>

            <template #action="record">
                <div class="flex justify-center items-center">
                    <Link
                        class="flex items-center mr-3"
                        :href="route('admin.happy_hours.edit', record.item.id)"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" />
                        Edit
                    </Link>
                </div>
            </template>
        </JTable>
    </div>
    <div
        v-else
        class="items-center mt-8 intro-y sm:flex-row"
    >
        <h2 class="mr-auto text-lg font-medium">
            Happy Hours
        </h2>

        <InfoAlert
            color="danger"
            class="mt-5 mb-3"
        >
            The company's guidelines do not permit the application of a Happy Hour discount. To address this issue or seek permission for any exceptions, please contact the super admin for further assistance.
        </InfoAlert>
    </div>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { CheckSquare } from 'lucide-vue-next';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    companyAllowHappyHourDiscount: {
        type: Boolean,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'offline_id',
            sortable: true,
        }, {
            key: 'location',
        },
        {
            key: 'product_type',
            label: 'Product Type'
        }, {
            key: 'name',
        }, {
            key: 'new_price',
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'start_date',
        }, {
            key: 'end_date',
        }, {
            key: 'authorizer_name',
            label: 'Authorizer'
        }, {
            key: 'happened_at',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
});

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-happy-hours/',
        'happy-hours.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-happy-hours/',
        'happy-hours.xlsx',
        params,
        props.exportPermission
    );
};

const objectArrayToString = (items, separator = ',<br>') => {
    if (!items) return;
    const toDisplay = [];
    for (const key in items) {
        toDisplay.push(items[key]);
    }

    return toDisplay.join(separator);
};
</script>
