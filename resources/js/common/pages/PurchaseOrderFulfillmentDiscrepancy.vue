<template>
    <PageTitle title="Close Delivery Order" />

    <InfoAlert
        color="primary"
        class="mb-3 mt-5"
    >
        If there is a positive discrepancy for the products that maintain batches, the batch numbers must be specified.
    </InfoAlert>

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Close Delivery Order
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <OutlinePrimaryButton
                :disabled="isReceivedQuantitiesMissing"
                text="Close Delivery Order"
                type="submit"
                class="w-15 sm mr-1"
                @click="closeDiscrepancyDeliveryOrder()"
            />

            <Link :href="deliveryOrderUrl">
                <SecondaryButton
                    type="button"
                    text="Cancel"
                    class="w-15"
                />
            </Link>
        </div>
    </div>

    <JSimpleTable
        v-if="state.transferItems"
        v-model:columns="state.dynamicColumns"
        :records="state.transferItems"
        :allow-search="true"
    >
        <template #product="data">
            <span
                class="flex"
            >
                {{ data.item.product }}
                <Tippy
                    v-if="data.item.is_extra_item"
                    content="This is an additionally received item which wasn't ordered initially."
                    class="flex"
                >
                    <Info class="ml-3 text-red-400" />
                </Tippy>
            </span>
        </template>

        <template
            v-if="pageProps.product_variant"
            #product_variant_values="data"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in data.item.product_variant_values"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>

        <template #received_quantity="data">
            <input
                type="number"
                class="form-control"
                :value="data.item.received_quantity"
                @blur="updateReceivedQuantity($event, data.item, data.index)"
            >
        </template>

        <template #status="data">
            <div class="flex flex-col">
                <span
                    v-if="parseFloat(data.item.received_quantity) > parseFloat(data.item.transfer_quantity)"
                    class="mb-3"
                >
                    <Tippy
                        tag="label"
                        class="cursor-pointer select-none"
                        content="System will transfer the extra quantity from the source location inventory to the destination location inventory."
                    >
                        Keep
                        <Info
                            class="text-cyan-400 inline-block mr-2"
                            :size="15"
                        />
                    </Tippy>
                </span>

                <JBadge
                    class="w-1/2"
                    :type="getStatusColor(data.item)"
                    :label="getItemStatus(data.item)"
                />

                <div class="mt-4">
                    <PrimaryButton
                        v-if="data.item.has_batch && parseFloat(data.item.received_quantity) !== 0.00"
                        type="button"
                        class="w-full mt-3"
                        text="Specify Batch Details"
                        @click="openBatchDetailsModal(data.index, data.item.id)"
                    />
                </div>
            </div>
        </template>

        <template #discrepancy_proof="data">
            <div
                v-if="data.item.discrepancy_proof"
                class="ol-span-5 md:col-span-2 relative image-fit cursor-pointer w-20"
            >
                <img
                    :src="data.item.discrepancy_proof"
                    :alt="data.item.discrepancy_proof"
                    class="blur-[1px]"
                >

                <Tippy
                    tag="a"
                    content="Download the image"
                    class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger right-0 top-0 -mr-2 -mt-2"
                    :href="data.item.discrepancy_proof"
                    download
                >
                    <Download class="w-4 h-4" />
                </Tippy>
            </div>

            <div v-else>
                N/A
            </div>
        </template>

        <template #remarks="data">
            <div class="flex items-center">
                <Tippy
                    v-if="data.item.remarks"
                    tag="label"
                    :content="'Initial Remarks: ' + data.item.remarks"
                >
                    <Info
                        class="text-cyan-400 mr-2"
                        :size="15"
                    />
                </Tippy>

                <textarea
                    type="text"
                    class="form-control"
                    :value="data.item.delivery_remarks"
                    @blur="updateDeliveryItemRemarks($event, data.item.id, data.item.delivery_remarks)"
                />
            </div>
        </template>
    </JSimpleTable>

    <div>
        <Modal
            v-if="state.batchDetailsIndex !== null"
            size="modal-xl"
            :show="state.displayBatchDetailsModal"
            @hidden="closeBatchDetailsModal"
        >
            <ModalHeader>
                <h2 class="font-medium text-base mr-auto pr-8">
                    Batch Details
                </h2>

                <a
                    class="absolute right-0 top-0 mt-2 mr-3"
                    href="javascript:;"
                    @click="closeBatchDetailsModal"
                >
                    <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
                </a>
            </ModalHeader>

            <ModalBody class="p-5 sm:p-10">
                <div
                    v-for="(batchData, index) in state.batchDetails"
                    :key="'batch-details-' + index"
                    class="grid grid-cols-12 gap-0 sm:gap-6 mb-3"
                >
                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="batchData.batch_number"
                            :readonly="batchData.batch_number !== null && !batchData.is_extra"
                            type="text"
                            input-label="Batch Number"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="batchData.quantity"
                            type="number"
                            input-label="Quantity"
                            :readonly="true"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <FormInput
                            v-model:input-value="batchData.received_quantity"
                            type="number"
                            input-label="Received Quantity"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <DeleteButton
                            v-if="(batchData.is_discrepancy || batchData.is_extra) && parseFloat(batchData.quantity) === 0.00"
                            class="mt-2 sm:mt-0 md:mt-0 lg:mt-8 xl:mt-8 w-12 h-8"
                            :disabled="state.batchDetails.length <= 1"
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
                        @click="closeBatchDetailsModal"
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
    </div>
