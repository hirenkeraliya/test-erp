<template>
    <PageTitle title="Serial Product Report" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Serial Product Report
        </h2>
    </div>

    <div
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl"
    >
        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.serial_number_id"
                    :records="serialNumbers"
                    :required="true"
                    input-label="Serial Number"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateSerialNumber"
                />
            </div>
        </div>
    </div>
    <div>
        <JTable
            v-model:columns="state.columns"
            :fetch-url="route('admin.product_serial_number_report.fetch')"
            :refresh-table-data="state.refreshTableData"
            :additional-query-params="state.parameters"
            :allow-column-customization="true"
            local-storage-key="admin-product-serial-number-reports-columns"
        />
    </div>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';

defineProps({
    serialNumbers: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    parameters: {
        serial_number_id: null,
    },

    columns: [
        {
            key: 'product',
            isDisplay: true,
        },
        {
            key: 'serial_number',
            isDisplay: true,
        },
        {
            key: 'status',
            isDisplay: true,
        },
        {
            key: 'location_details',
            isDisplay: true,
        },
        {
            key: 'stock',
            isDisplay: true,
        },

    ],
    refreshTableData: Math.random(),
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateSerialNumber = (serialNumber) => {
    state.parameters.serial_number_id = serialNumber;
    refreshTable();
};
</script>
