<template>
    <PageTitle title="Delivery Note" />

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
                Delivery Note
            </h2>

            <div class="w-full sm:w-auto block md:flex mt-4 sm:mt-0">
                <OutlinePrimaryButton
                    text="Partial Receive"
                    type="button"
                    class="w-15 sm mr-1 mb-2 md:mb-0"
                    @click="partiallyReceive()"
                />

                <PrimaryButton
                    text="Receive Full Quantity"
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

                <Link :href="deliveryOrderUrl">
                    <SecondaryButton
                        type="button"
                        text="Save Draft"
                        class="w-15"
                    />
                </Link>
            </div>
        </div>

        <JSimpleTable
            :columns="state.columns"
            :records="state.transferItems"
            :allow-search="true"
        >
            <template #product_name="data">
                <span
                    v-if="data.item.is_extra_item"
                    class="flex items-center"
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

            <template #transfer_quantity="data">
                <p>
                    {{ data.item.transfer_quantity }}
                </p>
                <p v-if="data.item.derivative !== null">
                    {{ data.item.derivative.name }}
                </p>
            </template>

            <template #received_quantity="data">
                <div class="mb-4">
                    <p
                        v-if="transferItems['data'][data.index].derivative !== null"
                        class="font-medium"
                    >
                        Partial Received: {{ data.item.partial_received / transferItems['data'][data.index].derivative.ratio }} {{ transferItems['data'][data.index].derivative.parent_unit_of_measure.name }}
                    </p>
                    <p
                        v-else
                        class="font-medium"
                    >
                        Partial Received: {{ data.item.partial_received }}
                    </p>
                </div>

                <div class="my-2">
                    <p class="font-medium">
                        Received:
                    </p>

                    <span v-if="data.item.is_extra_item">
                        {{ data.item.received_quantity }}
                    </span>

                    <div
                        v-else
                    >
                        <input
                            type="number"
                            min="0.01"
                            class="form-control"
                            :value="(data.item.received_quantity - data.item.partial_received)"
                            @blur="prepareReceivedQuantity($event, data.item, data.index)"
                        >
                    </div>
                </div>

                <div class="mt-4">
                    <p
                        v-if="transferItems['data'][data.index].derivative !== null"
                        class="font-medium"
                    >
                        Received: {{ data.item.total_received / transferItems['data'][data.index].derivative.ratio }} {{ transferItems['data'][data.index].derivative.parent_unit_of_measure.name }}
                    </p>
                    <p
                        v-else
                        class="font-medium"
                    >
                        Received: {{ data.item.total_received }}
                    </p>
                </div>

                <div class="mt-4">
                    <PrimaryButton
                        v-if="data.item.has_batch && parseFloat(data.item.received_quantity) !== 0.00 && !data.item.is_extra_item"
                        type="button"
                        class="w-full mt-3"
                        text="Specify Batch Details"
                        @click="openBatchDetailsModal(data.index)"
                    />
                </div>
            </template>

            <template #total_received="data">
                <span>
                    {{ data.item.received_quantity }}
                </span>
            </template>

            <template #status="data">
                <JBadge
                    :type="getStatusColor(data.item)"
                    :label="getItemStatus(data.item)"
                />
            </template>

            <template #discrepancy_proof="data">
                <div
                    v-if="(parseFloat(data.item.received_quantity) !== parseFloat(data.item.transfer_quantity)) &&
                        ! data.item.discrepancy_proof
                    "
                    class="flex flex-col sm:flex-row -mx-3"
                >
                    <div>
                        <JFileUpload
                            accept="image/*"
                            validation-field-name="discrepancy_proof"
                            @update:input-file="uploadDiscrepancyProof($event, data.item.id, data.index)"
                        />
                    </div>
                </div>

                <div
                    v-if="(parseFloat(data.item.received_quantity) === parseFloat(data.item.transfer_quantity))"
                    class="inline-flex items-center justify-center py-1 mr-2 font-bold leading-none alert-success-soft rounded px-2 mt-1.5 w-1/2"
                >
                    Not Required
                </div>

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
                        tag="div"
                        content="Remove this image?"
                        class="w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-danger right-0 top-0 -mr-2 -mt-2"
                        @click="removeDiscrepancyProof(data.item.id, data.index)"
                    >
                        <X class="w-4 h-4" />
                    </Tippy>

                    <Tippy
                        tag="a"
                        content="Download the image"
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
        <div
            v-if="state.partialReceivedFulfillments && state.partialReceivedFulfillments.length"
            class="text-left items-center p-5 border border-slate-200/60 mt-2"
        >
            <h3 class="font-medium text-base mr-auto">
                Partial Received Delivery Notes
            </h3>
            <JSimpleTable
                :columns="state.partialReceiveColumns"
                :records="state.partialReceivedFulfillments"
            >
                <template #info="data">
                    <div class="flex items-center justify-center cursor-pointer">
                        <div class="mr-1">
                            <List
                                @click="showPartialReceiveDetailsModal(data.item.id)"
                            />
                        </div>
                    </div>
                </template>
                <template #status="data">
                    <div class="inline-flex items-center">
                        <span
                            :class="getPartialReceiveStatusColor(data.item.status_id)"
                        >
                            {{ data.item.status }}
                        </span>
                    </div>
                </template>
                <template #action="data">
                    <div
                        v-if="data.item.status_id !== partiallyReceiveStatuses.completed && data.item.status_id !== partiallyReceiveStatuses.cancelled"
                        class="flex justify-center items-center"
                    >
                        <Dropdown
                            v-slot="{ dismiss }"
                            class="dropdown absolute"
                        >
                            <DropdownToggle
                                tag="a"
                                class="w-5 h-5 block"
                                href="javascript:;"
                            >
                                <MoreHorizontal class="w-5 h-5 text-slate-500" />
                            </DropdownToggle>

                            <DropdownMenu
                                class="w-60"
                            >
                                <DropdownContent>
                                    <DropdownItem
                                        v-if="data.item.status_id === partiallyReceiveStatuses.draft"
                                        @click="markAsApproved(data.item.id, dismiss)"
                                    >
                                        <Check class="w-4 h-4 mr-1" />
                                        Approve
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="data.item.status_id === partiallyReceiveStatuses.approved"
                                        @click="markAsCompleted(data.item.id, dismiss)"
                                    >
                                        <CheckSquare class="w-4 h-4 mr-2" /> Complete
                                    </DropdownItem>
                                    <DropdownItem
                                        v-if="data.item.status_id !== partiallyReceiveStatuses.completed && data.item.status_id !== partiallyReceiveStatuses.cancelled"
                                        class="text-danger"
                                        @click="markAsCancelled(data.item.id, dismiss)"
                                    >
                                        <X class="w-4 h-4 mr-2" /> Cancel
                                    </DropdownItem>
                                </DropdownContent>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </template>
            </JSimpleTable>
        </div>
    </div>

    <div
        v-if="state.display_add_items"
        class=""
    >
        <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
            <h2 class="text-lg font-medium mr-auto">
                Add Items Received
            </h2>
        </div>

        <div class="grid grid-cols-12 gap-6 mt-5">
            <div class="intro-y col-span-12 lg:col-span-12">
                <div class="intro-y box">
                    <div class="p-5">
                        <span>
                            <div class="overflow-unset overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="w-1/2">
                                                Product Selection
                                            </th>
                                            <th class="whitespace-nowrap">
                                                Stock Information
                                            </th>
                                            <th class="whitespace-nowrap">
                                                Received Quantity
                                            </th>
                                            <th class="whitespace-nowrap">
                                                Packing Details
                                            </th>
                                            <th class="whitespace-nowrap">
                                                Remarks
                                            </th>
                                            <th class="whitespace-nowrap">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="(item, itemIndex) in state.additional_items"
                                            :key="'stock-transfer-item-' + itemIndex"
                                        >
                                            <td class="w-1/2">
                                                <JProductFilter
                                                    :product-search-url="route(getFilteredInventoryProductsUrl)"
                                                    :get-product-url-name="getProductUrlName"
                                                    :selected-product-id="state.additional_items[itemIndex].product_id"
                                                    :validation-field-name="'transfer_items.' + itemIndex + '.product_id'"
                                                    @update:product-selected="productSelected($event, itemIndex)"
                                                    @update:display-product-filters="displayUpdateFilter(itemIndex)"
                                                />

                                                <strong
                                                    v-if="!pageProps.product_variant"
                                                >
                                                    Color: {{ state.additional_items[itemIndex].product_color }}
                                                </strong>

                                                <strong
                                                    v-if="!pageProps.product_variant"
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
                                                    UOM: {{ state.additional_items[itemIndex].product_uom ?? 'N/A' }}
                                                </strong>
                                            </td>

                                            <td class="mt-10 whitespace-nowrap">
                                                <span v-if="state.additional_items[itemIndex].product_id">
                                                    <span class="text-lg font-bold">Before Transfer:</span><br>
                                                    Stock On Hand:
                                                    <span class="font-medium">

                                                        {{ getOldStock(state.additional_items[itemIndex]) }}

                                                        {{ state.additional_items[itemIndex].product_uom }}

                                                        <Tippy
                                                            :content="'Reserved Stocks: ' + getOldReservedStock(state.additional_items[itemIndex])"
                                                        >
                                                            <Info
                                                                class="text-cyan-400 inline-block"
                                                                :size="15"
                                                            />
                                                        </Tippy>
                                                    </span>
                                                    <br>

                                                    Stock with Supplier:
                                                    <span class="font-medium">

                                                        {{ getOldExternalStock(state.additional_items[itemIndex]) }}

                                                        {{ state.additional_items[itemIndex].product_uom }}

                                                        <Tippy
                                                            :content="'Reserved Stocks: ' + getOldExternalReservedStock(state.additional_items)"
                                                        >
                                                            <Info
                                                                class="text-cyan-400 inline-block"
                                                                :size="15"
                                                            />
                                                        </Tippy>
                                                    </span>
                                                    <br><br>

                                                    <span class="text-lg font-bold">After Transfer:</span><br>

                                                    Balance Stock:
                                                    <span class="font-medium">
                                                        {{ getNewStock(state.additional_items[itemIndex]) }}
                                                        {{ state.additional_items[itemIndex].product_uom }}
                                                    </span>

                                                    <br>

                                                    Balance Stock with Supplier:
                                                    <span class="font-medium">
                                                        {{ getNewExternalStock(state.additional_items[itemIndex]) }}
                                                        {{ state.additional_items[itemIndex].product_uom }}
                                                    </span>
                                                </span>
                                            </td>

                                            <td class="whitespace-nowrap">
                                                <div
                                                    v-if="parseFloat(item.stock) <= 0"
                                                    class="w-24 text-center form-control text-danger font-extrabold"
                                                >
                                                    0
                                                </div>

                                                <input
                                                    v-if="parseFloat(item.stock) > 0"
                                                    type="text"
                                                    class="form-control w-24 text-center"
                                                    :value="item.quantity"
                                                    step="any"
                                                    @input="updateTransferStock($event, itemIndex, item.stock, item.quantity)"
                                                >

                                                <ValidationError :validation-field-name="'transfer_items.' + itemIndex + '.quantity'" />

                                                <br><br>

                                                <FormSelectBox
                                                    v-if="item.derivatives"
                                                    :selected-record="item.unit_of_measure_derivative_id"
                                                    :records="item.derivatives"
                                                    :display-label="false"
                                                    :required="true"
                                                    placeholder="Select derivative"
                                                    :validation-field-name="'transfer_items.' + itemIndex + '.unit_of_measure_derivative_id'"
                                                    class="mt-[0]"
                                                    @update:selected-record="updateUnitOfMeasureDerivativeId($event, itemIndex)"
                                                />

                                                <div
                                                    v-if="item.unit_of_measure_derivative_id"
                                                    class="mt-2 text-lg font-bold"
                                                >
                                                    {{ parseFloat(item.quantity) / parseFloat(item.derivative.ratio) }}

                                                    {{ state.additional_items[itemIndex].product_uom }}
                                                </div>
                                            </td>

                                            <td class="whitespace-nowrap w-2/12">
                                                <div class="grid grid-rows-3 gap-2">
                                                    <div>
                                                        <p>Type </p>
                                                        <FormSelectBox
                                                            :records="packageTypes"
                                                            :display-label="false"
                                                            class="mt-[0]"
                                                            input-label="Type"
                                                            validation-field-name="unit_of_measure_id"
                                                            :selected-record="item.package_type_id"
                                                            @update:selected-record="updatePackageTypeId($event, itemIndex)"
                                                        />

                                                        <PrimaryButton
                                                            v-if="item.has_batch"
                                                            type="button"
                                                            class="w-full mt-1"
                                                            text="Specify Batch Details*"
                                                            @click="openProductBatchDetailsModal(itemIndex)"
                                                        />
                                                    </div>

                                                    <div>
                                                        <p>Quantity </p>
                                                        <input
                                                            type="number"
                                                            class="form-control w-[100px]"
                                                            :value="item.package_quantity"
                                                            @input="updatePackageQuantity($event, itemIndex)"
                                                        >
                                                    </div>

                                                    <div>
                                                        <p>Quantity per Pack </p>
                                                        <input
                                                            type="number"
                                                            class="form-control w-[100px]"
                                                            :value="item.package_total_quantity"
                                                            @blur="updatePackageTotalQuantity($event, itemIndex)"
                                                        >
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="whitespace-nowrap">
                                                <FormTextarea
                                                    :input-value="item.remarks"
                                                    placeholder="Enter Remarks"
                                                    input-name="remarks"
                                                    class="mt-[0] w-[200px]"
                                                    @update:input-value="updateItemRemarks($event, itemIndex)"
                                                />
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
                        </span>

                        <div class="grid grid-flow-col grid-rows-1 gap-4 mt-3">
                            <OutlinePrimaryButton
                                text="+ Add New"
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
        :product-search-url="route(getFilteredInventoryProductsListUrl)"
        :filtered-category-url="route(getFilteredCategoriesUrl)"
        :filtered-brand-url="route(getFilteredBrandsUrl)"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />

    <BatchDetailsModal
        v-if="state.displayProductBatchDetailsModal"
        :batch-details="state.additional_items[state.batchDetailsModalIndex].batch_details"
        :modal-show="state.displayProductBatchDetailsModal"
        message="The total of all the quantities you specify with the batch numbers must match the product quantity for the stock transfer."
        @close-modal="closeAdditionalBatchDetailsModal()"
        @update:batch-details="updateAdditionalBatchDetails"
    />

    <PartiallyReceiveFulfillmentItem
        v-if="state.partialReceiveDetails && state.partialReceiveDetails.length > 0"
        :modal-show="state.displayPartialReceiveDetailsModal"
        :partial-receive-details="state.partialReceiveDetails"
        :columns-for-partially-receive-item-details="state.columnsForPartiallyReceiveItemDetails"
        @close-modal="closeModalPartiallyReceiveItemModal()"
    />

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
                            :readonly="batchData.batch_number !== null && !batchData.is_discrepancy"
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
                            :input-value="batchData.received_quantity"
                            type="number"
                            input-label="Received Quantity"
                            @update:input-value="updateBatchReceivedQuantity($event, index)"
                        />
                    </div>

                    <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                        <DeleteButton
                            v-if="(batchData.is_discrepancy || batchData.is_discrepancy) && parseFloat(batchData.quantity) === 0.00"
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
import BatchDetailsModal from '@commonComponents/BatchDetailsModal.vue';
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
import PartiallyReceiveFulfillmentItem from '@commonPages/PartiallyReceiveFulfillmentItem.vue';
import { numberFormat } from '@commonServices/helper';
import { confirmDialogBoxWithCenterText, showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, CheckSquare, Download, Info, List, MoreHorizontal, X } from 'lucide-vue-next';
import { computed, onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import FormInput from '@commonComponents/FormInput.vue';
import {
    Dropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownContent,
    DropdownItem,
} from '@commonVendor/dropdown';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    transferItems: {
        type: Object,
        required: true,
    },
    purchaseOrderProductIds: {
        type: Object,
        required: true,
    },
    purchaseOrderFulfillmentId: {
        type: Number,
        required: true,
    },
    locationId: {
        type: Number,
        required: true,
    },
    externalLocationId: {
        type: Number,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    partiallyReceiveStatuses: {
        type: Object,
        required: true,
    },
    discrepancyTypes: {
        type: Object,
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
    deliveryOrderUrl: {
        type: String,
        required: true,
    },
    markAsApprovedUrl: {
        type: String,
        required: true,
    },
    markAsCompletedUrl: {
        type: String,
        required: true,
    },
    markAsCancelledUrl: {
        type: String,
        required: true,
    },
    getFilteredInventoryProductsUrl: {
        type: String,
        required: true,
    },
    getProductUrlName: {
        type: String,
        required: true,
    },
    getFilteredInventoryProductsListUrl: {
        type: String,
        required: true,
    },
    getFilteredCategoriesUrl: {
        type: String,
        required: true,
    },
    getFilteredBrandsUrl: {
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
    updateBatchDetailsUrl: {
        type: String,
        required: true,
    },
    deleteBatchDetailUrl: {
        type: String,
        required: true,
    },
    discrepancyUrl: {
        type: String,
        required: true,
    },
    closedUrl: {
        type: String,
        required: true,
    },
    setReceivedSameQuantitiesUrl: {
        type: String,
        required: true,
    },
    discrepancyProofUrl: {
        type: String,
        required: true,
    },
    removeDiscrepancyProofUrl: {
        type: String,
        required: true,
    },
    removeAdditionalItemUrl: {
        type: String,
        required: true,
    },
    getLocationInventoryStocksUrl: {
        type: String,
        required: true,
    },
    updateAdditionalItemsUrl: {
        type: String,
        required: true,
    },
    deliveryNoteUrl: {
        type: String,
        required: true,
    },
    partialReceiveUrl: {
        type: String,
        required: true,
    },
    fetchPartiallyReceiveFulfillmentUrl: {
        type: String,
        required: true,
    },
    fetchPartiallyReceiveFulfillmentItems: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'product_name',
            label: 'Product',
            sortable: true,
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                },
            ]
            : [
                {
                    key: 'product_color',
                    label: 'Color',
                    sortable: true
                },
                {
                    key: 'product_size',
                    label: 'Size',
                    sortable: true
                },
            ]),
        {
            key: 'transfer_quantity',
            bodyClass: 'text-center',
            label: ' Transferred Quantity',
            sortable: true
        },
        {
            key: 'external_stock',
            bodyClass: 'text-center',
            label: 'Balance Stock With Supplier',
            sortable: true
        },
        {
            key: 'received_quantity',
            label: 'Received',
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

    partialReceiveColumns: [
        {
            key: 'id',
        },
        {
            key: 'received_by_user',
            bodyClass: 'text-center',
        },
        {
            key: 'received_by_user_type',
            bodyClass: 'text-center',
        },
        {
            key: 'status',
            bodyClass: 'text-center',
        },
        {
            key: 'info',
        },
        {
            key: 'action',
        },
    ],

    columnsForPartiallyReceiveItemDetails: [
        {
            key: 'id',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        {
            key: 'name',
            headerClass: 'text-left',
            bodyClass: 'text-left',
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes',
                },
            ]
            : [
                {
                    key: 'color',
                    headerClass: 'text-left',
                    bodyClass: 'text-left',
                },
                {
                    key: 'size',
                    headerClass: 'text-left',
                    bodyClass: 'text-left',
                },
            ]),
        {
            key: 'received_quantity',
            headerClass: 'text-right',
            bodyClass: 'text-right',
        },
    ],

    additional_items: [
        {
            purchase_order_fulfillment_id: props.purchaseOrderFulfillmentId,
            product_id: null,
            has_batch: false,
            product_color: null,
            product_size: null,
            product_variant_values: [],
            quantity: 0,
            product_uom: 0,
            received_quantity: 0,
            partial_received: 0,
            unit_of_measure_derivative_id: null,
            derivatives: null,
            derivative: null,
            stock: 0,
            reserved_stock: 0,
            package_type_id: null,
            package_quantity: 0,
            package_total_quantity: 0,
            batch_details: [],
            remarks: null,
        }
    ],
    batchDetailsModalIndex: 0,
    displayProductBatchDetailsModal: false,
    filterModalIndex: 0,
    displayInventoryUpdateFilterModal: false,
    transferItems: [],
    display_add_items: false,
    partialReceivedFulfillments: [],
    displayPartialReceiveDetailsModal: false,
    displayBatchDetailsModal: false,
    batchDetailsIndex: null,
    partialReceiveDetails: [],
    batchDetails: [],
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
        if (parseFloat(batchDetail.received_quantity) !== parseFloat(batchDetail.quantity)) {
            return true;
        }
    }

    return false;
};

