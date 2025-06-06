<template>
    <div class="block sm:flex items-center">
        <FormAjaxSelect
            :selected-record="state.selectedProduct"
            :search-records="searchProducts"
            placeholder="Product Name/UPC to search..."
            :validation-field-name="validationFieldName"
            class="w-full"
            :required="required"
            :input-label="inputLabel"
            :label-class="labelClass"
            @update:selected-record="selectProduct"
        />

        <div
            v-if="showProductFilters"
            class="ml-2 w-4/2"
            :class="filterButtonClass"
        >
            <button
                type="button"
                class="btn btn-outline-primary"
                @click="displayProductFilters"
            >
                <Filter :size="20" />
                Filter
            </button>
        </div>
    </div>
</template>

<script setup>
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import { Filter } from 'lucide-vue-next';
import { reactive, watch } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';

const props = defineProps({
    productSearchUrl: {
        type: String,
        required: true,
    },
    getProductUrlName: {
        type: String,
        required: true,
    },
    selectedProductId: {
        type: Number,
        default: null,
    },
    validationFieldName: {
        type: String,
        default: null,
    },
    showProductFilters: {
        type: Boolean,
        default: true,
    },
    inputLabel: {
        type: String,
        default: '',
    },
    labelClass: {
        type: String,
        default: '',
    },
    filterButtonClass: {
        type: String,
        default: 'mt-3',
    },
    required: {
        type: Boolean,
        default: false,
    },
    selectedProduct: {
        type: Object,
        default: null,
    },
});

const emits = defineEmits([
    'update:product-selected',
    'update:display-product-filters',
]);

const state = reactive({
    selectedProduct: props.selectedProduct
});

const selectProduct = (selectedProduct) => {
    state.selectedProduct = selectedProduct;

    emits('update:product-selected', selectedProduct || null);
};

const displayProductFilters = () => {
    emits('update:display-product-filters', true);
};

const searchProducts = (searchText, componentState) => {
    if (!searchText.trim()) {
        return;
    }

    axios.get(props.productSearchUrl, {
        params: {
            search_text: searchText,
            number_of_records: 5,
        }
    }).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};

const fetchSelectedProduct = () => {
    if (props.selectedProduct === null) {
        axios.get(route(props.getProductUrlName, props.selectedProductId)).then((response) => {
            state.selectedProduct = response.data.product;
        });
    }
};

if (props.selectedProductId) {
    fetchSelectedProduct();
}

watch(() => props.selectedProductId, () => {
    if (props.selectedProductId) {
        fetchSelectedProduct();
    }
    state.selectedProduct = props.selectedProduct;
});
</script>
