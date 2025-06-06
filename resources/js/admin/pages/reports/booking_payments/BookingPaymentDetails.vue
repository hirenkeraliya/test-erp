<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Booking Payment Details
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
                v-if="bookingPayments.products && bookingPayments.products.length"
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Products
                </h3>

                <JSimpleTable
                    :columns="columnsForBookingPaymentsDetails"
                    :records="bookingPayments.products"
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
                        #attributes="data"
                    >
                        <span v-if="pageProps.product_variant">
                            <p
                                v-for="(attribute, index) in data.item.attributes"
                                :key="index"
                                class="flex"
                            >
                                {{ attribute.name }} : {{ attribute.value }}
                            </p>
                        </span>
                    </template>

                    <template #quantity="data">
                        {{ truncateDecimal(data.item.quantity) }}
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="bookingPayments.refund"
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Booking Payment Refund
                </h3>

                <JSimpleTable
                    :columns="columnsForRefund"
                    :records="bookingPayments.refund"
                    :allow-search="true"
                >
                    <template #amount="data">
                        {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="bookingPayments.uses"
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Booking Payment Uses
                </h3>

                <JSimpleTable
                    :columns="columnsForUses"
                    :records="bookingPayments.uses"
                    :allow-search="true"
                >
                    <template #amount="data">
                        {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="bookingPayments.voidUses"
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Booking Payment Void Uses
                </h3>

                <JSimpleTable
                    :columns="columnsForVoidUses"
                    :records="bookingPayments.voidUses"
                >
                    <template #amount="data">
                        {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="bookingPayments.payments && bookingPayments.payments.length"
                class="text-left items-center p-5 my-4 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Payments
                </h3>

                <JSimpleTable
                    :columns="columnsForPaymentDetails"
                    :records="bookingPayments.payments"
                >
                    <template #amount="data">
                        {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                    </template>
                </JSimpleTable>

                <table class="table mt-2 intro-x font-medium bg-secondary">
                    <tbody>
                        <tr v-if="getTotalOf(bookingPayments.payments, 'amount') > 0">
                            <td
                                colspan="8"
                                class="text-right"
                            >
                                Total:
                                {{ displayAmountWithCurrencySymbol(getTotalOf(bookingPayments.payments, 'amount')) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="bookingPayments.mismatches && bookingPayments.mismatches.length"
                class="text-left items-center p-5 my-4 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Mismatches
                </h3>

                <JSimpleTable
                    :columns="columnsForMismatches"
                    :records="bookingPayments.mismatches"
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
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    bookingPayments: {
        type: Object,
        required: true,
    },
    columnsForPaymentDetails: {
        type: Array,
        required: true,
    },
    columnsForBookingPaymentsDetails: {
        type: Array,
        required: true,
    },
    columnsForMismatches: {
        type: Array,
        required: true,
    },
    columnsForRefund: {
        type: Array,
        required: true,
    },
    columnsForUses: {
        type: Array,
        required: true,
    },
    columnsForVoidUses: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
