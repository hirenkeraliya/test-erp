<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Order Picking List Details
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
                <TabListOrderPicking />
                <TabPanels class="mt-3 overflow-x-auto">
                    <TabPanel class="active">
                        <OrderListDetail
                            :orders="orders"
                        />
                    </TabPanel>
                    <TabPanel
                        class="leading-relaxed"
                    >
                        <OrderListItemDetail
                            :order-items="orderItems"
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
import TabListOrderPicking from '@commonComponents/TabListOrderPicking.vue';
import OrderListDetail from '@commonComponents/OrderListDetail.vue';
import OrderListItemDetail from '@commonComponents/OrderListItemDetail.vue';

defineProps({
    orders: {
        type: Object,
        required: true,
    },

    orderItems: {
        type: Object,
        required: true,
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
