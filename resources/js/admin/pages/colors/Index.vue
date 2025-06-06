<template>
    <PageTitle title="Colors" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Colors
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-1 sm:mt-0">
            <Link :href="route('admin.colors.create')">
                <PrimaryButton
                    text="Add New Color"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayColorGroupFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.colorGroups"
                    :records="colorGroups"
                    input-label="Color Groups"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Color Groups"
                    @update:selected-records="updateColorGroups"
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
        :fetch-url="route('admin.colors.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name or code"
    >
        <template #color_group="data">
            <div>
                {{ data.item.color_group ? data.item.color_group.name : 'N/A' }}
            </div>
        </template>

        <template #color_code="data">
            <div v-if="data.item.color_code">
                <span
                    :style="'background-color:' + data.item.color_code"
                    class="pt-1 pb-1 pr-10 mr-1"
                >
                &nbsp;
                </span>
                {{ data.item.color_code }}
            </div>
            <div v-else>
                N/A
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.colors.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayColorGroupFilter = !state.displayColorGroupFilter"
                />
            </p>
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
import axios from 'axios';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { RefreshCw } from 'lucide-vue-next';
import { showSuccessNotification } from '@commonServices/notifier';

const props = defineProps({
    colorGroups: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    saleChannels: {
        type: Array,
        required: true,
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true,
        }, {
            key: 'code',
            headerClass: 'text-left',
            bodyClass: 'text-left',
            sortable: true,
        }, {
            key: 'color_group',
            header: 'Color Group',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'color_code',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
    colorGroups: [],
    displayColorGroupFilter: false,
    disableRefreshButton: props.hasPendingSyncTransaction,

    parameters: {
        group_ids: [],
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.colorGroups = null;
    state.parameters.group_ids = [];
    refreshTable();
};

const updateColorGroups = (colorGroups) => {
    state.colorGroups = colorGroups;

    const groupIds = colorGroups.map((colorGroup) => {
        return colorGroup.id;
    });
    state.parameters.group_ids = groupIds;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-colors/',
        'colors.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-colors/',
        'colors.xlsx',
        params,
        props.exportPermission
    );
};

const syncData = (id, dismiss) => {
    axios.get(route('admin.colors.sync_data', id)).then(() => {
        showSuccessNotification('Successfully Synchronized');
        state.disableRefreshButton = true;
    });

    dismiss();
};
</script>
