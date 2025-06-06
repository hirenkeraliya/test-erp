<template>
    <PageTitle title="Companies" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Companies
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('super_admin.companies.create')">
                <PrimaryButton
                    type="button"
                    text="Add New Company"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <div
        v-if="state.displayCompanyFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="statuses"
                    input-label="Status"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select Status"
                    @update:selected-record="updateFilterStatus"
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
        :fetch-url="route('super_admin.companies.fetch_companies')"
        :columns="state.columns"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
    >
        <template #email="data">
            {{ data.item.email }}
            <Tippy
                v-if="!data.item.is_email_verified && data.item.email"
                :content="'Updating your email will require re-verification.'"
            >
                <TriangleAlert
                    class="text-red-400 ml-2"
                    :size="15"
                />
            </Tippy>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    v-if="!data.item.is_email_verified && data.item.email"
                    class="flex items-center"
                    :href="route('super_admin.companies.resend_verification_email', data.item.id)"
                >
                    <Tippy
                        :content="'Resend mail'"
                    >
                        <Mail class="w-4 h-5 mr-2" />
                    </Tippy>
                </Link>
                <Dropdown
                    v-if="!data.item.deleted_at || data.item.is_restore"
                    v-slot="{ dismiss }"
                    class="flex items-center mr-3"
                >
                    <DropdownToggle
                        tag="a"
                        class="w-5 h-5 block"
                        href="javascript:;"
                    >
                        <MoreHorizontal class="w-5 h-5 text-slate-500" />
                    </DropdownToggle>

                    <DropdownMenu
                        class="w-60"
                    >
                        <DropdownContent>
                            <template v-if="!data.item.deleted_at">
                                <DropdownItem
                                    class="flex items-center mr-3"
                                    @click="currencyUpdate(data.item.id, dismiss)"
                                >
                                    <CheckSquare class="w-4 h-4 mr-2" />
                                    Currency Rates Update
                                </DropdownItem>

                                <DropdownItem
                                    class="flex items-center mr-3"
                                    @click="edit(data.item.id, dismiss)"
                                >
                                    <CheckSquare class="w-4 h-4 mr-2" />
                                    Edit
                                </DropdownItem>

                                <DropdownItem
                                    class="flex items-center mr-3"
                                    @click="archive(data.item, dismiss)"
                                >
                                    <Archive class="w-4 h-4 mr-1" />
                                    Archive
                                </DropdownItem>
                            </template>
                            <DropdownItem
                                v-if="data.item.is_restore"
                                class="flex items-center mr-3"
                                @click="restore(data.item, $event)"
                            >
                                <Archive class="w-4 h-4 mr-1" />
                                Restore
                            </DropdownItem>
                        </DropdownContent>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </template>

        <template #light_logo="data">
            <img
                :src="data.item.light_logo"
                :alt="data.item.name"
                width="70"
                class="bg-gray-300"
            >
        </template>
        <template #dark_logo="data">
            <img
                :src="data.item.dark_logo"
                :alt="data.item.name"
                width="70"
                class="bg-gray-300"
            >
        </template>
        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayCompanyFilter = !state.displayCompanyFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { confirmDialogBox } from '@commonServices/notifier';
import { reactive } from 'vue';
import { CheckSquare, Mail, MoreHorizontal, TriangleAlert, Archive } from 'lucide-vue-next';
import { route } from 'ziggy';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import { CompanyHelpText } from '@commonStores/documentation';
import { router } from '@inertiajs/vue3';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';

const props = defineProps({
    statuses: {
        type: Array,
        required: true,
    },
    allStatuses: {
        type: Object,
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
            key: 'code',
            sortable: true
        }, {
            key: 'email',
            sortable: true
        }, {
            key: 'light_logo',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'dark_logo',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        },
    ],
    refreshTableData: Math.random(),
    displayCompanyFilter: false,
    parameters: {
        status: props.allStatuses.active,
    },
});

const edit = (companyId, dismiss) => {
    router.get(route('super_admin.companies.edit_company', companyId));
    dismiss();
};

const currencyUpdate = (companyId, dismiss) => {
    router.get(route('super_admin.companies.currency_rate_update', companyId));
    dismiss();
};

const archive = (company) => {
    const message = 'Archived company is not displayed/considered in search, tables etc. Are you sure you want to archive the company ' + company.name + '?';

    confirmDialogBox(message, () => {
        router.post(route('super_admin.companies.archive_company', company.id), {}, {
            onSuccess: () => router.get(route('super_admin.companies.index'))
        });
    });
};

const restore = (company) => {
    const message = 'Are you sure you want to restore the company named ' + company.name + '?';

    confirmDialogBox(message, () => {
        router.put(route('super_admin.companies.restore_company', company.id), {}, {
            onSuccess: () => router.get(route('super_admin.companies.index'))
        });
    });
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.status = props.allStatuses.active;
    refreshTable();
};

const updateFilterStatus = (status) => {
    state.parameters.status = status;
    refreshTable();
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(CompanyHelpText());
</script>
