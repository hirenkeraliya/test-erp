<template>
    <div class="block sm:flex flex-col mb-3 -mx-3 sm:flex-row">
        <div class="w-full px-3">
            <JFileUpload
                v-model:input-file="state.uploaded_file"
                accept=".xlsx, .xls, .ods"
                :input-label="inputLabel"
                :required="selectedProducts.length ? false : true"
                :validation-field-name="validationFieldName"
                @update:input-file="importRecords($event)"
            />
        </div>

        <div
            v-if="filePath"
            class="w-full px-3 mt-4 sm:mt-0"
        >
            <JFileDownload
                :file-path="filePath"
                input-label="Download Sample File"
            />
        </div>
    </div>

    <div class="block sm:flex justify-between w-full my-2">
        <div class="w-full pr-0 sm:pr-3">
            <div class="flex items-center">
                <p class="py-3 text-2xl font-medium text-primary">
                    {{ selectedProducts.length }}
                </p>
                <p class="ml-4 text-base font-medium text-black">
                    Selected Products
                </p>
            </div>
        </div>
        <div class="w-full pl-0 text-left sm:pl-3">
            <button
                :disabled="! selectedProducts.length"
                class="px-8 text-sm font-bold rounded-r-lg btn py-18 text-black-40 bg-slate-300"
                type="button"
                @click="openSelectedProductsModal"
            >
                View All
            </button>
        </div>
    </div>
</template>

<script setup>
import JFileDownload from '@commonComponents/JFileDownload.vue';
import JFileUpload from '@commonComponents/JFileUpload.vue';
import XLSX from 'xlsx';
import { route } from 'ziggy';
import { showSuccessNotification, showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import { isEmpty } from 'lodash';
import { computed, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    selectedProducts: {
        type: Array,
        default: null,
    },
    inputLabel: {
        type: String,
        default: null,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    filePath: {
        type: String,
        default: null,
    },
    unmatchedProducts: {
        type: Array,
        default: null,
    },
    getRecordName: {
        type: String,
        default: null,
    },
    dataPropertyNames: {
        type: Array,
        default: () => []
    },
    matchedProductsList: {
        type: Array,
        default: () => []
    },
    productUpcUrl: {
        type: String,
        required: true,
    },
});

const state = reactive({
    uploaded_file: null,
});

const emits = defineEmits([
    'display-selected-products-modal',
    'display-unmatched-products-modal',
    'update:column-details',
    'get-upload-file',
]);

const openSelectedProductsModal = () => {
    emits('display-selected-products-modal');
};

const openUnmatchedProductsModal = () => {
    emits('display-unmatched-products-modal');
};

const updateColumnsDetails = (promotionTypeDetails) => {
    emits('update:column-details', promotionTypeDetails);
};

const importRecords = (files) => {
    updateColumnsDetails({
        column_name: 'unmatchedProducts',
        value: [],
    });
    updateColumnsDetails({
        column_name: 'selectedProducts',
        value: [],
    });
    updateColumnsDetails({
        column_name: 'records',
        value: [],
    });

    const reader = new FileReader();

    const matchedProductsList = [];
    const selectedRecords = [];
    const selectedProducts = [];

    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const records = JSON.parse(JSON.stringify(XLSX.utils.sheet_to_json(worksheet, {
            blankRows: false,
            defval: null,
        })).replace(/"\s+|\s+"/g, '"'));
        const importProducts = [];

        for (const key in records) {
            if (!records[key].upc) {
                showErrorNotification('UPC is required.');
                if (props.validationFieldName) {
                    document.getElementById(props.validationFieldName).value = '';
                }
                return;
            }

            importProducts.push(records[key].upc.toString());
        }

        axios.post(route(props.productUpcUrl), {
            import_products: importProducts
        }).then((response) => {
            showSuccessNotification('The file processed successfully.');

            const products = response.data.products;
            const matchedProducts = [];
            for (const key in products) {

                let productColor = '';
                let productSize = '';
                let productVariantValues = [];
                let hasBatch = '';

                if (pageProps.value.product_variant) {
                    productVariantValues = products[key].product_variant_values;
                } else {
                    productColor = products[key].color ?? 'N/A';
                    productSize = products[key].size ?? 'N/A';
                }                

                const matchProduct = records.find(records => String(records.upc) === String(products[key].upc));
                matchedProductsList.push({ id: products[key].id, name: products[key].name, upc: products[key].upc, color_name : productColor, size_name : productSize, has_batch: hasBatch, product_variant_values: productVariantValues, quantity: matchProduct.quantity });
                matchedProducts.push(response.data.products[key].upc);
            }            

            if (props.matchedProductsList) {
                updateColumnsDetails({
                    column_name: 'matchedProductsList',
                    value: matchedProductsList,
                });
            }

            const unmatchedProducts = importProducts.filter((product) => {
                return !matchedProducts.includes(product);
            });

            updateColumnsDetails({
                column_name: 'unmatchedProducts',
                value: unmatchedProducts,
            });

            if (unmatchedProducts.length) {
                openUnmatchedProductsModal();
            }

            records.forEach((record) => response.data.products.forEach((product) => {
                if (record.upc.toString() === product.upc.toString()) {                    
                    let productColor = '';
                    let productSize = '';
                    let productVariantValues = [];
                    if (pageProps.value.product_variant) {
                        productVariantValues = product.product_variant_values;
                    } else {
                        productColor = product.color ?? 'N/A';
                        productSize = product.size ?? 'N/A';
                    }

                    const recordDetails = { id: product.id, name: product.name, compound_product_name: product.compound_product_name, upc: product.upc, color_name: productColor, size_name: productSize, has_batch: product.has_batch, product_variant_values: productVariantValues, quantity: record.quantity };

                    if (props.dataPropertyNames.length > 0) {
                        props.dataPropertyNames.forEach((column) => {
                            recordDetails[column] = record[column];
                        });
                    }

                    if (!isEmpty(props.getRecordName)) {
                        recordDetails[props.getRecordName] = record[props.getRecordName];
                    }                    

                    selectedProducts.push(recordDetails);

                    updateColumnsDetails({
                        column_name: 'selectedProducts',
                        value: selectedProducts,
                    });
                }
            }));

            if (!isEmpty(props.dataPropertyNames)) {
                if (unmatchedProducts.length > 0) {
                    for (const record in records) {
                        if (!unmatchedProducts.includes(records[record].upc.toString())) {
                            selectedRecords.push(records[record]);
                        }
                    }
                } else {
                    for (const key in records) {
                        if (props.unmatchedProducts.length <= 0) {
                            selectedRecords.push(records[key]);
                        }
                    }
                }

                updateColumnsDetails({
                    column_name: 'records',
                    value: selectedRecords,
                });
            }
        });

        if (props.validationFieldName) {
            document.getElementById(props.validationFieldName).value = '';
        }

        emits('get-upload-file', state.uploaded_file);
    };

    reader.readAsArrayBuffer(files);
};
</script>
