<template>
    <PageTitle title="Stock Transfer Delivery Notes" />

    <div
        v-if="!state.display_add_items"
    >
        <InfoAlert
            color="primary"
            class="mb-3 mt-5"
        >
            The changes of this page are auto-saved i.e. As soon as you change a value in any of the fields, the new values are saved automatically.
        </InfoAlert>

        <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
            <h2 class="text-lg font-medium mr-auto">
                Delivery Notes
            </h2>

            <div class="w-full sm:w-auto block md:flex mt-4 sm:mt-0">
                <PrimaryButton
                    text="Set 'Received' quantity same as 'Quantity'"
                    type="button"
                    class="w-15 sm mr-1 mb-2 md:mb-0"
                    @click="setReceivedQuantitySameAsQuantities()"
                />

                <OutlinePrimaryButton
                    :disabled="isReceivedQuantitiesMissing"
                    :text="hasDiscrepancy ? 'Discrepancy' : 'Close'"
                    type="submit"
                    class="w-15 sm mr-1 mb-2 md:mb-0"
                    @click="completeStockTransfer()"
                />

                <PrimaryButton
                    text="Received Extra Items?"
                    type="button"
                    class="w-15 sm mr-1 mb-2 md:mb-0"
                    @click="displayAddItems()"
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
            v-if="state.dynamicColumns.length > 0"
            v-model:columns="state.dynamicColumns"
            :records="state.stockTransferItems"
            :allow-search="true"
        >
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

            <template
                v-if="!pageProps.product_variant"
                #color="data"
            >
                {{ data.item.color }}
            </template>

            <template
                v-if="!pageProps.product_variant"
                #size="data"
            >
                {{ data.item.size }}
            </template>

            <template #product_name="data">
                <span
                    v-if="data.item.is_extra_item"
                    class="flex"
                >
                    <Tippy
                        content="This is an additionally received item which wasn't ordered initially."
                        class="flex"
                    >
                        {{ data.item.product_name }}
                        <Info class="ml-3 text-red-400" />
                    </Tippy>

                    <DeleteButton
                        type="button"
                        class="w-12 h-8 text-red-500 ml-3"
                        @click="removeAdditionalItem(data.item.id, data.index)"
                    />
                </span>
            </template>

            <template #quantity="data">
                {{ data.item.quantity }}
                <br>
                {{ data.item.derivative }}
            </template>

            <template #received="data">
                <span v-if="data.item.is_extra_item">
                    {{ data.item.received_quantity }}
                    <br>
                    {{ data.item.derivative }}
                </span>

                <input
                    v-else
                    type="number"
                    min="0.01"
                    class="form-control"
                    :value="data.item.received_quantity"
                    @blur="updateReceivedQuantity($event, data.item, data.index)"
                >
            </template>

            <template #status="data">
                <JBadge
                    :type="getStatusColor(data.item)"
                    :label="getItemStatus(data.item)"
                />
            </template>

            <template #discrepancy_proof="data">
                <div
                    v-if="(parseFloat(data.item.received_quantity) !== parseFloat(data.item.quantity)) &&
                        ! data.item.discrepancy_proof
                    "
                    class="flex flex-col sm:flex-row -mx-3"
                >
                    <div class="w-full md:w-1/2 px-3">
                        <JFileUpload
                            accept="image/*,video/*"
                            validation-field-name="discrepancy_proof"
                            @update:input-file="uploadDiscrepancyProof($event, data.item.stock_transfer_id, data.item.id, data.index)"
                        />
                    </div>
                </div>

                <div
                    v-if="(parseFloat(data.item.received_quantity) === parseFloat(data.item.quantity))"
                    class="inline-flex items-center justify-center py-1 mr-2 font-bold leading-none alert-success-soft rounded px-2 mt-1.5 w-1/2"
                >
                    Not Required
                </div>
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
                        tag="div"
                        content="Remove Uploaded image or video ?"
                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger right-0 top-0 -mr-2 -mt-2"
                        @click="removeDiscrepancyProof(data.item.id, data.index)"
                    >
                        <X class="w-4 h-4" />
                    </Tippy>

                    <Tippy
                        tag="a"
                        content="Download uploaded image or video"
                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-primary right-0 top-0 -mr-2 mt-5"
                        :href="data.item.discrepancy_proof"
                        download
                    >
                        <Download class="w-4 h-4" />
                    </Tippy>
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
    </div>

    <div
        v-if="state.display_add_items"
    >
        <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
            <h2 class="text-lg font-medium mr-auto">
                Add Additional Received Items
            </h2>
        </div>

        <div class="grid grid-cols-12 gap-6 mt-5">
            <div class="intro-y col-span-12 lg:col-span-12">
                <div class="intro-y box">
                    <div class="p-5">
                        <div class="overflow-unset overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap w-4/12">
                                            Product Selection
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Stocks
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Received Quantity
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Package Type
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Package Type Quantity
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Total Quantity Inside Package Type
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Remarks
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(item, itemIndex) in state.additional_items"
                                        :key="'stock-transfer-item-' + itemIndex"
                                    >
                                        <td class="whitespace-nowrap w-4/12">
                                            <div class="mt-6">
                                                <JProductFilter
                                                    :product-search-url="productSearchUrl"
                                                    :get-product-url-name="getProductUrlName"
                                                    :selected-product-id="state.additional_items[itemIndex].product_id"
                                                    :validation-field-name="'transfer_items.' + itemIndex + '.product_id'"
                                                    @update:product-selected="productSelected($event, itemIndex)"
                                                    @update:display-product-filters="displayUpdateFilter(itemIndex)"
                                                />
                                            </div>

                                            <div class="flex flex-wrap flex-row justify-start">
                                                <strong
                                                    v-if="! pageProps.product_variant"
                                                >
                                                    Color: {{ state.additional_items[itemIndex].product_color }}
                                                </strong>

                                                <strong 
                                                    v-if="! pageProps.product_variant"
                                                    class="pl-4"
                                                >
                                                    Size: {{ state.additional_items[itemIndex].product_size }}
                                                </strong>

                                                <strong 
                                                    v-if="pageProps.product_variant"
                                                    class="pl-4"
                                                >
                                                    <p
                                                        v-for="(product_variant, index) in state.additional_items[itemIndex].product_variant_values"
                                                        :key="index"
                                                        class="pl-4"
                                                    >
                                                        {{ product_variant.attribute.name }} : {{ product_variant.value }}
                                                    </p>
                                                </strong>

                                                <strong class="pl-4">
                                                    UOM: {{ state.additional_items[itemIndex].product_uom }}
                                                </strong>
                                            </div>
                                        </td>

                                        <td class="mt-10 whitespace-nowrap">
                                            <span v-if="state.additional_items[itemIndex].product_id">
                                                <span class="text-lg font-bold">Old:</span><br>
                                                From:
                                                <span class="font-medium">
                                                    {{ parseFloat(item.source_stock) }}
                                                    {{ state.additional_items[itemIndex].product_uom }}

                                                    <Tippy
                                                        tag="label"
                                                        :content="'Reserved Stocks: ' + item.source_reserved_stock"
                                                    >
                                                        <Info
                                                            class="text-cyan-400 inline-block"
                                                            :size="15"
                                                        />
                                                    </Tippy>
                                                </span>

                                                <br>

                                                To:
                                                <span class="font-medium">
                                                    {{ parseFloat(item.destination_stock) }}

                                                    {{ state.additional_items[itemIndex].product_uom }}
                                                    <Tippy
                                                        tag="label"
                                                        :content="'Reserved Stocks: ' + item.destination_reserved_stock"
                                                    >
                                                        <Info
                                                            class="text-cyan-400 inline-block"
                                                            :size="15"
                                                        />
                                                    </Tippy>
                                                </span><br><br>

                                                <span class="text-lg font-bold">New:</span><br>
                                                From:
                                                <span class="font-medium">
                                                    {{
                                                        calculateNewSourceStock(
                                                            item.source_stock,
                                                            item.quantity,
                                                            item.derivative
                                                        )
                                                    }}
                                                </span>

                                                <br>

                                                To:
                                                <span class="font-medium">
                                                    {{
                                                        calculateNewDestinationStock(
                                                            item.quantity,
                                                            item.destination_stock,
                                                            item.derivative
                                                        )
                                                    }}
                                                </span>
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <span v-if="state.additional_items[itemIndex].product_id">
                                                <input
                                                    type="text"
                                                    class="w-24 text-center form-control"
                                                    :value="item.quantity"
                                                    step="any"
                                                    @input="updateTransferStock($event, itemIndex, item.source_stock, item.unit_of_measure_derivative_id, item.derivative)"
                                                >

                                                <ValidationError :validation-field-name="'additional_items.' + itemIndex + '.quantity'" />

                                                <br><br>

                                                <FormSelectBox
                                                    v-if="item.derivatives && parseFloat(item.source_stock) > 0"
                                                    :selected-record="item.unit_of_measure_derivative_id"
                                                    :records="item.derivatives"
                                                    :display-label="false"
                                                    placeholder="Select derivative"
                                                    :validation-field-name="'transfer_items.' + itemIndex + '.unit_of_measure_derivative_id'"
                                                    class="mt-[0px] w-[200px]"
                                                    @update:selected-record="updateUnitOfMeasureDerivativeId($event, itemIndex, item.derivatives)"
                                                />

                                                <div
                                                    v-if="item.unit_of_measure_derivative_id"
                                                    class="mt-2 text-lg font-bold"
                                                >
                                                    {{ parseFloat(item.quantity) / parseFloat(item.derivative.ratio) }}

                                                    {{ state.additional_items[itemIndex].product_uom }}
                                                </div>
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <span v-if="state.additional_items[itemIndex].product_id">
                                                <FormSelectBox
                                                    :records="packageTypes"
                                                    :display-label="false"
                                                    class="mt-[0]"
                                                    input-label="Type"
                                                    validation-field-name="unit_of_measure_id"
                                                    :selected-record="item.package_type_id"
                                                    @update:selected-record="updatePackageTypeId($event, itemIndex)"
                                                />
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <span v-if="state.additional_items[itemIndex].product_id">
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    :value="item.package_quantity"
                                                    @input="updatePackageQuantity($event, itemIndex)"
                                                >
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <span v-if="state.additional_items[itemIndex].product_id">
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    :value="item.package_total_quantity"
                                                    @blur="updatePackageTotalQuantity($event, itemIndex)"
                                                >
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <span v-if="state.additional_items[itemIndex].product_id">
                                                <FormTextarea
                                                    :input-value="item.remarks"
                                                    placeholder="Enter Remarks"
                                                    input-name="remarks"
                                                    class="mt-[0] w-[200px]"
                                                    @update:input-value="updateItemRemarks($event, itemIndex)"
                                                />
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <DeleteButton
                                                type="button"
                                                class="w-12 h-8"
                                                :disabled="state.additional_items.length <= 1"
                                                @click="removeTransferItem(itemIndex)"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid grid-flow-col grid-rows-1 gap-4">
                            <OutlinePrimaryButton
                                text="+ Add New Transfer Product"
                                type="button"
                                class="border-dashed"
                                @click="addNewTransferItem()"
                            />
                        </div>

                        <div class="flex flex-row ml-auto">
                            <SecondaryButton
                                type="button"
                                text="Cancel"
                                class="w-24 mt-5"
                                @click="hideItemsModal"
                            />

                            <PrimaryButton
                                type="button"
                                text="Submit"
                                class="w-24 mt-5 ml-1"
                                @click="updateAdditionalItems"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <JProductFilterDetails
        v-if="state.displayInventoryUpdateFilterModal"
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="productListSearchUrl"
        :filtered-category-url="categorySearchUrl"
        :filtered-brand-url="brandSearchUrl"
        :show-has-inventory="true"
        :location-id="stockTransferLocations.source_location_id"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />

    <VideoPlay
        v-if="state.displayVideoPlayModal"
        :modal-show="state.displayVideoPlayModal"
        :video-url="state.videoUrl"
        @close-modal="closeModal"
    />
