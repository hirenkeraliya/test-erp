<template>
    <PageTitle title="Promoters" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Promoters
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.promoters.create')">
                <PrimaryButton
                    text="Add New Promoter"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayPromotersFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.locations"
                    :records="locations"
                    placeholder="Please select location"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2 "
                    @update:selected-records="updateLocations"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.promoterGroups"
                    :records="promoterGroups"
                    placeholder="Please select Promoter Groups"
                    input-label="Promoter Groups"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updatePromoterGroups"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="promoterStatuses"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    @update:selected-record="updateSelectedStatus($event)"
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
        :fetch-url="route('admin.promoters.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name, email, or locations"
    >
        <template #staff_id="record">
            {{ record.item.employee.staff_id }}
        </template>
        <template #name="record">
            {{ record.item.employee.first_name }} {{ record.item.employee.last_name }}
            <Tippy
                content="An inactive employee cannot be assigned to the sale items as a promoter."
            >
                <JBadge
                    v-if="! record.item.employee.status"
                    label="Inactive User"
                    type="danger"
                />
            </Tippy>
        </template>

        <template #promoter_group="record">
            <div>
                {{ record.item.promoter_group ? record.item.promoter_group.name : 'N/A' }}
            </div>
        </template>

        <template #locations="record">
            {{ prepareImplodedNames(record.item.locations) }}
        </template>

        <template #extra-header-data>
            <LoaderSvg
                v-if="isPromoterCommissionRegenerationRunning"
            />

            <PrimaryButton
                :text="regenerateCommissionButtonText"
                class="shadow-md mr-2 ml-0 mb-2 sm:mb-0 sm:ml-2 float-left sm:float-none"
                :disabled="isPromoterCommissionRegenerationRunning"
                @click="state.displayRegeneratePromoterCommissionModal = true"
            />

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayPromotersFilter = !state.displayPromotersFilter"
                />
            </p>
        </template>

        <template #email="record">
            {{ record.item.employee.email }}
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.promoters.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>

                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.promoters.change_password', data.item.id)"
                >
                    <Unlock class="w-4 h-4 mr-1" />
                    Change Password
                </Link>
            </div>
        </template>
    </JTable>

    <RegeneratePromoterCommission
        v-if="state.displayRegeneratePromoterCommissionModal"
        :modal-show="state.displayRegeneratePromoterCommissionModal"
        @close-modal="closeRegeneratePromoterCommissionModal"
    />
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
import RegeneratePromoterCommission from '@adminPages/promoters/RegeneratePromoterCommission.vue';
import LoaderSvg from '@svg/LoaderSvg.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    regenerateCommissionButtonText: {
        type: String,
        required: true,
    },
    isPromoterCommissionRegenerationRunning: {
        type: Boolean,
        default: false,
    },
    promoterGroups: {
        type: Array,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    promoterStatuses: {
        type: Array,
        required: true,
    },
    statusActive: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'staff_id',
        }, {
            key: 'name',
        }, {
            key: 'email',
        }, {
            key: 'locations',
        }, {
            key: 'promoter_group',
            header: 'Promoter Group',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }
    ],
    refreshTableData: Math.random(),
    locations: [],
    promoterGroups: [],
    parameters: {
        location_ids: [],
        group_ids: [],
        status: props.statusActive,
    },
    displayPromotersFilter: false,
    displayRegeneratePromoterCommissionModal: false,
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
    state.promoterGroups = [];
    state.parameters.location_ids = [];
    state.parameters.group_ids = [];
    state.parameters.status = props.statusActive;
    refreshTable();
};

const updatePromoterGroups = (promoterGroups) => {
    state.promoterGroups = promoterGroups;
    const groupIds = promoterGroups.map((promoterGroup) => {
        return promoterGroup.id;
    });
    state.parameters.group_ids = groupIds;
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-promoters/',
        'promoters.csv',
        params,
        props.exportPermission
    );
};
const exportExcelRecords = (params) => {
    return exportRecords(
        'export-promoters/',
        'promoters.xlsx',
        params,
        props.exportPermission
    );
};

const closeRegeneratePromoterCommissionModal = () => {
    state.displayRegeneratePromoterCommissionModal = false;
};

const updateSelectedStatus = (status) => {
    if (status === null) {
        state.parameters.status = props.statusAll;
    }

    if (status !== null) {
        state.parameters.status = status;
    }
    refreshTable();
};
</script>
