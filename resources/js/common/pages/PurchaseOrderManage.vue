<template>
    <PageTitle :title="getHeadingText()" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Purchase Order
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span>
                            {{ getHeadingText() }}
                        </span>
                    </h2>
                </div>
                <form @submit.prevent="savePurchaseOrder();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-4">
                            <template v-if="purchaseOrder">
                                <div
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                                >
                                    <p
                                        v-if="purchaseOrderForm.source_company_name"
                                        class="form-label font-medium text-md mb-1"
                                    >
                                        External Company Name: {{ purchaseOrderForm.source_company_name }}
                                    </p>
                                    <p class="form-label font-bold text-xl">
                                        From: {{ purchaseOrderForm.source_location_name }}
                                    </p>
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                                >
                                    <p
                                        v-if="purchaseOrderForm.destination_company_name"
                                        class="form-label font-medium text-md mb-1"
                                    >
                                        External Company Name: {{ purchaseOrderForm.destination_company_name }}
                                    </p>
                                    <p class="form-label font-bold text-xl">
                                        To: {{ purchaseOrderForm.destination_location_name }}
                                    </p>
                                </div>
                            </template>

                            <div
                                v-if="!purchaseOrder && defaultOrderType === staticDetails.purchase_request"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                            >
                                <p class="font-medium text-base">
                                    From <span class="text-danger">*</span>
                                </p>

                                <FormSelectBox
                                    :selected-record="purchaseOrderForm.external_company_id"
                                    :records="externalCompanies"
                                    :required="true"
                                    validation-field-name="external_company_id"
                                    placeholder="Please select external company"
                                    input-label="Select Vendor"
                                    class="mt-[2]"
                                    @update:selected-record="updateExternalCompanyId"
                                />

                                <div
                                    v-if="purchaseOrderForm.external_company_id"
                                    class="mt-2"
                                >
                                    <JTabs
                                        :records="locationTypes"
                                        :selected-record="purchaseOrderForm.external_type_id"
                                        return-selected-record="id"
                                        :required="true"
                                        @update:selected-record="updateExternalLocationType"
                                    />

                                    <TabPanel
                                        v-if="purchaseOrderForm.external_type_id === staticDetails.staticLocationTypes.store"
                                        class="active"
                                    >
                                        <FormSelectBox
                                            :selected-record="purchaseOrderForm.external_location_id"
                                            :records="state.externalStores"
                                            :required="true"
                                            validation-field-name="external_location_id"
                                            placeholder="Please select store"
                                            input-label="Stores"
                                            @update:selected-record="updateExternalLocationId"
                                        />
                                    </TabPanel>

                                    <TabPanel
                                        v-if="purchaseOrderForm.external_type_id === staticDetails.staticLocationTypes.warehouse"
                                        class="active"
                                    >
                                        <FormSelectBox
                                            :selected-record="purchaseOrderForm.external_location_id"
                                            :records="state.externalWarehouses"
                                            :required="true"
                                            validation-field-name="external_location_id"
                                            placeholder="Please select warehouse"
                                            input-label="Warehouses"
                                            @update:selected-record="updateExternalLocationId"
                                        />
                                    </TabPanel>
                                </div>
                            </div>

                            <div
                                v-if="!purchaseOrder && defaultOrderType === staticDetails.purchase_request"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border"
                            >
                                <p class="font-medium text-base mb-2">
                                    To <span class="text-danger">*</span>
                                </p>

                                <JTabs
                                    :records="locationTypes"
                                    :selected-record="purchaseOrderForm.type_id"
                                    :required="true"
                                    :disabled="selectedLocationId !== null"
                                    return-selected-record="id"
                                    @update:selected-record="updateLocationType"
                                />

                                <TabPanel
                                    v-if="purchaseOrderForm.type_id === staticDetails.staticLocationTypes.store"
                                    class="active"
                                >
                                    <FormSelectBox
                                        :selected-record="purchaseOrderForm.location_id"
                                        :records="stores"
                                        :required="true"
                                        :disabled="selectedLocationId !== null"
                                        validation-field-name="location_id"
                                        placeholder="Please select store"
                                        input-label="Stores"
                                        @update:selected-record="updateLocationId"
                                    />
                                </TabPanel>

                                <TabPanel
                                    v-if="purchaseOrderForm.type_id === staticDetails.staticLocationTypes.warehouse"
                                    class="active"
                                >
                                    <FormSelectBox
                                        :selected-record="purchaseOrderForm.location_id"
                                        :records="warehouses"
                                        :required="true"
                                        :disabled="selectedLocationId !== null"
                                        validation-field-name="location_id"
                                        placeholder="Please select warehouse"
                                        input-label="Warehouses"
                                        @update:selected-record="updateLocationId"
                                    />
                                </TabPanel>
                            </div>

                            <div
                                v-if="!purchaseOrder && defaultOrderType === staticDetails.transfer_request"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                            >
                                <p class="font-medium text-base mb-2">
                                    From <span class="text-danger">*</span>
                                </p>

                                <JTabs
                                    :records="locationTypes"
                                    :selected-record="purchaseOrderForm.type_id"
                                    :required="true"
                                    :disabled="selectedLocationId !== null"
                                    return-selected-record="id"
                                    @update:selected-record="updateLocationType"
                                />

                                <TabPanel
                                    v-if="purchaseOrderForm.type_id === staticDetails.staticLocationTypes.store"
                                    class="active"
                                >
                                    <FormSelectBox
                                        :selected-record="purchaseOrderForm.location_id"
                                        :records="stores"
                                        :required="true"
                                        :disabled="selectedLocationId !== null"
                                        validation-field-name="location_id"
                                        placeholder="Please select store"
                                        input-label="Stores"
                                        @update:selected-record="updateLocationId"
                                    />
                                </TabPanel>

                                <TabPanel
                                    v-if="purchaseOrderForm.type_id === staticDetails.staticLocationTypes.warehouse"
                                    class="active"
                                >
                                    <FormSelectBox
                                        :selected-record="purchaseOrderForm.location_id"
                                        :records="warehouses"
                                        :required="true"
                                        :disabled="selectedLocationId !== null"
                                        validation-field-name="location_id"
                                        placeholder="Please select warehouse"
                                        input-label="Warehouses"
                                        @update:selected-record="updateLocationId"
                                    />
                                </TabPanel>
                            </div>

                            <div
                                v-if="!purchaseOrder && defaultOrderType === staticDetails.transfer_request"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border"
                            >
                                <p class="font-medium text-base">
                                    To <span class="text-danger">*</span>
                                </p>

                                <FormSelectBox
                                    :selected-record="purchaseOrderForm.external_company_id"
                                    :records="externalCompanies"
                                    :required="true"
                                    validation-field-name="external_company_id"
                                    placeholder="Please select external company"
                                    input-label="Select Member"
                                    class="mt-[2]"
                                    @update:selected-record="updateExternalCompanyId"
                                />

                                <div
                                    v-if="purchaseOrderForm.external_company_id"
                                    class="mt-2"
                                >
                                    <JTabs
                                        :records="locationTypes"
                                        :selected-record="purchaseOrderForm.external_type_id"
                                        :required="true"
                                        return-selected-record="id"
                                        @update:selected-record="updateExternalLocationType"
                                    />

                                    <TabPanel
                                        v-if="purchaseOrderForm.external_type_id === staticDetails.staticLocationTypes.store"
                                        class="active"
                                    >
                                        <FormSelectBox
                                            :selected-record="purchaseOrderForm.external_location_id"
                                            :records="state.externalStores"
                                            :required="true"
                                            validation-field-name="external_location_id"
                                            placeholder="Please select store"
                                            input-label="Stores"
                                            @update:selected-record="updateExternalLocationId"
                                        />
                                    </TabPanel>

                                    <TabPanel
                                        v-if="purchaseOrderForm.external_type_id === staticDetails.staticLocationTypes.warehouse"
                                        class="active"
                                    >
                                        <FormSelectBox
                                            :selected-record="purchaseOrderForm.external_location_id"
                                            :records="state.externalWarehouses"
                                            :required="true"
                                            validation-field-name="external_location_id"
                                            placeholder="Please select warehouse"
                                            input-label="Warehouses"
                                            @update:selected-record="updateExternalLocationId"
                                        />
                                    </TabPanel>
                                </div>
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="purchaseOrderForm.reference_number"
                                    input-name="reference_number"
                                    input-label="Reference Number"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <JDatePicker
                                    v-model:input-value="purchaseOrderForm.require_date"
                                    input-label="Delivery by"
                                    validation-field-name="require_date"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="purchaseOrderForm.attention"
                                    input-name="attention"
                                    input-label="Attention"
                                    placeholder="Enter Name"
                                />
                            </div>
                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextarea
                                    v-model:input-value="purchaseOrderForm.remarks"
                                    input-name="remarks"
                                    input-label="Remarks"
                                />
                            </div>
                        </div>

                        <span
                            v-if="purchaseOrderForm.external_location_id &&
                                purchaseOrderForm.location_id"
                        >
                            <div class="flex flex-wrap mt-10 w-full text-left sm:text-right mb-4 justify-between">
                                <h4 class="self-center block font-medium text-base text-primary-p3">
                                    Items to transfer
                                </h4>
                                <div>
                                    <OutlinePrimaryButton
                                        text="Advance Selection"
                                        class="shadow-md text-sm mx-1 mb-2 sm:mb-0"
                                        @click="displayAdvanceMatrixProductSearchModalButton"
                                    />

                                    <OutlinePrimaryButton
                                        text="Bulk Upload"
                                        class="shadow-md text-sm mx-1"
                                        @click="state.displayBulkUploadProductsModal = !state.displayBulkUploadProductsModal"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="state.displayBulkUploadProductsModal"
                                class="p-5 mb-5 rounded transfer_order_bulk_products bg-slate-200"
                            >
                                <FileUploadAndDisplayRecordsForInterCompanyTransfer
                                    v-if="state.displayBulkUploadProductsModal"
                                    :selected-products="state.selectedProducts"
                                    :unmatched-products="state.unmatchedProducts"
                                    :product-upc-url="productFileUploadForUpcUrl"
                                    get-record-name="quantity"
                                    input-label="Bulk Upload Products"
                                    validation-field-name="purchase-order-items"
                                    file-path="/files/purchase-order-items-sample-file.xlsx"
                                    @display-selected-products-modal="openSelectedProductsModal"
                                    @update:column-details="updateColumnDetails"
                                    @display-unmatched-products-modal="openUnmatchedProductsModal"
                                />
                            </div>

                            <div class="overflow-unset overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="whitespace-nowrap w-4/12">
                                                Product Selection <span class="text-danger">*</span>
                                            </th>
                                            <th class="whitespace-nowrap">Stock</th>
                                            <th class="whitespace-nowrap">
                                                Transfer Stock <span class="text-danger">*</span>
                                            </th>
                                            <th class="whitespace-nowrap">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(item, itemIndex) in purchaseOrderForm.transfer_items"
                                            :key="'stock-transfer-item-' + itemIndex"
                                        >
                                            <td class="whitespace-nowrap w-4/12">
                                                <JProductFilter
                                                    :product-search-url="route(getFilteredInventoryProductsUrl)"
                                                    :get-product-url-name="getProductUrlName"
                                                    :selected-product="purchaseOrderForm.transfer_items[itemIndex].product ?? null"
                                                    :selected-product-id="purchaseOrderForm.transfer_items[itemIndex].product_id"
                                                    :validation-field-name="'transfer_items.' + itemIndex + '.product_id'"
                                                    @update:product-selected="productSelected($event, itemIndex)"
                                                    @update:display-product-filters="displayUpdateFilter(itemIndex)"
                                                />

                                                <strong
                                                    v-if="!pageProps.product_variant"
                                                >
                                                    Color: {{ purchaseOrderForm.transfer_items[itemIndex].product_color }}
                                                </strong>

                                                <strong 
                                                    v-if="!pageProps.product_variant"
                                                    class="pl-4"
                                                >
                                                    Size: {{ purchaseOrderForm.transfer_items[itemIndex].product_size }}
                                                </strong>
                                                <strong 
                                                    v-if="pageProps.product_variant"
                                                    class="pl-4"
                                                >
                                                    <p
                                                        v-for="(product_variant, index) in purchaseOrderForm.transfer_items[itemIndex].product_variant_values"
                                                        :key="index"
                                                        class="pl-4"
                                                    >
                                                        {{ product_variant.attribute.name }} : {{ product_variant.value }}
                                                    </p>
                                                </strong>
                                                <strong class="pl-4">
                                                    UOM:
                                                    {{ purchaseOrderForm.transfer_items[itemIndex].product_uom ?? 'N/A' }}
                                                </strong>
                                            </td>
                                            <td class="mt-10 whitespace-nowrap">
                                                <span v-if="purchaseOrderForm.transfer_items[itemIndex].product_id">
                                                    <span
                                                        class="text-lg font-bold"
                                                    >{{ defaultOrderType === staticDetails.transfer_request ? 'Before Transferred' : 'Before Transfer' }}:</span><br>
                                                    Stock On Hand:
                                                    <span class="font-medium">

                                                        {{ getOldStock(item) }}

                                                        {{ purchaseOrderForm.transfer_items[itemIndex].product_uom }}

                                                        <Tippy :content="'Reserved Stocks: ' + getOldReservedStock(item)">
                                                            <Info
                                                                class="text-cyan-400 inline-block"
                                                                :size="15"
                                                            />
                                                        </Tippy>
                                                    </span>
                                                    <br>

                                                    {{ defaultOrderType === staticDetails.transfer_request ? 'Stock with Member' : 'Stock with Supplier' }}
                                                    :
                                                    <span class="font-medium">

                                                        {{ getOldExternalStock(item) }}

                                                        {{ purchaseOrderForm.transfer_items[itemIndex].product_uom }}

                                                        <Tippy
                                                            :content="'Reserved Stocks: ' + getOldExternalReservedStock(item)"
                                                        >
                                                            <Info
                                                                class="text-cyan-400 inline-block"
                                                                :size="15"
                                                            />
                                                        </Tippy>
                                                    </span>
                                                    <br><br>

                                                    <span
                                                        class="text-lg font-bold"
                                                    >{{ defaultOrderType === staticDetails.transfer_request ? 'After Transferred' : 'After Transfer' }}:</span><br>

                                                    Balance Stock:
                                                    <span class="font-medium">
                                                        {{ getNewStock(item) }}
                                                        {{ purchaseOrderForm.transfer_items[itemIndex].product_uom }}
                                                    </span>

                                                    <br>

                                                    {{ defaultOrderType === staticDetails.transfer_request ? 'Balance Stock with Member' : 'Balance Stock with Supplier' }}:
                                                    <span class="font-medium">
                                                        {{ getNewExternalStock(item) }}
                                                        {{ purchaseOrderForm.transfer_items[itemIndex].product_uom }}
                                                    </span>
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap">
                                                <div
                                                    v-if="defaultOrderType === staticDetails.transfer_request && parseFloat(item.stock) <= 0"
                                                    class="w-24 text-center form-control text-danger font-extrabold"
                                                >
                                                    0
                                                </div>

                                                <input
                                                    v-if="(defaultOrderType === staticDetails.transfer_request && parseFloat(item.stock) > 0) || defaultOrderType === staticDetails.purchase_request"
                                                    type="text"
                                                    class="form-control w-24 text-center"
                                                    :value="item.quantity"
                                                    step="any"
                                                    @input="updateTransferStock($event, itemIndex, item.stock, item.quantity)"
                                                >

                                                <ValidationError
                                                    :validation-field-name="'transfer_items.' + itemIndex + '.quantity'"
                                                />

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
                                                    @update:selected-record="updateUnitOfMeasureDerivativeId($event, itemIndex, item.derivatives)"
                                                />

                                                <div
                                                    v-if="item.unit_of_measure_derivative_id"
                                                    class="mt-2 text-lg font-bold"
                                                >
                                                    {{ parseFloat(item.quantity) / parseFloat(item.derivative.ratio) }}

                                                    {{ purchaseOrderForm.transfer_items[itemIndex].product_uom }}
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap">
                                                <DeleteButton
                                                    type="button"
                                                    class="w-12 h-8"
                                                    :disabled="purchaseOrderForm.transfer_items.length <= 1"
                                                    @click="removeTransferItem(itemIndex)"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="grid grid-flow-col grid-rows-1 gap-4">
                                <OutlinePrimaryButton
                                    text="+ Add New"
                                    type="button"
                                    class="border-dashed"
                                    @click="addNewTransferItem()"
                                />
                            </div>
                        </span>

                        <div class="flex flex-row ml-auto">
                            <Link :href="route(props.getPurchaseOrderIndexUrl)">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mt-5"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="purchaseOrder ? 'Update' : 'Submit'"
                                class="w-24 mt-5 ml-1"
                            />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <JProductFilterDetails
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="route(productFilterDetailsSearchUrl)"
        :filtered-category-url="route(productFilterDetailsCategorySearchUrl)"
        :filtered-brand-url="route(productFilterDetailsBrandSearchUrl)"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />

    <SelectedProducts
        v-if="state.dynamicColumns.length > 0"
        v-model:columns="state.dynamicColumns"
        :modal-show="state.displaySelectedProductsModal"  
        :columns="state.fields"
        :records="state.selectedProducts"
        @close-modal="closeModal"
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
    </SelectedProducts>

    <UnmatchedProducts
        :modal-show="state.displayUnmatchedProductsModal"
        :records="state.unmatchedProducts"
        :show-non-inventory-products-upload="true"
        @close-modal="closeModal"
    />

    <AdvanceMatrixProductSelectionForInterCompanyTransferModal
        v-if="state.displayAdvanceProductSelectionModal"
        :modal-show="state.displayAdvanceProductSelectionModal"
        :purchase-order-form="purchaseOrderForm"
        :default-order-type="defaultOrderType"
        :static-details="staticDetails"
        :product-article-search-url="advanceProductSearchUrl"
        @update:filter-advance-products-selection="advanceFilterProductsSelection"
        @close-modal="closeAdvanceProductSelectionModal()"
    />
