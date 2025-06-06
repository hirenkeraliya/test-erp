<template>
    <Modal
        size="modal-xl"
        :show="modalShow"
        @hidden="closeModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Product Filters
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="closeModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody
            class="p-5 sm:p-10"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-x-5">
                <div>
                    <FormAjaxSelect
                        :selected-record="state.selectCategory"
                        :search-records="searchCategory"
                        input-label="Category"
                        placeholder="Please type the name of the category to search."
                        @update:selected-record="selectCategory"
                    />
                </div>

                <div>
                    <FormAjaxSelect
                        :selected-record="state.selectBrand"
                        :search-records="searchBrand"
                        input-label="Brand"
                        placeholder="Please type the name of the brand to search."
                        @update:selected-record="selectBrand"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-x-5">
                <div>
                    <FormInput
                        :input-value="state.searchProductName"
                        :required="true"
                        input-name="name"
                        input-label="Product Name"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        placeholder="Search Product Name/UPC..."
                        @update:input-value="searchProducts"
                    />
                </div>

                <div
                    v-if="showHasInventory"
                    class="mt-10"
                >
                    <label title="Display products that have a stock quantity greater than zero at the source location.">
                        <strong>Has Inventory</strong>
                    </label>

                    <Info
                        class="text-cyan-400 inline-block"
                        size="15"
                    />

                    <FormCheckbox
                        v-model:check-value="state.hasInventory"
                        class="ml-2"
                    />
                </div>
            </div>

            <JSimpleTable
                v-if="state.dynamicColumns.length > 0"
                :columns="state.dynamicColumns"
                :records="state.products"
            >
                <template #categories="data">
                    <span
                        v-if="!pageProps.product_variant"
                    >
                        <span
                            v-if="data.item.categories.length"
                            class="text-primary font-medium"
                        >
                            <span
                                v-for="(category, index) in data.item.categories"
                                :key="index"
                            >
                                {{ category.name }}

                                <span v-if="index != data.item.categories.length - 1">
                                    >
                                </span>
                            </span>
                        </span>

                        <span v-else>
                            N/A
                        </span>
                    </span>
                    <span
                        v-if="pageProps.product_variant"
                    >
                        <span
                            v-if="data.item.master_product.categories.length"
                            class="text-primary font-medium"
                        >
                            <span
                                v-for="(category, index) in data.item.master_product.categories"
                                :key="index"
                            >
                                {{ category.name }}

                                <span v-if="index != data.item.master_product.categories.length - 1">
                                    >
                                </span>
                            </span>
                        </span>

                        <span v-else>
                            N/A
                        </span>
                    </span>
                </template>

                <template #brand="data">
                    {{ data.item.brand ? data.item.brand.name : 'N/A' }}
                </template>

                <template
                    v-if="pageProps.product_variant"
                    #attributes="record"
                >
                    <span v-if="pageProps.product_variant">
                        <p
                            v-for="(attributeData, index) in record.item.product_variant_values"
                            :key="index"
                            class="flex"
                        >
                            {{ attributeData.attribute.name }} : {{ attributeData.value }}
                        </p>
                    </span>
                </template>

                <template
                    v-if="!pageProps.product_variant"
                    #color="data"
                >
                    {{ data.item.color ? data.item.color.name : 'N/A' }}
                </template>

                <template
                    v-if="!pageProps.product_variant"
                    #size="data"
                >
                    {{ data.item.size ? data.item.size.name : 'N/A' }}
                </template>

                <template #actions="data">
                    <OutlinePrimaryButton
                        text="Select Product"
                        class="inline-block mr-1 mb-2"
                        @click="selectProduct(data.item)"
                    />
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import { Modal, ModalHeader, ModalBody } from '@commonVendor/model';
import { X, Info } from 'lucide-vue-next';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormInput from '@commonComponents/FormInput.vue';
import { reactive, computed, onMounted  } from 'vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import axios from 'axios';
import { debounce } from 'lodash';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import { usePage  } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    productSearchUrl: {
        type: String,
        required: true,
    },
    modalShow: {
        type: Boolean,
        default: false,
    },
    filteredCategoryUrl: {
        type: String,
        required: true,
    },
    filteredBrandUrl: {
        type: String,
        required: true,
    },
    showHasInventory: {
        type: Boolean,
        required: false,
    },
    locationId: {
        type: [String, Number],
        default: null,
    },
});

const state = reactive({
    products: [],
    selectCategory: null,
    selectBrand: null,
    searchProductName: null,
    hasInventory: false,

    columns: [
        {
            key: 'name',
            sortable: true,
        },
        {
            key: 'categories',
        },
        {
            key: 'brand',
        },
        {
            key: 'attributes',
            isDisplay: true,
        },
        {
            key: 'color',
        },
        {
            key: 'size',
        },
        {
            key: 'actions',
        }
    ],
    dynamicColumns: [],
});

const getFilteredColumns = () => {
    const columns = state.columns || [];
    if (pageProps.value.product_variant) {
        return columns.filter(col => !['color', 'size', 'style'].includes(col.key));
    }
    return columns.filter(col => col.key !== 'attributes');
};
onMounted(() => {
    state.dynamicColumns = getFilteredColumns();
});

const emits = defineEmits(['close-modal', 'update:product-selected']);

const closeModal = () => {
    emits('close-modal');
    state.selectCategory = null;
    state.selectBrand = null;
    state.searchProductName = null;
    state.hasInventory = false;
    state.products = [];
};

const fetchProducts = () => {
    const params = {
        category_id: state.selectCategory ? state.selectCategory.id : null,
        brand_id: state.selectBrand ? state.selectBrand.id : null,
        search_text: state.searchProductName,
        has_inventory: state.hasInventory ? 1 : 0
    };

    if (state.hasInventory === true) {
        params.location_id = props.locationId;
    }

    axios.get(props.productSearchUrl, {
        params
    }).then((response) => {
        state.products = response.data.products;
    });
};

const debounceDelay = 1000;

const searchProducts = debounce((searchText) => {
    if (!searchText.trim()) {
        return;
    }

    state.searchProductName = searchText;
    fetchProducts();
}, debounceDelay);

const searchCategory = (searchText, componentState) => {
    axios.post(props.filteredCategoryUrl, {
        search_text: searchText,
    }).then((response) => {
        componentState.records = response.data.categories;
        componentState.isLoading = false;
    });
};

const selectCategory = (selectCategory) => {
    state.selectCategory = selectCategory;
};

const searchBrand = (searchText, componentState) => {
    axios.post(props.filteredBrandUrl, {
        search_text: searchText,
    }).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const selectBrand = (selectBrand) => {
    state.selectBrand = selectBrand;
};

const selectProduct = (selectedProduct) => {
    emits('update:product-selected', selectedProduct);
};
</script>
