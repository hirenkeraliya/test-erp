<template>
    <PageTitle title="Purchase Order Invoice" />
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Edit Invoices
        </h2>

        <div
            class="w-full sm:w-auto flex mt-4 sm:mt-0"
        >
            <Link :href="route(url)">
                <SecondaryButton
                    text="Back to List of Purchase Invoice"
                    class="shadow-md mx-2"
                />
            </Link>
        </div>
    </div>

    <JSimpleTable
        :columns="state.columns"
        :records="purchaseOrderFulfillments"
        :allow-search="true"
    >
        <template #action="record">
            <p
                v-if="record.item.purchase_order_invoice_id === null"
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <OutlinePrimaryButton
                    text="Add"
                    class="text-sm shadow-md"
                    @click="addInvoiceId(record.item.id)"
                />
            </p>
            <p
                v-else
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <OutlinePrimaryButton
                    text="Remove"
                    class="text-sm shadow-md"
                    @click="removeInvoiceId(record.item.id)"
                />
            </p>
        </template>
    </JSimpleTable>
</template>

<script setup>
import { reactive } from 'vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { route } from 'ziggy';
import { router } from '@inertiajs/vue3';
import { confirmDialogBoxWithCenterText } from '@commonServices/notifier';

const props = defineProps({
    purchaseOrderFulfillments: {
        type: Array,
        required: true,
    },
    purchaseOrderInvoiceId: {
        type: Number,
        required: true,
    },
    url: {
        type: String,
        required: true,
    },
    updateInvoiceIdUrl: {
        type: String,
        required: true,
    },
    removeUpdateInvoiceIdUrl: {
        type: String,
        required: true,
    }
});

const state = reactive({
    columns: [
        {
            key: 'happened_at',
        },
        {
            key: 'delivery_order_number',
        },
        {
            key: 'action',
        },
    ],
});

const addInvoiceId = (purchaseOrderFulfillmentId) => {
    const message = 'Are you sure you want to Add Invoice?';
    confirmDialogBoxWithCenterText(message, () => {
        router.post(route(props.updateInvoiceIdUrl, [purchaseOrderFulfillmentId, props.purchaseOrderInvoiceId]));
    });
};

const removeInvoiceId = (purchaseOrderFulfillmentId) => {
    const message = 'Are you sure you want to Remove Invoice?';
    confirmDialogBoxWithCenterText(message, () => {
        router.post(route(props.removeUpdateInvoiceIdUrl, [purchaseOrderFulfillmentId, props.purchaseOrderInvoiceId]));
    });
};

</script>