</template>

<script setup>
import AdvanceMatrixProductSelectionForInterCompanyTransferModal from '@commonComponents/AdvanceMatrixProductSelectionForInterCompanyTransferModal.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import FileUploadAndDisplayRecordsForInterCompanyTransfer from '@commonComponents/FileUploadAndDisplayRecordsForInterCompanyTransfer.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import JTabs from '@commonComponents/JTabs.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import ValidationError from '@commonComponents/ValidationError.vue';
import { numberFormat } from '@commonServices/helper';
import { showErrorNotification } from '@commonServices/notifier';
import { TabPanel } from '@commonVendor/tab';
import { usePage, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Info } from 'lucide-vue-next';
import onScan from 'onscan.js/onscan.js';
import { onMounted, computed, onUnmounted, reactive, watch } from 'vue';
import { route } from 'ziggy';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    externalStores: {
        type: Array,
        default: () => [],
    },
    externalWarehouses: {
        type: Array,
        default: () => [],
    },
    externalCompanies: {
        type: Array,
        required: true,
    },
    orderTypes: {
        type: Object,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    purchaseOrder: {
        type: Object,
        default: () => { },
    },
    defaultOrderType: {
        type: Number,
        required: true,
    },
    createdByCompanyId: {
        type: Number,
        default: null,
    },
    selectedLocationId: {
        type: [String, Number, null],
        required: true,
    },
    defaultLocationTypeId: {
        type: [String, Number, null],
        required: true,
    },
    locationTypes: {
        type: Object,
        default: () => { },
    },
    productFileUploadForUpcUrl: {
        type: String,
        required: true,
    },
    getFilteredInventoryProductsUrl: {
        type: String,
        required: true,
    },
    productFilterSearchUrl: {
        type: String,
        required: true,
    },
    getProductUrlName: {
        type: String,
        required: true,
    },
    cancelUrl: {
        type: String,
        required: true,
    },
    productFilterDetailsSearchUrl: {
        type: String,
        required: true,
    },
    productFilterDetailsCategorySearchUrl: {
        type: String,
        required: true,
    },
    productFilterDetailsBrandSearchUrl: {
        type: String,
        required: true,
    },
    advanceProductSearchUrl: {
        type: String,
        required: true,
    },
    getExternalLocationUrl: {
        type: String,
        required: true,
    },
    updatePurchaseOrderUrl: {
        type: String,
        required: true,
    },
    storePurchaseOrderUrl: {
        type: String,
        required: true,
    },
    getLocationInventoryStocksUrl: {
        type: String,
        required: true,
    },
    getPurchaseOrderIndexUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    fields: [
        {
            key: 'name',
        }, {
            key: 'product_variant_values',
            label: 'Attributes',
        }, {
            key: 'quantity',
        },
    ],

    externalWarehouses: [],
    externalStores: [],
    selectedProducts: [],
    unmatchedProducts: [],
    selectedProductIds: [],

    filterModalIndex: 0,

    displayInventoryUpdateFilterModal: false,
    displayBulkUploadProductsModal: false,
    displaySelectedProductsModal: false,
    displayUnmatchedProductsModal: false,
    displayAdvanceProductSelectionModal: false,

    selectedLocationId: null,
    dynamicColumns: [],
});

