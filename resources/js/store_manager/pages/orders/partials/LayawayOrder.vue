<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="hideLayawayOrderModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Complete Layaway Order
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideLayawayOrderModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="text-lg font-medium flex justify-between">
                <p>
                    Paid: {{ order.total_amount_paid }}
                </p>

                <p>
                    Due: {{ order.layaway_pending_amount }}
                </p>
            </div>

            <div class="grid grid-rows-1 grid-flow-col">
                <JMultiSelect
                    :selected-records="state.paymentTypes"
                    :records="paymentTypes"
                    placeholder="Please select payment type"
                    multi="true"
                    input-label="Payment Types"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    :disabled="isPaymentTypeSelectBoxDisabled"
                    @update:selected-records="updatePaymentTypes"
                />

                <SecondaryButton
                    v-if="Object.keys(state.paymentTypes).length > 0"
                    type="button"
                    text="Reset"
                    class="w-32 h-12 ml-3 mt-auto"
                    @click="state.paymentTypes = []"
                />
            </div>

            <div class="grid grid-cols-4 grid-flow-row gap-4">
                <div
                    v-for="(paymentType, index) in state.paymentTypes"
                    :key="index"
                >
                    <FormInput
                        :input-value="paymentType.amount"
                        type="number"
                        :input-label="paymentType.name"
                        :validation-field-name="'payment_types.' + index + '.amount'"
                        @update:input-value="updatePaymentTypeAmount($event, index)"
                    />
                </div>
            </div>

            <div class="mt-5">
                <SecondaryButton
                    type="button"
                    text="Cancel"
                    class="w-24"
                    @click="hideLayawayOrderModal"
                />

                <PrimaryButton
                    type="button"
                    text="Complete Layaway Order"
                    class="ml-2"
                    @click="completeLayawayOrder"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { route } from 'ziggy';
import { X } from 'lucide-vue-next';
import FormInput from '@commonComponents/FormInput.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    order: {
        type: Object,
        required: true,
    },
    paymentTypes: {
        type: Object,
        required: true
    }
});

const state = reactive({
    paymentTypes: [],
    isPaymentTypeSelectBoxDisabled: false,
});

const layawayOrderForm = useForm({
    paymentTypes: [],
    orderId: props.order.id,
});

const emits = defineEmits(['layaway-order-close-modal']);

const hideLayawayOrderModal = () => {
    emits('layaway-order-close-modal');
};

const completeLayawayOrder = () => {
    layawayOrderForm.paymentTypes = state.paymentTypes.filter((paymentType) => paymentType.value !== null).map((paymentType) => {
        return {
            type_id: paymentType.id,
            amount: paymentType.amount
        };
    });

    layawayOrderForm.post(route('store_manager.orders.complete_layaway_order'), {
        onSuccess: () => hideLayawayOrderModal(),
    });
};

const updatePaymentTypes = (paymentTypes) => {
    state.paymentTypes = [];
    state.paymentTypes = paymentTypes.map((paymentType) => {
        return {
            id: paymentType.id,
            name: paymentType.name,
            amount: paymentType.amount,
        };
    });
};

const updatePaymentTypeAmount = (amount, index) => {
    if (parseFloat(amount) >= parseFloat(props.order.layaway_pending_amount)) {
        state.isPaymentTypeSelectBoxDisabled = true;
        state.paymentTypes[index].amount = props.order.layaway_pending_amount;
        return;
    }

    state.paymentTypes[index].amount = amount;
};

const isPaymentTypeSelectBoxDisabled = computed(() => {
    let paymentTypeAmount = 0;

    state.paymentTypes.forEach(function (paymentType) {
        paymentTypeAmount += paymentType.amount;
    });

    return parseFloat(paymentTypeAmount) >= parseFloat(props.order.layaway_pending_amount);
});
</script>
