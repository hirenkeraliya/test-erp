<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                {{ counterClosingDetails.closed_at === 'N/A' ? 'Close Counter' : 'Counter Closed' }}
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
            class="p-5 sm:p-10 text-left"
        >
            <TabGroup>
                <TabListClosedCounter />

                <TabPanels class="mt-3 overflow-x-auto">
                    <TabPanel
                        class="leading-relaxed active"
                    >
                        <div v-if="counterClosingDetails.closed_at === 'N/A'">
                            <div class="pl-3">
                                <FormInput
                                    :input-value="state.closingBalance"
                                    input-label="Closing Balance"
                                    :required="true"
                                    validation-field-name="closing_balance"
                                    @update:input-value="updateClosingBalance($event)"
                                />

                                <FormInput
                                    v-if="parseFloat(state.expectedClosingAmount) !== parseFloat(state.closingBalance)"
                                    v-model:inputValue="state.counterCloseReason"
                                    input-label="Reason"
                                    :required="true"
                                    validation-field-name="reason"
                                />
                            </div>

                            <div
                                class="w-full md:w-3/4 p-3"
                            >
                                <div v-if="props.counterClosingDetails.denominations.length <= 0">
                                    <InfoAlert
                                        color="primary"
                                        class="mb-3 mt-5"
                                    >
                                        No denominations were found. Please contact admin to configure denominations.
                                    </InfoAlert>
                                </div>

                                <div
                                    v-for="(denominationData, index) in state.denominations"
                                    :key="index"
                                    class="grid grid-cols-12 gap-0 sm:gap-6"
                                >
                                    <div class="col-span-12 2xl:col-span-4 xl:col-span-6 lg:col-span-6 md:col-span-12 sm:col-span-12">
                                        <label
                                            for="denomination-display"
                                            class="mt-3"
                                        >
                                            Denomination
                                        </label>

                                        <p class="bg-gray-300 border rounded p-2">
                                            {{ displayAmountWithCurrencySymbol(denominationData.denomination) }}
                                        </p>
                                    </div>

                                    <div class="col-span-12 2xl:col-span-4 xl:col-span-6 lg:col-span-6 md:col-span-12 sm:col-span-12">
                                        <FormInput
                                            v-model:input-value="denominationData.quantity"
                                            type="number"
                                            input-name="quantity"
                                            class="mr-1"
                                            input-label="Quantity"
                                            :validation-field-name="'denominations.' + index + '.quantity'"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="counterClosingDetails.closed_at === 'N/A'"
                                class="text-left p-3"
                            >
                                <OutlinePrimaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-14 sm:w-24 mr-2"
                                    @click="closeModal()"
                                />

                                <PrimaryButton
                                    type="button"
                                    text="Close Counter"
                                    class="w-32"
                                    @click="closeCounter()"
                                />
                            </div>
                        </div>

                        <div v-else>
                            <DenominationsCloseCounterDetail
                                :counter-details="counterClosingDetails"
                            />
                        </div>
                    </TabPanel>

                    <TabPanel
                        class="leading-relaxed"
                    >
                        <CounterSalesSummary
                            :counter-details="counterClosingDetails"
                        />
                    </TabPanel>

                    <TabPanel
                        class="leading-relaxed"
                    >
                        <CounterPaymentsTable
                            :counter-details="counterClosingDetails"
                        />
                    </TabPanel>

                    <TabPanel
                        class="leading-relaxed"
                    >
                        <CounterCashTransactionsSummary
                            :counter-details="counterClosingDetails"
                        />
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </ModalBody>
    </Modal>
</template>
<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { X } from 'lucide-vue-next';
import { TabPanel, TabGroup, TabPanels } from '@commonVendor/tab';
import CounterPaymentsTable from '@commonComponents/CounterPaymentsTable.vue';
import CounterCashTransactionsSummary from '@storeManagerPages/reports/day_close/partials/CounterCashTransactionsSummary.vue';
import CounterSalesSummary from '@commonComponents/CounterSalesSummary.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { onUpdated, reactive } from 'vue';
import { showErrorNotification } from '@commonServices/notifier';
import { route } from 'ziggy';
import { router } from '@inertiajs/vue3';
import TabListClosedCounter from '@commonComponents/TabListClosedCounter.vue';
import DenominationsCloseCounterDetail from '@commonComponents/DenominationsCloseCounterDetail.vue';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import InfoAlert from '@commonComponents/InfoAlert.vue';

const props = defineProps({
    counterClosingDetails: {
        type: Object,
        required: true,
    },
    modalShow: {
        type: Boolean,
        default: false,
    },
    counterUpdateId: {
        type: Number,
        default: null,
    },
});

const state = reactive({
    denominations: [],

    closingBalance: 0,
    expectedClosingAmount: 0,
    counterCloseReason: null,
});

const emits = defineEmits([
    'close-modal',
    'refresh-table',
]);

const closeModal = () => {
    emits('close-modal');
};

const closeCounter = () => {
    if (state.expectedClosingAmount !== state.closingBalance && !state.counterCloseReason) {
        showErrorNotification('Reason is required when the closing amount does not match.');
        return;
    }

    const params = {
        closing_balance: state.closingBalance,
        mismatch_amount_reason: state.counterCloseReason,
        denominations: state.denominations,
    };

    const refreshDelay = 1000;

    router.post(route('store_manager.day_close_counters.close_counter', props.counterUpdateId),
        params,
        {
            onSuccess: (page) => {
                if (page.props.flash.error) {
                    return;
                }

                closeModal();

                setTimeout(() => {
                    emits('refresh-table');
                }, refreshDelay);
            },
        }
    );
};

const updateClosingBalance = (closingAmount) => {
    state.closingBalance = closingAmount || 0;

    if (parseFloat(state.closingBalance) === 0) {
        state.denominations = [];
    }
};

onUpdated(() => {
    if (props.counterClosingDetails) {
        state.closingBalance = parseFloat(props.counterClosingDetails.closing_balance);
        state.expectedClosingAmount = parseFloat(props.counterClosingDetails.closing_balance);
        state.denominations = props.counterClosingDetails.denominations;
        state.updatedDenominations = [];

        if (parseFloat(props.counterClosingDetails.closing_balance) <= 0) {
            state.closingBalance = parseFloat(props.counterClosingDetails.opening_balance);
            state.expectedClosingAmount = parseFloat(props.counterClosingDetails.opening_balance);
        }
    }
});
</script>
