<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Order Return Details
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody
            class="p-5 sm:p-10 text-center"
        >
            <div
                v-if="orderReturnDetails.order_items && orderReturnDetails.order_items.length"
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    {{ titleOfTable() }}
                </h3>

                <JSimpleTable
                    :columns="state.columnsForOrderReturnItemDetails"
                    :records="orderReturnDetails.order_items"
                    :allow-search="true"
                >
                    <template #total_price_paid="data">
                        {{ displayAmountWithCurrencySymbol(data.item.total_price_paid) }}
                    </template>
                    <template #upc="data">
                        <div class="flex justify-left items-center">
                            <span>
                                {{ data.item.upc }}
                            </span>

                            <Tippy
                                v-if="data.item.promoters"
                                :content="'Promoters: ' + data.item.promoters"
                            >
                                <Info
                                    class="text-cyan-400 ml-2"
                                    :size="15"
                                />
                            </Tippy>
                        </div>
                    </template>
                    <template #action="data">
                        <div class="flex items-center">
                            <FormCheckbox
                                v-if="! data.item.is_returned"
                                class=" flex flex-row"
                                label-class="mt-0"
                                :checkbox-name="data.item.product"
                                :check-value="isReturnProductSelected(data.item.id)"
                                @update:check-value="selectReturnProduct($event, data.item)"
                            />
                        </div>
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="state.orderReturnItems && state.orderReturnItems.length"
                class="text-left items-center p-5 border border-slate-200/60 mt-2"
            >
                <h3 class="font-medium text-base mr-auto">
                    Selected Order Return Items
                </h3>
                <JSimpleTable
                    :columns="state.columnsForSelectedItemDetails"
                    :records="state.orderReturnItems"
                >
                    <template #total_price_paid="data">
                        {{ displayAmountWithCurrencySymbol(data.item.total_price_paid) }}
                    </template>

                    <template #action="data">
                        <div class="flex">
                            <div class="input-form col-span-6 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    :input-value="data.item.return_quantity"
                                    type="number"
                                    input-label="Return Quantity"
                                    placeholder="Return Quantity"
                                    @update:input-value="updateReturnQuantity($event, data.item.product_id)"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormSelectBox
                                    class="w-full ml-2"
                                    :selected-record="data.item.order_return_reason_id"
                                    :records="orderReturnReasons"
                                    record-key-name="reason"
                                    :placeholder="'Please select reason'"
                                    :input-label="'Reason'"
                                    @update:selected-record="updateReasons($event, data.item.product_id)"
                                />
                            </div>
                        </div>
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="state.orderReturnItems && state.orderReturnItems.length"
                class="text-right"
            >
                <div class="w-1/3 ml-auto">
                    <div class="flex justify-between text-lg sm:space-y-2 items-center">
                        <span class="font-medium">Total: </span>
                        <p class="font-normal">
                            {{ displayAmountWithCurrencySymbol(getSubtotalAmount(), true) }}
                        </p>
                    </div>
                </div>

                <PrimaryButton
                    v-if="state.orderReturnItems && state.orderReturnItems.length"
                    type="button"
                    text="Proceed"
                    class="ml-2 mt-4"
                    @click="proceedReturnOrder"
                />
            </div>
        </ModalBody>
    </Modal>

    <Receipt
        v-if="Object.keys(state.orderReturnData).length > 0"
        :order-return="state.orderReturnData"
        :print-receipt-data="state.printReceiptData"
    />
</template>

<script setup>
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import Receipt from '@commonComponents/Receipt.vue';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import { showErrorNotification } from '@commonServices/notifier';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Info, X } from 'lucide-vue-next';
import { nextTick, onMounted, reactive } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    orderReturnDetails: {
        type: Object,
        required: true,
    },
    orderReturnReasons: {
        type: Object,
        required: true
    },
});

