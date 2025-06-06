<template>
    <PageTitle title="Gift Card" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Gift Card
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.gift_cards.upload_gift_card_view')">
                <PrimaryButton
                    text="Upload Gift Card"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayGiftCardsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.expiry_date"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Expiry Date"
                    @update:input-value="updateDate($event)"
                />
            </div>

            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.created_date"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Created At"
                    @update:input-value="updateCreatedDate($event)"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="giftCardStatuses"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    placeholder="Select Status"
                    @update:selected-record="updateSelectedStatus($event)"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.type"
                    :records="giftCardTypes"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Gift Card Type"
                    placeholder="Select Type"
                    @update:selected-record="updateSelectedTypes($event)"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.gift_cards.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by number, amount or available"
    >
        <template #total_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.total_amount) }}
        </template>

        <template #available_amount="record">
            {{ displayAmountWithCurrencySymbol(record.item.available_amount) }}
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayGiftCardsFilter = !state.displayGiftCardsFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';

const props = defineProps({
    giftCardStatuses: {
        type: Array,
        required: true,
    },
    giftCardTypes: {
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
            key: 'type_id',
            label: 'Type',
        }, {
            key: 'number',
            sortable: true
        }, {
            key: 'expiry_date',
            sortable: true
        }, {
            key: 'total_amount',
            label: 'Amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'available_amount',
            label: 'Available',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'created_at',
        }
    ],
    parameters: {
        status: '',
        type: '',
        expiry_date: null,
        created_date: null,
    },
    refreshTableData: Math.random(),
    displayGiftCardsFilter: false,
});

const updateSelectedStatus = (statuses) => {
    state.parameters.status = statuses;
    refreshTable();
};

const updateSelectedTypes = (type) => {
    state.parameters.type = type;
    refreshTable();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateDate = (expiryDate) => {
    state.parameters.expiry_date = expiryDate;
    refreshTable();
};

const updateCreatedDate = (createdDate) => {
    state.parameters.created_date = createdDate;
    refreshTable();
};

const clearAll = () => {
    state.parameters.expiry_date = null;
    state.parameters.created_date = null;
    state.parameters.status = '';
    state.parameters.type = '';
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-gift-cards/',
        'gift_cards.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-gift-cards/',
        'gift_cards.xlsx',
        params,
        props.exportPermission
    );
};

</script>