</template>

<script setup>
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import { confirmDialogBoxWithCenterText, showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Download, Info, X } from 'lucide-vue-next';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import FormInput from '@commonComponents/FormInput.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    transferItems: {
        type: Object,
        required: true,
    },
    discrepancyTypes: {
        type: Object,
        required: true,
    },
    purchaseOrderFulfillmentId: {
        type: Number,
        required: true,
    },
    deliveryOrderUrl: {
        type: String,
        required: true,
    },
    purchaseOrderFulfillmentDeliveryNoteItemRemarksUrl: {
        type: String,
        required: true,
    },
    updateReceivedQuantitiesUrl: {
        type: String,
        required: true,
    },
    closedDiscrepancyUrl: {
        type: String,
        required: true,
    },
    updateDiscrepancyBatchDetailsUrl: {
        type: String,
        required: true,
    },
    deleteBatchDetailUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'product',
            sortable: true
        },
        {
            key: 'color',
            sortable: true
        },
        {
            key: 'size',
            sortable: true
        },
        {
            key: 'product_variant_values',
            label: 'Attributes',
        },
        {
            key: 'transfer_quantity',
            sortable: true
        },
        {
            key: 'received_quantity',
            sortable: true
        },
        {
            key: 'status',
            sortable: true
        },
        {
            key: 'discrepancy_proof',
            sortable: true
        },
        {
            key: 'remarks',
            sortable: true
        },
    ],

    transferItems: null,
    exceedBatchDetailsIndex: null,
    displayExceedBatchDetailsModal: false,

    shortageBatchDetailsIndex: null,
    displayShortageBatchDetailsModal: false,
    batchDetailsIndex: null,
    displayBatchDetailsModal: false,
    purchaseOrderFulfillmentItemId: null,
    dynamicColumns: [],
});

const getStatusColor = (itemDetails) => {
    if (parseFloat(itemDetails.received_quantity) <= 0 || itemDetails.received_quantity == null) {
        return 'dark';
    }

    if (parseFloat(itemDetails.received_quantity) < parseFloat(itemDetails.transfer_quantity)) {
        return 'primary';
    }

    if (parseFloat(itemDetails.received_quantity) > parseFloat(itemDetails.transfer_quantity)) {
        return 'danger';
    }

    return 'success';
};

const getItemStatus = (itemDetails) => {
    if (parseFloat(itemDetails.received_quantity) <= 0 || itemDetails.received_quantity == null) {
        return 'Not Received';
    }

    if (itemDetails.batches.length !== 0 && checkBatchQuantity(itemDetails)) {
        return 'Batch Discrepancy';
    }

    if (parseFloat(itemDetails.received_quantity) < parseFloat(itemDetails.transfer_quantity)) {
        return 'Partially Received';
    }

    if (parseFloat(itemDetails.received_quantity) > parseFloat(itemDetails.transfer_quantity)) {
        return 'Extra Received';
    }

    return 'Received';
};

const checkBatchQuantity = (itemDetails) => {
    const partialReceived = parseFloat(itemDetails.received_quantity);
    const batchDetails = itemDetails.batches;

    const totalBatchQuantity = parseFloat(batchDetails.reduce((sum, item) => sum + parseFloat(item.received_quantity), 0));

    return isBatchDiscrepancy(batchDetails) && partialReceived === totalBatchQuantity;
};

const isBatchDiscrepancy = (batchDetails) => {
    for (const batchDetail of batchDetails) {
        if (batchDetail.received_quantity !== batchDetail.quantity) {
            return true;
        }
    }
    return false;
};

const updateDeliveryItemRemarks = (element, purchaseOrderFulfillmentItemId, $previousValue) => {
    if ($previousValue !== element.target.value) {
        axios.post(route(props.purchaseOrderFulfillmentDeliveryNoteItemRemarksUrl, purchaseOrderFulfillmentItemId), {
            remarks: element.target.value,
        });
    }
};

