<template>
    <PageTitle title="Edit Request Order" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Edit Request Order
        </h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="intro-y box">
                <div
                    class="flex flex-col sm:flex-row items-center py-2 px-5 sm:p-5 bg-slate-100 border-b border-slate-200/60"
                >
                    <h2 class="font-medium text-base mr-auto">
                        <span>Items to transfer</span>
                    </h2>
                </div>
                <form @submit.prevent="saveStockTransfer();">
                    <div class="grid grid-cols-12 gap-6 mt-5">
                        <div class="intro-y col-span-12 lg:col-span-12">
                            <div class="intro-y box">
                                <div class="px-5 pb-5">
                                    <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                        <div
                                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                                        >
                                            <label class="form-label font-bold text-xl">
                                                From: {{ stockTransferForm.source_location_name }}
                                            </label>
                                        </div>

                                        <div
                                            class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-6 xl:col-span-6 bg-gray-50 p-5 rounded border mb-3 sm:mb-0"
                                        >
                                            <label class="form-label font-bold text-xl">
                                                To: {{ stockTransferForm.destination_location_name }}
                                            </label>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-12 gap-0 sm:gap-6">
                                        <div
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                                        >
                                            <FormInput
                                                v-model:input-value="stockTransferForm.reference_number"
                                                input-name="reference_number"
                                                input-label="Reference Number"
                                            />
                                        </div>

                                        <div
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                                        >
                                            <FormInput
                                                v-model:input-value="stockTransferForm.attention"
                                                input-name="attention"
                                                input-label="Attention"
                                                placeholder="Enter Name"
                                            />
                                        </div>

                                        <div
                                            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-4"
                                        >
                                            <FormTextarea
                                                v-model:input-value="stockTransferForm.remarks"
                                                input-name="remarks"
                                                input-label="Remarks"
                                                class="w-[200px]"
                                            />
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap justify-end w-full mb-4 text-left sm:text-right">
                                        <div>
                                            <OutlinePrimaryButton
                                                text="Advance Selection"
                                                class="shadow-md text-sm mx-1"
                                                @click="displayAdvanceMatrixProductSearchModalButton"
                                            />

                                            <OutlinePrimaryButton
                                                text="Bulk Upload"
                                                class="shadow-md text-sm mx-1 mt-2 sm:mt-0"
                                                @click="state.displayBulkUploadProductsModal = !state.displayBulkUploadProductsModal"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        v-if="state.displayBulkUploadProductsModal"
                                        class="p-5 mb-5 rounded transfer_order_bulk_products bg-slate-200"
                                    >
                                        <FileUploadAndDisplayRecordsForStockTransfer
                                            :selected-products="state.selectedProducts"
                                            :unmatched-products="state.unmatchedProducts"
                                            :product-upc-url="productUpcUrl"
                                            :data-property-names="['quantity', 'remarks']"
                                            input-label="Bulk Upload Products"
                                            validation-field-name="stock-transfer-items"
                                            file-path="/files/stock-transfer-items-sample-file.xlsx"
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
                                                    <th class="whitespace-nowrap">
                                                        From Stock
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        To Stock
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        Transfer Stock <span class="text-danger">*</span>
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        New From Stock
                                                    </th>
                                                    <th class="whitespace-nowrap">
                                                        New To Stock
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
                                                    v-for="(item, itemIndex) in stockTransferForm.transfer_items"
                                                    :key="'stock-transfer-item-' + itemIndex"
                                                >
                                                    <td class="whitespace-nowrap w-4/12">
                                                        <JProductFilter
                                                            :product-search-url="productSearchUrl"
                                                            :get-product-url-name="getProductUrlName"
                                                            :selected-product="stockTransferForm.transfer_items[itemIndex].product ?? null"
                                                            :selected-product-id="stockTransferForm.transfer_items[itemIndex].product_id"
                                                            :validation-field-name="'transfer_items.' + itemIndex + '.product_id'"
                                                            @update:product-selected="productSelected($event, itemIndex)"
                                                            @update:display-product-filters="displayUpdateFilter(itemIndex)"
                                                        />

                                                        <span v-if="stockTransferForm.transfer_items[itemIndex].product_id">
                                                            <strong
                                                                v-if="! pageProps.product_variant"
                                                            >
                                                                Color:
                                                                {{ stockTransferForm.transfer_items[itemIndex].product_color }}
                                                            </strong>

                                                            <strong 
                                                                v-if="! pageProps.product_variant"
                                                                class="pl-4"
                                                            >
                                                                Size:
                                                                {{ stockTransferForm.transfer_items[itemIndex].product_size }}
                                                            </strong>

                                                            <strong 
                                                                v-if="pageProps.product_variant"
                                                                class="pl-4"
                                                            >
                                                                <p
                                                                    v-for="(product_variant, index) in stockTransferForm.transfer_items[itemIndex].product_variant_values"
                                                                    :key="index"
                                                                    class="pl-4"
                                                                >
                                                                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                                                                </p>
                                                            </strong>

                                                            <strong class="pl-4">
                                                                UOM:
                                                                {{ stockTransferForm.transfer_items[itemIndex].product_uom ?? 'N/A' }}
                                                            </strong>
                                                        </span>
                                                    </td>

                                                    <td class="mt-10 whitespace-nowrap">
                                                        <span v-if="stockTransferForm.transfer_items[itemIndex].product_id">
                                                            {{ item.source_stock }}

                                                            {{ stockTransferForm.transfer_items[itemIndex].product_uom }}

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
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <span v-if="stockTransferForm.transfer_items[itemIndex].product_id">
                                                            {{ item.destination_stock }}

                                                            {{ stockTransferForm.transfer_items[itemIndex].product_uom }}

                                                            <Tippy
                                                                tag="label"
                                                                :content="'Reserved Stocks: ' + item.destination_reserved_stock"
                                                            >
                                                                <Info
                                                                    class="text-cyan-400 inline-block"
                                                                    :size="15"
                                                                />
                                                            </Tippy>
                                                        </span>
                                                    </td>

                                                    <td class="mt-10 whitespace-nowrap">
                                                        <span v-if="stockTransferForm.transfer_items[itemIndex].product_id">

                                                            <div
                                                                v-if="parseFloat(item.source_stock) + parseFloat(item.initial_transfer_quantity) <= 0"
                                                                class="w-24 text-center form-control text-danger font-extrabold"
                                                            >
                                                                0
                                                            </div>

                                                            <input
                                                                v-else
                                                                type="text"
                                                                class="w-24 text-center form-control"
                                                                :value="item.transfer_stock"
                                                                step="any"
                                                                @input="updateTransferStock($event, itemIndex, item.source_stock, item.initial_transfer_quantity, item.unit_of_measure_derivative_id, item.derivative)"
                                                            >

                                                            <ValidationError
                                                                :validation-field-name="'transfer_items.' + itemIndex + '.transfer_stock'"
                                                            />

                                                            <br><br>

                                                            <FormSelectBox
                                                                v-if="item.derivatives && parseFloat(item.source_stock) + parseFloat(item.initial_transfer_quantity) > 0"
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
                                                                {{ parseFloat(item.transfer_stock) / parseFloat(item.derivative.ratio) }}

                                                                {{ stockTransferForm.transfer_items[itemIndex].product_uom }}
                                                            </div>
                                                        </span>
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <span v-if="stockTransferForm.transfer_items[itemIndex].product_id">
                                                            {{
                                                                calculateNewSourceStock(
                                                                    item.source_reserved_stock,
                                                                    item.source_stock,
                                                                    item.transfer_stock,
                                                                    item.derivative
                                                                )
                                                            }}

                                                            {{ stockTransferForm.transfer_items[itemIndex].product_uom }}
                                                        </span>
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <span v-if="stockTransferForm.transfer_items[itemIndex].product_id">
                                                            {{
                                                                calculateNewDestinationStock(
                                                                    item.destination_reserved_stock,
                                                                    item.transfer_stock,
                                                                    item.destination_stock,
                                                                    item.derivative
                                                                )
                                                            }}

                                                            {{ stockTransferForm.transfer_items[itemIndex].product_uom }}
                                                        </span>
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <FormTextarea
                                                            v-if="stockTransferForm.transfer_items[itemIndex].product_id"
                                                            :input-value="item.remarks"
                                                            input-name="remarks"
                                                            placeholder="Enter Remarks"
                                                            class="mt-[0] w-[200px]"
                                                            @update:input-value="updateItemRemarks($event, itemIndex)"
                                                        />
                                                    </td>

                                                    <td class="whitespace-nowrap">
                                                        <DeleteButton
                                                            type="button"
                                                            class="w-12 h-8"
                                                            :disabled="stockTransferForm.transfer_items.length <= 1"
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

                                    <div class="mt-5">
                                        <Link :href="cancelUrl">
                                            <SecondaryButton
                                                type="button"
                                                text="Cancel"
                                                class="w-24 mr-1"
                                            />
                                        </Link>

                                        <PrimaryButton
                                            type="submit"
                                            text="Update"
                                            class="w-24"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <JProductFilterDetails
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="productSearchListUrl"
        :filtered-category-url="filteredCategoryUrl"
        :filtered-brand-url="filteredBrandUrl"
        :show-has-inventory="true"
        :location-id="stockTransferForm.source_location_id"
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
        @close-modal="closeModal"
    />

    <AdvanceMatrixProductSelectionModal
        v-if="state.displayAdvanceProductSelectionModal"
        :modal-show="state.displayAdvanceProductSelectionModal"
        :stock-transfer-form="stockTransferForm"
        :product-article-search-url="productArticleSearchUrl"
        @update:filter-advance-products-selection="advanceFilterProductsSelection"
        @close-modal="closeAdvanceProductSelectionModal()"
    />
