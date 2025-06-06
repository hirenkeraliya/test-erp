<template>
    <PageTitle title="Online Sales Charges" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Online Sales Charges
        </h2>

        <div
            v-if="saleChannel > 0"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync Data"
                class="btn btn-outline-primary"
                @click="syncData()"
            >
                <RefreshCw class="text-primary w-5" />
            </Tippy>
        </div>

        <div class="w-full sm:w-auto flex mt-1 sm:mt-0">
            <Link :href="route('admin.online_sales_charges.create')">
                <PrimaryButton
                    text="Add New Online Sales Charge"
                    class="shadow-md"
                />
            </Link>
        </div>
    </div>
    <JTable
        :fetch-url="route('admin.online_sales_charges.fetch')"
        :columns="state.columns"
        :additional-query-params="state.parameters"
        search-title="Search by name"
    >
        <template #status="data">
            <div class="flex justify-center items-center">
                <JSwitch
                    input-class="ml-0 mt-0"
                    :is-checked="data.item.status"
                    class="mt-[0px]"
                    @update:is-checked="setStatus(data.item.id)"
                />
            </div>
        </template>

        <template #action="data">
            <div class="flex justify-center items-center">
                <Link
                    class="flex items-center mr-3"
                    :href="route('admin.online_sales_charges.edit', data.item.id)"
                >
                    <CheckSquare class="w-4 h-4 mr-2" />
                    Edit
                </Link>
                <button
                    class="flex items-center mr-3"
                    @click="deleteRecord(data.item.id)"
                >
                    <Archive class="w-4 h-4 mr-1" />
                    Delete
                </button>
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { reactive } from 'vue';
import { CheckSquare, Archive, RefreshCw } from 'lucide-vue-next';
import { route } from 'ziggy';
import { confirmDialogBox, showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';
import JSwitch from '@commonComponents/JSwitch.vue';
import axios from 'axios';

defineProps({
    saleChannel: {
        type: Number,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            sortable: true,
        }, {
            key: 'zone',
        }, {
            key: 'shipping_charges_type',
        }, {
            key: 'status',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }, {
            key: 'action',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
});

const deleteRecord = (onlineSalesChargeId) => {
    const message = 'Are you sure want to delete?';
    confirmDialogBox(message, () => {
        router.post(route('admin.online_sales_charges.delete', onlineSalesChargeId), {}, {
            onSuccess: () => router.get(route('admin.online_sales_charges.index'))
        });
    });
};

const setStatus = (onlineSalesChargeId) => {
    axios.post(route('admin.online_sales_charges.toggleStatus'), { onlineSalesChargeId }).then(() => {
        showSuccessNotification('Status updated successfully.');
    }).catch((error) => {
        if (error.message) {
            showErrorNotification(error.message);
        }
    });
};

const syncData = () => {
    axios.get(route('admin.online_sales_charges.sync_data'))
        .then(() => {
            showSuccessNotification('Successfully Synchronized');
        });
};
</script>