const purchaseOrderForm = useForm({
    type_id: props.defaultLocationTypeId,
    location_id: props.selectedLocationId,
    external_company_id: null,
    external_type_id: props.staticDetails.staticLocationTypes.store,
    external_location_id: null,
    created_by_company_id: props.createdByCompanyId,
    require_date: null,
    attention: null,
    reference_number: null,
    remarks: null,
    transfer_items: [],
    order_type: props.defaultOrderType,
    source_location_name: null,
    destination_location_name: null,
    source_company_name: null,
    destination_company_name: null,
});

const getHeadingText = () => {
    const headingText = props.purchaseOrder ? 'Edit ' : 'Create ';

    const orderType = Object.values(props.orderTypes).find(type => type.id === purchaseOrderForm.order_type);

    const transferType = orderType ? orderType.name : '';

    return headingText + transferType;
};

const updateExternalLocationType = (typeId) => {
    purchaseOrderForm.external_type_id = typeId;
    purchaseOrderForm.external_location_id = null;
};

const updateLocationType = (typeId) => {
    purchaseOrderForm.type_id = typeId;
    purchaseOrderForm.location_id = null;
};

const addNewTransferItem = () => {
    purchaseOrderForm.transfer_items.push({
        product_id: null,
        product_color: null,
        product_size: null,
        product_variant_values: [],
        product_uom: null,
        external_product_name: null,
        unit_of_measure_derivative_id: null,
        derivative: null,
        derivatives: null,
        stock: 0,
        reserved_stock: 0,
        external_stock: 0,
        external_reserved_stock: 0,
        quantity: 0,
    });
};

