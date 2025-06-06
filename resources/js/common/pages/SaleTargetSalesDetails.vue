<template>
    <Modal
        size="modal-xl"
        :show="saleTargetShow"
        @hidden="hideSaleTarget"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Sale Target Sales And Sale Returns Details
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideSaleTarget"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <TabGroup class="md:ml-0 md:pl-0 mb-5">
                <TabList class="block sm:nav nav-pills bg-slate-200 rounded-md p-1 items-center">
                    <Tab
                        class="w-full py-2 px-2 leading-none active"
                        tag="button"
                    >
                        Sale
                    </Tab>
                    <Tab
                        class="w-full py-2 px-2 leading-none"
                        tag="button"
                    >
                        Sale Return
                    </Tab>
                </TabList>

                <TabPanels class="mt-5 float-clean">
                    <TabPanel class="w-full active">
                        <JSimpleTable
                            :columns="state.salesColumn"
                            :records="sales"
                            row-classes="border-b-2 border-slate-300 intro-x"
                            table-classes="table overflow-hidden border-0 border-none rounded-md mb-3"
                            :allow-search="true"
                        >
                            <template #offline_sale_id="data">
                                <div
                                    class="cursor-pointer"
                                    @click="fetchSaleLink(data.item.offline_sale_id)"
                                >
                                    {{ data.item.offline_sale_id }}
                                </div>
                            </template>
                        </JSimpleTable>
                    </TabPanel>

                    <TabPanel class="w-full leading-relaxed">
                        <JSimpleTable
                            :columns="state.saleReturnColumn"
                            :records="saleReturns"
                            row-classes="border-b-2 border-slate-300 intro-x"
                            table-classes="table overflow-hidden border-0 border-none rounded-md mb-3"
                            :allow-search="true"
                        >
                            <template #offline_sale_return_id="data">
                                <div
                                    class="cursor-pointer"
                                    @click="fetchSaleReturnLink(data.item.offline_sale_return_id)"
                                >
                                    {{ data.item.offline_sale_return_id }}
                                </div>
                            </template>
                        </JSimpleTable>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { reactive } from 'vue';
import '@left4code/tw-starter/dist/js/modal';
import { X } from 'lucide-vue-next';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import { route } from 'ziggy';
import { router } from '@inertiajs/vue3';
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@commonVendor/tab';

const props = defineProps({
    saleTargetShow: {
        type: Boolean,
        default: false,
    },
    sales: {
        type: Array,
        required: true,
    },
    saleReturns: {
        type: Array,
        required: true,
    },
    fetchSaleUrl: {
        type: String,
        required: true,
    },
    fetchSaleReturnUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    salesColumn: [
        {
            key: 'offline_sale_id',
            label: 'Receipt Number',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'location_name',
            label: 'Location Name',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'counter_name',
            label: 'Counter Name',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'amount',
            label: 'Amount',
            headerClass: 'border-0 border-none bg-slate-300 text-center',
            bodyClass: 'border-b-2 border-slate-300 text-center border-0 border-none bg-slate-200',
        }
    ],
    saleReturnColumn: [
        {
            key: 'offline_sale_return_id',
            label: 'Receipt Number',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'location_name',
            label: 'Location Name',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'counter_name',
            label: 'Counter Name',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
            bodyClass: 'border-b-2 border-slate-300 text-left border-0 border-none bg-slate-200',
        }, {
            key: 'amount',
            label: 'Amount',
            headerClass: 'border-0 border-none bg-slate-300 text-center',
            bodyClass: 'border-b-2 border-slate-300 text-center border-0 border-none bg-slate-200',
        }
    ],
});

const emits = defineEmits([
    'update:hide-sale-target-modal',
]);

const hideSaleTarget = () => {
    emits('update:hide-sale-target-modal', false);
};

const fetchSaleReturnLink = (receiptNumber) => {
    hideSaleTarget();

    router.get(route(props.fetchSaleReturnUrl, {offline_sale_return_id : receiptNumber}));
};
const fetchSaleLink = (receiptNumber) => {
    hideSaleTarget();

    router.get(route(props.fetchSaleUrl, {offline_sale_id : receiptNumber}));
};
</script>
