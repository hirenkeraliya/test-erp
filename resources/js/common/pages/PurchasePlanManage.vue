<template>
    <PageTitle title="Purchase Plan" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Purchase Plan
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span>{{ purchasePlan ? 'Edit' : 'Add' }} Purchase Plan</span>
                    </h2>
                </div>
                <form @submit.prevent="savePurchasePlan();">
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-4">
                            <template v-if="purchasePlan">
                                <div
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                                >
                                    <p
                                        v-if="purchasePlanForm.source_company_name"
                                        class="form-label font-medium text-md mb-1"
                                    >
                                        External Company Name: {{ purchasePlanForm.source_company_name }}
                                    </p>
                                    <p class="form-label font-bold text-xl">
                                        From: {{ purchasePlanForm.source_location_name }}
                                    </p>
                                </div>

                                <div
                                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                                >
                                    <p
                                        v-if="purchasePlanForm.destination_company_name"
                                        class="form-label font-medium text-md mb-1"
                                    >
                                        External Company Name: {{ purchasePlanForm.destination_company_name }}
                                    </p>
                                    <p class="form-label font-bold text-xl">
                                        To: {{ purchasePlanForm.destination_location_name }}
                                    </p>
                                </div>
                            </template>

                            <div
                                v-if="!purchasePlan"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                            >
                                <p class="font-medium text-base">
                                    From <span class="text-danger">*</span>
                                </p>

                                <FormSelectBox
                                    :selected-record="purchasePlanForm.vendor_id"
                                    :records="vendors"
                                    :required="true"
                                    validation-field-name="vendor_id"
                                    placeholder="Please select vendor"
                                    input-label="Select Vendor"
                                    class="mt-[2]"
                                    @update:selected-record="updateVendorId"
                                />
                            </div>

                            <div
                                v-if="!purchasePlan"
                                class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border"
                            >
                                <p class="font-medium text-base mb-2">
                                    To <span class="text-danger">*</span>
                                </p>

                                <JTabs
                                    :records="locationTypes"
                                    :selected-record="purchasePlanForm.type_id"
                                    :required="true"
                                    :disabled="selectedLocationId !== null"
                                    return-selected-record="id"
                                    @update:selected-record="updateLocationType"
                                />

                                <TabPanel
                                    v-if="purchasePlanForm.type_id === staticDetails.staticLocationTypes.store"
                                    class="active"
                                >
                                    <FormSelectBox
                                        :selected-record="purchasePlanForm.location_id"
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
                                    v-if="purchasePlanForm.type_id === staticDetails.staticLocationTypes.warehouse"
                                    class="active"
                                >
                                    <FormSelectBox
                                        :selected-record="purchasePlanForm.location_id"
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

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormInput
                                    v-model:input-value="purchasePlanForm.reference_number"
                                    input-name="reference_number"
                                    input-label="Reference Number"
                                />
                            </div>

                            <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
                                <FormTextarea
                                    v-model:input-value="purchasePlanForm.remarks"
                                    input-name="remarks"
                                    input-label="Remarks"
                                />
                            </div>
                        </div>

                        <span
                            v-if="purchasePlanForm.location_id"
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
                                <FileUploadAndDisplayRecordsForPurchasePlan
                                    v-if="state.displayBulkUploadProductsModal"
                                    :selected-products="state.selectedProducts"
                                    :unmatched-products="state.unmatchedProducts"
                                    :product-upc-url="productFileUploadForUpcUrl"
                                    get-record-name="quantity"
                                    input-label="Bulk Upload Products"
                                    validation-field-name="purchase-plan-items"
                                    file-path="/files/purchase-plan-items-sample-file.xlsx"
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
                                            <th class="whitespace-nowrap">Purchase Cost</th>
                                            <th class="whitespace-nowrap">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(item, itemIndex) in purchasePlanForm.transfer_items"
                                            :key="'stock-transfer-item-' + itemIndex"
                                        >
                                            <td class="whitespace-nowrap w-4/12">
                                                <JProductFilter
                                                    :product-search-url="route(getFilteredInventoryProductsUrl)"
                                                    :get-product-url-name="getProductUrlName"
                                                    :selected-product="purchasePlanForm.transfer_items[itemIndex].product ?? null"
                                                    :selected-product-id="purchasePlanForm.transfer_items[itemIndex].product_id"
                                                    :validation-field-name="'transfer_items.' + itemIndex + '.product_id'"
                                                    @update:product-selected="productSelected($event, itemIndex)"
                                                    @update:display-product-filters="displayUpdateFilter(itemIndex)"
                                                />

                                                <strong>
                                                    Color: {{ purchasePlanForm.transfer_items[itemIndex].product_color }}
                                                </strong>

                                                <strong class="pl-4">
                                                    Size: {{ purchasePlanForm.transfer_items[itemIndex].product_size }}
                                                </strong>
                                                <strong class="pl-4">
                                                    UOM:
                                                    {{ purchasePlanForm.transfer_items[itemIndex].product_uom ?? 'N/A' }}
                                                </strong>
                                            </td>
                                            <td class="mt-10 whitespace-nowrap">
                                                <span v-if="purchasePlanForm.transfer_items[itemIndex].product_id">
                                                    <span
                                                        class="text-lg font-bold"
                                                    >{{ 'Before Transfer' }}:</span><br>
                                                    Stock On Hand:
                                                    <span class="font-medium">

                                                        {{ getOldStock(item) }}

                                                        {{ purchasePlanForm.transfer_items[itemIndex].product_uom }}

                                                        <Tippy :content="'Reserved Stocks: ' + getOldReservedStock(item)">
                                                            <Info
                                                                class="text-cyan-400 inline-block"
                                                                :size="15"
                                                            />
                                                        </Tippy>
                                                    </span>
                                                    <br><br>

                                                    <span
                                                        class="text-lg font-bold"
                                                    >{{ 'After Transfer' }}:</span><br>

                                                    Balance Stock:
                                                    <span class="font-medium">
                                                        {{ getNewStock(item) }}
                                                        {{ purchasePlanForm.transfer_items[itemIndex].product_uom }}
                                                    </span>
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap">
                                                <input
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

                                                    {{ purchasePlanForm.transfer_items[itemIndex].product_uom }}
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap">
                                                <div>
                                                    <JSwitch
                                                        input-label="Is the cost price the same as the product cost?"
                                                        :is-checked="item.is_product_purchase_cost"
                                                        class="mt-6"
                                                        @update:is-checked="updateIsPurchaseCostRequire($event, itemIndex, item.is_product_purchase_cost)"
                                                    />
                                                </div>

                                                <div>
                                                    <FormInput
                                                        v-if="! item.is_product_purchase_cost"
                                                        v-model:input-value="item.purchase_cost"
                                                        placeholder="Enter Purchase Cost"
                                                        :input-group-prefix="currencySymbol"
                                                        @input="updatePurchaseCost($event, itemIndex, item.purchase_cost)"
                                                    />

                                                    <ValidationError
                                                        :validation-field-name="'transfer_items.' + itemIndex + '.purchase_cost'"
                                                    />
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap">
                                                <DeleteButton
                                                    type="button"
                                                    class="w-12 h-8"
                                                    :disabled="purchasePlanForm.transfer_items.length <= 1"
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
                            <Link :href="route(props.getPurchasePlanIndexUrl)">
                                <SecondaryButton
                                    type="button"
                                    text="Cancel"
                                    class="w-24 mt-5"
                                />
                            </Link>

                            <PrimaryButton
                                type="submit"
                                :text="purchasePlan ? 'Update' : 'Submit'"
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
        :modal-show="state.displaySelectedProductsModal"
        :columns="state.fields"
        :records="state.selectedProducts"
        @close-modal="closeModal"
    />

    <UnmatchedProducts
        :modal-show="state.displayUnmatchedProductsModal"
        :records="state.unmatchedProducts"
        :show-non-inventory-products-upload="true"
        @close-modal="closeModal"
    />

    <AdvanceMatrixProductSelectionForPurchasePlanModal
        v-if="state.displayAdvanceProductSelectionModal"
        :modal-show="state.displayAdvanceProductSelectionModal"
        :purchase-plan-form="purchasePlanForm"
        :static-details="staticDetails"
        :product-article-search-url="advanceProductSearchUrl"
        @update:filter-advance-products-selection="advanceFilterProductsSelection"
        @close-modal="closeAdvanceProductSelectionModal()"
    />