const updateExternalLocationId = (locationId) => {
    purchaseOrderForm.external_location_id = locationId;

    if (purchaseOrderForm.transfer_items.length <= 0) {
        addNewTransferItem();
    }
};

const updateLocationId = (locationId) => {
    purchaseOrderForm.location_id = parseInt(locationId);

    if (purchaseOrderForm.transfer_items.length <= 0) {
        addNewTransferItem();
    }
};

const updateExternalCompanyId = (externalCompanyId) => {
    purchaseOrderForm.external_company_id = externalCompanyId;

    axios.get(route(props.getExternalLocationUrl, externalCompanyId))
        .then((response) => {
            state.externalStores = response.data.externalStores;
            state.externalWarehouses = response.data.externalWarehouses;
        });
};

const savePurchaseOrder = () => {
    if (props.purchaseOrder) {
        purchaseOrderForm.post(route(props.updatePurchaseOrderUrl, props.purchaseOrder.data.id));
        return;
    }

    purchaseOrderForm.post(route(props.storePurchaseOrderUrl));
};

const advanceFilterProductsSelection = (selectedProducts) => {
    state.selectedProductIds = [];

    for (const productKey in selectedProducts) {
        for (const key in purchaseOrderForm.transfer_items) {
            if (purchaseOrderForm.transfer_items[key].product_id === selectedProducts[productKey].id) {
                showErrorNotification('The product has already been selected.');
                return;
            }
        }

        if (purchaseOrderForm.transfer_items[0].product_id === null) {
            purchaseOrderForm.transfer_items.splice(0, 1);
        }

        let productColor = '';
        let productSize = '';

        if (! pageProps.value.product_variant) {
            productColor = selectedProducts[productKey].color ? selectedProducts[productKey].color.name : 'N/A';
            productSize = selectedProducts[productKey].size ? selectedProducts[productKey].size.name : 'N/A';
        }

        purchaseOrderForm.transfer_items.push({
            product_id: selectedProducts[productKey].id,
            product: {
                id: selectedProducts[productKey].id,
                name: selectedProducts[productKey].compound_product_name,
            },
            product_color: productColor,
            product_size: productSize,
            product_variant_values: selectedProducts[productKey].product_variant_values,
            product_uom: selectedProducts[productKey].unit_of_measure ? selectedProducts[productKey].unit_of_measure.name : null,
            external_product_name: null,
            unit_of_measure_derivative_id: null,
            derivative: null,
            derivatives: selectedProducts[productKey].derivatives ? selectedProducts[productKey].derivatives : null,
            quantity: selectedProducts[productKey].quantity ? selectedProducts[productKey].quantity : 0,
            stock: selectedProducts[productKey].stock ?? 0,
            external_stock: selectedProducts[productKey].external_stock ?? 0,
            external_reserved_stock: selectedProducts[productKey].external_reserved_stock ?? 0,
            reserved_stock: selectedProducts[productKey].stock ?? 0,
        });
    }
};

