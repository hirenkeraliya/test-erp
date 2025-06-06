<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Unmatched Products
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <div class="text-lg font-bold text-orange-600 px-6 pt-5 mb-3">
            Important Instructions
        </div>

        <div class="w-full mx-auto">
            <ul class="h-auto overflow-y-auto pr-2.5">
                <li class="px-6 cursor-pointer">
                    <p
                        class="mb-3"
                    >
                        The products with the following UPC codes could not be found in our records. You may confirm the correct UPC codes on the products page and re-upload the file with the correct UPC codes. Take note that only active products are allowed and you cannot include non-inventory or non selling products.
                    </p>
                </li>
            </ul>

            <div class="mt-5 border border-solid border-black-20 rounded-20">
                <ul class="h-auto overflow-y-auto pr-2.5">
                    <li
                        v-for="record in records"
                        :key="record.id"
                        class="py-3.5 px-6 flex items-center justify-between cursor-pointer rounded"
                    >
                        <div class="flex flex-row">
                            <div class="my-auto">
                                <ChevronRight />
                            </div>

                            <p class="ml-1 text-base text-black-60 font-bold">
                                {{ record }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader } from '@commonVendor/model';
import { ChevronRight, X } from 'lucide-vue-next';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    records: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