const addNewTransferItem = () => {
    state.additional_items.push({
        purchase_order_fulfillment_id: props.purchaseOrderFulfillmentId,
        batch_details: [],
        product_id: null,
        has_batch: false,
        product_color: null,
        product_size: null,
        product_variant_values: [],
        quantity: 0,
        received_quantity: 0,
        partial_received: 0,
        stock: 0,
        reserved_stock: 0,
        package_type_id: null,
        package_quantity: 0,
        package_total_quantity: 0,
        remarks: null,
    });
};

const removeTransferItem = (index) => {
    state.additional_items.splice(index, 1);
};

const updateDeliveryItemRemarks = (element, purchaseOrderFulfillmentItemId, $previousValue) => {
    if ($previousValue !== element.target.value) {
        axios.post(route(props.purchaseOrderFulfillmentDeliveryNoteItemRemarksUrl, purchaseOrderFulfillmentItemId), {
            remarks: element.target.value,
        });
    }
};

const prepareReceivedQuantity = (element, itemDetails, itemIndex) => {
    if (element.target.value < 0) {
        showErrorNotification('You cannot add negative quantity.');
        updateReceivedQuantity(0, itemDetails, itemIndex);
        return;
    }

    let quantity = parseFloat(element.target.value) + parseFloat(itemDetails.partial_received);

    if (props.transferItems['data'][itemIndex].derivative) {
        quantity = quantity / props.transferItems['data'][itemIndex].derivative.ratio;
    }

    if (quantity > itemDetails.external_stock) {
        showErrorNotification('You cannot add more than the available external quantity.');
        updateReceivedQuantity(parseFloat(itemDetails.external_stock) - parseFloat(itemDetails.partial_received), itemDetails, itemIndex);
        return;
    }

    updateReceivedQuantity(element.target.value, itemDetails, itemIndex);
};

