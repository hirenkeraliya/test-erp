<template>
    <Modal
        size="modal-xl"
        :show="displayReportModal"
        @hidden="hideReportModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ customReportSelected.name }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideReportModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="flex flex-col gap-4">
                <slot
                    :name="customReportSelected.name"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import '@left4code/tw-starter/dist/js/modal';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';


defineProps({
    displayReportModal: {
        type: Boolean,
        default: false,
    },
    customReportSelected: {
        type: Object,
        default: () => {},
    },
});

const emits = defineEmits([
    'update:hide-report-modal',
]);

const hideReportModal = () => {
    emits('update:hide-report-modal', false);
};
</script>