</template>

<script setup>
import { usePage, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { route } from 'ziggy';
import { onMounted, computed, reactive, watch, onUnmounted } from 'vue';
import { numberFormat } from '@commonServices/helper';
import FileUploadAndDisplayRecordsForStockTransfer from '@commonComponents/FileUploadAndDisplayRecordsForStockTransfer.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import axios from 'axios';
import FormInput from '@commonComponents/FormInput.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import ValidationError from '@commonComponents/ValidationError.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import AdvanceMatrixProductSelectionModal from '@commonComponents/AdvanceMatrixProductSelectionModal.vue';
import onScan from 'onscan.js/onscan.js';
import { showErrorNotification } from '@commonServices/notifier';
import { Info } from 'lucide-vue-next';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    stockTransfer: {
        type: Object,
        default: () => { },
    },
    productSearchListUrl: {
        type: String,
        required: true,
    },
    filteredCategoryUrl: {
        type: String,
        required: true,
    },
    filteredBrandUrl: {
        type: String,
        required: true,
    },
    getInventoryStocks: {
        type: String,
        required: true,
    },
    cancelUrl: {
        type: String,
        required: true,
    },
    productSearchUrl: {
        type: String,
        required: true,
    },
    productUpcUrl: {
        type: String,
        required: true,
    },
    getProductUrlName: {
        type: String,
        required: true,
    },
    productArticleSearchUrl: {
        type: String,
        required: true,
    },
    stockTransfersUpdateRequestOrder: {
        type: String,
        required: true,
    },
});

