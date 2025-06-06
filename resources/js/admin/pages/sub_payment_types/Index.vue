<template>
    <PageTitle title="Sub Payment Types" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto sm:mr-2 md:mr-auto">
            Sub Payment Types
        </h2>

        <div class="w-full sm:w-auto block sm:flex mt-4 sm:mt-0">
            <Link
                :href="route('admin.payment_types.index')"
                class="flex items-center mr-2"
            >
                <SecondaryButton
                    text="Back to List of Payment types"
                    class="shadow-md w-full sm:w-auto mb-2 sm:mb-0"
                />
            </Link>

            <Link :href="route('admin.sub_payment_types.create', paymentTypeId)">
                <PrimaryButton
                    text="Add New Sub Payment Type"
                    class="shadow-md w-full sm:w-auto"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.sub_payment_types.fetch', paymentTypeId)"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
    >
        <template #image_name="data">
            <img
                :src="'/images/payment_types/' + data.item.image_name"
                class="img-fluid cursor-pointer mx-auto"
            >
        </template>

        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(paymentTypeId, data.item.id, $event)"
                />
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    :href="route('admin.sub_payment_types.edit', [paymentTypeId, data.item.id])"
                    class="flex items-center mr-3"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { CheckSquare } from 'lucide-vue-next';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import JSwitch from '@commonComponents/JSwitch.vue';
import { router } from '@inertiajs/vue3';
import { exportRecordsUsingRouter } from '@commonServices/helper';

const props = defineProps({
    paymentTypeId: {
        type: Number,
        default: null,
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
            sortable: true
        }, {
            key: 'name',
            sortable: true
        }, {
            key: 'image_name',
            label: 'image',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }, {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center'
        }
    ]
});

const setStatus = (paymentTypeId, subPaymentTypeId, status) => {
    router.post(route('admin.sub_payment_types.set_status', [paymentTypeId, subPaymentTypeId, status ? 1 : 0]));
};

const exportCsvRecords = (params) => {
    return exportRecordsUsingRouter(
        route('admin.sub_payment_types.export_sub_payment_types', [props.paymentTypeId, 'sub-payment-types.csv']),
        'sub-payment-types.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecordsUsingRouter(
        route('admin.sub_payment_types.export_sub_payment_types', [props.paymentTypeId, 'sub-payment-types.xlsx']),
        'sub-payment-types.xlsx',
        params,
        props.exportPermission
    );
};
</script>