const updateReceivedQuantity = (element, itemDetails, itemIndex) => {
    state.transferItems[itemIndex].discrepancy_proof = null;
    const inputValue = parseFloat((element ?? 0)) + parseFloat(state.transferItems[itemIndex].partial_received);

    let discrepancyStatus = null;

    if (parseFloat(state.transferItems[itemIndex].transfer_quantity) > parseFloat(inputValue)) {
        discrepancyStatus = props.discrepancyTypes.negative;
    }

    if (parseFloat(state.transferItems[itemIndex].transfer_quantity) < parseFloat(inputValue)) {
        discrepancyStatus = props.discrepancyTypes.positive;
    }

    axios.post(route(props.updateReceivedQuantitiesUrl, itemDetails.purchase_order_fulfillment_id), {
        item_id: itemDetails.id,
        received_quantity: parseFloat(inputValue),
        status: discrepancyStatus
    }).then(() => {
        state.transferItems[itemIndex].received_quantity = parseFloat(inputValue);
        state.transferItems[itemIndex].total_received = state.transferItems[itemIndex].received_quantity;
        state.transferItems[itemIndex].discrepancy_type = null;
    }).catch((error) => {
        showErrorNotification(error.response.data.message);
    });
};

const hasDiscrepancy = computed(() => {
    for (const key in state.transferItems) {
        if (
            (parseFloat(state.transferItems[key].transfer_quantity) !==
            parseFloat(state.transferItems[key].received_quantity))
        ) {
            return true;
        }

        if (state.transferItems[key].is_extra_item === true) {
            return true;
        }

        if (isBatchDiscrepancy(state.transferItems[key].batches)) {
            return true;
        }
    }

    return false;
});

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