const state = reactive({
    locationTypes: [
        { id: 'Store', name: 'Store' },
        { id: 'Warehouse', name: 'Warehouse' },
    ],
    fields: [
        {
            key: 'name',
        }, {
            key: 'quantity',
        },
    ],

    filterModalIndex: null,
    displayInventoryUpdateFilterModal: false,
    displayBulkUploadProductsModal: false,
    displaySelectedProductsModal: false,
    displayUnmatchedProductsModal: false,
    displayAdvanceProductSelectionModal: false,

    selectedProducts: [],
    unmatchedProducts: [],
    selectedProductIds: [],
});

const stockTransferForm = useForm({
    source_location_id: null,
    destination_location_id: null,
    source_location_name: null,
    destination_location_name: null,
    attention: null,
    reference_number: null,
    remarks: null,
    transfer_items: [
        {
            product_id: null,
            product_color: null,
            product_size: null,
            product_variant_values: [],
            product_uom: null,
            transfer_stock: 0,
            initial_transfer_quantity: 0,
            source_stock: 0,
            destination_stock: 0,
            remarks: null,
            derivatives: null,
            derivative: null,
            unit_of_measure_derivative_id: null,
        }
    ],
});

const updateTransferStock = (element, index, sourceStock, initialTransferQuantity, derivativeId, derivative) => {
    if (
        (typeof element.target.value === 'string' && element.target.value.length === 0) ||
        isNaN(element.target.value) === true
    ) {
        stockTransferForm.transfer_items[index].transfer_stock = 0;
        return;
    }

    let maximumIncrementValue = parseFloat(sourceStock);
    if (props.stockTransfer) {
        maximumIncrementValue += parseFloat(initialTransferQuantity);
    }

    if (derivativeId) {
        maximumIncrementValue = parseFloat(maximumIncrementValue) * parseFloat(derivative.ratio);
    }

    if (parseFloat(maximumIncrementValue) > 0) {
        if (parseFloat(element.target.value) >= maximumIncrementValue) {
            stockTransferForm.transfer_items[index].transfer_stock = maximumIncrementValue;
            stockTransferForm.transfer_items[index].package_total_quantity = maximumIncrementValue;
            return;
        }

        stockTransferForm.transfer_items[index].transfer_stock = parseFloat(element.target.value);
        stockTransferForm.transfer_items[index].package_total_quantity = parseFloat(element.target.value);
    }
};