const closeAdvanceProductSelectionModal = () => {
    state.displayAdvanceProductSelectionModal = false;
    onScanProductCheck();
};

const displayAdvanceMatrixProductSearchModalButton = () => {
    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }

    state.displayAdvanceProductSelectionModal = true;
};

onUnmounted(() => {
    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }
});

const getFilteredColumns = () => {
    const columns = state.fields || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['product_color', 'product_size'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'product_variant_values');
};

onMounted(() => {
    if (props.purchaseOrder) {
        Object.assign(purchaseOrderForm, JSON.parse(JSON.stringify(props.purchaseOrder.data)));
    }

    if (props.externalStores) {
        state.externalStores = props.externalStores;
    }

    if (props.externalWarehouses) {
        state.externalWarehouses = props.externalWarehouses;
    }
    state.dynamicColumns = getFilteredColumns();        
    onScanProductCheck();
});

const onScanProductCheck = () => {
    onScan.attachTo(document, {
        reactToPaste: true,
        onPaste: (pasteValue) => {
            axios.get(route(props.getFilteredInventoryProductsUrl), {
                params: {
                    search_text: pasteValue,
                    number_of_records: 5,
                }
            }).then((response) => {
                if (response.data.products[0]) {
                    const transferItems = purchaseOrderForm.transfer_items;
                    const lastIndex = transferItems.length - 1;

                    if (transferItems[lastIndex].product_id === null) {
                        productSelected(response.data.products[0], lastIndex);
                        return;
                    }

                    const oldProductIndex = purchaseOrderForm.transfer_items.find(product => product.product_id === response.data.products[0].id);
                    if (oldProductIndex) {
                        showErrorNotification('Product with UPC: ' + pasteValue + ' already selected.');
                        return;
                    }

                    addNewTransferItem();
                    productSelected(response.data.products[0], lastIndex + 1);
                }
            });
        },
    });
};

