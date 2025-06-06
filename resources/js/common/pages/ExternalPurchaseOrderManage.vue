<template>
    <PageTitle title="External Purchase order" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            External Purchase order
        </h2>

        <div class="w-full sm:w-auto block md:flex mt-4 sm:mt-0">
            <PrimaryButton
                text="Ship Full Quantity"
                type="button"
                class="w-15 sm mr-1 mb-2 md:mb-0"
                @click="setTransferQuantitySameAsQuantities()"
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
                        <span v-if="externalPurchaseOrder">Edit External Purchase order</span>
                        <span v-else>Add External Purchase order</span>
                    </h2>
                </div>

                <form @submit.prevent="saveExternalPurchaseOrder();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-4">
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="externalPurchaseOrderForm.fob"
                                    input-name="fob"
                                    input-label="Fob"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="externalPurchaseOrderForm.freight_charges"
                                    input-name="freight_charges"
                                    input-label="Freight Charges"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="externalPurchaseOrderForm.insurance_charges"
                                    input-name="insurance_charges"
                                    input-label="Insurance Charges"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="externalPurchaseOrderForm.duty"
                                    input-name="duty"
                                    input-label="Duty"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="externalPurchaseOrderForm.sst"
                                    input-name="sst"
                                    input-label="Sst"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="externalPurchaseOrderForm.handling_charges"
                                    input-name="handling_charges"
                                    input-label="Handling Charges"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="externalPurchaseOrderForm.other_charges"
                                    input-name="other_charges"
                                    input-label="Other Charges"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextarea
                                    v-model:input-value="externalPurchaseOrderForm.notes"
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
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr
                                                    v-for="(item, itemIndex) in externalPurchaseOrderForm.transfer_items"
                                                    :key="'transfer-item-' + itemIndex"
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
                                                            :value="item.received_quantity"
                                                            @input="updateReceivedQuantity($event, itemIndex, item.quantity)"
                                                        >
                                                        <ValidationError
                                                            :validation-field-name="'transfer_items.' + itemIndex + '.received_quantity'"
                                                        />
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <FormTextarea
                                                            :input-value="item.remarks"
                                                            placeholder="Enter Remarks"
                                                            input-name="remarks"
                                                            class="mt-[0] w-[200px]"
                                                            @update:input-value="updateItemRemarks($event, itemIndex)"
                                                        />
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="flex flex-row ml-auto">
                                        <Link :href="route(getExternalPurchaseOrderIndexUrl, purchasePlan.id)">
                                            <SecondaryButton
                                                type="button"
                                                text="Cancel"
                                                class="w-24 mt-5"
                                            />
                                        </Link>

                                        <PrimaryButton
                                            type="submit"
                                            :text="externalPurchaseOrder ? 'Update' : 'Submit'"
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
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import FormInput from '@commonComponents/FormInput.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { onMounted } from 'vue';
import { route } from 'ziggy';
import { confirmDialogBoxWithCenterText } from '@commonServices/notifier';
import ValidationError from '@commonComponents/ValidationError.vue';

const props = defineProps({
    storeExternalPurchaseOrderUrl: {
        type: String,
        required: true,
    },
    getExternalPurchaseOrderIndexUrl: {
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
    purchasePlan: {
        type: Object,
        required: true,
    },
    transferItems: {
        type: Object,
        default: () => {},
    },

});

const externalPurchaseOrderForm = useForm({
    notes: null,
    fob: null,
    freight_charges: null,
    insurance_charges: null,
    duty: null,
    sst: null,
    handling_charges: null,
    other_charges: null,
    transfer_items: [
        {
            purchase_plan_item_id: null,
            product_id: null,
            product_name: null,
            product_upc: null,
            product_color: null,
            product_size: null,
            quantity: null,
            received_quantity: null,
            remarks: null,
            cost_price: null,
            unit_of_measure_derivative_id: null,
        }
    ],
});

const saveExternalPurchaseOrder = () => {
    if (props.externalPurchaseOrder) {
        externalPurchaseOrderForm.post(route(props.updateExternalPurchaseOrderUrl, props.externalPurchaseOrder.data.id));
        return;
    }
    externalPurchaseOrderForm.post(route(props.storeExternalPurchaseOrderUrl, props.purchasePlan.id));
};

onMounted(() => {
    if (props.transferItems) {
        const filteredItems = props.transferItems.data.filter(item => item !== null && !(Array.isArray(item) && item.length === 0));
        Object.assign(externalPurchaseOrderForm.transfer_items, filteredItems);
    }

    if (props.externalPurchaseOrder) {
        Object.assign(externalPurchaseOrderForm, props.externalPurchaseOrder.data);
    }
});

const setTransferQuantitySameAsQuantities = () => {
    const message = 'Do you want to ship full quantity?';

    confirmDialogBoxWithCenterText(message, () => {
        setTransferQuantity();
    });
};

const setTransferQuantity = () => {
    for (const key in externalPurchaseOrderForm.transfer_items) {
        externalPurchaseOrderForm.transfer_items[key].received_quantity = externalPurchaseOrderForm.transfer_items[key].quantity;
    }
};

const updateReceivedQuantity = (element, itemIndex, quantity) => {
    externalPurchaseOrderForm.transfer_items[itemIndex].received_quantity = 0;
    const inputValue = element.target.value ? element.target.value : 0;
    if (parseFloat(quantity) < parseFloat(inputValue)) {
        externalPurchaseOrderForm.transfer_items[itemIndex].received_quantity = parseFloat(quantity);
        return;
    }

    externalPurchaseOrderForm.transfer_items[itemIndex].received_quantity = parseFloat(inputValue);
};

const updateItemRemarks = (value, itemIndex) => {
    externalPurchaseOrderForm.transfer_items[itemIndex].remarks =  value;
};
</script>