</template>

<script setup>
import DeleteButton from '@commonComponents/DeleteButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import ValidationError from '@commonComponents/ValidationError.vue';
import VideoPlay from '@commonComponents/VideoPlay.vue';
import { numberFormat } from '@commonServices/helper';
import { confirmDialogBox, showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Download, Info, X, PlayCircle } from 'lucide-vue-next';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    stockTransferItems: {
        type: Object,
        required: true,
    },
    stockTransferId: {
        type: Number,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    discrepancyTypes: {
        type: Object,
        required: true,
    },
    cancelUrl: {
        type: String,
        required: true,
    },
    updateStatusUrl: {
        type: String,
        required: true,
    },
    updateAdditionalItemsUrl: {
        type: String,
        required: true,
    },
    redirectUrl: {
        type: String,
        required: true,
    },
    inventoryStockUrl: {
        type: String,
        required: true,
    },
    updateReceivedQuantitiesUrl: {
        type: String,
        required: true,
    },
    removeDiscrepancyProofUrl: {
        type: String,
        required: true,
    },
    discrepancyProofUrl: {
        type: String,
        required: true,
    },
    closeStockTransferUrl: {
        type: String,
        required: true,
    },
    setReceivedSameQuantitiesUrl: {
        type: String,
        required: true,
    },
    removeAdditionalItemUrl: {
        type: String,
        required: true,
    },
    packageTypes: {
        type: Object,
        default: () => {},
    },
    stockTransferLocations: {
        type: Object,
        default: () => {},
    },
    productSearchUrl: {
        type: String,
        required: true,
    },
    productListSearchUrl: {
        type: String,
        required: true,
    },
    categorySearchUrl: {
        type: String,
        required: true,
    },
    brandSearchUrl: {
        type: String,
        required: true,
    },
    getProductUrlName: {
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
            sortable: true,
        },
        {
            key: 'color',
        },
        {
            key: 'size',
        },
        {
            key: 'product_variant_values',
            label: 'Attributes',
        },
        {
            key: 'quantity',
            bodyClass: 'text-center',
        },
        {
            key: 'received',
        },
        {
            key: 'status',
        },
        {
            key: 'discrepancy_proof',
        },
        {
            key: 'remarks',
        },
    ],

    source_location_id: props.stockTransferLocations.source_location_id,
    destination_location_id: props.stockTransferLocations.destination_location_id,

    additional_items: [
        {
            stock_transfer_id: props.stockTransferId,
            product_id: null,
            has_batch: false,
            product_color: null,
            product_size: null,
            product_variant_values: [],
            product_uom: null,
            quantity: 0,
            received_quantity: 0,
            source_stock: 0,
            destination_stock: 0,
            package_type_id: null,
            package_quantity: 0,
            package_total_quantity: 0,
            remarks: null,
            derivatives: null,
            derivative: null,
            unit_of_measure_derivative_id: null,
        }
    ],
    filterModalIndex: 0,
    displayInventoryUpdateFilterModal: false,
    stockTransferItems: [],
    display_add_items: false,
    displayVideoPlayModal: false,
    videoUrl: null,
    refreshTableData: Math.random(),
    dynamicColumns: [],
});

