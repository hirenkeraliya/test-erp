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
                v-if="message"
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
                        v-model:input-value="batchData.batch_number"
                        type="text"
                        input-label="Batch Number"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <FormInput
                        v-model:input-value="batchData.quantity"
                        type="number"
                        input-label="Quantity"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                    <DeleteButton
                        class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                        :disabled="state.batch_details.length <= 1"
                        @click="removeBatchDetailsOf(index)"
                    />
                </div>
            </div>

            <div class="grid grid-flow-col grid-rows-1 gap-4">
                <OutlinePrimaryButton
                    text="+ Add New Batch Details"
                    type="button"
                    class="border-dashed"
                    @click="addNewBatchDetails()"
                />
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
import DeleteButton from '@commonComponents/DeleteButton.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { onMounted, reactive } from 'vue';
import { showErrorNotification } from '@commonServices/notifier';

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
    isBatchError: false,
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
    state.isBatchError = false;
    state.batch_details.forEach(item => {
        if (item.quantity <= 0) {
            showErrorNotification('Quantity is required.');
            state.isBatchError = true;
        }

        if (item.batch_number === null && item.batch_number === '') {
            showErrorNotification('Batch Number is required.');
            state.isBatchError = true;
        }
    });

    if (state.isBatchError) {
        return;
    }

    emits('update:batch-details', JSON.parse(JSON.stringify(state.batch_details)));
    closeModal();
};

const addNewBatchDetails = () => {
    state.batch_details.push({
        batch_number: null,
        quantity: null,
    });
};

const removeBatchDetailsOf = (index) => {
    state.batch_details.splice(index, 1);
};

onMounted(() => {
    state.batch_details = JSON.parse(JSON.stringify(props.batchDetails));
});
</script>