const completeStockTransfer = () => {
    if (isReceivedQuantitiesMissing.value) {
        return;
    }

    if (hasDiscrepancy.value) {
        const message = 'Are you sure to notify the supplier on this discrepancy?';
        confirmDialogBoxWithCenterText(message, () => {
            router.post(route(props.discrepancyUrl, props.purchaseOrderFulfillmentId), {}, {
                onSuccess: () => router.get(props.deliveryOrderUrl)
            });
        });

        return;
    }

    router.post(route(props.closedUrl, props.purchaseOrderFulfillmentId));
};

const setReceivedQuantitySameAsQuantities = () => {
    const message = 'If you have already specified the received quantity for any of the items, that will be overwritten. Are you sure?';

    confirmDialogBoxWithCenterText(message, () => {
        setReceivedQuantity();
        router.post(route(props.setReceivedSameQuantitiesUrl, props.purchaseOrderFulfillmentId));
    });
};

const setReceivedQuantity = () => {
    for (const key in state.transferItems) {
        if (!state.transferItems[key].is_extra_item) {
            state.transferItems[key].discrepancy_proof = null;
            state.transferItems[key].received_quantity = parseFloat(state.transferItems[key].transfer_quantity);
        }
    }
};

const uploadDiscrepancyProof = (file, purchaseOrderFulfillmentItemId, index) => {
    router.post(route(props.discrepancyProofUrl, purchaseOrderFulfillmentItemId), {
        discrepancy_proof: file
    });

    state.transferItems[index].discrepancy_proof = URL.createObjectURL(file);
};

