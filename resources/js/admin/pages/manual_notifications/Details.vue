<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Manual Notification Details for {{ type }}
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
                v-if="manualNotificationDetails && manualNotificationDetails.length"
            >
                <JSimpleTable
                    :columns="columnsForManualNotificationDetails"
                    :records="manualNotificationDetails"
                    :allow-search="true"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    manualNotificationDetails: {
        type: Object,
        required: true,
    },
    columnsForManualNotificationDetails: {
        type: Array,
        required: true,
    },
    type: {
        type: String,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
