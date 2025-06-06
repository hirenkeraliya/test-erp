<template>
    <PageTitle title="Ship Transfer Stock" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Transfer Stock
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
        <div class="intro-y col-span-12">
            <div class="intro-y box">
                <div class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60">
                    <h2 class="font-medium text-base mr-auto">
                        <span>Delivery Order details</span>
                    </h2>
                </div>

                <form
                    @submit.prevent="savePurchaseOrderDeliveryOrderDetails();"
                >
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6">
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <JDateTimePicker
                                    v-model:input-value="transferItemForm.happened_at"
                                    input-label="Date"
                                    validation-field-name="happened_at"
                                    :required="true"
                                />
                            </div>
                            <div
                                class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
                            >
                                <FormTextarea
                                    v-model:input-value="transferItemForm.notes"
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
                                                        Quantity to Ship
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Package Type
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Package Type Quantity
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Quantity per Pack
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Remarks
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Batch
                                                    </th>
                                                    <th
                                                        v-if="purchaseOrderFulfillment"
                                                        class="whitespace-nowrap"
                                                    >
                                                        Action
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr
                                                    v-for="(item, itemIndex) in transferItemForm.transfer_items"
                                                    :key="'transfer-item-' + itemIndex"
                                                >
                                                    <td class="whitespace-nowrap">
                                                        <div>{{ item.product_name }} </div>
                                                        <div class="mt-1">
                                                            <span
                                                                v-if="! pageProps.product_variant"
                                                            >
                                                                <b>Color:</b> {{ item.product_color }}
                                                                <b>Size:</b> {{ item.product_size }}
                                                            </span>
                                                            <span
                                                                v-if="pageProps.product_variant"
                                                            >
                                                                <p
                                                                    v-for="(product_variant, index) in item.product_variant_values"
                                                                    :key="index"
                                                                    class="pl-4"
                                                                >
                                                                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                                                                </p>
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
                                                            :value="item.transfer_quantity"
                                                            @input="updateTransferQuantity($event, itemIndex, item.quantity)"
                                                        >
                                                        <ValidationError
                                                            :validation-field-name="'transfer_items.' + itemIndex + '.transfer_quantity'"
                                                        />
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <FormSelectBox
                                                            :records="packageTypes"
                                                            :selected-record="item.package_type_id"
                                                            :display-label="false"
                                                            input-label="Type"
                                                            :validation-field-name="'transfer_items.' + itemIndex + '.package_type_id'"
                                                            class="mt-[0]"
                                                            @update:selected-record="updatePackageTypeId($event, itemIndex)"
                                                        />
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            :value="item.package_quantity"
                                                            @input="updatePackageQuantity($event, itemIndex)"
                                                        >
                                                        <ValidationError
                                                            :validation-field-name="'transfer_items.' + itemIndex + '.package_quantity'"
                                                        />
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            :value="item.package_total_quantity"
                                                            @blur="updatePackageTotalQuantity($event, itemIndex)"
                                                        >
                                                        <ValidationError
                                                            :validation-field-name="'transfer_items.' + itemIndex + '.package_total_quantity'"
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
                                                    <td
                                                        v-if="purchaseOrderFulfillment && item.id"
                                                        class="whitespace-nowrap"
                                                    >
                                                        <FormCheckbox
                                                            class="mt-2 flex flex-row"
                                                            label-class="mt-0"
                                                            :check-value="isPrintColumnSelected(item.id)"
                                                            @update:check-value="selectPrintColumn($event, item.id)"
                                                        />
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-5">
                                        <Link :href="deliveryOrderUrl">
                                            <SecondaryButton
                                                type="button"
                                                text="Cancel"
                                                class="w-24 mt-5"
                                            />
                                        </Link>

                                        <PrimaryButton
                                            type="submit"
                                            :text="purchaseOrderFulfillment ? 'Update' : 'Submit'"
                                            class="w-24 mt-5 ml-1"
                                        />

                                        <PrimaryButton
                                            v-if="state.print_transfer_items.length > 0 && purchaseOrderFulfillment"
                                            type="button"
                                            text="Print Box Sticker"
                                            class="mt-5 ml-1"
                                            @click="printDeliveryOrderBoxSticker(purchaseOrderFulfillment.data.id)"
                                        />

                                        <Tippy
                                            v-if="state.print_transfer_items.length > 0 && purchaseOrderFulfillment"
                                            class="inline-flex items-center"
                                            content="Changes to the quantity will not be reflected in the Print Box Sticker unless you click the update button."
                                        >
                                            <Info
                                                class="text-cyan-400 ml-2"
                                                :size="18"
                                            />
                                        </Tippy>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <BatchDetailsModal
        v-if="state.batchDetailsModalIndex !== null"
        :batch-details="transferItemForm.transfer_items[state.batchDetailsModalIndex].batch_details"
        :modal-show="state.displayProductBatchDetailsModal"
        message="The total of all the quantities you specify with the batch numbers must match the product quantity for the stock transfer."
        @close-modal="closeBatchDetailsModal()"
        @update:batch-details="updateBatchDetails"
    />