const openVideoPlayModal = (data) => {
    state.displayVideoPlayModal = true;
    state.videoUrl = data;
};

const closeModal = () => {
    state.displayVideoPlayModal = false;
};

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

const addNewTransferItem = () => {
    state.additional_items.push({
        stock_transfer_id: props.stockTransferId,
        product_id: null,
        has_batch: false,
        product_color: null,
        product_size: null,
        product_variant_values: [],
        quantity: 0,
        received_quantity: 0,
        source_stock: 0,
        destination_stock: 0,
        package_quantity: 0,
        package_total_quantity: 0,
        remarks: null,
        derivatives: null,
        unit_of_measure_derivative_id: null,
    });
};

const removeTransferItem = (index) => {
    state.additional_items.splice(index, 1);
};

const updateDeliveryItemRemarks = (element, stockTransferItemId, $previousValue) => {
    if (!element.target.value) {
        return;
    }

    if ($previousValue !== element.target.value) {
        axios.post(route(props.addDeliveryNoteItemRemarksUrl, stockTransferItemId), {
            remarks: element.target.value,
        });
    }
};

const updateReceivedQuantity = (element, itemDetails, itemIndex) => {
    state.stockTransferItems[itemIndex].discrepancy_proof = null;
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

    axios.post(route(props.updateReceivedQuantitiesUrl, itemDetails.stock_transfer_id), {
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

const hasDiscrepancy = computed(() => {
    for (const key in state.stockTransferItems) {
        if (
            (parseFloat(state.stockTransferItems[key].quantity) !==
            parseFloat(state.stockTransferItems[key].received_quantity))
        ) {
            return true;
        }

        if (state.stockTransferItems[key].is_extra_item === true) {
            return true;
        }
    }

    return false;
});

const isReceivedQuantitiesMissing = computed(() => {
    for (const key in state.stockTransferItems) {
        if (state.stockTransferItems[key].received_quantity == null ||
            state.stockTransferItems[key].received_quantity < 0
        ) {
            return true;
        }
    }
    return false;
});

const completeStockTransfer = () => {
    if (isReceivedQuantitiesMissing.value) {
        return;
    }

    if (hasDiscrepancy.value) {
        const message = 'There is a discrepancy in the stock. The sourcing party will need to confirm this before the transfer is finalized and the inventory is updated. Are you sure?';

        confirmDialogBox(message, () => {
            router.post(route(props.updateStatusUrl, props.stockTransferId), {
                status_id: props.statuses.discrepancy
            }, {
                onSuccess: () => router.get(props.cancelUrl)
            });
        });

        return;
    }

    const message = 'Are you sure you want to close the stock transfer?';

    confirmDialogBox(message, () => {
        router.put(route(props.closeStockTransferUrl, props.stockTransferId));
    });
};

const setReceivedQuantitySameAsQuantities = () => {
    const message = 'If you have already specified the received quantity for any of the items, that will be overwritten. Are you sure?';

    confirmDialogBox(message, () => {
        setReceivedQuantity();
        router.post(route(props.setReceivedSameQuantitiesUrl, props.stockTransferId));
    });
};

const setReceivedQuantity = () => {
    for (const key in state.stockTransferItems) {
        state.stockTransferItems[key].discrepancy_proof = null;
        state.stockTransferItems[key].received_quantity = parseFloat(state.stockTransferItems[key].quantity);
    }
};

const uploadDiscrepancyProof = (file, stockTransferId, stockTransferItemId, index) => {
    router.post(route(props.discrepancyProofUrl, [stockTransferId, stockTransferItemId]), {
        discrepancy_proof: file
    }, {
        onSuccess: () => {
            state.stockTransferItems[index].discrepancy_proof = URL.createObjectURL(file);
            state.stockTransferItems[index].mime_type = file.type;
        }
    });
};

const displayAddItems = () => {
    state.display_add_items = true;
};

const hideItemsModal = () => {
    removeTransferItem();
    addNewTransferItem();
    state.display_add_items = false;
};

const removeDiscrepancyProof = (stockTransferItemId, index) => {
    axios.get(route(props.removeDiscrepancyProofUrl, stockTransferItemId));
    state.stockTransferItems[index].discrepancy_proof = null;
};

const removeAdditionalItem = (stockTransferItemId, index) => {
    confirmDialogBox('Are you sure you want to remove the received extra stock transfer item?', () => {
        axios.get(route(props.removeAdditionalItemUrl, stockTransferItemId))
            .then((response) => {
                if (response) {
                    state.stockTransferItems.splice(index, 1);
                    showSuccessNotification('Additional Item deleted successfully.');
                }
            });
    });
};

const productSelected = (selectedProduct, index) => {    
    if (selectedProduct) {
        state.additional_items[index].product_id = selectedProduct.id;
        state.additional_items[index].remarks = null;
        state.additional_items[index].has_batch = selectedProduct.has_batch;
        state.additional_items[index].received_quantity = 0;
        state.additional_items[index].product_color = selectedProduct.color ? selectedProduct.color.name : 'N/A';
        state.additional_items[index].product_size = selectedProduct.size ? selectedProduct.size.name : 'N/A';

        if(pageProps.value.product_variant){
            state.additional_items[index].product_variant_values = selectedProduct.product_variant_values;
        }

        if(! pageProps.value.product_variant){
            state.additional_items[index].product_uom = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.name : null;
            state.additional_items[index].derivatives = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.derivatives : null;
        }else{
            state.additional_items[index].product_uom = selectedProduct.master_product.unit_of_measure ? selectedProduct.master_product.unit_of_measure.name : null;
            state.additional_items[index].derivatives = selectedProduct.master_product.unit_of_measure ? selectedProduct.master_product.unit_of_measure.derivatives : null;
        }
        state.additional_items[index].unit_of_measure_derivative_id = null;
        getSelectedProductStock(selectedProduct.id, index);
        return;
    }

    state.additional_items[index].product_id = null;
    state.additional_items[index].product_color = null;
    state.additional_items[index].product_size = null;
    state.additional_items[index].product_variant_values = [];
    state.additional_items[index].product_uom = null;
    state.additional_items[index].quantity = 0;
    state.additional_items[index].received_quantity = 0;
    state.additional_items[index].source_stock = 0;
    state.additional_items[index].remarks = null;
    state.additional_items[index].derivatives = null;
    state.additional_items[index].unit_of_measure_derivative_id = null;
};

const getSelectedProductStock = (productId, index) => {
    const params = {
        product_ids: [productId],
        source_location_id: props.stockTransferLocations.source_location_id,
        destination_location_id: props.stockTransferLocations.destination_location_id,
    };

    axios.get(route(props.inventoryStockUrl), { params })
        .then((response) => {
            const sourceInventories = response.data.source_inventories;
            const destinationInventories = response.data.destination_inventories;

            sourceInventories.every(function (sourceInventory) {
                state.additional_items[index].source_stock = sourceInventory.stock;
                state.additional_items[index].source_reserved_stock = sourceInventory.reserved_stock;
                return sourceInventory;
            });

            destinationInventories.every(function (destinationInventory) {
                state.additional_items[index].destination_stock = destinationInventory.stock;
                state.additional_items[index].destination_reserved_stock = destinationInventory.reserved_stock;
                return destinationInventory;
            });
        });
};

const updateTransferStock = (element, index, maximumIncrementValue, derivativeId, derivative) => {
    if (
        (typeof element.target.value === 'string' && element.target.value.length === 0) ||
        isNaN(element.target.value) === true
    ) {
        state.additional_items[index].quantity = 0;
        return;
    }

    if (derivativeId) {
        maximumIncrementValue = parseFloat(maximumIncrementValue) * parseFloat(derivative.ratio);
    }

    if (parseFloat(maximumIncrementValue) > 0) {
        if (parseFloat(element.target.value) >= parseFloat(maximumIncrementValue)) {
            state.additional_items[index].quantity = parseFloat(maximumIncrementValue);
            state.additional_items[index].received_quantity = parseFloat(maximumIncrementValue);
            state.additional_items[index].package_total_quantity = parseFloat(maximumIncrementValue);
            return;
        }

        state.additional_items[index].quantity = parseFloat(element.target.value);
        state.additional_items[index].received_quantity = parseFloat(element.target.value);
        state.additional_items[index].package_total_quantity = parseFloat(element.target.value);
    }
};

const updatePackageTypeId = (packageTypeId, index) => {
    state.additional_items[index].package_type_id = packageTypeId;
};

const updatePackageQuantity = (element, index) => {
    const inputValue = element.target.value ? element.target.value : 0;

    state.additional_items[index].package_quantity = parseInt(inputValue);
};

const updatePackageTotalQuantity = (element, index) => {
    const inputValue = element.target.value;

    if (parseFloat(inputValue) !== parseFloat(state.additional_items[index].quantity)) {
        showErrorNotification('The transferred stock and the total quantity of packages do not match.');
        return;
    }

    state.additional_items[index].package_total_quantity = parseFloat(inputValue);
};

const updateItemRemarks = (value, index) => {
    state.additional_items[index].remarks = value;
};

const updateAdditionalItems = () => {
    const httpStatusOk = 200;
    axios.put(route(props.updateAdditionalItemsUrl, props.stockTransferId), state)
        .then((response) => {
            if (response.data) {
                hideItemsModal();
                showSuccessNotification('Stock Transfer additional received items added successfully.');
                return;
            }

            if (response.status === httpStatusOk) {
                router.get(route(props.redirectUrl, props.stockTransferId));
            }
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const displayUpdateFilter = (index) => {
    state.displayInventoryUpdateFilterModal = true;
    state.filterModalIndex = index;
};

const filteredProductSelected = (selectedProduct) => {
    state.displayInventoryUpdateFilterModal = false;

    productSelected(selectedProduct, state.filterModalIndex);
};

const updateUnitOfMeasureDerivativeId = (derivativeId, index, derivatives) => {
    if (!derivativeId) {
        state.additional_items[index].transfer_stock = 0;
    }

    state.additional_items[index].unit_of_measure_derivative_id = derivativeId;
    state.additional_items[index].derivative = derivatives.find((derivative) => derivative.id === derivativeId);
};

const calculateNewSourceStock = (sourceStock, transferStock, derivative) => {
    if (derivative) {
        return numberFormat(parseFloat(sourceStock) - (parseFloat(transferStock) / parseFloat(derivative.ratio)));
    }

    return numberFormat(parseFloat(sourceStock) - parseFloat(transferStock));
};

const calculateNewDestinationStock = (transferStock, destinationStock, derivative) => {
    if (derivative) {
        return numberFormat(parseFloat(destinationStock) + (parseFloat(transferStock) / parseFloat(derivative.ratio)));
    }

    return numberFormat(parseFloat(destinationStock) + parseFloat(transferStock));
};

onMounted(() => {
    if (props.stockTransferItems) {
        state.stockTransferItems = props.stockTransferItems.data;
    }
    state.dynamicColumns = getFilteredColumns();    
});

const getFilteredColumns = () => {
    const columns = state.columns || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['color', 'size'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'product_variant_values');
};

</script>
