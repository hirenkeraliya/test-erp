<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
        <div>
            <FormAjaxSelect
                :selected-record="state.selectedCategories"
                :search-records="searchCategory"
                :multi-select="true"
                input-label="Categories"
                placeholder="Please type the name of the category to search."
                @update:selected-record="selectCategories"
            />
        </div>

        <div>
            <FormAjaxSelect
                :selected-record="state.selectedBrands"
                :search-records="searchBrand"
                :multi-select="true"
                input-label="Brands"
                placeholder="Please type the name of the brand to search."
                @update:selected-record="selectBrands"
            />
        </div>

        <AttributesFilters
            v-if="pageProps.product_variant"
            :attributes="attributes"
            @update-params="updateParams($event, params)"
        />

        <div v-if="!pageProps.product_variant">
            <FormAjaxSelect
                :selected-record="state.selectedSizes"
                :search-records="searchSize"
                :multi-select="true"
                input-label="Sizes"
                placeholder="Please type the name of the size to search."
                @update:selected-record="selectSizes"
            />
        </div>

        <div v-if="!pageProps.product_variant">
            <FormAjaxSelect
                :selected-record="state.selectedColors"
                :search-records="searchColor"
                :multi-select="true"
                input-label="Colors"
                placeholder="Please type the name of the color to search."
                @update:selected-record="selectColors"
            />
        </div>

        <div>
            <FormAjaxSelect
                :selected-record="state.selectedDepartments"
                :search-records="searchDepartment"
                :multi-select="true"
                input-label="Department"
                placeholder="Please type the name of the department to search."
                @update:selected-record="selectDepartments"
            />
        </div>

        <div>
            <FormSelectBox
                :selected-record="state.parameters.product_type_id"
                :records="productTypes"
                placeholder="Please select Type"
                label-class="block font-medium text-base text-primary-p3 mb-2"
                input-label="Type"
                @update:selected-record="updateTypeId"
            />
        </div>

        <div v-if="productStatuses.length > 0">
            <FormSelectBox
                :selected-record="state.parameters.status"
                :records="productStatuses"
                label-class="block font-medium text-base text-primary-p3 mb-2"
                input-label="Status"
                @update:selected-record="updateSelectedStatus($event)"
            />
        </div>

        <div>
            <FormSelectBox
                :selected-record="state.parameters.batch"
                :records="productBatches"
                label-class="block font-medium text-base text-primary-p3 mb-2"
                input-label="Batch"
                @update:selected-record="updateSelectedBatch($event)"
            />
        </div>

        <div>
            <JDatePicker
                :range-picker="true"
                :input-value="state.parameters.date_range"
                label-class="block font-medium text-base text-primary-p3 mb-2"
                input-label="Date Range"
                @update:input-value="updateDate($event)"
            />
        </div>

        <div>
            <FormAjaxSelect
                :selected-record="state.selectedArticleNumber"
                :search-records="searchArticleNumber"
                :multi-select="true"
                track-by="article_number"
                label="article_number"
                input-label="Article Number"
                placeholder="Please type the article number of the product to search."
                @update:selected-record="selectArticleNumbers"
            />
        </div>

        <div>
            <FormAjaxSelect
                :selected-record="state.selectedTags"
                :search-records="searchTag"
                :multi-select="true"
                input-label="Tags"
                placeholder="Please type the name of the tag to search."
                @update:selected-record="selectTags"
            />
        </div>

        <div v-if="!pageProps.product_variant">
            <FormAjaxSelect
                :selected-record="state.selectedStyles"
                :search-records="searchStyle"
                :multi-select="true"
                input-label="Styles"
                placeholder="Please type the name of the style to search."
                @update:selected-record="selectStyles"
            />
        </div>

        <div v-if="isActiveProducts">
            <FormAjaxSelect
                :selected-record="state.selectedProductCollection"
                :search-records="searchProductCollection"
                :multi-select="true"
                input-label="Product Collections"
                placeholder="Please type the name of the product collection to search."
                @update:selected-record="selectProductCollections"
            />
        </div>

        <div>
            <FormSelectBox
                :selected-record="state.parameters.product_sync_type_id"
                :records="productSyncTypes"
                placeholder="Please select Type"
                label-class="block font-medium text-base text-primary-p3 mb-2"
                input-label="Sync"
                @update:selected-record="updateProductSyncType"
            />
        </div>

        <div>
            <slot />
        </div>
    </div>

    <div class="mt-3">
        <OutlinePrimaryButton
            type="button"
            text="Clear"
            class="btn-sm w-24 h-10"
            @click="clearAll()"
        />
    </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import AttributesFilters from '@commonComponents/AttributesFilters.vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    productStatuses: {
        type: Array,
        required: true,
    },

    productTypes: {
        type: Array,
        required: true,
    },

    productSyncTypes: {
        type: Array,
        required: true,
    },

    allProductSyncType: {
        type: Number,
        required: true,
    },

    productBatches: {
        type: Array,
        required: true,
    },

    allStatus: {
        type: String,
        required: true,
    },
    categoriesUrl: {
        type: String,
        required: true,
    },
    brandsUrl: {
        type: String,
        required: true,
    },
    sizesUrl: {
        type: String,
        required: true,
    },
    colorsUrl: {
        type: String,
        required: true,
    },
    departmentsUrl: {
        type: String,
        required: true,
    },
    articleNumbersUrl: {
        type: String,
        required: true,
    },
    stylesUrl: {
        type: String,
        required: true,
    },
    productCollectionsUrl: {
        type: String,
        default: '',
        required: false,
    },
    tagsUrl: {
        type: String,
        required: true,
    },
    isActiveProducts: {
        type: Boolean,
        default: true,
    },
    attributes: {
        type: Object,
        default: () => { },
    },
});

