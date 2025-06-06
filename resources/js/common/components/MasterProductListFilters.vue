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
import { reactive } from 'vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import axios from 'axios';

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
    departmentsUrl: {
        type: String,
        required: true,
    },
    articleNumbersUrl: {
        type: String,
        required: true,
    },
    isActiveProducts: {
        type: Boolean,
        default: true,
    }
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
        article_numbers: null,
    },

    selectedProductId: null,
    newSelectedProductId: null,
    selectedProduct: null,
    refreshTableData: Math.random(),
    selectedBrands: null,
    selectedCategories: null,
    selectedDepartments: null,
    selectedArticleNumber: null,
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
    state.parameters.article_numbers = null;
    state.selectedCategories = null;
    state.selectedBrands = null;
    state.selectedDepartments = null;
    state.selectedArticleNumber = null;
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

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(props.departmentsUrl, filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    const minSearchLength = 3;
    
    if (searchText.length >= minSearchLength) {
        axios.post(props.articleNumbersUrl, filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const updateSelectedStatus = (statuses) => {
    state.parameters.status = statuses;
    emits('refresh-table');
    emits('update-params', state.parameters);
};
</script>