const state = reactive({
    columnsForOrderReturnItemDetails: [
        {
            key: 'upc'
        }, {
            key: 'product'
        }, {
            key: 'color'
        }, {
            key: 'size'
        }, {
            key: 'quantity',
            bodyClass: 'text-center'
        }, {
            key: 'total_price_paid',
            label: 'Price Paid',
            bodyClass: 'text-right'
        }, {
            key: 'action',
            bodyClass: 'text-center'
        }
    ],

    columnsForSelectedItemDetails: [
        {
            key: 'product_name'
        }, {
            key: 'quantity',
            bodyClass: 'text-center'
        }, {
            key: 'total_price_paid',
            label: 'Price Paid',
            bodyClass: 'text-right'
        }, {
            key: 'action',
            bodyClass: 'text-center'
        }
    ],

    orderReturnItems: [],
    printReceiptData: Math.random(),
    orderReturnData: {},
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};

const orderReturnDetailsForm = useForm({
    order_return_items: state.orderReturnItems,
    order_return_round_off_amount: 0.0,
    member_id: null,
});

const isReturnProductSelected = (id) => {
    for (const key in state.orderReturnItems) {
        if (state.orderReturnItems[key].id === id) {
            return true;
        }
    }
    return false;
};

const selectReturnProduct = (event, item) => {
    state.orderReturnItems.check = event;

    if (state.orderReturnItems.check) {
        state.orderReturnItems.push({
            id: item.id,
            product_id: item.product_id,
            order_item_id: item.id,
            product_name: item.product,
            quantity: item.quantity,
            total_price_paid: item.total_price_paid,
            price_paid_per_unit: item.price_paid_per_unit,
            return_quantity: 0,
            order_return_reason_id: null,
        });
        return;
    }

    for (const key in state.orderReturnItems) {
        if (state.orderReturnItems[key].id === item.id) {
            state.orderReturnItems.splice(key, 1);
        }
    }
};

const updateReasons = (reasonId, productId) => {
    const orderReturnItemIndex = state.orderReturnItems.findIndex((orderReturnItem) => orderReturnItem.product_id === productId);

    state.orderReturnItems[orderReturnItemIndex].order_return_reason_id = reasonId;
};

const updateReturnQuantity = (value, productId) => {
    const orderReturnItemIndex = state.orderReturnItems.findIndex((orderReturnItem) => orderReturnItem.product_id === productId);

    if (value > state.orderReturnItems[orderReturnItemIndex].quantity) {
        state.orderReturnItems[orderReturnItemIndex].return_quantity = state.orderReturnItems[orderReturnItemIndex].quantity;
        getSubtotalAmount();
        return;
    }

    state.orderReturnItems[orderReturnItemIndex].return_quantity = value;
    getSubtotalAmount();
};

const proceedReturnOrder = () => {
    orderReturnDetailsForm.order_return_items = state.orderReturnItems;

    axios.post(route('store_manager.order_returns.store'), orderReturnDetailsForm)
        .then((response) => {
            state.orderReturnData = response.data.order_return;

            nextTick(() => {
                closeModal();
                state.printReceiptData = Math.random();
                router.get(route('store_manager.order_returns.index'));
            });
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const getSubtotalAmount = () => {
    let subtotal = 0.0;

    state.orderReturnItems.forEach((product) => {
        const price = product.price_paid_per_unit;
        subtotal += price * product.return_quantity;
    });

    return subtotal;
};

const titleOfTable = () => {
    let count = 0;

    props.orderReturnDetails.order_items.forEach(orderReturnItem => {
        if (orderReturnItem.is_returned) {
            count++;
        }
    });

    if (Object.keys(props.orderReturnDetails.order_items).length === count) {
        return 'Order Return Items.';
    }

    return 'Confirm Order Items For Returns';
};

onMounted(() => {
    if (Object.keys(props.orderReturnDetails).length > 0) {
        orderReturnDetailsForm.member_id = props.orderReturnDetails.member_details.id;
    }
});
</script>
