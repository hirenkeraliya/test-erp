<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Sale Details
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <JSimpleTable
                :columns="state.columns"
                :records="saleDetails"
            >
                <template #gross_sales="data">
                    {{ displayAmountWithCurrencySymbol(data.item.gross_sales) }}
                </template>

                <template #total_discount_amount="data">
                    -{{ displayAmountWithCurrencySymbol(data.item.total_discount_amount) }}
                </template>

                <template #total_tax_amount="data">
                    {{ displayAmountWithCurrencySymbol(data.item.total_tax_amount) }}
                </template>

                <template #total_amount_paid="data">
                    {{ displayAmountWithCurrencySymbol(data.item.total_amount_paid) }}
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { reactive } from 'vue';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    saleDetails: {
        type: Array,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'offline_sale_id',
            label: 'Receipt Id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'bill_reference_number',
            bodyClass: 'text-left',
            label: '# Reference',
            headerClass: 'text-left',
        }, {
            key: 'location',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'counter',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'cashier',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'happened_at',
            label: 'Date & Time',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'gross_sales',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }, {
            key: 'total_discount_amount',
            bodyClass: 'text-right',
            label: 'Discount',
            headerClass: 'text-right',
        }, {
            key: 'total_tax_amount',
            bodyClass: 'text-right',
            label: 'Tax',
            headerClass: 'text-right',
        }, {
            key: 'total_amount_paid',
            bodyClass: 'text-right',
            label: 'Amount',
            headerClass: 'text-right',
        }, {
            key: 'notes',
            label: 'Remarks',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }
    ],
});

const emits = defineEmits([
    'close-modal'
]);

const closeModal = () => {
    emits('close-modal');
};
</script>
