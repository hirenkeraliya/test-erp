<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Voucher Transaction Details
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-center">
            <JSimpleTable
                :columns="columnsForVoucherTransactionDetails"
                :records="voucherDetails"
                :allow-extra-header-details="true"
                :allow-pagination-and-sorting="false"
                :allow-search="true"
                first-div-class="pb-2 sm:pb-5 mt-0 intro-y"
            >
                <template #extra-header-data>
                    <PrimaryButton
                        type="button"
                        text="PDF"
                        class="text-right btn-sm w-24 h-10 mt-3"
                        @click="exportPDFRecords"
                    />
                </template>

                <template #location="data">
                    {{ data.item.location }}
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { printReport } from '@commonServices/helper';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    voucherDetails: {
        type: Array,
        required: true,
    },
    columnsForVoucherTransactionDetails: {
        type: Array,
        required: true,
    },
    voucherDetailsPdfPrint: {
        type: String,
        required: true,
    },
    exportPermission: {
        type: String,
        default: null,
    },
});

const exportPDFRecords = () => {
    printReport(props.voucherDetailsPdfPrint, props.exportPermission);
};

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
