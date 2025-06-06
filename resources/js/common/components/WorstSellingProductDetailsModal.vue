<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="hideModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Worst Selling Product
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <div
                class="grid gap-2"
            >
                <div class="text-center">
                    <img
                        :src="productData.image_url"
                        :alt="productData.name"
                        class="w-1/2 rounded-lg mx-auto"
                    >
                </div>

                <div class="bg-slate-200 p-5 rounded mt-5">
                    <p class="text-sm lg:text-lg font-semibold text-slate-700">
                        {{ productData.name }}
                    </p>

                    <ul class="list-disc ml-5">
                        <li class="mt-1 text-sm font-medium">
                            Units Sold:
                            {{ truncateDecimal(productData.total_units_sold) }}
                        </li>

                        <li class="mt-1 text-sm font-medium">
                            Sales:
                            {{ displayAmountWithCurrencySymbol(productData.total_sales) }}
                        </li>
                    </ul>
                </div>
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { truncateDecimal, displayAmountWithCurrencySymbol } from '@commonServices/helper';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    productData: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits([
    'update:hide-modal',
]);

const hideModal = () => {
    emits('update:hide-modal', false);
};
</script>
