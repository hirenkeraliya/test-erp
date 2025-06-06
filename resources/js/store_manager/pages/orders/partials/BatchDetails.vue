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

        <ModalBody class="px-5 sm:p-10">
            <div
                v-for="(batch_details, index) in state.details.batch_details"
                :key="index"
            >
                <div class="grid grid-cols-4 gap-4 items-center">
                    <FormInput
                        v-model:input-value="batch_details.batch_number"
                        input-label="Batch Number"
                        input-name="batch_number"
                        label-class="mt-0"
                        :disabled="batch_details.batch_number !== null"
                        :required="true"
                    />

                    <FormInput
                        :input-value="batch_details.quantity"
                        input-label="Quantity"
                        input-name="quantity"
                        label-class="mt-0"
                        :disabled="batch_details.quantity !== null"
                        :required="true"
                        @update:input-value="updateBatchDetailsQuantity($event, index)"
                    />

                    <JDatePicker
                        v-model:input-value="batch_details.batch_expiry_date"
                        input-label="Batch Expiry Date"
                        :disabled="batch_details.batch_expiry_date !== null"
                        :required="true"
                    />

                    <DeleteButton
                        type="button"
                        class="w-12 h-8 mt-auto text-red-600"
                        :disabled="state.details.batch_details.length <= 1"
                        @click="removePrintItem(itemIndex)"
                    />
                </div>
            </div>
            <OutlinePrimaryButton
                text="+ Add Batch Details"
                type="button"
                class="border-dashed mt-3"
                @click="addNewPrintItem()"
            />

            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
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
import JDatePicker from '@commonComponents/JDatePicker.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { onMounted, reactive } from 'vue';
import { showErrorNotification } from '@commonServices/notifier';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const state = reactive({
    details: {
        product_id: null,
        batch_details: [
            {
                batch_number: null,
                batch_expiry_date: null,
                quantity: 0,
            },
        ],
    }
});

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    selectedProduct: {
        type: Object,
        default: null,
    },
});

const emits = defineEmits([
    'close-batch-details-modal',
    'update-batch-details',
]);

const closeModal = () => {
    emits('close-batch-details-modal');
};

onMounted(() => {
    state.details.product_id = props.selectedProduct.id;

    if (props.selectedProduct.batch_details.length > 0) {
        state.details.batch_details = [];
        props.selectedProduct.batch_details.forEach(batchDetail => {
            state.details.batch_details.push(
                {
                    batch_number: batchDetail.batch_number,
                    batch_expiry_date: batchDetail.batch_expiry_date,
                    quantity: batchDetail.quantity ?? 0,
                }
            );
        });
    }
});

const updateBatchDetails = () => {
    emits('update-batch-details', state.details);
    closeModal();
};

const removePrintItem = (index) => {
    state.details.batch_details.splice(index, 1);
};

const addNewPrintItem = () => {
    const totalBatchQuantity = state.details.batch_details.reduce((total, batch) => total + batch.quantity, 0);

    if (props.selectedProduct.quantity < totalBatchQuantity) {
        showErrorNotification('Cannot add more batch details. The total quantity of batched products exceeds the specified quantity for the selected product.');
        return;
    }

    state.details.batch_details.push({
        batch_number: null,
        batch_expiry_date: null,
    });
};

const updateBatchDetailsQuantity = (value, index) => {
    const totalBatchQuantity = props.selectedProduct.quantity;

    const batchDetail = state.details.batch_details[index];
    if (value > totalBatchQuantity) {
        batchDetail.quantity = totalBatchQuantity;
        showErrorNotification('Cannot add more quantity. The specified quantity for the selected product.');
        return;
    }

    batchDetail.quantity = value;
};
</script>
