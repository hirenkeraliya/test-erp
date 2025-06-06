<template>
    <PageTitle title="Barcode Prints" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Barcode Prints
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
                            Add Barcode Print
                        </span>
                    </h2>
                </div>

                <div class="px-5 py-5 intro-x">
                    <div class="grid grid-cols-12 gap-0 sm:gap-6">
                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-2 xl:col-span-2">
                            <strong>Print Columns:</strong>
                            <div class="mb-3 sm:mb-0">
                                <FormCheckbox
                                    v-for="(print, index) in state.printColumns"
                                    :key="'print-columns-' + index"
                                    class="mt-2 flex flex-row"
                                    label-class="mt-0"
                                    :check-label="print.name"
                                    :check-value="isPrintColumnSelected(print.id)"
                                    @update:check-value="selectPrintColumn($event, index, print.id)"
                                />
                            </div>
                        </div>

                        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-2 xl:col-span-2">
                            <strong>Print Size Options:</strong>
                            <div
                                v-for="(printSize, index) in printSizes"
                                :key="'print-size-' + index"
                                class="flex flex-col sm:flex-row mt-2"
                            >
                                <div class="form-check mr-2">
                                    <label class="form-check-label ml-0">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            :checked="isPrintSizeSelected(printSize.name)"
                                            @input="updatePrintSize(printSize.name)"
                                        >
                                        {{ printSize.name }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-3 xl:col-span-3">
                            <FormSelectBox
                                v-model:selected-record="barcodePrintForm.product_price"
                                :records="productPrices"
                                input-label="Print Prices"
                                label-class="block font-medium text-base text-primary-p3 mb-2 -mt-auto md:-mt-3"
                                :required="true"
                            />

                            <FormTextarea
                                v-model:input-value="barcodePrintForm.remark"
                                input-name="remark"
                                input-label="Remark"
                                label-class="block font-medium text-base text-primary-p3 mb-2 -mt-auto"
                            />
                        </div>

                        <div class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-5 xl:col-span-5">
                            <div class="mt-3 sm:mt-0">
                                <JTabs
                                    :records="state.moduleTypes"
                                    :selected-record="barcodePrintForm.module_type"
                                    input-label=""
                                    @update:selected-record="updateModuleType"
                                />

                                <TabPanel
                                    v-if="barcodePrintForm.module_type === moduleTypeStaticEnum.byModule"
                                    class="active"
                                >
                                    <div>
                                        <FormSelectBox
                                            v-model:selected-record="barcodePrintForm.selected_module_by"
                                            :records="grnStockTransferStatus"
                                            input-label="Select Module Type "
                                            label-class="block font-medium text-base text-primary-p3 mb-2"
                                            :required="true"
                                        />
                                    </div>

                                    <div v-if="barcodePrintForm.selected_module_by">
                                        <FormInput
                                            v-model:input-value="barcodePrintForm.reference_number"
                                            type="text"
                                            :input-label="selectModuleStatus"
                                            label-class="block font-medium text-base text-primary-p3 mb-2"
                                            input-name="Reference Number"
                                            validation-field-name="Reference Number"
                                            :required="true"
                                        />
                                    </div>
                                </TabPanel>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="barcodePrintForm.module_type === moduleTypeStaticEnum.manual">
                    <InfoAlert
                        v-if="barcodeCountThresholdForAsyncPrint"
                        color="danger"
                        class="ml-3"
                    >
                        <span class="flex">
                            "Barcodes will be prepared in the background when you try to print more than
                            {{ barcodeCountThresholdForAsyncPrint }} pieces."
                        </span>
                    </InfoAlert>

                    <div class="w-full text-left sm:text-right mb-0 justify-between mt-0 px-5 py-1">
                        <JBadge
                            :label="`Print Quantity: ${state.barcodeTotalQuantity}`"
                            class="mb-1 sm:mb-2 md:mb-2 lg:mb-2 xl:mb-0"
                        />
                        <OutlinePrimaryButton
                            text="Advance Selection"
                            class="shadow-md text-sm mx-1"
                            @click="displayAdvanceMatrixProductSearchModalButton"
                        />
                    </div>
                </div>
                <div class="w-full p-5">
                    <div
                        v-if="barcodePrintForm.module_type === moduleTypeStaticEnum.manual"
                        class="active"
                    >
                        <div class="overflow-unset overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap w-4/12">
                                            Product Selection
                                        </th>
                                        <th
                                            v-if="!pageProps.product_variant"
                                            class="whitespace-nowrap"
                                        >
                                            Color
                                        </th>
                                        <th
                                            v-if="!pageProps.product_variant"
                                            class="whitespace-nowrap"
                                        >
                                            Size
                                        </th>
                                        <th
                                            v-if="pageProps.product_variant"
                                            class="whitespace-nowrap"
                                        >
                                            Attributes
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Article Number
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Print Quantity
                                        </th>
                                        <th class="whitespace-nowrap">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(item, itemIndex) in barcodePrintForm.print_items"
                                        :key="'print-item-' + itemIndex"
                                    >
                                        <td class="whitespace-nowrap w-4/12">
                                            <JProductFilter
                                                :product-search-url="route('warehouse_manager.get_filtered_products')"
                                                get-product-url-name="warehouse_manager.get_product"
                                                :selected-product="barcodePrintForm.print_items[itemIndex].product ?? null"
                                                :selected-product-id="barcodePrintForm.print_items[itemIndex].product_id"
                                                :validation-field-name="'print_items.' + itemIndex + '.product_id'"
                                                @update:product-selected="productSelected($event, itemIndex)"
                                                @update:display-product-filters="displayUpdateFilter(itemIndex)"
                                            />
                                        </td>

                                        <td
                                            v-if="! pageProps.product_variant"
                                            class="whitespace-nowrap"
                                        >
                                            {{ barcodePrintForm.print_items[itemIndex].product_color }}
                                        </td>

                                        <td
                                            v-if="! pageProps.product_variant"
                                            class="whitespace-nowrap"
                                        >
                                            {{ barcodePrintForm.print_items[itemIndex].product_size }}
                                        </td>

                                        <td
                                            v-if="pageProps.product_variant"
                                        >
                                            <span v-if="pageProps.product_variant">
                                                <p
                                                    v-for="(attribute, index) in barcodePrintForm.print_items[itemIndex].attributes"
                                                    :key="index"
                                                    class="flex"
                                                >
                                                    {{ attribute.name }} : {{ attribute.value }}
                                                </p>
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap">
                                            {{ barcodePrintForm.print_items[itemIndex].article_number }}
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <input
                                                class="form-control"
                                                type="number"
                                                step="any"
                                                placeholder="Enter Quantity"
                                                :value="item.quantity"
                                                @input="updatePrintQuantity($event, itemIndex)"
                                            >
                                            <ValidationError
                                                :validation-field-name="'print_items.' + itemIndex + '.quantity'"
                                            />
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <DeleteButton
                                                type="button"
                                                class="w-12 h-8"
                                                :disabled="barcodePrintForm.print_items.length <= 1"
                                                @click="removePrintItem(itemIndex)"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid grid-flow-col grid-rows-1 gap-4">
                            <OutlinePrimaryButton
                                text="+ Add Product"
                                type="button"
                                class="border-dashed"
                                @click="addNewPrintItem()"
                            />
                        </div>
                    </div>
                    <div class="flex flex-row ml-auto">
                        <Link :href="route('warehouse_manager.barcode_prints.index')">
                            <SecondaryButton
                                type="button"
                                text="Cancel"
                                class="w-24 mt-5"
                            />
                        </Link>

                        <PrimaryButton
                            id="barcode-print-submit"
                            type="button"
                            :text="barcodePrintForm.module_type === props.moduleTypeStaticEnum.manual ? 'Print' : 'Submit'"
                            class="mt-5 ml-1"
                            @click="barcodePrint()"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <JProductFilterDetails
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="route('warehouse_manager.get_filtered_products_list')"
        :filtered-category-url="route('warehouse_manager.categories.get_filtered_categories')"
        :filtered-brand-url="route('warehouse_manager.brands.get_filtered_brands')"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />

    <AdvanceProductSelectionModalForBarcode
        v-if="state.displayAdvanceProductSelectionModal"
        :modal-show="state.displayAdvanceProductSelectionModal"
        :stock-transfer-form="barcodePrintForm"
        product-article-search-url="warehouse_manager.products.search_products_by_article_number"
        @update:filter-advance-product-selection="advanceFilterProductSelection"
        @close-modal="closeAdvanceProductSelectionModal()"
    />
</template>
<script setup>
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { route } from 'ziggy';
import { onMounted, computed, reactive } from 'vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import DeleteButton from '@commonComponents/DeleteButton.vue';
import ValidationError from '@commonComponents/ValidationError.vue';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import axios from 'axios';
import { showErrorNotification, confirmDialogBox } from '@commonServices/notifier';
import onScan from 'onscan.js/onscan.js';
import AdvanceProductSelectionModalForBarcode from '@commonComponents/AdvanceProductSelectionModalForBarcode.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { TabPanel } from '@commonVendor/tab';
import JTabs from '@commonComponents/JTabs.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SecondaryButton from '@commonComponents/SecondaryButton.vue';
import FormTextarea from '@commonComponents/FormTextarea.vue';
import JBadge from '@commonComponents/JBadge.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const barcodeCountThresholdForAsyncPrint = computed(() => usePage().props.barcode_count_threshold_for_async_print);
const pageProps = computed(() => usePage().props);

const props = defineProps({
    printColumns: {
        type: Object,
        required: true,
    },
    styleColumns: {
        type: Object,
        required: true,
    },
    printSizes: {
        type: Object,
        required: true,
    },
    styleDisplayForPrintSizeName: {
        type: String,
        required: true,
    },
    defaultPrintColumns: {
        type: Object,
        required: true,
    },
    productPrices: {
        type: Array,
        required: true,
    },
    originalCapitalPriceStaticValue: {
        type: String,
        required: true,
    },
    grnStockTransferStatus: {
        type: Object,
        required: true,
    },
    grnStockTransferStaticStatus: {
        type: Object,
        required: true,
    },
    moduleTypeStaticEnum: {
        type: Object,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
});

const initialPrintItems = () => {
    return [{
        product_id: null,
        product_color: null,
        product_size: null,
        article_number: null,
        quantity: null,
        attributes: [],
    }];
};

const barcodePrintForm = useForm({
    print_items: initialPrintItems(),
    print_columns: props.defaultPrintColumns,
    print_size: props.printSizes[0].name,
    product_price: null,
    module_type: props.moduleTypeStaticEnum.manual,
    reference_number: null,
    selected_module_by: null,
    remark: null,
});

const state = reactive({
    filterModalIndex: null,
    displayInventoryUpdateFilterModal: false,
    displayAdvanceProductSelectionModal: false,
    printColumns: [],
    barcodeTotalQuantity: 0,
    barcodeCountThresholdForAsyncPrint: barcodeCountThresholdForAsyncPrint.value ? parseInt(barcodeCountThresholdForAsyncPrint.value) : null,
    moduleTypes: [
        { id: 'manual', name: 'Manual' },
        { id: 'by_module', name: 'By Module' },
    ],
});

const barcodePrint = () => {
    if (barcodePrintForm.module_type === props.moduleTypeStaticEnum.manual) {
        if (state.barcodeCountThresholdForAsyncPrint === null ||
            state.barcodeTotalQuantity <= state.barcodeCountThresholdForAsyncPrint
        ) {
            document.getElementById('barcode-print-submit').disabled = true;

            axios.post(route('warehouse_manager.barcode_prints.products_barcode_print_manual'), barcodePrintForm)
                .then((response) => {
                    if (response.data.url) {
                        window.open(response.data.url);
                    }
                }).catch((error) => {
                    if (error.response.data.message) {
                        showErrorNotification(error.response.data.message);
                    }
                }).finally(function () {
                    document.getElementById('barcode-print-submit').disabled = false;
                });
        }

        if (state.barcodeCountThresholdForAsyncPrint && state.barcodeTotalQuantity > state.barcodeCountThresholdForAsyncPrint) {
            const message = 'Barcodes will be prepared in the background because you reached the maximum limit. Are You Sure?';
            confirmDialogBox(message, () => {
                barcodePrintForm.post(route('warehouse_manager.barcode_prints.products_barcode_print'));
            });
        }
        return;
    }

    if (barcodePrintForm.module_type === props.moduleTypeStaticEnum.byModule) {
        barcodePrintForm.post(route('warehouse_manager.barcode_prints.products_barcode_print'));
    }
};

const addNewPrintItem = () => {
    barcodePrintForm.print_items.push({
        product_id: null,
        product_color: null,
        product_size: null,
        article_number: null,
        quantity: 0,
        attributes: [],
    });
};

const removePrintItem = (index) => {
    barcodePrintForm.print_items.splice(index, 1);
    barcodeTotalQuantity();
};

const displayUpdateFilter = (index) => {
    state.displayInventoryUpdateFilterModal = true;
    state.filterModalIndex = index;
};

const productSelected = (selectedProduct, index) => {
    if (selectedProduct) {
        barcodePrintForm.print_items[index].product_id = selectedProduct.id;

        if (pageProps.value.product_variant) {
            barcodePrintForm.print_items[index].attributes = selectedProduct.product_variant_values.map(item => ({
                name: item.attribute.name,
                value: item.value,
            }));
            barcodePrintForm.print_items[index].article_number = selectedProduct.master_product ? selectedProduct.master_product.article_number : 'N/A';
        } else {
            barcodePrintForm.print_items[index].product_color = selectedProduct.color ? selectedProduct.color.name : 'N/A';
            barcodePrintForm.print_items[index].product_size = selectedProduct.size ? selectedProduct.size.name : 'N/A';
            barcodePrintForm.print_items[index].article_number = selectedProduct.article_number ?? 'N/A';
        }
        return;
    }

    barcodePrintForm.print_items[index].product_id = null;
    barcodePrintForm.print_items[index].product_color = null;
    barcodePrintForm.print_items[index].product_size = null;
    barcodePrintForm.print_items[index].article_number = null;
};

const filteredProductSelected = (selectedProduct) => {
    state.displayInventoryUpdateFilterModal = false;

    productSelected(selectedProduct, state.filterModalIndex);
};

const updatePrintQuantity = (element, index) => {
    if (
        (typeof element.target.value === 'string' && element.target.value.length === 0) ||
        isNaN(element.target.value) === true
    ) {
        barcodePrintForm.print_items[index].quantity = 0;
        return;
    }
    barcodePrintForm.print_items[index].quantity = element.target.value;
    barcodeTotalQuantity();
};

const barcodeTotalQuantity = () => {
    let totalQuantity = 0;
    barcodePrintForm.print_items.forEach(item => {
        if (item.quantity) {
            totalQuantity += parseInt(item.quantity);
        }
    });

    state.barcodeTotalQuantity = totalQuantity;
};

const isPrintColumnSelected = (id) => {
    for (const key in barcodePrintForm.print_columns) {
        if (barcodePrintForm.print_columns[key] === id) {
            return true;
        }
    }

    return false;
};

const isPrintSizeSelected = (name) => {
    if (barcodePrintForm.print_size === name) {
        return true;
    }

    return false;
};

const updatePrintSize = (name) => {
    if (!pageProps.value.product_variant && name === props.styleDisplayForPrintSizeName) {
        barcodePrintForm.print_columns.push(props.styleColumns.id);
        state.printColumns.push(props.styleColumns);
    }

    if (name !== props.styleDisplayForPrintSizeName) {
        barcodePrintForm.print_columns = barcodePrintForm.print_columns.filter(id => id !== props.styleColumns.id);
        state.printColumns = state.printColumns.filter(column => column.id !== props.styleColumns.id);
    }
    barcodePrintForm.print_size = name;
};

const selectPrintColumn = (event, index, printColumn) => {
    state.printColumns[index].check = event;

    if (state.printColumns[index].check) {
        barcodePrintForm.print_columns.push(printColumn);
        return;
    }

    for (const key in barcodePrintForm.print_columns) {
        if (barcodePrintForm.print_columns[key] === printColumn) {
            barcodePrintForm.print_columns.splice(key, 1);
        }
    }
};

onMounted(() => {
    state.printColumns = props.printColumns;

    barcodePrintForm.product_price = props.originalCapitalPriceStaticValue;

    onScanProductCheck();
});

const displayAdvanceMatrixProductSearchModalButton = () => {
    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }
    state.displayAdvanceProductSelectionModal = true;
};

const closeAdvanceProductSelectionModal = () => {
    state.displayAdvanceProductSelectionModal = false;
    onScanProductCheck();
};

const onScanProductCheck = () => {
    onScan.attachTo(document, {
        reactToPaste: true,
        onPaste: (pasteValue) => {
            axios.get(route('warehouse_manager.get_filtered_inventory_products'), {
                params: {
                    search_text: pasteValue,
                    number_of_records: 5,
                }
            }).then((response) => {
                if (response.data.products[0]) {
                    const transferItems = barcodePrintForm.print_items;
                    const lastIndex = transferItems.length - 1;

                    if (transferItems[lastIndex].product_id === null) {
                        productSelected(response.data.products[0], lastIndex);
                        return;
                    }

                    const oldProductIndex = barcodePrintForm.print_items.find(product => product.product_id === response.data.products[0].id);
                    if (oldProductIndex) {
                        showErrorNotification('Product with UPC: ' + pasteValue + ' already selected.');
                        return;
                    }

                    addNewPrintItem();
                    productSelected(response.data.products[0], lastIndex + 1);
                }
            });
        },
    });
};

const advanceFilterProductSelection = (selectedProduct) => {
    for (const key in barcodePrintForm.print_items) {
        if (barcodePrintForm.print_items[key].product_id === selectedProduct.id) {
            return;
        }
    }
    if (barcodePrintForm.print_items[0].product_id === null) {
        barcodePrintForm.print_items.splice(0, 1);
    }

    let productColor = '';
    let productSize = '';
    let attributes = [];

    if (pageProps.value.product_variant) {
        for (let index = 0; index < selectedProduct.attribute_names.length; index++) {
            attributes.push({
                name: selectedProduct.attribute_names[index],
                value: selectedProduct.variant_values[index],
            });
        }
    } else {
        productColor = selectedProduct.color ? selectedProduct.color.name : 'N/A';
        productSize = selectedProduct.size ? selectedProduct.size.name : 'N/A';
    }

    barcodePrintForm.print_items.push({
        product_id: selectedProduct.id,
        product: {
            id: selectedProduct.id,
            name: selectedProduct.compound_product_name,
        },
        product_color: productColor,
        product_size: productSize,
        quantity: selectedProduct.print_quantity,
        article_number: selectedProduct.article_number,
        attributes: attributes,
    });
    barcodeTotalQuantity();
};

const updateModuleType = (moduleType) => {
    barcodePrintForm.module_type = moduleType;

    if (moduleType === props.moduleTypeStaticEnum.manual) {
        onScanProductCheck();
        addNewPrintItem();
        return;
    }

    if (document.scannerDetectionData) {
        onScan.detachFrom(document);
    }
    barcodePrintForm.print_items = [];
};

const selectModuleStatus = computed(() => {
    if (barcodePrintForm.selected_module_by === props.grnStockTransferStaticStatus.goodReceivedNote) {
        return 'GRN Reference Number';
    }
    if (barcodePrintForm.selected_module_by === props.grnStockTransferStaticStatus.transferOrder) {
        return 'Transfer Order Number';
    }
    if (barcodePrintForm.selected_module_by === props.grnStockTransferStaticStatus.requestOrder) {
        return 'Request Order Number';
    }
    if (barcodePrintForm.selected_module_by === props.grnStockTransferStaticStatus.transferIn) {
        return 'Transfer In Number';
    }
    return 'Transfer Out Number';
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
