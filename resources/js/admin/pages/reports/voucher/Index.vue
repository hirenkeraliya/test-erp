<template>
    <PageTitle title="Vouchers" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Vouchers
        </h2>
    </div>

    <div
        v-if="state.displayVoucherReportFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.locations"
                    :records="locations"
                    placeholder="Please select location"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateLocations"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedMember"
                    :search-records="searchMembers"
                    placeholder="Member Name to search..."
                    input-label="Member"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateMember"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status_type"
                    :records="voucherStatusTypes"
                    placeholder="Please select Status"
                    input-label="Status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStatusType"
                />
            </div>
            <div>
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Date Range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
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
        v-model:columns="state.columns"
        :fetch-url="route('admin.vouchers.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-voucher-reports-columns"
        search-title="Search by member, number, minimum spend, percentage, or flat"
    >
        <template #minimum_spend_amount="data">
            {{ displayAmountWithCurrencySymbol(data.item.minimum_spend_amount) }}
        </template>

        <template #discount="data">
            <div v-if="data.item.discount_type === discountTypePercentage">
                {{ displayAmountWithPercentageSymbol(data.item.discount) }}
            </div>
            <div v-else>
                {{ displayAmountWithCurrencySymbol(data.item.discount) }}
            </div>
        </template>

        <template #status="data">
            <JBadge
                :label="data.item.status"
                :type="getStatusBadgeColor(data.item.status)"
            />
        </template>

        <template #info="data">
            <List @click="showVoucherDetailsModal(data.item)" />
        </template>

        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    :label="'Active: ' + truncateDecimal(record.data.count_of_active_vouchers)"
                />
            </div>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayVoucherReportFilter = !state.displayVoucherReportFilter"
                />
            </p>
        </template>
    </JTable>

    <VoucherDetails
        v-if="state.displayVoucherDetailsModal"
        :modal-show="state.displayVoucherDetailsModal"
        :voucher-details="state.voucherTransactionDetails"
        :columns-for-voucher-transaction-details="state.columnsForVoucherTransactionDetails"
        :voucher-details-pdf-print="route('admin.vouchers.print_voucher_transaction_details', state.voucherId)"
        :export-permission="exportPermission"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { displayAmountWithCurrencySymbol, displayAmountWithPercentageSymbol, exportRecords, truncateDecimal } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';
import { List } from 'lucide-vue-next';
import VoucherDetails from '@commonPages/voucherDetails.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JBadge from '@commonComponents/JBadge.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },

    discountTypePercentage: {
        type: Number,
        required: true,
    },

    voucherStatusTypes: {
        type: Array,
        required: true,
    },

    voucherStatusStaticArray: {
        type: Object,
        required: true,
    },

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
            key: 'number',
            isDisplay: true,
        }, {
            key: 'created_at',
            label: 'Date',
            isDisplay: true,
        }, {
            key: 'member',
            isDisplay: true,
        }, {
            key: 'location',
            isDisplay: true,
        }, {
            key: 'voucher_type',
            isDisplay: true,
        }, {
            key: 'minimum_spend_amount',
            isDisplay: true,
            label: 'Minimum Spend',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'discount',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'status',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'expiry_date',
            isDisplay: true,
        }, {
            key: 'info',
            isDisplay: true,
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],

    columnsForVoucherTransactionDetails: [
        {
            key: 'date',
            sortable: true
        }, {
            key: 'offline_sale_id',
            label: 'Receipt Number',
            sortable: true
        }, {
            key: 'location',
            label: 'Location (Code)',
            sortable: true
        }, {
            key: 'action_type',
            sortable: true
        }
    ],
    refreshTableData: Math.random(),
    selectedMember: null,
    locations: null,
    voucherId: null,
    parameters: {
        date_range: null,
        location_ids: null,
        status_type: null
    },
    voucherTransactionDetails: [],
    displayVoucherDetailsModal: false,
    displayVoucherReportFilter: false,
});

const updateStatusType = (statusType) => {
    state.parameters.status_type = null;
    if (statusType !== null) {
        state.parameters.status_type = parseInt(statusType);
    }
    refreshTable();
};

const getStatusBadgeColor = (statusType) => {
    if (statusType === props.voucherStatusStaticArray.used) {
        return 'success';
    }

    if (statusType === props.voucherStatusStaticArray.expired) {
        return 'danger';
    }

    return 'primary';
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = null;
    state.parameters.location_ids = null;
    state.parameters.member_id = null;
    state.parameters.status_type = null;
    state.selectedMember = null;
    state.locations = null;
    state.voucherId = null;
    refreshTable();
};

const updateLocations = (locations) => {
    state.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });
    state.parameters.location_ids = locationIds;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateMember = (selectMember) => {
    state.selectedMember = selectMember;
    state.parameters.member_id = null;
    if (selectMember !== null) {
        state.parameters.member_id = selectMember.id;
    }
    refreshTable();
};

const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };
    axios.get(route('admin.members.get_filtered_members', filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-vouchers/',
        'vouchers.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-vouchers/',
        'vouchers.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const showVoucherDetailsModal = (voucher) => {
    axios.get(route('admin.vouchers.fetch_voucher_transaction_details', voucher.id))
        .then((response) => {
            state.voucherTransactionDetails = response.data.voucherTransactionDetails;
        });
    state.displayVoucherDetailsModal = true;
    state.voucherId = voucher.id;
};

const closeModal = () => {
    state.voucherTransactionDetails = [];
    state.displayVoucherDetailsModal = false;
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
