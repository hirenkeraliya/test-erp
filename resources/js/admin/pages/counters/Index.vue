<template>
    <PageTitle title="Counters" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Counters
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.counters.create')">
                <PrimaryButton
                    text="Add New Counter"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>
    <div
        v-if="state.displayCounterFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.locations"
                    :records="locations"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select location"
                    @update:selected-records="updateLocations"
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
        :fetch-url="route('admin.counters.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name or store"
    >
        <template #name="record">
            <template v-if="record.item.is_locked">
                {{ record.item.name }}
                <Tippy
                    tag="label"
                    content="Locked counters cannot be opened by cashiers."
                >
                    <Lock class="w-4 h-4 inline-block text-red-950" />
                </Tippy>
            </template>
        </template>

        <template #location="record">
            {{ record.item.location.name }}
        </template>

        <template #app_version="record">
            <Tippy
                :content="`Current POS App Version <br> Last Updated At: `+ record.item.last_updated_at"
            >
                <JBadge
                    v-if="record.item.app_version"
                    :label="record.item.app_version"
                />
            </Tippy>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.counters.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>

        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <div class="grid grid-cols-8 gap-4">
                    <div
                        v-for="(appVersionCount, index) in record.data.appVersionCounts"
                        :key="index"
                    >
                        <JBadge
                            :label="'V ' + appVersionCount.app_version + ': ' + appVersionCount.count"
                        />
                    </div>
                </div>
            </div>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayCounterFilter = !state.displayCounterFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Lock } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JBadge from '@commonComponents/JBadge.vue';

const props = defineProps({
    locations: {
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
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            sortable: true
        }, {
            key: 'location',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'app_version',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
    parameters: {
        locations: [],
        location_ids: [],
    },
    displayCounterFilter: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateLocations = (locations) => {
    state.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });
    state.parameters.location_ids = locationIds;
    refreshTable();
};

const clearAll = () => {
    state.locations = null;
    state.parameters.location_ids = [];
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-counters/',
        'counters.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-counters/',
        'counters.xlsx',
        params,
        props.exportPermission
    );
};
</script>
