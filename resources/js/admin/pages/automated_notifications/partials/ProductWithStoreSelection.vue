<template>
    <FileUploadAndDisplayRecordsForProductWithStore
        :unmatched-products="state.unmatchedProducts"
        :matched-products-list="state.matchedProductsList"
        :selected-products="state.selectedProducts"
        :data-property-names="['low_stock_alert_threshold']"
        :input-label="label"
        :validation-field-name="validationFieldName"
        product-upc-url="admin.get_matching_upc_product_with_store"
        file-path="/files/automated-notifications-product-sample-file.xlsx"
        @display-selected-products-modal="openSelectedProductsModal"
        @get-upload-file="getUploadFile"
    />

    <SelectedProductsWithStore
        :modal-show="state.displaySelectedProductsModal"
        :columns="state.fields"
        :records="state.selectedProducts"
        :allow-to-clear-selected-products="allowToClearSelectedProducts"
        :allow-to-download-selected-products="allowToDownloadSelectedProducts"
        @close-modal="closeModal"
        @clear-selected-products="clearSelectedProducts"
        @download-selected-products="downloadExcelRecords"
    />

    <unmatchedProductsWithStore
        :modal-show="state.displayUnmatchedProductsModal"
        :records="state.unmatchedProducts"
        @close-modal="closeModal"
    />
</template>

<script setup>
import unmatchedProductsWithStore from '@commonComponents/UnmatchedProductsWithStore.vue';
import { onMounted, onUpdated, reactive } from 'vue';
import FileUploadAndDisplayRecordsForProductWithStore from '@commonComponents/FileUploadAndDisplayRecordsForProductWithStore.vue';
import SelectedProductsWithStore from '@commonComponents/SelectedProductsWithStore.vue';

const props = defineProps({
    automatedNotificationForm: {
        type: Object,
        required: true,
    },
    columnName: {
        type: String,
        default: 'products'
    },
    label: {
        type: String,
        default: 'Select Products'
    },
    validationFieldName: {
        type: String,
        default: ''
    },
    editSelectedProducts: {
        type: Object,
        default: () => {},
    },
    allowToClearSelectedProducts: {
        type: Boolean,
        default: false,
    },
    allowToDownloadSelectedProducts: {
        type: Boolean,
        default: false,
    },
});

const state = reactive({
    displayUnmatchedProductsModal: false,
    unmatchedProducts: [],
    displaySelectedProductsModal: false,
    selectedProducts: [],
    matchedProductsList: [],
    fields: [
        {
            key: 'id',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'upc',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'location_name',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'location_code',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'low_stock_alert_threshold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ],
});

const emits = defineEmits([
    'update:product-upc',
    'clear-selected-products',
    'download-selected-products',
    'get-upload-file',
]);

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const getUploadFile = (file) => {
    emits('get-upload-file', file);
};

const closeModal = () => {
    if (state.displaySelectedProductsModal) {
        state.displaySelectedProductsModal = false;
        return;
    }
    if (state.displayUnmatchedProductsModal) {
        state.displayUnmatchedProductsModal = false;
    }
};

const clearSelectedProducts = () => {
    emits('clear-selected-products');
};

onUpdated(() => {
    if (state.matchedProductsList.length) {
        emits('update:product-upc', {
            column_name: props.columnName,
            value: state.matchedProductsList
        });
    }
});

onMounted(() => {
    state.selectedProducts = props.editSelectedProducts;
});

const downloadExcelRecords = () => {
    emits('download-selected-products');
};
</script>