const updateItemRemarks = (value, index) => {
    stockTransferForm.transfer_items[index].remarks = value;
};

const addNewTransferItem = () => {
    stockTransferForm.transfer_items.push({
        product_id: null,
        product_color: null,
        product_size: null,
        product_variant_values: [],
        transfer_stock: 0,
        initial_transfer_quantity: 0,
        source_stock: 0,
        destination_stock: 0,
        remarks: null,
        derivatives: null,
        unit_of_measure_derivative_id: null,
    });
};

const removeTransferItem = (index) => {
    stockTransferForm.transfer_items.splice(index, 1);
};

const displayUpdateFilter = (index) => {
    state.displayInventoryUpdateFilterModal = true;
    state.filterModalIndex = index;
};

const productSelected = (selectedProduct, index) => {
    if (!selectedProduct) {
        stockTransferForm.transfer_items[index].product_id = null;
        stockTransferForm.transfer_items[index].product_color = null;
        stockTransferForm.transfer_items[index].product_size = null;
        stockTransferForm.transfer_items[index].product_variant_values = [];
        stockTransferForm.transfer_items[index].product_uom = null;
        stockTransferForm.transfer_items[index].transfer_stock = 0;
        stockTransferForm.transfer_items[index].source_stock = 0;
        stockTransferForm.transfer_items[index].remarks = null;
        stockTransferForm.transfer_items[index].derivatives = null;
        stockTransferForm.transfer_items[index].unit_of_measure_derivative_id = null;
        return;
    }

    const oldProductIndex = stockTransferForm.transfer_items.find(product => product.product_id === selectedProduct.id);

    if (oldProductIndex) {
        return;
    }

    if (selectedProduct) {
        stockTransferForm.transfer_items[index].product_id = selectedProduct.id;
        stockTransferForm.transfer_items[index].remarks = null;
        stockTransferForm.transfer_items[index].product_color = selectedProduct.color ? selectedProduct.color.name : 'N/A';
        stockTransferForm.transfer_items[index].product_size = selectedProduct.size ? selectedProduct.size.name : 'N/A';

        if(pageProps.value.product_variant){
            stockTransferForm.transfer_items[index].product_variant_values = selectedProduct.product_variant_values;
        }
       
        if(! pageProps.value.product_variant){
            stockTransferForm.transfer_items[index].product_uom = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.name : null;
            stockTransferForm.transfer_items[index].derivatives = selectedProduct.unit_of_measure ? selectedProduct.unit_of_measure.derivatives : null;
        }else{
            stockTransferForm.transfer_items[index].product_uom = selectedProduct.master_product.unit_of_measure ? selectedProduct.master_product.unit_of_measure.name : null;
            stockTransferForm.transfer_items[index].derivatives = selectedProduct.master_product.unit_of_measure ? selectedProduct.master_product.unit_of_measure.derivatives : null;
        }
        stockTransferForm.transfer_items[index].unit_of_measure_derivative_id = null;
        getSelectedProductStock(selectedProduct.id, index);
    }
};

