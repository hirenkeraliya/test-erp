<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Layaway Sale Details
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
                v-if="layawaySale.sale_items && layawaySale.sale_items.length"
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Layaway Sale Items
                </h3>

                <JSimpleTable
                    :columns="columnsForLayawaySaleItemDetails"
                    :records="layawaySale.sale_items"
                    :allow-search="true"
                >
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

                    <template #total_discount_amount="data">
                        <div class="flex justify-center items-center">
                            <span>
                                -{{ displayAmountWithCurrencySymbol(data.item.total_discount_amount) }}
                            </span>

                            <Tippy
                                v-if="data.item.sale_item_discounts"
                                :content="data.item.sale_item_discounts"
                            >
                                <Info
                                    class="text-cyan-400 ml-2"
                                    :size="15"
                                />
                            </Tippy>
                        </div>
                    </template>

                    <template #quantity="data">
                        {{ truncateDecimal(data.item.quantity) }}
                    </template>

                    <template #unit_price="data">
                        {{ displayAmountWithCurrencySymbol(data.item.unit_price) }}
                    </template>

                    <template #subtotal="data">
                        {{ displayAmountWithCurrencySymbol(data.item.subtotal) }}
                    </template>

                    <template #total_tax_amount="data">
                        {{ displayAmountWithCurrencySymbol(data.item.total_tax_amount) }}
                    </template>

                    <template #total_price_paid="data">
                        {{ displayAmountWithCurrencySymbol(data.item.total_price_paid) }}
                    </template>
                </JSimpleTable>

                <table class="table mt-2 intro-x font-medium bg-secondary">
                    <tbody>
                        <tr>
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Total: ' + displayAmountWithCurrencySymbol(getTotalOf(layawaySale.sale_items, 'subtotal')) }}
                            </td>
                        </tr>
                        <tr v-if="layawaySale.total_discount_amount > 0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Discount: ' + displayAmountWithCurrencySymbol(layawaySale.total_discount_amount) }}
                            </td>
                        </tr>
                        <tr v-if="layawaySale.total_tax_amount > 0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Tax: ' + displayAmountWithCurrencySymbol(layawaySale.total_tax_amount) }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                {{ 'Price Paid: ' + displayAmountWithCurrencySymbol(layawaySale.total_amount_paid) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="layawaySale.sale_discounts && layawaySale.sale_discounts.length"
                class="text-left items-center p-5 my-4 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Layaway Sale Discounts
                </h3>

                <JSimpleTable
                    :columns="columnsForLayawaySaleDiscounts"
                    :records="layawaySale.sale_discounts"
                    :allow-search="true"
                >
                    <template #amount="data">
                        {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                    </template>
                </JSimpleTable>

                <table class="table mt-2 intro-x font-medium bg-secondary">
                    <tbody>
                        <tr v-if="getTotalOf(layawaySale.sale_discounts, 'amount') > 0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                Total:
                                {{ displayAmountWithCurrencySymbol(getTotalOf(layawaySale.sale_discounts, 'amount')) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="layawaySale.payments && layawaySale.payments.length"
                class="text-left items-center p-5 my-4 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Layaway Sale payments
                </h3>

                <JSimpleTable
                    :columns="columnsForLayawaySalePaymentDetails"
                    :records="layawaySale.payments"
                    :allow-search="true"
                >
                    <template #amount="data">
                        {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                    </template>
                </JSimpleTable>

                <table class="table mt-2 intro-x font-medium bg-secondary">
                    <tbody>
                        <tr v-if="getTotalOf(layawaySale.payments, 'amount') > 0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                Total:
                                {{ displayAmountWithCurrencySymbol(getTotalOf(layawaySale.payments, 'amount')) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="layawaySale.sale_mismatches && layawaySale.sale_mismatches.length"
                class="text-left items-center p-5 my-4 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Layaway Sale Mismatches
                </h3>

                <JSimpleTable
                    :columns="columnsForLayawaySaleMismatches"
                    :records="layawaySale.sale_mismatches"
                    :allow-search="true"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { displayAmountWithCurrencySymbol, getTotalOf, truncateDecimal } from '@commonServices/helper';
import { Info, X } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const pageProps = computed(() => usePage().props);
defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    layawaySale: {
        type: Object,
        required: true,
    },
    columnsForLayawaySalePaymentDetails: {
        type: Array,
        required: true,
    },
    columnsForLayawaySaleItemDetails: {
        type: Array,
        required: true,
    },
    columnsForLayawaySaleDiscounts: {
        type: Array,
        required: true,
    },
    columnsForLayawaySaleMismatches: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
