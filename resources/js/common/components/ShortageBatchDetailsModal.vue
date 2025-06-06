<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Batch Details
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
            <InfoAlert
                color="primary"
                class="mb-3 w-full"
            >
                {{ message }}
            </InfoAlert>

            <div
                v-for="(batchData, index) in state.batch_details"
                :key="'batch-details-' + index"
                class="grid grid-cols-12 gap-0 sm:gap-6 mb-3"
            >
                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <FormInput
                        :input-value="batchData.batch_number"
                        type="text"
                        input-label="Batch Number"
                        :readonly="true"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <FormInput
                        v-model:input-value="batchData.quantity"
                        type="number"
                        input-label="Quantity"
                    />
                </div>
            </div>

            <div class="text-left mt-5">
                <OutlinePrimaryButton
                    type="button"
                    text="Cancel"
                    class="w-24 mr-1"
                    @click="closeModal()"
                />

                <PrimaryButton
                    type="button"
                    text="Save"
                    class="w-24"
                    @click="updateBatchDetails()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import FormInput from '@commonComponents/FormInput.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { onMounted, reactive } from 'vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },

    batchDetails: {
        type: Array,
        required: true,
    },

    message: {
        type: String,
        required: true,
    },
});

const state = reactive({
    batch_details: [],
});

const emits = defineEmits([
    'close-modal',
    'update:batch-details'
]);

const closeModal = () => {
    state.batch_details = [];
    emits('close-modal');
};

const updateBatchDetails = () => {
    emits('update:batch-details', JSON.parse(JSON.stringify(state.batch_details)));
    closeModal();
};

onMounted(() => {
    state.batch_details = JSON.parse(JSON.stringify(props.batchDetails));
});
</script>