const removeDiscrepancyProof = (purchaseOrderFulfillmentItemId, index) => {
    axios.get(route(props.removeDiscrepancyProofUrl, purchaseOrderFulfillmentItemId));
    state.transferItems[index].discrepancy_proof = null;
};

const displayAddItems = () => {
    state.display_add_items = true;
};

const hideItemsModal = () => {
    removeTransferItem();
    addNewTransferItem();
    state.display_add_items = false;
};

const removeAdditionalItem = (purchaseOrderFulfillmentItemId, index) => {
    axios.get(route(props.removeAdditionalItemUrl, purchaseOrderFulfillmentItemId))
        .then(() => {
            state.transferItems.splice(index, 1);
            showSuccessNotification('Additional Item deleted successfully.');
        });
};

const productSelected = (selectedProduct, index) => {
    if (selectedProduct) {
        state.additional_items[index].product_id = selectedProduct.id;
        state.additional_items[index].remarks = null;
        state.additional_items[index].has_batch = selectedProduct.has_batch;
        state.additional_items[index].received_quantity = 0;
        if (pageProps.value.product_variant) {
            state.additional_items[index].product_variant_values = selectedProduct.product_variant_values;
            state.additional_items[index].product_uom = selectedProduct.master_product.unit_of_measure ? selectedProduct.master_product.unit_of_measure.name : null;
        } else {
            state.additional_items[index].product_color = selectedProduct.color ? selectedProduct.color.name : 'N/A';
            state.additional_items[index].product_size = selectedProduct.size ? selectedProduct.size.name : 'N/A';
            state.additional_items[index].product_uom = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.name : null;
        }
        state.additional_items[index].quantity = 0;
        state.additional_items[index].received_quantity = 0;
        state.additional_items[index].package_type_id = null;
        state.additional_items[index].package_quantity = null;
        state.additional_items[index].package_total_quantity = null;
        state.additional_items[index].unit_of_measure_derivative_id = null;
        state.additional_items[index].derivatives = null;
        state.additional_items[index].derivative = null;

        const purchaseOrderProductIndex = props.purchaseOrderProductIds.find(productId => productId === selectedProduct.id);

        if (!purchaseOrderProductIndex) {
            if (pageProps.value.product_variant) {
                state.additional_items[index].derivatives = selectedProduct.master_product.unit_of_measure ? selectedProduct.master_product.unit_of_measure.derivatives : null;
            }else{
                state.additional_items[index].derivatives = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.derivatives : null;
            }
        }

        getSelectedProductStock(selectedProduct.id, index);
        return;
    }

    state.additional_items[index].product_id = null;
    state.additional_items[index].product_color = null;
    state.additional_items[index].product_size = null;
    state.additional_items[index].quantity = 0;
    state.additional_items[index].received_quantity = 0;
    state.additional_items[index].stock = 0;
    state.additional_items[index].remarks = null;
    state.additional_items[index].quantity = 0;
    state.additional_items[index].package_type_id = null;
    state.additional_items[index].package_quantity = null;
    state.additional_items[index].package_total_quantity = null;
    state.additional_items[index].unit_of_measure_derivative_id = null;
    state.additional_items[index].derivatives = null;
    state.additional_items[index].derivative = null;
};