const updateTransferStock = (element, index) => {
    purchaseOrderForm.transfer_items[index].quantity = element.target.value === '' ? 0 : parseFloat(element.target.value);
};

const updateUnitOfMeasureDerivativeId = (derivativeId, index, derivatives) => {
    purchaseOrderForm.transfer_items[index].unit_of_measure_derivative_id = derivativeId;
    purchaseOrderForm.transfer_items[index].derivative = derivatives.find((derivative) => derivative.id === derivativeId);
};

const removeTransferItem = (index) => {
    purchaseOrderForm.transfer_items.splice(index, 1);
    state.selectedProducts.splice(index, 1);
};

const displayUpdateFilter = (index) => {
    state.displayInventoryUpdateFilterModal = true;
    state.filterModalIndex = index;

    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }
};

const productSelected = (selectedProduct, index) => {
    const itemToUpdate = purchaseOrderForm.transfer_items[index];

    itemToUpdate.product_id = null;
    itemToUpdate.product_color = 'N/A';
    itemToUpdate.product_size = 'N/A';
    itemToUpdate.product_variant_values = [];
    itemToUpdate.product_uom = null;
    itemToUpdate.unit_of_measure_derivative_id = null;
    itemToUpdate.derivative = null;
    itemToUpdate.derivatives = null;
    itemToUpdate.quantity = 0;
    itemToUpdate.stock = 0;
    itemToUpdate.reserved_stock = 0;
    itemToUpdate.external_stock = 0;
    itemToUpdate.external_reserved_stock = 0;

    const oldProductIndex = purchaseOrderForm.transfer_items.find(product => product.product_id === selectedProduct.id);

    if (oldProductIndex) {
        return;
    }

    if (selectedProduct) {
        itemToUpdate.product_id = selectedProduct.id;
        itemToUpdate.quantity = selectedProduct.quantity ?? 0;
        itemToUpdate.stock = selectedProduct.stock ?? 0;
        itemToUpdate.reserved_stock = selectedProduct.reserved_stock ?? 0;
        itemToUpdate.external_stock = selectedProduct.external_stock ?? 0;
        itemToUpdate.external_reserved_stock = selectedProduct.external_reserved_stock ?? 0;
        itemToUpdate.product_color = selectedProduct?.color?.name ?? 'N/A';
        itemToUpdate.product_size = selectedProduct?.size?.name ?? 'N/A';

        if (pageProps.value.product_variant) {
            itemToUpdate.product_variant_values = selectedProduct.product_variant_values;
        }

        if (!pageProps.value.product_variant) {
            itemToUpdate.product_uom = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.name : null;
            itemToUpdate.derivatives = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.derivatives : null;
        } else {
            itemToUpdate.product_uom = selectedProduct.master_product.unit_of_measure ? selectedProduct.master_product.unit_of_measure.name : null;
            itemToUpdate.derivatives = selectedProduct.master_product.unit_of_measure ? selectedProduct.master_product.unit_of_measure.derivatives : null;
        }

        getSelectedProductStock(selectedProduct.id, index);
    }
};

