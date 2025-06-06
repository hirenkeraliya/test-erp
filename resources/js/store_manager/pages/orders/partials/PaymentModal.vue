<template>
    <Modal
        size="modal-lg"
        :show="modalShow"
        @hidden="hidePaymentTypeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ paymentType?.name ?? 'Payment Type' }}
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hidePaymentTypeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10">
            <div class="grid grid-cols-4 gap-4 text-center">
                <div>
                    <PrimaryButton
                        text="1"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(1)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="2"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(2)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="3"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(3)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="4"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(4)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="5"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(5)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="6"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(6)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="7"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(7)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="8"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(8)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="9"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(9)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="0"
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount(0)"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="."
                        class="w-full bg-slate-200 border-slate-200 text-black"
                        @click="calculateAmount('.')"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="Exact"
                        class="w-full"
                        @click="exactCalculations()"
                    />
                </div>

                <div>
                    <PrimaryButton
                        text="Clear"
                        class="w-full"
                        @click="clear()"
                    />
                </div>
            </div>

            <div class="mt-8">
                <FormInput
                    v-model:input-value="state.notes"
                    input-label="Notes"
                    input-name="payment_notes"
                />
            </div>

            <div class="mt-6">
                <div class="flex justify-between text-danger text-base font-medium items-center space-y-1">
                    <p> Entered Amount: </p>
                    <p>
                        {{ displayAmountWithCurrencySymbol(state.amount) }}
                    </p>
                </div>

                <div
                    v-if="getChangeDue() > 0.00"
                    class="flex justify-between text-info text-base font-medium items-center space-y-1r"
                >
                    <p> Change Due: </p>
                    <p> {{ displayAmountWithCurrencySymbol(getChangeDue()) }} </p>
                </div>

                <div class="flex justify-between text-base font-medium items-center space-y-1">
                    <p> Total: </p>
                    <p> {{ displayAmountWithCurrencySymbol(props.totalAmount) }} </p>
                </div>

                <div class="flex justify-between text-base font-medium items-center space-y-1">
                    <p> Paid: </p>
                    <p> {{ displayAmountWithCurrencySymbol(state.paidAmount) }} </p>
                </div>

                <div class="flex justify-between text-base font-medium items-center space-y-1">
                    <p> Due: </p>
                    <p> {{ displayAmountWithCurrencySymbol(getDueAmount()) }} </p>
                </div>
            </div>
            <div class="mt-5">
                <PrimaryButton
                    type="button"
                    text="Submit"
                    class="w-24"
                    @click="updatePaymentData()"
                />
            </div>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import FormInput from '@commonComponents/FormInput.vue';
import { X } from 'lucide-vue-next';
import { onMounted, onUpdated, reactive } from 'vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';

const props = defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    totalAmount: {
        type: Number,
        default: 0,
    },
    paymentType: {
        type: Object,
        default: () => { },
    },
    payments: {
        type: Object,
        default: () => { },
    },
});

const state = reactive({
    amount: 0,
    paidAmount: 0,
    notes: null,
});

const emits = defineEmits(['close-payment-modal', 'add-payment-type']);

const hidePaymentTypeModal = () => {
    emits('close-payment-modal');
};

const getChangeDue = () => {
    return state.amount - props.totalAmount;
};

const getDueAmount = () => {
    const amount = props.totalAmount - state.paidAmount;

    if (amount < 0) {
        return parseFloat(0);
    }

    return amount;
};

const calculateAmount = (amount) => {
    if (state.amount === 0 && amount === '.') {
        state.amount = 0 + amount.toString();
        return;
    }

    if (state.amount === 0) {
        state.amount = amount.toString();
        return;
    }

    const combinedAmount = state.amount.toString() + amount.toString();
    const maxDecimalPlaces = 2;

    if (combinedAmount.includes('.') && combinedAmount.split('.')[1].length > maxDecimalPlaces) {
        return;
    }

    state.amount = combinedAmount;
};

const clear = () => {
    state.amount = 0;
};

const exactCalculations = () => {
    state.amount = getDueAmount();
};

const paidAmount = () => {
    return props.payments.reduce((accumulator, payment) => {
        return accumulator + payment.amount;
    }, 0);
};

const updatePaymentData = () => {
    const data = {
        type_id: props.paymentType.id,
        amount: parseFloat(state.amount),
        notes: state.notes,
    };

    state.amount = 0;
    emits('add-payment-type', data);
};

onUpdated(() => {
    state.paidAmount = paidAmount();

    if (props.modalShow === false) {
        document.removeEventListener('keydown', () => {});
    }
});

onMounted(() => {
    document.addEventListener('keydown', (event) => handleKeyPressed(event.key));
});

const handleKeyPressed = (key) => {
    if ((key >= '0' && key <= '9') || key === '.') {
        return calculateAmount(key);
    }

    if (key === 'Backspace') {
        if (state.amount.length === 1) {
            state.amount = 0;
            return;
        }

        const removeLastCharacter = -1;

        if (state.amount.length > 1) {
            state.amount = state.amount.slice(0, removeLastCharacter);
        }
    }
};
</script>
