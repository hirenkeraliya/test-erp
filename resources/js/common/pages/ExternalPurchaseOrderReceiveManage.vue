<template>
    <PageTitle title="External Purchase Order Partial Receive" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto" />

        <div class="w-full sm:w-auto block md:flex mt-4 sm:mt-0">
            <PrimaryButton
                text="Receive Full Quantity"
                type="button"
                class="w-15 sm mr-1 mb-2 md:mb-0"
                @click="setReceiveQuantitySameAsQuantities()"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span v-if="externalPurchaseOrderPartialReceive">Edit Partial Receive</span>
                        <span v-else>Add Partial Receive</span>
                    </h2>
                </div>

                <form @submit.prevent="savePartialReceive();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-4">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDateTimePicker
                                    v-model:input-value="externalPurchaseOrderPartialReceiveForm.received_date"
                                    input-label="Received Date"
                                    :required="true"
                                    validation-field-name="received_date"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextarea
                                    v-model:input-value="externalPurchaseOrderPartialReceiveForm.notes"
                                    input-name="notes"
                                    input-label="Notes"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-6 mt-5">
                        <div class="intro-y col-span-12 lg:col-span-12">
                            <div class="intro-y box">
                                <div class="p-5">
                                    <div class="overflow-unset overflow-x-auto">
                                        <table class="table mb-2">
                                            <thead>
                                                <tr>
                                                    <th class="whitespace-nowrap">
                                                        Product
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Quantity
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Received Quantity
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Remarks
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Batch
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr
                                                    v-for="(item, itemIndex) in externalPurchaseOrderPartialReceiveForm.receive_items"
                                                    :key="'receive-item-' + itemIndex"
                                                >
                                                    <td class="whitespace-nowrap">
                                                        <div>{{ item.product_name }} </div>
                                                        <div class="mt-1">
                                                            <span>
                                                                <b>Color:</b> {{ item.product_color }}
                                                                <b>Size:</b> {{ item.product_size }}
                                                            </span>
                                                        </div>
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        {{ item.quantity }}
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            :value="item.quantity_received"
                                                            @input="updateReceivedQuantity($event, itemIndex, item.quantity)"
                                                        >
                                                        <ValidationError
                                                            :validation-field-name="'receive_items.' + itemIndex + '.quantity_received'"
                                                        />
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        {{ item.remarks }}
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        {{ item.product_has_batch ? '' : 'No' }}

                                                        <PrimaryButton
                                                            v-if="item.product_has_batch"
                                                            type="button"
                                                            class="w-full ml-1"
                                                            text="Specify Batch Details*"
                                                            @click="openProductBatchDetailsModal(itemIndex, item.batch_details)"
                                                        />
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="flex flex-row ml-auto">
                                        <Link :href="route(getExternalPurchaseOrderReceiveIndexUrl, externalPurchaseOrder.id)">
                                            <SecondaryButton
                                                type="button"
                                                text="Cancel"
                                                class="w-24 mt-5"
                                            />
                                        </Link>

                                        <PrimaryButton
                                            type="submit"
                                            :text="externalPurchaseOrderPartialReceive ? 'Update' : 'Submit'"
                                            class="w-24 mt-5 ml-1"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <PartialReceiveBatchDetailsModal
        v-if="state.batchDetailsModalIndex !== null"
        :batch-details="externalPurchaseOrderPartialReceiveForm.receive_items[state.batchDetailsModalIndex].batch_details"
        :modal-show="state.displayProductBatchDetailsModal"
        message="The total of all the quantities you specify with the batch numbers must match the product quantity for the partial receive."
        @close-modal="closeBatchDetailsModal()"
        @update:batch-details="updateBatchDetails"
    />
</template>

<script setup>
import PartialReceiveBatchDetailsModal from '@commonComponents/PartialReceiveBatchDetailsModal.vue';
import { useForm } from '@inertiajs/vue3';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { confirmDialogBoxWithCenterText } from '@commonServices/notifier';
import ValidationError from '@commonComponents/ValidationError.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';

