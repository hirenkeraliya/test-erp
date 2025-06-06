<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Complimentary Item Reasons
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="px-5 sm:p-10">
            <div>
                <FormSelectBox
                    v-model:selected-record="state.selectedComplimentaryItemReason"
                    :records="complimentaryItemReasons"
                    input-label="Type"
                    record-key-name="reason"
                    label-class="form-label w-full flex sm:flex-row"
                />
            </div>
            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="updateComplimentaryItemReason()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { reactive } from 'vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    complimentaryItemReasons: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    selectedComplimentaryItemReason: null,
});

const emits = defineEmits(['close-complimentary-item-modal', 'update-complimentary-item-discount']);

const closeModal = () => {
    state.selectedComplimentaryItemReason = null;
    emits('close-complimentary-item-modal');
};

const updateComplimentaryItemReason = () => {
    emits('update-complimentary-item-discount', state.selectedComplimentaryItemReason);
    closeModal();
};
</script>
