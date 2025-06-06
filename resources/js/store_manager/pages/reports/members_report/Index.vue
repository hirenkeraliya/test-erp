<template>
    <PageTitle title="Members Report" />

    <div class="flex flex-col items-center mt-6 intro-y sm:flex-row">
        <h2 class="mr-auto text-lg font-medium">
            Members Report
        </h2>
    </div>

    <div
        v-if="state.displayMembersReportFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
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
                class="w-24 h-10 btn-sm"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('store_manager.members_report.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-members-reports-columns"
    >
        <template #members_count="record">
            <Tippy
                content="Members"
            >
                <JBadge
                    :label="record.item.members_count"
                    type="primary"
                    @click="showMemberDetailsModal(record.item)"
                />
            </Tippy>
        </template>
        <template #extra-header-data="record">
            <JBadge
                :label="'Members: ' + record.data.total_members"
            />

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayMembersReportFilter = !state.displayMembersReportFilter"
                />
            </p>
        </template>
    </JTable>

    <MemberDetails
        v-if="state.displayMemberDetailsModal"
        :modal-show="state.displayMemberDetailsModal"
        :members="state.memberData"
        :columns-for-member-details="state.modelColumns"
        @close-modal="closeModal"
    />
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { exportRecords, currentDate, printReport } from '@commonServices/helper';
import { reactive } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import axios from 'axios';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JBadge from '@commonComponents/JBadge.vue';
import MemberDetails from '@commonComponents/MemberDetails.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    locations: {
        type: Array,
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
            key: 'date',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'members_count',
            label: 'Registered Members',
            isDisplay: true,
            bodyClass: 'text-center',
            sortable: true,
        },
    ],

    modelColumns: [
        {
            key: 'title',
            sortable: true,
        },
        {
            key: 'first_name',
            sortable: true,
        },
        {
            key: 'email',
            sortable: true,
        },
        {
            key: 'email',
            sortable: true,
        },
        {
            key: 'mobile_number',
            sortable: true,
        },
        {
            key: 'card_number',
            sortable: true,
        }
    ],
    refreshTableData: Math.random(),
    selectedMember: null,
    displayMemberDetailsModal: false,
    displayMembersReportFilter: false,
    memberData: {},

    parameters: {
        location_ids: null,
        date_range: [
            currentDate(),
            currentDate()
        ],
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.location_ids = null;
    state.parameters.date_range = [
        currentDate(),
        currentDate()
    ];
    state.selectedMember = null;
    state.selectedLocations = null;
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-members-report/',
        'members_report.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-members-report/',
        'members_report.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('store_manager.members_report.print_members_report', params), props.exportPermission);
};

const showMemberDetailsModal = (member) => {
    state.displayMemberDetailsModal = true;
    state.parameters.select_date = member.date;
    axios.get(route('store_manager.members_report.fetch_member_details', state.parameters)).then((response) => {
        state.memberData = response.data.data;
    });
};

const closeModal = () => {
    state.memberData = {};
    state.displayMemberDetailsModal = false;
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