const props = defineProps({
    storeExternalPurchaseOrderReceiveUrl: {
        type: String,
        required: true,
    },
    getExternalPurchaseOrderReceiveIndexUrl: {
        type: String,
        required: true,
    },
    updateExternalPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    externalPurchaseOrder: {
        type: Object,
        default: () => { },
    },
    transferItems: {
        type: Object,
        default: () => {},
    },
    externalPurchaseOrderPartialReceive: {
        type: Object,
        default: () => { },
    },
    updateExternalPurchaseOrderReceiveUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    displayProductBatchDetailsModal: false,
    batchDetailsModalIndex: null,
});

const externalPurchaseOrderPartialReceiveForm = useForm({
    notes: null,
    received_date: null,
    receive_items: [
        {
            external_purchase_order_item_id: null,
            product_id: null,
            product_name: null,
            product_upc: null,
            product_color: null,
            product_size: null,
            quantity: null,
            received_quantity: null,
            batch_details: [],
            remarks: null,
            unit_of_measure_derivative_id: null,
        }
    ],
});

const savePartialReceive = () => {
    if (props.externalPurchaseOrderPartialReceive) {
        externalPurchaseOrderPartialReceiveForm.post(route(props.updateExternalPurchaseOrderReceiveUrl, props.externalPurchaseOrderPartialReceive.data.id));
        return;
    }

    externalPurchaseOrderPartialReceiveForm.post(route(props.storeExternalPurchaseOrderReceiveUrl, props.externalPurchaseOrder.id));
};

onMounted(() => {
    if (props.transferItems) {
        const filteredItems = props.transferItems.data.filter(item => item !== null && !(Array.isArray(item) && item.length === 0));
        Object.assign(externalPurchaseOrderPartialReceiveForm.receive_items, filteredItems);
    }

    if (props.externalPurchaseOrderPartialReceive) {
        Object.assign(externalPurchaseOrderPartialReceiveForm, props.externalPurchaseOrderPartialReceive.data);
    }
});

const setReceiveQuantitySameAsQuantities = () => {
    const message = 'Do you want to ship full quantity?';

    confirmDialogBoxWithCenterText(message, () => {
        setReceiveQuantity();
    });
};

const setReceiveQuantity = () => {
    for (const key in externalPurchaseOrderPartialReceiveForm.receive_items) {
        externalPurchaseOrderPartialReceiveForm.receive_items[key].quantity_received = externalPurchaseOrderPartialReceiveForm.receive_items[key].quantity;
    }
};

const updateReceivedQuantity = (element, itemIndex, quantityReceived) => {

    externalPurchaseOrderPartialReceiveForm.receive_items[itemIndex].quantity_received = 0;
    const inputValue = element.target.value ? element.target.value : 0;
    if (parseFloat(quantityReceived) < parseFloat(inputValue)) {
        externalPurchaseOrderPartialReceiveForm.receive_items[itemIndex].quantity_received = parseFloat(quantityReceived);
        return;
    }

    externalPurchaseOrderPartialReceiveForm.receive_items[itemIndex].quantity_received = parseFloat(inputValue);
};

const openProductBatchDetailsModal = (itemIndex) => {
    state.batchDetailsModalIndex = itemIndex;

    if (!externalPurchaseOrderPartialReceiveForm.receive_items[state.batchDetailsModalIndex].batch_details.length) {
        externalPurchaseOrderPartialReceiveForm.receive_items[state.batchDetailsModalIndex].batch_details = [
            {
                batch_number: null,
                quantity: null,
                expiry_date: null,
                notes: null,
            }
        ];
    }

    state.displayProductBatchDetailsModal = true;
};

const closeBatchDetailsModal = () => {
    state.displayProductBatchDetailsModal = false;
    state.batchDetailsModalIndex = null;
};

const updateBatchDetails = (batchDetails) => {
    externalPurchaseOrderPartialReceiveForm.receive_items[state.batchDetailsModalIndex].batch_details = batchDetails;
};
</script>
