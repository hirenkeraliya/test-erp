<template>
    <PageTitle title="Close Stock Transfer" />

    <InfoAlert
        color="primary"
        class="mb-3 mt-5"
    >
        If there is a positive discrepancy for the products that maintain batches, the batch numbers must be specified.
    </InfoAlert>

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Close Stock Transfer
        </h2>

        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <PrimaryButton
                text="Close Stock Transfer"
                type="submit"
                class="w-15 sm mr-1"
                @click="closeDiscrepancyStockTransfer()"
            />

            <Link :href="cancelUrl">
                <SecondaryButton
                    type="button"
                    text="Save for Later"
                    class="w-15"
                />
            </Link>
        </div>
    </div>

    <JSimpleTable
        v-if="state.stockTransferItems"
        :columns="state.columns"
        :records="state.stockTransferItems"
        :allow-search="true"
    >
        <template #product_name="data">
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

        <template #quantity="data">
            {{ data.item.quantity }}
            <br>
            {{ data.item.derivative }}
        </template>

        <template #received="data">
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
                    v-if="parseFloat(data.item.received_quantity) > parseFloat(data.item.quantity)"
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

                <span v-if="data.item.has_batch">
                    <PrimaryButton
                        v-if="data.item.discrepancy_type === discrepancyTypes.positive || data.item.is_extra_item"
                        type="button"
                        class="w-full mt-3"
                        :text="getText(data.item)"
                        @click="openExceedBatchDetailsModal(data.index)"
                    />

                    <PrimaryButton
                        v-if="parseFloat(data.item.received_quantity) < parseFloat(data.item.quantity) && !data.item.is_extra_item"
                        type="button"
                        class="w-full mt-3"
                        :text="'Specify Batch Details for shortage ' + (parseFloat(data.item.quantity) - parseFloat(data.item.received_quantity)) + ' units'"
                        @click="openShortageBatchDetailsModal(data.index)"
                    />
                </span>
            </div>
        </template>

        <template #discrepancy_proof="data">
            <div
                v-if="data.item.discrepancy_proof"
                class="ol-span-5 md:col-span-2 relative image-fit cursor-pointer w-20"
            >
                <div v-if="data.item.mime_type === mimeTypes.videoMp4 || data.item.mime_type === mimeTypes.videoMpeg || data.item.mime_type === mimeTypes.videoQuickTime">
                    <Tippy
                        content="Preview"
                        class="cursor-pointer flex justify-center"
                        @click="openVideoPlayModal(data.item.discrepancy_proof)"
                    >
                        <PlayCircle class="text-indigo-900" />
                    </Tippy>
                </div>
                <div v-else>
                    <img
                        :src="data.item.discrepancy_proof"
                        :alt="data.item.discrepancy_proof"
                        class="blur-[1px]"
                    >
                </div>

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

    <BatchDetailsModal
        v-if="state.exceedBatchDetailsIndex !== null"
        :batch-details="state.stockTransferItems[state.exceedBatchDetailsIndex].batch_details"
        :modal-show="state.displayExceedBatchDetailsModal"
        :message="getExceedDiscrepancyBatchDetailsMessage()"
        @close-modal="closeExceedBatchDetailsModal()"
        @update:batch-details="updateExceedBatchDetails"
    />

    <ShortageBatchDetailsModal
        v-if="state.shortageBatchDetailsIndex !== null"
        :batch-details="state.stockTransferItems[state.shortageBatchDetailsIndex].batch_details"
        :modal-show="state.displayShortageBatchDetailsModal"
        :message="getShortageDiscrepancyBatchDetailsMessage()"
        @close-modal="closeShortageBatchDetailsModal()"
        @update:batch-details="updateShortageBatchDetails"
    />

    <VideoPlay
        v-if="state.displayVideoPlayModal"
        :modal-show="state.displayVideoPlayModal"
        :video-url="state.videoUrl"
        @close-modal="closeModal"
    />
