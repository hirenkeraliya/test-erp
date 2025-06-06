<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="hideCancelOrderModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Cancel Order
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideCancelOrderModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div>
                <FormSelectBox
                    v-model:selected-record="cancelOrderForm.cancelOrderReasonId"
                    :records="cancelOrderReasons"
                    :required="true"
                    input-label="Cancel Order Reason"
                    record-key-name="reason"
                    label-class="form-label w-full flex sm:flex-row"
                />
            </div>

            <div class="mt-5">
                <SecondaryButton
                    type="button"
                    text="Cancel"
                    class="w-24"
                    @click="hideCancelOrderModal"
                />

                <PrimaryButton
                    type="button"
                    text="Cancel Order"
                    class="ml-2"
                    @click="cancelOrder"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { route } from 'ziggy';
import { X } from 'lucide-vue-next';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    orderId: {
        type: String,
        required: true,
    },
    cancelOrderReasons: {
        type: Object,
        required: true
    }
});

const cancelOrderForm = useForm({
    cancelOrderReasonId: null,
    orderId: props.orderId,
});

const emits = defineEmits(['cancel-order-close-modal']);

const hideCancelOrderModal = () => {
    emits('cancel-order-close-modal');
};

const cancelOrder = () => {
    cancelOrderForm.post(route('store_manager.orders.cancel_order'), {
        onSuccess: () => hideCancelOrderModal(),
    });
};
</script>