const getSelectedProductStock = (productId, index) => {
    const params = {
        product_id: productId,
        location_id: props.locationId,
        external_location_id: props.externalLocationId,
    };

    axios.get(route(props.getLocationInventoryStocksUrl), { params })
        .then((response) => {
            const locationInventoryStocks = response.data;

            const currentSelectedProduct = locationInventoryStocks.find((locationInventoryStock) => locationInventoryStock.product_id === productId);

            if (currentSelectedProduct === undefined) {
                state.additional_items[index].stock = 0;
                state.additional_items[index].reserved_stock = 0;
                state.additional_items[index].external_stock = 0;
                state.additional_items[index].external_reserved_stock = 0;
                return;
            }

            state.additional_items[index].stock = currentSelectedProduct.stock;
            state.additional_items[index].reserved_stock = currentSelectedProduct.reserved_stock;
            state.additional_items[index].external_stock = currentSelectedProduct.external_stock;
            state.additional_items[index].external_reserved_stock = currentSelectedProduct.external_reserved_stock;
        });
};

const updateTransferStock = (element, index) => {
    const value = element.target.value !== '' ? element.target.value : 0;
    state.additional_items[index].quantity = parseFloat(value);
    state.additional_items[index].received_quantity = parseFloat(value);
    updateUnitOfMeasureQuantity(index);
};

const updatePackageTypeId = (packageTypeId, index) => {
    state.additional_items[index].package_type_id = packageTypeId;
    updateUnitOfMeasureQuantity(index);
};

