<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Closed Counter Details
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
            <TabGroup>
                <TabListClosedCounter />

                <TabPanels class="mt-3 overflow-x-auto">
                    <TabPanel class="active">
                        <DenominationsCloseCounterDetail
                            :counter-details="counterClosingDetails"
                        />
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
                            :display-total="true"
                        />
                    </TabPanel>

                    <TabPanel
                        class="leading-relaxed"
                    >
                        <CounterCashTransactionsSummary
                            :counter-details="counterClosingDetails"
                            :display-total="true"
                        />
                    </TabPanel>

                    <TabPanel class="leading-relaxed">
                        <CounterAttemptDetail
                            v-if="Object.keys(counterClosingDetails).length !== 0"
                            :counter-details="counterClosingDetails"
                            :counter-update-id="closingCounterId"
                            print-counter-update-attempt-url="store_manager.closed_counters.export_closed_counter_attempts"
                        />
                    </TabPanel>

                    <TabPanel class="leading-relaxed">
                        <CounterTillDetail
                            v-if="Object.keys(counterClosingDetails).length !== 0"
                            :counter-details="counterClosingDetails"
                            :counter-update-id="closingCounterId"
                            print-counter-update-till-url="store_manager.closed_counters.export_closed_counter_tills"
                            print-take-break-url="store_manager.closed_counters.export_take_break"
                            print-drawer-detail-url="store_manager.closed_counters.export_drawer_details"
                            print-track-offline-mode-url="store_manager.closed_counters.export_track_offline_mode"
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
import { TabPanel, TabGroup, TabPanels } from '@commonVendor/tab';
import CounterPaymentsTable from '@commonComponents/CounterPaymentsTable.vue';
import CounterCashTransactionsSummary from '@commonComponents/CounterCashTransactionsSummary.vue';
import CounterSalesSummary from '@commonComponents/CounterSalesSummary.vue';
import TabListClosedCounter from '@commonComponents/TabListClosedCounter.vue';
import DenominationsCloseCounterDetail from '@commonComponents/DenominationsCloseCounterDetail.vue';
import CounterTillDetail from '@commonComponents/CounterTillDetail.vue';
import CounterAttemptDetail from '@commonComponents/CounterAttemptDetail.vue';

defineProps({
    counterClosingDetails: {
        type: Object,
        required: true,
    },
    closingCounterId: {
        type: Number,
        default: null,
    },
    modalShow: {
        type: Boolean,
        default: false,
    },
});

const emits = defineEmits([
    'close-modal',
]);

const closeModal = () => {
    emits('close-modal');
};
</script>