const updateReceivedQuantity = (element, itemDetails, itemIndex) => {
    const inputValue = element.target.value && element.target.value > 0 ? element.target.value : 0;
    if (inputValue === state.transferItems[itemIndex].received_quantity) {
        return;
    }

    let discrepancyStatus = null;

    if (parseFloat(state.transferItems[itemIndex].transfer_quantity) > parseFloat(inputValue)) {
        discrepancyStatus = props.discrepancyTypes.negative;
    }

    if (parseFloat(state.transferItems[itemIndex].transfer_quantity) < parseFloat(inputValue)) {
        discrepancyStatus = props.discrepancyTypes.positive;
    }

    axios.post(route(props.updateReceivedQuantitiesUrl, props.purchaseOrderFulfillmentId), {
        item_id: itemDetails.id,
        received_quantity: parseFloat(inputValue),
        status: discrepancyStatus
    }).then(() => {
        state.transferItems[itemIndex].received_quantity = parseFloat(inputValue);
        state.transferItems[itemIndex].discrepancy_type = null;
    }).catch((error) => {
        showErrorNotification(error.response.data.message);
    });
};

const closeDiscrepancyDeliveryOrder = () => {
    if (isReceivedQuantitiesMissing.value) {
        return;
    }

    const message = 'Stock of all items will be updated accordingly and this cannot be undone. Are you sure?';

    confirmDialogBoxWithCenterText(message, () => {
        router.put(route(props.closedDiscrepancyUrl, props.purchaseOrderFulfillmentId), {
            transfer_items: getPreparedTransferItems()
        }, {
            onSuccess: () => router.get(props.deliveryOrderUrl)
        });
    });
};

const isReceivedQuantitiesMissing = computed(() => {
    for (const key in state.transferItems) {
        const receivedQuantity = state.transferItems[key].received_quantity;
        if (receivedQuantity == null ||
            receivedQuantity < 0
        ) {
            return true;
        }

        const batchQuantity = state.transferItems[key].batches.reduce((sum, item) => sum + parseFloat(item.received_quantity), 0);
        if (state.transferItems[key].has_batch && parseFloat(receivedQuantity) !== batchQuantity) {
            return true;
        }
    }
    return false;
});

const getPreparedTransferItems = () => {
    return state.transferItems.map((transferItem) => {
        return {
            id: transferItem.id,
            batch_details: transferItem.batch_details,
        };
    });
};

const getFilteredColumns = () => {
    const columns = state.columns || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['color', 'size'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'product_variant_values');
};

onMounted(() => {
    if (props.transferItems) {
        state.transferItems = props.transferItems.data;
    }
    state.dynamicColumns = getFilteredColumns();
});

const openBatchDetailsModal = (itemIndex, purchaseOrderFulfillmentItemId) => {
    state.batchDetailsIndex = itemIndex;
    state.purchaseOrderFulfillmentItemId = purchaseOrderFulfillmentItemId;

    if (
        state.transferItems[state.batchDetailsIndex].batch_details.length === 0
    ) {
        state.transferItems[state.batchDetailsIndex].batch_details = JSON.parse(JSON.stringify(state.transferItems[state.batchDetailsIndex].batches));
        state.batchDetails = state.transferItems[state.batchDetailsIndex].batch_details;
    }

    state.displayBatchDetailsModal = true;
};

const closeBatchDetailsModal = () => {
    state.displayBatchDetailsModal = false;
    state.batchDetailsIndex = null;
    state.purchaseOrderFulfillmentItemId = null;
};

const addNewBatchDetails = () => {
    state.batchDetails.push({
        batch_number: null,
        is_extra: true,
        quantity: 0,
        received_quantity: 0,
    });
};

const removeBatchDetailsOf = (index) => {
    axios.post(route(props.deleteBatchDetailUrl, state.purchaseOrderFulfillmentItemId), {
        batch_number: state.batchDetails[index].batch_number,
    });

    state.batchDetails.splice(index, 1);
};

const updateBatchDetails = () => {
    state.isBatchError = false;
    state.batchDetails.forEach(item => {
        if (item.quantity < 0) {
            showErrorNotification('Quantity is required.');
            state.isBatchError = true;
            return;
        }

        if (item.batch_number === null && item.batch_number === '') {
            showErrorNotification('Batch Number is required.');
            state.isBatchError = true;
        }
    });

    const batchDetailsQuantitySum = state.batchDetails.reduce((sum, item) => sum + parseFloat(item.received_quantity), 0);
    const itemQuantitySum = parseFloat(state.transferItems[state.batchDetailsIndex].received_quantity);

    if (batchDetailsQuantitySum !== itemQuantitySum) {
        state.isBatchError = true;
        showErrorNotification('Please specify the quantity accurately, as there seems to be a discrepancy.');
        return;
    }

    if (state.isBatchError) {
        return;
    }

    axios.post(route(props.updateDiscrepancyBatchDetailsUrl, state.purchaseOrderFulfillmentItemId), {
        batch_details: state.batchDetails,
        discrepancy_status: props.discrepancyTypes.batch_discrepancy
    }).then(() => {
        showSuccessNotification('Batch Details Update Successfully.');
        state.transferItems[state.batchDetailsIndex].batch_details = JSON.parse(JSON.stringify(state.batchDetails));
        closeBatchDetailsModal();
    }).catch((error) => {
        if (error.response.data.message) {
            showErrorNotification(error.response.data.message);
        }
    });
};
</script>
