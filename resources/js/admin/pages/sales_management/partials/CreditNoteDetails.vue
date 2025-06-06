<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Credit Note Details
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
            class="p-5"
        >
            <div
                v-if="selectedCreditNote.uses"
                class="text-left items-center p-5 border border-slate-200/60"
            >
                <h3 class="font-medium text-base mr-auto">
                    Credit Note Use Details
                </h3>

                <JSimpleTable
                    :columns="state.columnsForCreditNoteUses"
                    :records="selectedCreditNote.uses"
                    :allow-search="true"
                >
                    <template #amount="record">
                        <div class="flex justify-end items-center">
                            {{ displayAmountWithCurrencySymbol(record.item.amount) }}
                        </div>
                    </template>

                    <template #sale_id="record">
                        <div class="flex items-center">
                            {{ record.item.sale_id }}
                        </div>
                    </template>

                    <template #booking_payment_id="record">
                        <div class="flex items-center">
                            {{ record.item.booking_payment_id }}
                        </div>
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="selectedCreditNote.refund_details"
                class="text-left items-center p-5 border border-slate-200/60 mt-2"
            >
                <h3 class="font-medium text-base">
                    Credit Note Refund Details
                </h3>

                <JSimpleTable
                    :records="selectedCreditNote.refund_details"
                    :columns="state.columnsForCreditNoteRefund"
                    :allow-search="true"
                >
                    <template #amount="record">
                        <div class="flex justify-end items-center">
                            {{ displayAmountWithCurrencySymbol(record.item.amount) }}
                        </div>
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="selectedCreditNote.expiry_details"
                class="text-left items-center p-5 border border-slate-200/60 mt-2"
            >
                <h3 class="font-medium text-base">
                    Credit Note Expiry Details
                </h3>

                <JSimpleTable
                    :records="selectedCreditNote.expiry_details"
                    :columns="state.columnsForCreditNoteExpiry"
                    :allow-search="true"
                >
                    <template #expired_amount="record">
                        <div class="flex justify-end items-center">
                            {{ displayAmountWithCurrencySymbol(record.item.expired_amount) }}
                        </div>
                    </template>
                </JSimpleTable>
            </div>

            <div
                v-if="selectedCreditNote.credit_note_refund_mismatches &&
                    selectedCreditNote.credit_note_refund_mismatches.length"
                class="text-left items-center p-5 border border-slate-200/60 mt-2"
            >
                <h3 class="font-medium text-base">
                    Credit Note Refund Mismatches
                </h3>

                <JSimpleTable
                    :records="selectedCreditNote.credit_note_refund_mismatches"
                    :columns="state.columnsForCreditNoteRefundMismatches"
                    :allow-search="true"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import { X } from 'lucide-vue-next';
import { reactive } from 'vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    selectedCreditNote: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    columnsForCreditNoteUses: [
        {
            key: 'date',
            sortable: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'sale_id',
            sortable: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'booking_payment_id',
            sortable: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        }
    ],
    columnsForCreditNoteRefund: [
        {
            key: 'payment_name',
            sortable: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        }, {
            key: 'approved_by',
            sortable: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'refunded_date',
            sortable: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }
    ],
    columnsForCreditNoteExpiry: [
        {
            key: 'expiry_date',
            sortable: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'expired_amount',
            label: 'Expired',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        }
    ],

    columnsForCreditNoteRefundMismatches: [
        {
            key: 'message',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }
    ],
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