const state = reactive({
    parameters: {
        status: props.productStatuses.length > 0 ? props.allStatus : null,
        batch: props.allBatch,
        date_range: null,
        product_type_id: null,
        product_sync_type_id: props.allProductSyncType,
        category_ids: null,
        brand_ids: null,
        department_ids: null,
        color_ids: null,
        size_ids: null,
        article_numbers: null,
        tag_ids: null,
        style_ids: null,
        product_collection_ids: null,
        attributes: null,
    },

    selectedProductId: null,
    newSelectedProductId: null,
    selectedProduct: null,
    refreshTableData: Math.random(),
    selectedBrands: null,
    selectedCategories: null,
    selectedDepartments: null,
    selectedSizes: null,
    selectedColors: null,
    selectedTags: null,
    selectedArticleNumber: null,
    selectedStyles: null,
    selectedProductCollection: null,
    productId: null,
});

const emits = defineEmits([
    'refresh-table',
    'update-params',
    'clear-all'
]);

const updateSelectedBatch = (batches) => {
    state.parameters.batch = batches;
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const updateTypeId = (typeId) => {
    state.parameters.product_type_id = typeId;
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const updateProductSyncType = (syncTypeId) => {
    state.parameters.product_sync_type_id = syncTypeId;
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const clearAll = () => {
    state.parameters.date_range = null;
    state.parameters.status = props.productStatuses.length > 0 ? props.allStatus : '';
    state.parameters.batch = props.allBatch;
    state.parameters.product_type_id = null;
    state.parameters.product_sync_type_id = props.allProductSyncType;
    state.parameters.category_ids = null;
    state.parameters.brand_ids = null;
    state.parameters.department_ids = null;
    state.parameters.color_ids = null;
    state.parameters.size_ids = null;
    state.parameters.style_ids = null;
    state.selectedStyles = null;
    state.parameters.product_collection_ids = null;
    state.selectedProductCollection = null;
    state.parameters.article_numbers = null;
    state.selectedCategories = null;
    state.selectedBrands = null;
    state.selectedSizes = null;
    state.selectedColors = null;
    state.selectedDepartments = null;
    state.selectedArticleNumber = null;
    state.parameters.tag_ids = null;
    state.parameters.attributes = null,
    emits('refresh-table');
    emits('update-params', state.parameters);
    emits('clear-all');
};

const selectCategories = (selectedCategories) => {
    state.selectedCategories = selectedCategories;

    if (selectedCategories !== null) {
        state.parameters.category_ids = selectedCategories.map(function (category) {
            return category.id;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const selectBrands = (selectedBrands) => {
    state.selectedBrands = selectedBrands;
    if (selectedBrands !== null) {
        state.parameters.brand_ids = selectedBrands.map(function (brand) {
            return brand.id;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const selectSizes = (selectedSizes) => {
    state.selectedSizes = selectedSizes;
    if (selectedSizes !== null) {
        state.parameters.size_ids = selectedSizes.map(function (size) {
            return size.id;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const selectColors = (selectedColors) => {
    state.selectedColors = selectedColors;
    if (selectedColors !== null) {
        state.parameters.color_ids = selectedColors.map(function (color) {
            return color.id;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const selectDepartments = (selectedDepartments) => {
    state.selectedDepartments = selectedDepartments;
    if (selectedDepartments !== null) {
        state.parameters.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const updateParams = (params) => {
    state.parameters.attributes = params;
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectedArticleNumber = selectedNumbers;
    if (selectedNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const searchCategory = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(props.categoriesUrl, filterData).then((response) => {
        componentState.records = response.data.categories;
        componentState.isLoading = false;
    });
};

const searchBrand = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(props.brandsUrl, filterData).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const searchSize = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(props.sizesUrl, filterData).then((response) => {
        componentState.records = response.data.sizes;
        componentState.isLoading = false;
    });
};

const searchColor = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(props.colorsUrl, filterData).then((response) => {
        componentState.records = response.data.colors;
        componentState.isLoading = false;
    });
};

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(props.departmentsUrl, filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const minSearchLength = 3;

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(props.articleNumbersUrl, filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const searchStyle = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(props.stylesUrl, filterData).then((response) => {
            componentState.records = response.data.styles;
            componentState.isLoading = false;
        });
    }
};

const searchProductCollection = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(props.productCollectionsUrl, filterData).then((response) => {
            componentState.records = response.data.productCollections;
            componentState.isLoading = false;
        });
    }
};

const selectStyles = (selectedStyles) => {
    state.selectedStyles = selectedStyles;
    if (selectedStyles !== null) {
        state.parameters.style_ids = selectedStyles.map(function (selectedStyle) {
            return selectedStyle.id;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const selectProductCollections = (selectedProductCollections) => {
    state.selectedProductCollection = selectedProductCollections;
    if (selectedProductCollections !== null) {
        state.parameters.product_collection_ids = selectedProductCollections.map(function (selectedProductCollection) {
            return selectedProductCollection.id;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const searchTag = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(props.tagsUrl, filterData).then((response) => {
        componentState.records = response.data.tags;
        componentState.isLoading = false;
    });
};

const selectTags = (selectedTags) => {
    state.selectedTags = selectedTags;
    if (selectedTags !== null) {
        state.parameters.tag_ids = selectedTags.map(function (tag) {
            return tag.id;
        });
    }
    emits('refresh-table');
    emits('update-params', state.parameters);
};

const updateSelectedStatus = (statuses) => {
    state.parameters.status = statuses;
    emits('refresh-table');
    emits('update-params', state.parameters);
};
</script>