const getSelectedProductsStock = () => {
    const params = {
        product_ids: state.selectedProductIds,
        source_location_id: stockTransferForm.source_location_id,
        destination_location_id: stockTransferForm.destination_location_id,
    };

    axios.get(props.getInventoryStocks, { params })
        .then((response) => {
            const sourceInventories = response.data.source_inventories;
            const destinationInventories = response.data.destination_inventories;

            stockTransferForm.transfer_items.map(function (item) {
                sourceInventories.every(function (sourceInventory) {
                    if (sourceInventory.product_id === item.product_id) {
                        item.source_stock = sourceInventory.stock;
                        item.source_reserved_stock = sourceInventory.reserved_stock;
                    }
                    return sourceInventory;
                });

                destinationInventories.every(function (destinationInventory) {
                    if (destinationInventory.product_id === item.product_id) {
                        item.destination_stock = destinationInventory.stock;
                        item.destination_reserved_stock = destinationInventory.reserved_stock;
                    }
                    return destinationInventory;
                });
                return item;
            });
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const getSelectedProductStock = (productId, index) => {
    const params = {
        product_ids: [productId],
        source_location_id: stockTransferForm.source_location_id,
        destination_location_id: stockTransferForm.destination_location_id,
    };

    axios.get(props.getInventoryStocks, { params })
        .then((response) => {
            const sourceInventories = response.data.source_inventories;
            const destinationInventories = response.data.destination_inventories;

            sourceInventories.every(function (sourceInventory) {
                stockTransferForm.transfer_items[index].source_stock = sourceInventory.stock;
                stockTransferForm.transfer_items[index].source_reserved_stock = sourceInventory.reserved_stock;
                return sourceInventory;
            });

            destinationInventories.every(function (destinationInventory) {
                stockTransferForm.transfer_items[index].destination_stock = destinationInventory.stock;
                stockTransferForm.transfer_items[index].destination_reserved_stock = destinationInventory.reserved_stock;
                return destinationInventory;
            });
        })
        .catch((error) => {
            if (error.response.data.message) {
                showErrorNotification(error.response.data.message);
            }
        });
};

const filteredProductSelected = (selectedProduct) => {
    state.displayInventoryUpdateFilterModal = false;

    productSelected(selectedProduct, state.filterModalIndex);
};

const saveStockTransfer = () => {
    stockTransferForm.put(route(props.stockTransfersUpdateRequestOrder, props.stockTransfer.data.id));
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
            stockTransferForm.transfer_items = [];
            for (const key in state.selectedProducts) {
                stockTransferForm.transfer_items.push({
                    product_id: state.selectedProducts[key].id,
                    product: { id: state.selectedProducts[key].id, name: state.selectedProducts[key].compound_product_name },
                    transfer_stock: String(state.selectedProducts[key].quantity).trim() ?? 0,
                    initial_transfer_quantity: 0,
                    remarks: state.selectedProducts[key].remarks,
                    product_color: state.selectedProducts[key].color_name ?? 'N/A',
                    product_size: state.selectedProducts[key].size_name ?? 'N/A',
                    product_variant_values: state.selectedProducts[key].product_variant_values,
                    derivatives: state.selectedProducts[key].derivatives,
                    unit_of_measure_derivative_id: null,
                    product_uom: state.selectedProducts[key].uom_name ?? 'N/A',
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

const advanceFilterProductsSelection = (selectedProducts) => {
    for (const productKey in selectedProducts) {
        for (const key in stockTransferForm.transfer_items) {
            if (stockTransferForm.transfer_items[key].product_id === selectedProducts[productKey].id) {
                stockTransferForm.transfer_items.splice(0, 1);
            }
        }

        if (stockTransferForm.transfer_items[0] && stockTransferForm.transfer_items[0].product_id === null) {
            stockTransferForm.transfer_items.splice(0, 1);
        }

        let productColor = '';
        let productSize = '';

        if (! pageProps.value.product_variant) {
            productColor = selectedProducts[productKey].color ? selectedProducts[productKey].color.name : 'N/A';
            productSize = selectedProducts[productKey].size ? selectedProducts[productKey].size.name : 'N/A';
        } 

        stockTransferForm.transfer_items.push({
            product_id: selectedProducts[productKey].id,
            product: {
                id: selectedProducts[productKey].id,
                name: selectedProducts[productKey].compound_product_name,
            },
            product_color: productColor,
            product_size: productSize,
            product_variant_values: selectedProducts[productKey].product_variant_values,
            product_uom: selectedProducts[productKey].unit_of_measure ? selectedProducts[productKey].unit_of_measure.name : null,
            derivatives: selectedProducts[productKey].unit_of_measure ? selectedProducts[productKey].unit_of_measure.derivatives : null,
            source_stock: selectedProducts[productKey].source_stock,
            destination_stock: selectedProducts[productKey].destination_stock,
            transfer_stock: selectedProducts[productKey].stock,
            initial_transfer_quantity: 0,
            remarks: null,
            unit_of_measure_derivative_id: null,
        });

        state.selectedProductIds.push(selectedProducts[productKey].id);
    }

    if (state.selectedProductIds.length) {
        getSelectedProductsStock();
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

const updateUnitOfMeasureDerivativeId = (derivativeId, index, derivatives) => {
    if (!derivativeId) {
        stockTransferForm.transfer_items[index].transfer_stock = 0;
    }

    stockTransferForm.transfer_items[index].unit_of_measure_derivative_id = derivativeId;
    stockTransferForm.transfer_items[index].derivative = derivatives.find((derivative) => derivative.id === derivativeId);
};

const calculateNewSourceStock = (sourceReservedStock, sourceStock, transferStock, derivative) => {
    if (derivative) {
        return numberFormat(
            parseFloat(sourceReservedStock) +
            parseFloat(sourceStock) -
            (parseFloat(transferStock) / parseFloat(derivative.ratio))
        );
    }

    return numberFormat(parseFloat(sourceReservedStock) + parseFloat(sourceStock) - parseFloat(transferStock));
};

const calculateNewDestinationStock = (destinationReservedStock, transferStock, destinationStock, derivative) => {
    if (derivative) {
        return numberFormat(
            parseFloat(destinationReservedStock) + (parseFloat(transferStock) / parseFloat(derivative.ratio)) + parseFloat(destinationStock)
        );
    }

    return numberFormat(parseFloat(destinationReservedStock) + parseFloat(transferStock) + parseFloat(destinationStock));
};

onUnmounted(() => {
    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }
});

onMounted(() => {
    Object.assign(stockTransferForm, JSON.parse(JSON.stringify(props.stockTransfer.data)));

    onScanProductCheck();
});

const onScanProductCheck = () => {
    onScan.attachTo(document, {
        reactToPaste: true,
        onPaste: (pasteValue) => {
            axios.get(props.productSearchUrl, {
                params: {
                    search_text: pasteValue,
                    number_of_records: 5,
                }
            }).then((response) => {
                if (response.data.products[0]) {
                    const transferItems = stockTransferForm.transfer_items;
                    const lastIndex = transferItems.length - 1;
                    if (transferItems[lastIndex].product_id === null) {
                        productSelected(response.data.products[0], lastIndex);
                        return;
                    }
                    const oldProductIndex = stockTransferForm.transfer_items.find(product => product.product_id === response.data.products[0].id);
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
</script>