</template>

<script setup>
import BatchDetailsModal from '@commonComponents/BatchDetailsModal.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import ShortageBatchDetailsModal from '@commonComponents/ShortageBatchDetailsModal.vue';
import { confirmDialogBox, showErrorNotification } from '@commonServices/notifier';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Download, Info, PlayCircle } from 'lucide-vue-next';
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import VideoPlay from '@commonComponents/VideoPlay.vue';

const props = defineProps({
    stockTransferItems: {
        type: Object,
        required: true,
    },
    discrepancyTypes: {
        type: Object,
        required: true,
    },
    stockTransferId: {
        type: Number,
        required: true,
    },
    cancelUrl: {
        type: String,
        required: true,
    },
    updateReceivedQuantitiesUrl: {
        type: String,
        required: true,
    },
    closeDiscrepancyUrl: {
        type: String,
        required: true,
    },
    addDeliveryNoteItemRemarksUrl: {
        type: String,
        required: true,
    },
    mimeTypes: {
        type: Object,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'product_name',
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
            key: 'quantity',
            sortable: true
        },
        {
            key: 'received',
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

    stockTransferItems: null,
    exceedBatchDetailsIndex: null,
    displayExceedBatchDetailsModal: false,

    shortageBatchDetailsIndex: null,
    displayShortageBatchDetailsModal: false,
    displayVideoPlayModal: false,
    videoUrl: null,
});

const getStatusColor = (itemDetails) => {
    if (parseFloat(itemDetails.received_quantity) <= 0 || itemDetails.received_quantity == null) {
        return 'dark';
    }

    if (parseFloat(itemDetails.received_quantity) < parseFloat(itemDetails.quantity)) {
        return 'primary';
    }

    if (parseFloat(itemDetails.received_quantity) > parseFloat(itemDetails.quantity)) {
        return 'danger';
    }

    return 'success';
};

const getItemStatus = (itemDetails) => {
    if (parseFloat(itemDetails.received_quantity) <= 0 || itemDetails.received_quantity == null) {
        return 'Not Received';
    }

    if (parseFloat(itemDetails.received_quantity) < parseFloat(itemDetails.quantity)) {
        return 'Partially Received';
    }

    if (parseFloat(itemDetails.received_quantity) > parseFloat(itemDetails.quantity)) {
        return 'Extra Received';
    }

    return 'Received';
};

const updateDeliveryItemRemarks = (element, stockTransferItemId, $previousValue) => {
    if ($previousValue !== element.target.value) {
        axios.post(route(props.addDeliveryNoteItemRemarksUrl, stockTransferItemId), {
            remarks: element.target.value,
        });
    }
};

const updateReceivedQuantity = (element, itemDetails, itemIndex) => {
    const inputValue = element.target.value ? element.target.value : 0;
    if (inputValue === state.stockTransferItems[itemIndex].received_quantity) {
        return;
    }

    let discrepancyStatus = null;

    if (parseFloat(state.stockTransferItems[itemIndex].quantity) > parseFloat(inputValue)) {
        discrepancyStatus = props.discrepancyTypes.negative;
    }

    if (parseFloat(state.stockTransferItems[itemIndex].quantity) < parseFloat(inputValue)) {
        discrepancyStatus = props.discrepancyTypes.positive;
    }

    state.stockTransferItems[itemIndex].batch_details = [];

    axios.post(route(props.updateReceivedQuantitiesUrl, props.stockTransferId), {
        item_id: itemDetails.id,
        received_quantity: parseFloat(inputValue),
        status: discrepancyStatus
    }).then(() => {
        state.stockTransferItems[itemIndex].received_quantity = parseFloat(inputValue);
        state.stockTransferItems[itemIndex].discrepancy_type = null;
    }).catch((error) => {
        showErrorNotification(error.response.data.message);
    });
};

const closeDiscrepancyStockTransfer = () => {
    const message = 'Stock of all items will be updated accordingly and this cannot be undone. Are you sure?';

    confirmDialogBox(message, () => {
        router.put(route(props.closeDiscrepancyUrl, props.stockTransferId), {
            stock_transfer_items: getPreparedStockTransferItems()
        }, {
            onSuccess: () => router.get(props.cancelUrl)
        });
    });
};

const getPreparedStockTransferItems = () => {
    return state.stockTransferItems.map((stockTransferItem) => {
        return {
            id: stockTransferItem.id,
            batch_details: stockTransferItem.batch_details,
        };
    });
};

const getText = (item) => {
    if (item.is_extra_item) {
        return 'Specify Batch Details for extra ' + parseFloat(item.received_quantity) + ' units';
    }

    return 'Specify Batch Details for extra ' + parseFloat(item.received_quantity) - parseFloat(item.quantity) + ' units';
};

const openExceedBatchDetailsModal = (itemIndex) => {
    state.exceedBatchDetailsIndex = itemIndex;
    if (state.stockTransferItems[state.exceedBatchDetailsIndex].batch_details.length === 0) {
        if (
            state.stockTransferItems[state.exceedBatchDetailsIndex].discrepancy_type ===
            props.discrepancyTypes.positive ||
            state.stockTransferItems[state.exceedBatchDetailsIndex].is_extra_item === true
        ) {
            state.stockTransferItems[state.exceedBatchDetailsIndex].batch_details = [
                {
                    batch_number: null,
                    quantity: null,
                }
            ];
        }
    }

    state.displayExceedBatchDetailsModal = true;
};

const updateExceedBatchDetails = (batchDetails) => {
    state.stockTransferItems[state.exceedBatchDetailsIndex].batch_details = batchDetails;
};

const closeExceedBatchDetailsModal = () => {
    state.displayExceedBatchDetailsModal = false;
    state.exceedBatchDetailsIndex = null;
};

const openShortageBatchDetailsModal = (itemIndex) => {
    state.shortageBatchDetailsIndex = itemIndex;

    if (
        parseFloat(state.stockTransferItems[state.shortageBatchDetailsIndex].received_quantity) <
        parseFloat(state.stockTransferItems[state.shortageBatchDetailsIndex].quantity) &&
        state.stockTransferItems[state.shortageBatchDetailsIndex].batch_details.length === 0
    ) {
        state.stockTransferItems[state.shortageBatchDetailsIndex].batch_details = JSON.parse(JSON.stringify(state.stockTransferItems[state.shortageBatchDetailsIndex].batches));
    }

    state.displayShortageBatchDetailsModal = true;
};

const updateShortageBatchDetails = (batchDetails) => {
    state.stockTransferItems[state.shortageBatchDetailsIndex].batch_details = batchDetails;
};

const closeShortageBatchDetailsModal = () => {
    state.displayShortageBatchDetailsModal = false;
    state.shortageBatchDetailsIndex = null;
};

const getExceedDiscrepancyBatchDetailsMessage = () => {
    const stockTransferItem = state.stockTransferItems[state.exceedBatchDetailsIndex];
    const exceedQuantity = stockTransferItem.received_quantity - stockTransferItem.quantity;

    return 'You need to specify batch details for the extra ' + exceedQuantity + ' quantities only.';
};

const getShortageDiscrepancyBatchDetailsMessage = () => {
    const stockTransferItem = state.stockTransferItems[state.shortageBatchDetailsIndex];

    return 'You need to adjust the quantities as you received. The total of all the specified quantities has to be ' + stockTransferItem.received_quantity + '.';
};

const openVideoPlayModal = (data) => {
    state.displayVideoPlayModal = true;
    state.videoUrl = data;
};

const closeModal = () => {
    state.displayVideoPlayModal = false;
};

onMounted(() => {
    if (props.stockTransferItems) {
        state.stockTransferItems = props.stockTransferItems.data;
    }
});
</script>