</template>

<script setup>
import BatchDetailsModal from '@commonComponents/BatchDetailsModal.vue';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import ValidationError from '@commonComponents/ValidationError.vue';
import { currentSingleDateTime, numberFormat } from '@commonServices/helper';
import { confirmDialogBoxWithCenterText, showErrorNotification } from '@commonServices/notifier';
import { usePage, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Info } from 'lucide-vue-next';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    transferItems: {
        type: Object,
        default: () => {},
    },
    purchaseOrderFulfillment: {
        type: Object,
        default: () => {},
    },
    packageTypes: {
        type: Object,
        required: true,
    },
    deliveryOrderUrl: {
        type: String,
        required: true,
    },
    updateUrl: {
        type: String,
        required: true,
    },
    addShippingDetailsUrl: {
        type: String,
        required: true,
    },
    printBoxStickerUrl: {
        type: String,
        required: true,
    },

});

const state = reactive({
    displayProductBatchDetailsModal: false,
    batchDetailsModalIndex: null,
    print_transfer_items: [],
});

const transferItemForm = useForm({
    _method: 'put',
    happened_at: currentSingleDateTime(),
    delivery_order_number: null,
    notes: null,
    transfer_items: [
        {
            purchase_order_item_id: null,
            product_id: null,
            product_name: null,
            product_upc: null,
            product_color: null,
            product_size: null,
            product_variant_values: [],
            quantity: null,
            rejected_quantity: null,
            transfer_quantity: null,
            price_per_unit: null,
            package_type_id: null,
            package_quantity: null,
            package_total_quantity: null,
            batch_details: [],
            remarks: null,
        }
    ],
    order_type: props.defaultOrderType,
});

const updateTransferQuantity = (element, itemIndex, quantity) => {
    transferItemForm.transfer_items[itemIndex].transfer_quantity = 0;
    const inputValue = element.target.value ? element.target.value : 0;
    if (parseFloat(quantity) < parseFloat(inputValue)) {
        transferItemForm.transfer_items[itemIndex].transfer_quantity = parseFloat(quantity);
        return;
    }

    transferItemForm.transfer_items[itemIndex].transfer_quantity = parseFloat(inputValue);
    updateUnitOfMeasureQuantity(itemIndex);
};

const openProductBatchDetailsModal = (itemIndex) => {
    state.batchDetailsModalIndex = itemIndex;

    if (!transferItemForm.transfer_items[state.batchDetailsModalIndex].batch_details.length) {
        transferItemForm.transfer_items[state.batchDetailsModalIndex].batch_details = [
            {
                batch_number: null,
                quantity: null,
            }
        ];
    }

    state.displayProductBatchDetailsModal = true;
};

const updatePackageQuantity = (element, itemIndex) => {
    const inputValue = element.target.value ? element.target.value : 0;
    transferItemForm.transfer_items[itemIndex].package_quantity = parseInt(inputValue);
    updateUnitOfMeasureQuantity(itemIndex);
};

