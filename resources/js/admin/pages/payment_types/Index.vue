<template>
    <PageTitle title="Payment Types" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Payment Types
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <Link :href="route('admin.payment_types.create')">
                <PrimaryButton
                    text="Add New Payment Type"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>

    <JTable
        :fetch-url="route('admin.payment_types.fetch')"
        :columns="state.columns"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        search-title="Search by name"
    >
        <template #image_name="data">
            <img
                :src="'/images/payment_types/' + data.item.image_name"
                class="img-fluid cursor-pointer w-10 mx-auto"
            >
        </template>

        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    :disabled="staticPaymentTypes.includes(data.item.id) ? true : false"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(data.item.id, $event)"
                />
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <span
                    v-if="! staticPaymentTypes.includes(data.item.id)"
                    class="inline-flex justify-center"
                >
                    <Link
                        :href="route('admin.sub_payment_types.index', data.item.id)"
                        class="flex items-center mr-3"
                    >
                        <ListPlus class="w-4 h-4 mr-1" />
                        Sub Payment Types
                    </Link>

                    <Link
                        :href="route('admin.payment_types.edit', data.item.id)"
                        class="flex items-center mr-3"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" />
                        Edit
                    </Link>
                </span>

                <Tippy
                    v-else
                    class="flex justify-center items-center"
                    content="This payment type is the system default. Cannot be modified."
                >
                    <Info class="text-cyan-400" />
                </Tippy>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { CheckSquare, ListPlus, Info } from 'lucide-vue-next';
import JSwitch from '@commonComponents/JSwitch.vue';
import { router } from '@inertiajs/vue3';
import { exportRecords } from '@commonServices/helper';

const props = defineProps({
    staticPaymentTypes: {
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

const setStatus = (paymentTypeId, status) => {
    router.post(route('admin.payment_types.set_status', [paymentTypeId, status ? 1 : 0]));
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'payment-types-export/',
        'payment-types.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'payment-types-export/',
        'payment-types.xlsx',
        params,
        props.exportPermission
    );
};
</script>