const updateUnitOfMeasureQuantity = (itemIndex) => {
    let receivedQuantity = state.additional_items[itemIndex].received_quantity;
    let packageQuantity = state.additional_items[itemIndex].package_quantity;
    const packageTypeId = state.additional_items[itemIndex].package_type_id;

    if (!packageQuantity) {
        packageQuantity = 1;
    }

    if (!receivedQuantity) {
        receivedQuantity = 0;
    }

    if (!packageTypeId) {
        state.additional_items[itemIndex].package_quantity = null;
        state.additional_items[itemIndex].package_total_quantity = null;
        return;
    }

    state.additional_items[itemIndex].package_quantity = packageQuantity;
    state.additional_items[itemIndex].package_total_quantity = numberFormat(parseFloat(receivedQuantity) / parseFloat(packageQuantity));
};

const openProductBatchDetailsModal = (itemIndex) => {
    state.batchDetailsModalIndex = itemIndex;

    if (!state.additional_items[state.batchDetailsModalIndex].batch_details.length) {
        state.additional_items[state.batchDetailsModalIndex].batch_details = [
            {
                batch_number: null,
                quantity: null,
            }
        ];
    }

    state.displayProductBatchDetailsModal = true;
};

const updatePackageQuantity = (element, index) => {
    const inputValue = element.target.value ? element.target.value : 0;

    state.additional_items[index].package_quantity = parseInt(inputValue);
    updateUnitOfMeasureQuantity(index);
};

const updatePackageTotalQuantity = (element, index) => {
    const inputValue = element.target.value;
    const packageTotalQuantity = (parseFloat(inputValue) * state.additional_items[index].package_quantity);
    if (parseFloat(state.additional_items[index].quantity) !== parseFloat(packageTotalQuantity)) {
        showErrorNotification('The transferred stock and the total quantity of packages do not match.');
        return;
    }

    updateUnitOfMeasureQuantity(index);
};

const updateItemRemarks = (value, index) => {
    state.additional_items[index].remarks = value;
};

const closeAdditionalBatchDetailsModal = () => {
    state.displayProductBatchDetailsModal = false;
    state.batchDetailsModalIndex = null;
};

const updateAdditionalBatchDetails = (batchDetails) => {
    state.additional_items[state.batchDetailsModalIndex].batch_details = batchDetails;
};

const updateAdditionalItems = () => {
    router.post(route(props.updateAdditionalItemsUrl, props.purchaseOrderFulfillmentId),
        state, {
            onSuccess: (success) => {
                if (success.props.flash.error) {
                    showErrorNotification(success.props.flash.error);
                    return;
                }

                router.get(route(props.deliveryNoteUrl, props.purchaseOrderFulfillmentId));
                hideItemsModal();
                showSuccessNotification('Additional received items added successfully.');
            },
        });
};

const displayUpdateFilter = (index) => {
    state.displayInventoryUpdateFilterModal = true;
    state.filterModalIndex = index;
};

const updateUnitOfMeasureDerivativeId = (derivativeId, index) => {
    state.additional_items[index].unit_of_measure_derivative_id = derivativeId;
    state.additional_items[index].derivative = state.additional_items[index].derivatives.find((derivative) => derivative.id === derivativeId);
};

const filteredProductSelected = (selectedProduct) => {
    state.displayInventoryUpdateFilterModal = false;

    productSelected(selectedProduct, state.filterModalIndex);
};

const partiallyReceive = () => {
    const message = 'If you have already specified the received quantity for any of the items, that will be overwritten. Are you sure?';

    confirmDialogBoxWithCenterText(message, () => {
        axios.post(route(props.partialReceiveUrl, props.purchaseOrderFulfillmentId), {
            partial_items: state.transferItems
        }).then(() => {
            showSuccessNotification('Partial Received Successful.');
            window.location.reload();
        });
    });
};

const fetchPartialReceive = () => {
    axios.get(route(props.fetchPartiallyReceiveFulfillmentUrl, props.purchaseOrderFulfillmentId))
        .then((response) => {
            state.partialReceivedFulfillments = response.data.partially_receive_fulfillments;
        });
};

const showPartialReceiveDetailsModal = (partialReceiveId) => {
    state.partialReceiveDetails = [];
    axios.get(route(props.fetchPartiallyReceiveFulfillmentItems, partialReceiveId))
        .then((response) => {
            state.partialReceiveDetails = response.data.partial_receive_fulfillment_items;
        });

    state.displayPartialReceiveDetailsModal = true;
};

const closeModalPartiallyReceiveItemModal = () => {
    state.displayPartialReceiveDetailsModal = false;
};

const getOldStock = (item) => {
    return numberFormat(parseFloat(item.stock));
};

const getOldReservedStock = (item) => {
    return numberFormat(parseFloat(item.reserved_stock ?? 0));
};

const getOldExternalStock = (item) => {
    return numberFormat(parseFloat(item.external_stock));
};

const getOldExternalReservedStock = (item) => {
    return numberFormat(parseFloat(item.external_reserved_stock));
};

