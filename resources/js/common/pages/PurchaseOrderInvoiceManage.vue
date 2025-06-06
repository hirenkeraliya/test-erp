<template>
    <PageTitle title="Add Invoice" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Purchase Order Invoices
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>
                            Add Invoice
                        </span>
                    </h2>
                </div>
                <form
                    @submit.prevent="savePurchaseOrderInvoice();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <div class="mt-3">
                                    <FormSelectBox
                                        :selected-record="purchaseOrderInvoiceForm.purchase_order_id"
                                        :records="purchaseOrders"
                                        :required="true"
                                        validation-field-name="order_number"
                                        record-key-name="order_number"
                                        placeholder="Please select Order Number"
                                        input-label="Order Number"
                                        @update:selected-record="fetchFulfillmentRecord"
                                    />
                                </div>
                            </div>
                        </div>
                        <JSimpleTable
                            v-if="state.displayFulfillmentDetails"
                            :columns="state.columns"
                            :records="state.purchaseOrderFulfillments"
                            :allow-pagination-and-sorting="false"
                            :allow-search="true"
                        >
                            <template #action="record">
                                <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                                    <OutlinePrimaryButton
                                        :text="purchaseOrderInvoiceForm.fulfillment_ids.includes(record.item.id) ? 'Remove' : 'Add'"
                                        class="text-sm shadow-md"
                                        @click="addRemoveFulfillment(record.item.id)"
                                    />
                                </p>
                            </template>
                        </JSimpleTable>

                        <div class="flex flex-row ml-auto">
                            <Link :href="route(url)">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mt-5"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                text="Submit"
                                class="w-24 mt-5 ml-1"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { route } from 'ziggy';
import axios from 'axios';
import { reactive } from 'vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const props = defineProps({
    purchaseOrders: {
        type: Array,
        required: true,
    },
    url: {
        type: String,
        required: true,
    },
    storeUrl: {
        type: String,
        required: true,
    },
    detailsUrl: {
        type: String,
        required: true,
    }
});

const state = reactive({
    purchaseOrderFulfillments: [],
    displayFulfillmentDetails: false,

    columns: [
        {
            key: 'happened_at',
            sortable: true
        },
        {
            key: 'delivery_order_number',
            sortable: true
        },
        {
            key: 'action'
        },
    ],
});

const purchaseOrderInvoiceForm = useForm({
    purchase_order_id: null,
    fulfillment_ids: [],
});

const savePurchaseOrderInvoice = () => {
    purchaseOrderInvoiceForm.post(route(props.storeUrl));
};

const fetchFulfillmentRecord = (purchaseOrderId) => {
    purchaseOrderInvoiceForm.purchase_order_id = purchaseOrderId;

    if (!purchaseOrderId) {
        return;
    }

    axios.get(route(props.detailsUrl, purchaseOrderId))
        .then((response) => {
            state.purchaseOrderFulfillments = response.data.purchaseOrderFulfillments;
            state.displayFulfillmentDetails = true;
        });
};

const addRemoveFulfillment = (purchaseOrderFulfillmentId) => {
    const index = purchaseOrderInvoiceForm.fulfillment_ids.indexOf(purchaseOrderFulfillmentId);

    const notFound = -1;
    if (index !== notFound) {
        purchaseOrderInvoiceForm.fulfillment_ids.splice(index, 1);
    } else {
        purchaseOrderInvoiceForm.fulfillment_ids.push(purchaseOrderFulfillmentId);
    }
};

</script>
