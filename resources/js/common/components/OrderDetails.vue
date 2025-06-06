<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Order Details
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
                v-if="order.order_items && order.order_items.length"
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Order Items ({{ order.type }})
                </h3>

                <JSimpleTable
                    :columns="columnsForOrderItemDetails"
                    :records="order.order_items"
                    :allow-search="true"
                >
                    <template #product="data">
                        {{ data.item.product }}

                        <Tippy
                            v-if="data.item.bundle"
                            :content="'Unit Of Measure: ' + data.item.bundle.package_type_name + '\n Units: ' + data.item.bundle.units"
                        >
                            <Info
                                class="text-cyan-400 ml-2"
                                :size="15"
                            />
                        </Tippy>
                    </template>

                    <template
                        v-if="pageProps.product_variant"
                        #product_variant_values="record"
                    >
                        <span v-if="pageProps.product_variant">
                            <p
                                v-for="(product_variant, index) in record.item.product_variant_values"
                                :key="index"
                                class="flex"
                            >
                                {{ product_variant.attribute.name }} : {{ product_variant.value }}
                            </p>
                        </span>
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

                    <template #subtotal="data">
                        {{ displayAmountWithCurrencySymbol(data.item.subtotal) }}
                    </template>

                    <template #total_discount_amount="data">
                        <div>
                            <Tippy
                                v-if="data.item.complimentary_item_reason"
                                :content="'Complimentary Discount: ' + data.item.complimentary_item_reason"
                            >
                                <Info
                                    class="text-cyan-400 ml-2"
                                    :size="15"
                                />
                            </Tippy>

                            <Tippy
                                v-if="data.item.total_discount_amount > 0.0 && data.item.complimentary_item_reason === null"
                                content="Price Override"
                            >
                                <Info
                                    class="text-cyan-400 ml-2"
                                    :size="15"
                                />
                            </Tippy>
                            <span>
                                {{ displayAmountWithCurrencySymbol(data.item.total_discount_amount, true) }}
                            </span>
                        </div>
                    </template>
                </JSimpleTable>

                <table class="table mt-2 intro-x font-medium bg-secondary">
                    <tbody>
                        <tr>
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Total: ' + displayAmountWithCurrencySymbol(getTotalOf(order.order_items, 'subtotal')) }}
                            </td>
                        </tr>
                        <tr v-if="order.total_discount_amount > 0.0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Discount: ' + displayAmountWithCurrencySymbol(order.total_discount_amount, true) }}
                            </td>
                        </tr>
                        <tr v-if="order.total_tax_amount > 0.0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Tax: ' + displayAmountWithCurrencySymbol(order.total_tax_amount) }}
                            </td>
                        </tr>
                        <tr v-if="order.round_off > 0.0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Round Off: ' + displayAmountWithCurrencySymbol(order.round_off) }}
                            </td>
                        </tr>
                        <tr v-if="order.delivery_charges > 0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Delivery Charges: ' + displayAmountWithCurrencySymbol(order.delivery_charges) }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Price Paid: ' + displayAmountWithCurrencySymbol(order.total_amount_paid) }}
                            </td>
                        </tr>
                        <tr v-if="order.layaway_pending_amount">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Layaway Pending: ' + displayAmountWithCurrencySymbol(order.layaway_pending_amount) }}
                            </td>
                        </tr>
                        <tr v-if="order.credit_pending_amount">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Credit Pending: ' + displayAmountWithCurrencySymbol(order.credit_pending_amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="order.payments && order.payments.length"
                class="text-left items-center p-5 my-4 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Order Payments
                </h3>

                <JSimpleTable
                    :columns="columnsForPaymentDetails"
                    :records="order.payments"
                    :allow-search="true"
                />

                <table class="table mt-2 intro-x font-medium bg-secondary">
                    <tbody>
                        <tr v-if="getTotalOf(order.payments, 'amount') > 0">
                            <td
                                colspan="2"
                                class="w-8/12"
                            >
                                Total:
                            </td>
                            <td class="w-4/12">
                                {{ getTotalOf(order.payments, 'amount') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { displayAmountWithCurrencySymbol, getTotalOf } from '@commonServices/helper';
import { Info, X } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const pageProps = computed(() => usePage().props);

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    order: {
        type: Object,
        required: true,
    },
    columnsForPaymentDetails: {
        type: Array,
        required: true,
    },
    columnsForOrderItemDetails: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