const updatePackageTotalQuantity = (element, itemIndex) => {
    const inputValue = element.target.value;
    const packageTotalQuantity = (parseFloat(inputValue) * transferItemForm.transfer_items[itemIndex].package_quantity);
    if (parseFloat(transferItemForm.transfer_items[itemIndex].transfer_quantity) !== parseFloat(packageTotalQuantity)) {
        showErrorNotification('The transferred stock and the total quantity of packages do not match.');
        return;
    }

    updateUnitOfMeasureQuantity(itemIndex);
};

const updatePackageTypeId = (packageTypeId, itemIndex) => {
    transferItemForm.transfer_items[itemIndex].package_type_id = packageTypeId;
    updateUnitOfMeasureQuantity(itemIndex);
};

const updateUnitOfMeasureQuantity = (itemIndex) => {
    let transferQuantity = transferItemForm.transfer_items[itemIndex].transfer_quantity;
    let packageQuantity = transferItemForm.transfer_items[itemIndex].package_quantity;
    const packageTypeId = transferItemForm.transfer_items[itemIndex].package_type_id;

    if (!packageQuantity) {
        packageQuantity = 1;
    }

    if (!transferQuantity) {
        transferQuantity = 0;
    }

    if (!packageTypeId) {
        transferItemForm.transfer_items[itemIndex].package_quantity = null;
        transferItemForm.transfer_items[itemIndex].package_total_quantity = null;
        return;
    }

    transferItemForm.transfer_items[itemIndex].package_quantity = packageQuantity;
    transferItemForm.transfer_items[itemIndex].package_total_quantity = numberFormat(parseFloat(transferQuantity) / parseFloat(packageQuantity));
};

const updateBatchDetails = (batchDetails) => {
    transferItemForm.transfer_items[state.batchDetailsModalIndex].batch_details = batchDetails;
};

const closeBatchDetailsModal = () => {
    state.displayProductBatchDetailsModal = false;
    state.batchDetailsModalIndex = null;
};

const savePurchaseOrderDeliveryOrderDetails = () => {
    if (props.purchaseOrderFulfillment) {
        transferItemForm.put(route(props.updateUrl, props.purchaseOrderFulfillment.data.id));
        return;
    }

    transferItemForm.put(props.addShippingDetailsUrl);
};

const isPrintColumnSelected = (id) => {
    for (const key in state.print_transfer_items) {
        if (state.print_transfer_items[key] === id) {
            return true;
        }
    }
    return false;
};

const selectPrintColumn = (value, id) => {
    if (id === null) {
        return;
    }
    state.print_transfer_items.check = value;

    if (state.print_transfer_items.check) {
        state.print_transfer_items.push(id);
        return;
    }

    for (const key in state.print_transfer_items) {
        if (state.print_transfer_items[key] === id) {
            state.print_transfer_items.splice(key, 1);
        }
    }
};

const printDeliveryOrderBoxSticker = (purchaseOrderFulfillmentId) => {
    axios.post(route(props.printBoxStickerUrl, [purchaseOrderFulfillmentId, state.print_transfer_items])).then((response) => {
        if (response) {
            const printWindow = window.open('', 'Receipt', 'height=400,width=600');
            printWindow.document.write(response.data);
            printWindow.document.close(); // necessary for IE >= 10

            printWindow.onload = () => {
                printWindow.focus(); // necessary for IE >= 10
                printWindow.print();
            };
        }
    });
};

onMounted(() => {
    if (props.transferItems) {
        const filteredItems = props.transferItems.data.filter(item => item !== null && !(Array.isArray(item) && item.length === 0));
        Object.assign(transferItemForm.transfer_items, filteredItems);
    }

    if (props.purchaseOrderFulfillment) {
        Object.assign(transferItemForm, props.purchaseOrderFulfillment.data);
    }
});

const setTransferQuantitySameAsQuantities = () => {
    const message = 'Do you want to ship full quantity?';

    confirmDialogBoxWithCenterText(message, () => {
        setTransferQuantity();
    });
};

const setTransferQuantity = () => {
    for (const key in transferItemForm.transfer_items) {
        transferItemForm.transfer_items[key].transfer_quantity = transferItemForm.transfer_items[key].quantity;
        updateUnitOfMeasureQuantity(key);
    }
};
</script>
