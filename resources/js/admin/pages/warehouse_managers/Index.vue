<template>
    <PageTitle title="Warehouse managers" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Warehouse Managers
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.warehouse_managers.create')">
                <PrimaryButton
                    text="Add New Warehouse Manager"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayWarehouseManagerFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.parameters.locations"
                    :records="locations"
                    placeholder="Please select Location"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
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
        :fetch-url="route('admin.warehouse_managers.fetch')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name, locations"
    >
        <template #staff_id="record">
            {{ record.item.employee.staff_id }}
        </template>
        <template #name="record">
            {{ record.item.employee.first_name }} {{ record.item.employee.last_name }}
            <Tippy
                content="Inactive employees cannot log in as a warehouse manager."
            >
                <JBadge
                    v-if="! record.item.employee.status"
                    label="Inactive User"
                    type="danger"
                />
            </Tippy>
        </template>

        <template #email="record">
            {{ record.item.employee.email }}
        </template>

        <template #locations="record">
            {{ prepareImplodedNames(record.item.locations) }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.warehouse_managers.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.warehouse_managers.change_password', data.item.id)"
                >
                    <Unlock class="w-4 h-4 mr-1" />
                    Change Password
                </Link>
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayWarehouseManagerFilter = !state.displayWarehouseManagerFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare, Unlock } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JBadge from '@commonComponents/JBadge.vue';
import { prepareImplodedNames, exportRecords } from '@commonServices/helper';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

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
            key: 'staff_id',
        }, {
            key: 'name',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'email',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'locations',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
    parameters: {
        locations: [],
        location_ids: [],
    },
    displayWarehouseManagerFilter: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};
const updateLocations = (locations) => {
    state.parameters.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });
    state.parameters.location_ids = locationIds;
    refreshTable();
};
const clearAll = () => {
    state.parameters.locations = null;
    state.parameters.location_ids = [];
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-warehouse-managers/',
        'warehouseManagers.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-warehouse-managers/',
        'warehouseManagers.xlsx',
        params,
        props.exportPermission
    );
};
</script>
