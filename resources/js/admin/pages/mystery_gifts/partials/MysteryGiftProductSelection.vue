<template>
    <FileUploadAndDisplayRecordsForMysteryGift
        :unmatched-products="state.unmatchedProducts"
        :matched-products-list="state.matchedProductsList"
        :selected-products="state.selectedProducts"
        :input-label="label"
        :validation-field-name="validationFieldName"
        product-upc-url="admin.products.get_matching_upc_and_is_selling_products"
        file-path="/files/mystery-gift-products-sample-file.xlsx"
        @display-selected-products-modal="openSelectedProductsModal"
        @update:column-details="updateColumnDetails"
        @display-unmatched-products-modal="openUnmatchedProductsModal"
    />

    <SelectedProducts
        :modal-show="state.displaySelectedProductsModal"
        :columns="state.fields"
        :records="state.selectedProducts"
        :allow-to-clear-selected-products="allowToClearSelectedProducts"
        :allow-to-download-selected-products="allowToDownloadSelectedProducts"
        @close-modal="closeModal"
        @clear-selected-products="clearSelectedProducts"
        @download-selected-products="downloadExcelRecords"
    >
        <template #color="record">
            {{ record.item.color ? record.item.color.name : record.item.color_name }}
        </template>
        <template #size="record">
            {{ record.item.size ? record.item.size.name : record.item.size_name }}
        </template>
        <template #quantity="record">
            {{ record.item.quantity }}
        </template>
        <template
            v-if="pageProps.product_variant"
            #product_variant_values="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.product_variant_values"
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
        @close-modal="closeModal"
    />
</template>

<script setup>
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import { onMounted, onUpdated, reactive , computed} from 'vue';
import { usePage } from '@inertiajs/vue3';
import FileUploadAndDisplayRecordsForMysteryGift from '@commonComponents/FileUploadAndDisplayRecordsForMysteryGift.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    promotionForm: {
        type: Object,
        required: true,
    },
    columnName: {
        type: String,
        default: 'regular_product_ids'
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
            headerClass: 'text-left'
        }, {
            key: 'name',
            bodyClass: 'text-left',
            headerClass: 'text-left'
        }, {
            key: 'upc',
            bodyClass: 'text-left',
            headerClass: 'text-left'
        }, ...(pageProps.value.product_variant
            ? [
                {
                    key: 'product_variant_values',
                    label: 'Attributes'
                },
            ]
            : [
                {
                    key: 'color',
                    bodyClass: 'text-left',
                    headerClass: 'text-left'
                },
                {
                    key: 'size',
                    bodyClass: 'text-left',
                    headerClass: 'text-left'
                },
            ]),
        {
            key: 'quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        }
    ],
});

const emits = defineEmits([
    'update:product-ids',
    'clear-selected-products',
    'download-selected-products',
]);

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const openUnmatchedProductsModal = () => {
    state.displayUnmatchedProductsModal = true;
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

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const clearSelectedProducts = () => {
    emits('clear-selected-products');
};

onUpdated(() => {
    if (state.matchedProductsList.length) {
        emits('update:product-ids', {
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