</template>

<script setup>
import AdvanceMatrixProductSelectionForPurchasePlanModal from '@commonComponents/AdvanceMatrixProductSelectionForPurchasePlanModal.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import FileUploadAndDisplayRecordsForPurchasePlan from '@commonComponents/FileUploadAndDisplayRecordsForPurchasePlan.vue';
import FormInput from '@commonComponents/FormInput.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
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
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Info } from 'lucide-vue-next';
import onScan from 'onscan.js/onscan.js';
import { computed, onMounted, onUnmounted, reactive, watch } from 'vue';
import { route } from 'ziggy';
import JSwitch from '@commonComponents/JSwitch.vue';
const pageProps = computed(() => usePage().props);
const currencySymbol = pageProps.value.currency_symbol;

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    vendors: {
        type: Array,
        required: true,
    },
    staticDetails: {
        type: Object,
        required: true,
    },
    purchasePlan: {
        type: Object,
        default: () => { },
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
    updatePurchasePlanUrl: {
        type: String,
        required: true,
    },
    storePurchasePlanUrl: {
        type: String,
        required: true,
    },
    getLocationInventoryStocksUrl: {
        type: String,
        required: true,
    },
    getPurchasePlanIndexUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    fields: [
        {
            key: 'name',
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
});

const purchasePlanForm = useForm({
    type_id: props.defaultLocationTypeId,
    location_id: props.selectedLocationId,
    vendor_id: null,
    total_amount: null,
    reference_number: null,
    remarks: null,
    transfer_items: [],
    source_location_name: null,
    destination_location_name: null,
});

const updateLocationType = (typeId) => {
    purchasePlanForm.type_id = typeId;
    purchasePlanForm.location_id = null;
};

const addNewTransferItem = () => {
    purchasePlanForm.transfer_items.push({
        product_id: null,
        product_color: null,
        product_size: null,
        product_uom: null,
        external_product_name: null,
        unit_of_measure_derivative_id: null,
        derivative: null,
        derivatives: null,
        stock: 0,
        reserved_stock: 0,
        quantity: 0,
        is_product_purchase_cost: true,
        purchase_cost: null,
    });
};

const updateLocationId = (locationId) => {
    purchasePlanForm.location_id = parseInt(locationId);

    if (purchasePlanForm.transfer_items.length <= 0) {
        addNewTransferItem();
    }
};

const updateVendorId = (vendorId) => {
    purchasePlanForm.vendor_id = vendorId;
};

const savePurchasePlan = () => {
    if (props.purchasePlan) {
        purchasePlanForm.post(route(props.updatePurchasePlanUrl, props.purchasePlan.data.id));
        return;
    }

    purchasePlanForm.post(route(props.storePurchasePlanUrl));
};

const advanceFilterProductsSelection = (selectedProducts) => {
    state.selectedProductIds = [];

    for (const productKey in selectedProducts) {
        for (const key in purchasePlanForm.transfer_items) {
            if (purchasePlanForm.transfer_items[key].product_id === selectedProducts[productKey].id) {
                showErrorNotification('The product has already been selected.');
                return;
            }
        }

        if (purchasePlanForm.transfer_items[0].product_id === null) {
            purchasePlanForm.transfer_items.splice(0, 1);
        }

        purchasePlanForm.transfer_items.push({
            product_id: selectedProducts[productKey].id,
            product: {
                id: selectedProducts[productKey].id,
                name: selectedProducts[productKey].compound_product_name,
            },
            product_color: selectedProducts[productKey].color ? selectedProducts[productKey].color.name : 'N/A',
            product_size: selectedProducts[productKey].size ? selectedProducts[productKey].size.name : 'N/A',
            product_uom: selectedProducts[productKey].unit_of_measure ? selectedProducts[productKey].unit_of_measure.name : null,
            unit_of_measure_derivative_id: null,
            derivative: null,
            derivatives: selectedProducts[productKey].derivatives ? selectedProducts[productKey].derivatives : null,
            quantity: selectedProducts[productKey].quantity ? selectedProducts[productKey].quantity : 0,
            stock: selectedProducts[productKey].stock ?? 0,
            reserved_stock: selectedProducts[productKey].stock ?? 0,
            is_product_purchase_cost: true,
            purchase_cost: null,
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

onMounted(() => {
    if (props.purchasePlan) {
        Object.assign(purchasePlanForm, JSON.parse(JSON.stringify(props.purchasePlan.data)));
    }
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
                    const transferItems = purchasePlanForm.transfer_items;
                    const lastIndex = transferItems.length - 1;

                    if (transferItems[lastIndex].product_id === null) {
                        productSelected(response.data.products[0], lastIndex);
                        return;
                    }

                    const oldProductIndex = purchasePlanForm.transfer_items.find(product => product.product_id === response.data.products[0].id);
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
    purchasePlanForm.transfer_items[index].quantity = element.target.value === '' ? 0 : parseFloat(element.target.value);
};

const updatePurchaseCost = (element, index) => {
    purchasePlanForm.transfer_items[index].purchase_cost = parseFloat(element.target.value);
};

const updateIsPurchaseCostRequire = (element, index) => {
    purchasePlanForm.transfer_items[index].is_product_purchase_cost = element;

    if (purchasePlanForm.transfer_items[index].is_product_purchase_cost) {
        purchasePlanForm.transfer_items[index].purchase_cost = '';
    }
};

const updateUnitOfMeasureDerivativeId = (derivativeId, index, derivatives) => {
    purchasePlanForm.transfer_items[index].unit_of_measure_derivative_id = derivativeId;
    purchasePlanForm.transfer_items[index].derivative = derivatives.find((derivative) => derivative.id === derivativeId);
};

const removeTransferItem = (index) => {
    purchasePlanForm.transfer_items.splice(index, 1);
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
    const itemToUpdate = purchasePlanForm.transfer_items[index];

    itemToUpdate.product_id = null;
    itemToUpdate.product_color = 'N/A';
    itemToUpdate.product_size = 'N/A';
    itemToUpdate.product_uom = null;
    itemToUpdate.unit_of_measure_derivative_id = null;
    itemToUpdate.derivative = null;
    itemToUpdate.derivatives = null;
    itemToUpdate.quantity = 0;
    itemToUpdate.stock = 0;
    itemToUpdate.reserved_stock = 0;

    const oldProductIndex = purchasePlanForm.transfer_items.find(product => product.product_id === selectedProduct.id);

    if (oldProductIndex) {
        return;
    }

    if (selectedProduct) {
        itemToUpdate.product_id = selectedProduct.id;
        itemToUpdate.quantity = selectedProduct.quantity ?? 0;
        itemToUpdate.stock = selectedProduct.stock ?? 0;
        itemToUpdate.reserved_stock = selectedProduct.reserved_stock ?? 0;
        itemToUpdate.product_color = selectedProduct?.color?.name ?? 'N/A';
        itemToUpdate.product_size = selectedProduct?.size?.name ?? 'N/A';
        itemToUpdate.product_uom = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.name : null;

        if (selectedProduct.unit_of_measure && selectedProduct.unit_of_measure.derivatives) {
            itemToUpdate.derivatives = selectedProduct.unit_of_measure.derivatives;
        }

        getSelectedProductStock(selectedProduct.id, index);
    }
};

const getSelectedProductStock = (productId, index) => {
    const params = {
        product_ids: [productId],
        location_id: purchasePlanForm.location_id,
    };

    axios.get(route(props.getLocationInventoryStocksUrl), { params })
        .then((response) => {
            purchasePlanForm.transfer_items[index].stock = response.data[0].stock;
            purchasePlanForm.transfer_items[index].reserved_stock = response.data[0].reserved_stock;
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
            purchasePlanForm.transfer_items = [];
            state.selectedProductIds = [];
            for (const key in state.selectedProducts) {
                purchasePlanForm.transfer_items.push({
                    product_id: state.selectedProducts[key].id,
                    product: { id: state.selectedProducts[key].id, name: state.selectedProducts[key].compound_product_name },
                    quantity: state.selectedProducts[key].quantity,
                    product_color: state.selectedProducts[key].color ? state.selectedProducts[key].color.name : 'N/A',
                    product_size: state.selectedProducts[key].size ? state.selectedProducts[key].size.name : 'N/A',
                    product_uom: state.selectedProducts[key].unit_of_measure ? state.selectedProducts[key].unit_of_measure.name : 'N/A',
                    derivatives: state.selectedProducts[key].derivatives,
                    is_product_purchase_cost: true,
                    purchase_cost: null,
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
        location_id: purchasePlanForm.location_id,
    };

    axios.get(route(props.getLocationInventoryStocksUrl), { params })
        .then((response) => {
            const products = response.data;

            purchasePlanForm.transfer_items.map(function (item) {
                const productIndex = products.findIndex(product => product.product_id === item.product_id);

                if (item.product_id === response.data[productIndex].product_id) {
                    item.stock = response.data[productIndex].stock ?? 0;
                    item.reserved_stock = response.data[productIndex].reserved_stock ?? 0;
                }
                return item;
            });

            return purchasePlanForm.transfer_items;
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

const getNewStock = (item) => {
    let newStock = 0;

    let quantity = parseFloat(item.quantity);
    if (item.derivative) {
        quantity = numberFormat(parseFloat(quantity) / parseFloat(item.derivative.ratio));
    }

    newStock = numberFormat(parseFloat(item.stock) + parseFloat(quantity));

    return numberFormat(parseFloat(newStock));
};
</script>
