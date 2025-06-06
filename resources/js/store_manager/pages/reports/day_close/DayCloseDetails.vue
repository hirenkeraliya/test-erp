<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Day Close Details
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
            <TabGroup>
                <TabList class="block sm:nav nav-pills bg-slate-200 rounded-md p-1">
                    <Tab
                        class="w-full py-2 active"
                        tag="button"
                    >
                        Sales Details
                    </Tab>
                    <Tab
                        class="w-full py-2"
                        tag="button"
                    >
                        Payment Details
                    </Tab>
                    <Tab
                        class="w-full py-2"
                        tag="button"
                    >
                        Cash Transaction Details
                    </Tab>
                    <Tab
                        class="w-full py-2"
                        tag="button"
                    >
                        Order Details
                    </Tab>
                    <Tab
                        class="w-full py-2"
                        tag="button"
                    >
                        Order Payments
                    </Tab>
                </TabList>

                <TabPanels class="mt-3 overflow-x-auto">
                    <TabPanel
                        class="leading-relaxed active"
                    >
                        <CounterSalesSummary
                            :counter-details="dayClose"
                        />
                    </TabPanel>

                    <TabPanel
                        class="leading-relaxed"
                    >
                        <CounterPaymentsTable
                            :counter-details="dayClose"
                        />
                    </TabPanel>

                    <TabPanel
                        class="leading-relaxed"
                    >
                        <CounterCashTransactionsSummary
                            :day-close="dayClose"
                        />
                    </TabPanel>

                    <TabPanel
                        class="leading-relaxed"
                    >
                        <OrdersSummary
                            :counter-details="dayClose"
                        />
                    </TabPanel>

                    <TabPanel
                        class="leading-relaxed"
                    >
                        <OrderPaymentsTable
                            :counter-details="dayClose"
                        />
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X } from 'lucide-vue-next';
import { TabPanel, TabGroup, TabPanels, Tab, TabList } from '@commonVendor/tab';
import CounterPaymentsTable from '@commonComponents/CounterPaymentsTable.vue';
import CounterCashTransactionsSummary from '@commonComponents/DayCloseCounterCashTransactionsSummary.vue';
import CounterSalesSummary from '@commonComponents/CounterSalesSummary.vue';
import OrdersSummary from '@commonComponents/OrdersSummary.vue';
import OrderPaymentsTable from '@commonComponents/OrderPaymentsTable.vue';

defineProps({
    modalShow: {
        type: Boolean,
        default: false,
    },
    dayClose: {
        type: Object,
        required: true,
    },
});

const emits = defineEmits(['close-modal']);

const closeModal = () => {
    emits('close-modal');
};
</script>
