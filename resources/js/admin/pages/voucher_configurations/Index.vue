<template>
    <PageTitle title="Voucher Configuration" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Voucher Configuration
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <InfoAlert
                v-if="expiredBirthdayVoucher"
                color="danger"
                class="mr-3"
            >
                The birthday voucher configuration has expired. Please update
                <Link
                    class="mr-1 underline decoration-dotted"
                    :href="route('admin.vouchers_configuration.edit', expiredBirthdayVoucher.id)"
                >
                    here
                </Link>
            </InfoAlert>

            <Link :href="route('admin.vouchers_configuration.create')">
                <PrimaryButton
                    text="New Campaign "
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <InfoAlert
        color="primary"
        class="mb-3 mt-5"
    >
        1. Vouchers are not available for Staff Purchase. <br>
        2. Please
        <a
            href="/images/discount_applicable_flow.png"
            class="underline"
            target="_blank"
        >
            click here
        </a>
        to explore how discounts are applied stacked in Promotion.
    </InfoAlert>

    <div>
        <div
            v-if="state.displayVoucherConfigurationFilter"
            class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
                <div>
                    <FormSelectBox
                        :selected-record="state.parameters.restricted_by_type_id"
                        :records="restrictedByTypes"
                        input-label="Restricted By Type"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        placeholder="Please select Restricted By Type"
                        @update:selected-record="updateRestrictedByType"
                    />
                </div>
                <div>
                    <FormSelectBox
                        :selected-record="state.parameters.voucher_type_id"
                        :records="voucherTypes"
                        input-label="Voucher Type"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        placeholder="Please select Voucher Type"
                        @update:selected-record="updateVoucherType"
                    />
                </div>
                <div>
                    <FormSelectBox
                        :selected-record="state.parameters.discount_type_id"
                        :records="discountTypes"
                        input-label="Discount Type"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        placeholder="Please select Discount Type"
                        @update:selected-record="updateDiscountTypes"
                    />
                </div>
                <div>
                    <FormSelectBox
                        :selected-record="state.parameters.status"
                        :records="statuses"
                        input-label="Status"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        placeholder="Please select Status"
                        @update:selected-record="updateStatus"
                    />
                </div>

                <div>
                    <FormSelectBox
                        :selected-record="state.parameters.type"
                        :records="types"
                        input-label="Types"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        placeholder="Please select Types"
                        @update:selected-record="updateTypes"
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
    </div>

    <JTable
        :fetch-url="route('admin.vouchers_configuration.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by restricted by type, voucher type, discount type, or get value"
    >
        <template #status="data">
            <JSwitch
                input-class="ml-0 mt-0"
                :is-checked="data.item.status"
                class="mt-[0px]"
                @update:is-checked="setStatus(data.item.id, $event)"
            />
        </template>

        <template #restricted_by_type="data">
            {{ data.item.restricted_by_type }}
            <div
                v-if="data.item.mystery_gift_id != null"
                class="mt-2"
            >
                <span class="bg-orange-200 text-orange-800 text-md font-medium me-2 px-3 py-2 rounded-full">
                    System Generated
                </span>
            </div>
        </template>

        <template #action="data">
            <div
                v-if="data.item?.mystery_gift_id === null"
                class="flex justify-center items-center"
            >
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.vouchers_configuration.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
        <template #get_value="data">
            {{ data.item.get_value ? displayAmountWithCurrencySymbol(data.item.get_value): 'N/A' }}
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayVoucherConfigurationFilter = !state.displayVoucherConfigurationFilter"
                />
            </p>
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
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { router } from '@inertiajs/vue3';
import { CheckSquare, Info } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';

const props = defineProps({
    expiredBirthdayVoucher: {
        type: Object,
        default: null,
    },
    statuses: {
        type: Object,
        default: null,
    },
    restrictedByTypes: {
        type: Object,
        default: null,
    },
    voucherTypes: {
        type: Object,
        default: null,
    },
    discountTypes: {
        type: Object,
        default: null,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    allTypes: {
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
            key: 'restricted_by_type',
            label: 'Restricted By',
        }, {
            key: 'voucher_type',
        }, {
            key: 'discount_type',
        }, {
            key: 'get_value',
            sortable: true,
            label: 'Value',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'usage',
            label: 'Redeemed',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        }, {
            key: 'start_date',
            sortable: true
        }, {
            key: 'end_date',
            sortable: true
        }, {
            key: 'status',
            sortable: true,
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }, {
            key: 'action',
            headerClass: 'text-center',
            bodyClass: 'text-center',
        }
    ],

    parameters: {
        status: null,
        restricted_by_type_id: null,
        voucher_type_id: null,
        discount_type_id: null,
        type: props.allTypes.manual,
    },

    refreshTableData: Math.random(),
    displayVoucherConfigurationFilter: false,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.restricted_by_type_id = null;
    state.parameters.voucher_type_id = null;
    state.parameters.discount_type_id = null;
    state.parameters.status = null;
    state.parameters.type = props.allTypes.manual;
    refreshTable();
};

const setStatus = (voucherConfigurationId, status) => {
    router.post(route('admin.vouchers_configuration.set_status', [voucherConfigurationId, status ? 1 : 0]));
};

const updateStatus = (status) => {
    state.parameters.status = status;
    refreshTable();
};

const updateRestrictedByType = (restrictedByTypeId) => {
    state.parameters.restricted_by_type_id = restrictedByTypeId;
    refreshTable();
};

const updateVoucherType = (voucherTypeId) => {
    state.parameters.voucher_type_id = voucherTypeId;
    refreshTable();
};

const updateDiscountTypes = (discountTypeId) => {
    state.parameters.discount_type_id = discountTypeId;
    refreshTable();
};

const updateTypes = (type) => {
    state.parameters.type = parseInt(type);
    refreshTable();
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-voucher-configurations/',
        'voucher-configurations.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-voucher-configurations/',
        'voucher-configurations.xlsx',
        params,
        props.exportPermission
    );
};
</script>