const getNewStock = (item) => {
    let newStock = 0;

    let quantity = parseFloat(item.quantity);
    if (item.derivative) {
        quantity = numberFormat(parseFloat(quantity) / parseFloat(item.derivative.ratio));
    }

    newStock = numberFormat(parseFloat(item.stock) - parseFloat(quantity));

    return numberFormat(parseFloat(newStock));
};

const getNewExternalStock = (item) => {
    let newExternalStock = 0;

    let quantity = parseFloat(item.quantity);
    if (item.derivative) {
        quantity = numberFormat(parseFloat(quantity) / parseFloat(item.derivative.ratio));
    }

    newExternalStock = numberFormat(parseFloat(item.external_stock) + parseFloat(quantity));

    return numberFormat(parseFloat(newExternalStock));
};

onMounted(() => {
    if (props.transferItems) {
        state.transferItems = props.transferItems.data;
        fetchPartialReceive();
    }
});

const closeBatchDetailsModal = () => {
    state.displayBatchDetailsModal = false;
    state.batchDetailsIndex = null;
    window.location.reload();
};

const openBatchDetailsModal = (itemIndex) => {
    state.batchDetailsIndex = itemIndex;

    if (
        state.transferItems[state.batchDetailsIndex].batch_details.length === 0
    ) {
        state.transferItems[state.batchDetailsIndex].batch_details = JSON.parse(JSON.stringify(state.transferItems[state.batchDetailsIndex].batches));
        state.batchDetails = state.transferItems[state.batchDetailsIndex].batch_details;
    }

    state.displayBatchDetailsModal = true;
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

    const purchaseOrderFulfillmentItem = state.batchDetails.find(batchDetail => 'purchase_order_fulfillment_item_id' in batchDetail);

    const purchaseOrderFulfillmentItemId = purchaseOrderFulfillmentItem ? purchaseOrderFulfillmentItem.purchase_order_fulfillment_item_id : null;

    axios.post(route(props.updateBatchDetailsUrl, purchaseOrderFulfillmentItemId), {
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

const addNewBatchDetails = () => {
    state.batchDetails.push({
        batch_number: null,
        is_discrepancy: true,
        quantity: 0,
        received_quantity: 0,
    });
};

const removeBatchDetailsOf = (index) => {
    const purchaseOrderFulfillmentItem = state.batchDetails.find(batchDetail => 'purchase_order_fulfillment_item_id' in batchDetail);

    const purchaseOrderFulfillmentItemId = purchaseOrderFulfillmentItem ? purchaseOrderFulfillmentItem.purchase_order_fulfillment_item_id : null;

    axios.post(route(props.deleteBatchDetailUrl, purchaseOrderFulfillmentItemId), {
        batch_number: state.batchDetails[index].batch_number,
    });
    state.batchDetails.splice(index, 1);
};

const updateBatchReceivedQuantity = (value, index) => {
    const batchData = state.batchDetails[index];
    state.batchDetails[index].received_quantity = value;
    state.batchDetails[index].is_discrepancy = batchData.quantity !== value;
};

const getPartialReceiveStatusColor = (status) => {
    if (status === props.partiallyReceiveStatuses.approved) {
        return 'btn btn-rounded btn-success-soft';
    }

    if (status === props.partiallyReceiveStatuses.completed) {
        return 'btn btn-rounded btn-success-soft';
    }

    if (status === props.partiallyReceiveStatuses.cancelled) {
        return 'btn btn-rounded btn-danger-soft';
    }

    if (status === props.partiallyReceiveStatuses.draft) {
        return 'btn btn-rounded btn-warning-soft';
    }
};

const delayTime = 1000;

const markAsApproved = (partialReceiveFulfillmentId, dismiss) => {
    const message = 'Are you sure you want to approve the partial receive?';
    confirmDialogBoxWithCenterText(message, () => {
        router.post(route(props.markAsApprovedUrl, partialReceiveFulfillmentId), {}, {
            onSuccess: ()  => {
                showSuccessNotification('Partial Receive Fulfillment Approve successfully');
                setTimeout(() => {
                    window.location.reload();
                }, delayTime);
            }
        });
    });
    dismiss();
};

const markAsCompleted = (partialReceiveFulfillmentId, dismiss) => {
    const message = 'Are you sure you want to completed the Partial Receive?';
    confirmDialogBoxWithCenterText(message, () => {
        router.post(route(props.markAsCompletedUrl, partialReceiveFulfillmentId), {}, {
            onSuccess: ()  => {
                showSuccessNotification('Partial Receive Fulfillment Complete successfully');
                setTimeout(() => {
                    window.location.reload();
                }, delayTime);
            }
        });
    });
    dismiss();
};

const markAsCancelled = (partialReceiveFulfillmentId, dismiss) => {
    const message = 'Are you sure you want to cancel the Partial Receive?';
    confirmDialogBoxWithCenterText(message, () => {
        router.post(route(props.markAsCancelledUrl, partialReceiveFulfillmentId), {}, {
            onSuccess: ()  => {
                showSuccessNotification('Partial Receive Fulfillment Cancel successfully');
                setTimeout(() => {
                    window.location.reload();
                }, delayTime);
            }
        });
    });
    dismiss();
};
</script>