const getSelectedProductStock = (productId, index) => {
    const params = {
        product_ids: [productId],
        location_id: purchaseOrderForm.location_id,
        external_location_id: purchaseOrderForm.external_location_id
    };

    axios.get(route(props.getLocationInventoryStocksUrl), { params })
        .then((response) => {
            purchaseOrderForm.transfer_items[index].stock = response.data[0].stock;
            purchaseOrderForm.transfer_items[index].reserved_stock = response.data[0].reserved_stock;
            purchaseOrderForm.transfer_items[index].external_stock = response.data[0].external_stock ?? 0;
            purchaseOrderForm.transfer_items[index].external_reserved_stock = response.data[0].external_reserved_stock ?? 0;
        });
};

const filteredProductSelected = (selectedProduct) => {
    state.displayInventoryUpdateFilterModal = false;

    onScanProductCheck();
    productSelected(selectedProduct, state.filterModalIndex);
};

const openSelectedProductsModal = () => {    
    state.displaySelectedProductsModal = true;
};

const openUnmatchedProductsModal = () => {
    state.displayUnmatchedProductsModal = true;
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

watch(() => state.selectedProducts,
    () => {
        if (state.selectedProducts) {            
            purchaseOrderForm.transfer_items = [];
            state.selectedProductIds = [];
            for (const key in state.selectedProducts) {                
                purchaseOrderForm.transfer_items.push({
                    product_id: state.selectedProducts[key].id,
                    product: { id: state.selectedProducts[key].id, name: state.selectedProducts[key].compound_product_name },
                    quantity: state.selectedProducts[key].quantity,
                    product_color: state.selectedProducts[key].color ? state.selectedProducts[key].color.name : 'N/A',
                    product_size: state.selectedProducts[key].size ? state.selectedProducts[key].size.name : 'N/A',
                    product_variant_values: state.selectedProducts[key].product_variant_values,
                    product_uom: state.selectedProducts[key].unit_of_measure ?? 'N/A',
                    derivatives: state.selectedProducts[key].derivatives,
                    external_stock: state.selectedProducts[key].external_stock ?? 0,
                    external_reserved_stock: state.selectedProducts[key].external_reserved_stock ?? 0,
                });

                state.selectedProductIds.push(state.selectedProducts[key].id);
            }

            if (state.selectedProductIds.length) {
                getSelectedProductsStock();
            }
        }
    }
);

const closeModal = () => {
    if (state.displaySelectedProductsModal) {
        state.displaySelectedProductsModal = false;
        return;
    }

    if (state.displayUnmatchedProductsModal) {
        state.displayUnmatchedProductsModal = false;
    }
};

const getSelectedProductsStock = () => {
    const params = {
        product_ids: state.selectedProductIds,
        location_id: purchaseOrderForm.location_id,
        external_location_id: purchaseOrderForm.external_location_id,
    };

    axios.get(route(props.getLocationInventoryStocksUrl), { params })
        .then((response) => {
            const products = response.data;

            purchaseOrderForm.transfer_items.map(function (item) {
                const productIndex = products.findIndex(product => product.product_id === item.product_id);

                if (item.product_id === response.data[productIndex].product_id) {
                    item.stock = response.data[productIndex].stock ?? 0;
                    item.reserved_stock = response.data[productIndex].reserved_stock ?? 0;
                    item.external_stock = response.data[productIndex].external_stock ?? 0;
                    item.external_reserved_stock = response.data[productIndex].external_reserved_stock ?? 0;
                }
                return item;
            });

            return purchaseOrderForm.transfer_items;
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const getOldStock = (item) => {
    return numberFormat(parseFloat(item.stock));
};

const getOldReservedStock = (item) => {
    return numberFormat(parseFloat(item.reserved_stock ?? 0)) + ' ' + item.product_uom;
};

const getOldExternalStock = (item) => {
    return numberFormat(parseFloat(item.external_stock));
};

const getOldExternalReservedStock = (item) => {
    return numberFormat(parseFloat(item.external_reserved_stock)) + ' ' + item.product_uom;
};

const getNewStock = (item) => {
    let newStock = 0;

    let quantity = parseFloat(item.quantity);
    if (item.derivative) {
        quantity = numberFormat(parseFloat(quantity) / parseFloat(item.derivative.ratio));
    }

    if ((purchaseOrderForm.created_by_company_id && purchaseOrderForm.order_type === props.staticDetails.purchase_request) || (!purchaseOrderForm.created_by_company_id && purchaseOrderForm.order_type === props.staticDetails.transfer_request)) {
        newStock = numberFormat(parseFloat(item.stock) + parseFloat(quantity));
    }

    if ((purchaseOrderForm.created_by_company_id && purchaseOrderForm.order_type === props.staticDetails.transfer_request) || (!purchaseOrderForm.created_by_company_id && purchaseOrderForm.order_type === props.staticDetails.purchase_request)) {
        newStock = numberFormat(parseFloat(item.stock) - parseFloat(quantity));
    }

    return numberFormat(parseFloat(newStock));
};

const getNewExternalStock = (item) => {
    let newExternalStock = 0;

    let quantity = parseFloat(item.quantity);
    if (item.derivative) {
        quantity = numberFormat(parseFloat(quantity) / parseFloat(item.derivative.ratio));
    }

    if ((purchaseOrderForm.created_by_company_id && purchaseOrderForm.order_type === props.staticDetails.purchase_request) || (!purchaseOrderForm.created_by_company_id && purchaseOrderForm.order_type === props.staticDetails.transfer_request)) {
        newExternalStock = numberFormat(parseFloat(item.external_stock) - parseFloat(quantity));
    }

    if ((purchaseOrderForm.created_by_company_id && purchaseOrderForm.order_type === props.staticDetails.transfer_request) || (!purchaseOrderForm.created_by_company_id && purchaseOrderForm.order_type === props.staticDetails.purchase_request)) {
        newExternalStock = numberFormat(parseFloat(item.external_stock) + parseFloat(quantity));
    }
    return numberFormat(parseFloat(newExternalStock));
};
</script>
