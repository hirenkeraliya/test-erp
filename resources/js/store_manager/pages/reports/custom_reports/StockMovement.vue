<template>
    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="stockMovementReportTypes"
                input-label="Report Type"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                :selected-record="state.parameters.filter_by"
                :records="stockMovementFilters"
                input-label="Filter By"
                @update:selected-record="updateSelectedFilterId($event)"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockMovementFilterStaticDetails.byProduct"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6"
        >
            <JProductFilter
                :product-search-url="route('store_manager.get_filtered_inventory_products')"
                get-product-url-name="store_manager.get_product"
                :selected-product-id="state.parameters.product_id"
                validation-field-name="product_id"
                input-label="Product"
                filter-button-class="mt-8"
                @update:product-selected="productSelected($event, itemIndex)"
                @update:display-product-filters="displayUpdateFilter(itemIndex)"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockMovementFilterStaticDetails.byProductCollection"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6"
        >
            <FormSelectBox
                :selected-record="state.parameters.product_collection_id"
                :records="productCollections"
                placeholder="Please select Product Collection"
                input-label="Product Collection"
                @update:selected-record="updateProductCollectionId"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockMovementFilterStaticDetails.byMasterProduct"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6"
        >
            <FormAjaxSelect
                :selected-record="state.selectArticleNumbers"
                :search-records="searchArticleNumber"
                track-by="article_number"
                label="article_number"
                input-label="Article Number"
                label-class=""
                placeholder="Please type the article number of the product to search."
                @update:selected-record="selectArticleNumbers"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockMovementFilterStaticDetails.byBrand"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6"
        >
            <JMultiSelect
                :selected-records="state.brandIds"
                :records="state.brands"
                input-label="Brands"
                placeholder="Please select brand(s)"
                @update:selected-records="updateBrandId"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockMovementFilterStaticDetails.byDepartment"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6"
        >
            <JMultiSelect
                :selected-records="state.departmentIds"
                :records="state.departments"
                input-label="Departments"
                :placeholder="'Please select Department(s)'"
                @update:selected-records="updateDepartmentIds"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockMovementFilterStaticDetails.byCategories"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6"
        >
            <JMultiSelect
                v-if="state.categories"
                :selected-records="state.selectedCategories"
                :records="state.categories"
                input-label="Categories"
                placeholder="Please select categories"
                @update:selected-records="selectedCategories"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockMovementFilterStaticDetails.byProducts"
            class="input-form col-span-12 sm:col-span-12 md:col-span-6 lg:col-span-6 xl:col-span-6"
        >
            <FileUploadAndDisplayRecords
                :selected-products="state.selectedProducts"
                :unmatched-products="state.unmatchedProducts"
                product-upc-url="store_manager.products.get_matching_upc_products"
                input-label="Products"
                validation-field-name="product-ids"
                file-path="/files/stock-movement-custom-report-products-sample-file.xlsx"
                @display-selected-products-modal="openSelectedProductsModal"
                @update:column-details="updateColumnDetails"
                @display-unmatched-products-modal="openUnmatchedProductsModal"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <JDatePicker
                v-model:input-value="state.parameters.date_range"
                :required="true"
                :range-picker="true"
                input-label="Date Filter"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
            <OutlineDangerButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="clearData"
            />

            <PrimaryButton
                type="button"
                text="PDF"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportStockMovement"
            />

            <PrimaryButton
                type="button"
                text="Excel"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportExcelRecord"
            />

            <PrimaryButton
                type="button"
                text="CSV"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportCsvRecord"
            />
        </div>
    </div>

    <SelectedProducts
        :modal-show="state.displaySelectedProductsModal"
        :columns="state.fields"
        :records="state.selectedProducts"
        @close-modal="closeModal"
    >
        <template #color="record">
            {{ record.item.color ? record.item.color.name : record.item.color_name }}
        </template>
        <template #size="record">
            {{ record.item.size ? record.item.size.name : record.item.size_name }}
        </template>
    </SelectedProducts>

    <UnmatchedProducts
        :modal-show="state.displayUnmatchedProductsModal"
        :records="state.unmatchedProducts"
        @close-modal="closeModal"
    />

    <JProductFilterDetails
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="route('store_manager.get_filtered_inventory_products_list')"
        :filtered-category-url="route('store_manager.categories.get_filtered_categories')"
        :filtered-brand-url="route('store_manager.brands.get_filtered_brands')"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { showErrorNotification } from '@commonServices/notifier';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FileUploadAndDisplayRecords from '@commonComponents/FileUploadAndDisplayRecords.vue';
import SelectedProducts from '@commonComponents/SelectedProducts.vue';
import UnmatchedProducts from '@commonComponents/UnmatchedProducts.vue';
import { route } from 'ziggy';
import axios from 'axios';
import { exportRecords, printReport } from '@commonServices/helper';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    stockMovementFilters: {
        type: Object,
        required: true,
    },
    stockMovementReportTypes: {
        type: Object,
        required: true,
    },
    stockMovementFilterStaticDetails: {
        type: Object,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    parameters: {
        date_range: null,
        category_ids: null,
        product_ids: null,
        product_id: null,
        product_collection_id: null,
        brand_ids: null,
        department_ids: null,
        report_type: null,
        filter_by: null,
    },

    selectedCategory: null,
    selectedCategories: null,
    customReportSelected: null,
    selectedProducts: [],
    unmatchedProducts: [],
    displayInventoryUpdateFilterModal: false,
    selectArticleNumbers: [],
    brands: [],
    brandIds: [],
    departments: [],
    categories: [],
    departmentIds: null,
    fields: [
        {
            key: 'id',
        }, {
            key: 'name',
        }, {
            key: 'upc'
        }, {
            key: 'color'
        }, {
            key: 'size'
        }
    ],

    displaySelectedProductsModal: false,
    displayUnmatchedProductsModal: false,
});

const updateSelectedFilterId = (filterBy) => {
    state.parameters.filter_by = filterBy;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    state.parameters.product_collection_id = null;
    state.selectArticleNumbers = [];
    state.brands = [];
    state.departments = [];

    if (filterBy === props.stockMovementFilterStaticDetails.byBrand) {
        axios.post(route('store_manager.brands.get_brands'))
            .then((response) => {
                state.brands = response.data.brands;
            });
    }

    if (filterBy === props.stockMovementFilterStaticDetails.byDepartment) {
        axios.get(route('store_manager.departments.get_departments_list'))
            .then((response) => {
                state.departments = response.data.departments;
            });
    }
};

const updateBrandId = (brandIds) => {
    state.parameters.department_ids = null;
    state.brandIds = brandIds;
    state.parameters.brand_ids = state.brandIds.map((brand) => {
        return brand.id;
    });
};

const updateDepartmentIds = (departmentIds) => {
    state.parameters.brand_ids = null;
    state.departmentIds = departmentIds;
    state.parameters.department_ids = state.departmentIds.map((department) => {
        return department.id;
    });
};

const displayUpdateFilter = () => {
    state.displayInventoryUpdateFilterModal = true;
};

const productSelected = (selectedProduct) => {
    if (selectedProduct) {
        state.parameters.article_number = null;
        state.selectArticleNumbers = null;
        state.parameters.product_id = selectedProduct.id;
        return;
    }
    state.parameters.product_id = null;
};

const filteredProductSelected = (selectedProduct) => {
    state.displayInventoryUpdateFilterModal = false;
    productSelected(selectedProduct);
};

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    const minSearchLength = 3;
    
    if (searchText.length >= minSearchLength) {
        axios.post(route('store_manager.products.get_filtered_article_number'), filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectArticleNumbers = selectedNumbers;
    state.parameters.product_id = null;
    if (selectedNumbers !== null) {
        state.parameters.article_number = selectedNumbers.article_number;
    }
};

const clearData = () => {
    emits('update:clear-button');
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

const openSelectedProductsModal = () => {
    state.displaySelectedProductsModal = true;
};

const openUnmatchedProductsModal = () => {
    state.displayUnmatchedProductsModal = true;
};

const updateColumnDetails = (details) => {
    state[details.column_name] = details.value;
};

const validationCheck = () => {
    if (state.parameters.date_range === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    return false;
};

const exportStockMovement = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    state.parameters.product_ids = state.selectedProducts.map((product) => {
        return product.id;
    });
    printReport(route('store_manager.custom_reports.stock_movement_report_print', state.parameters));
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    return exportRecords(
        'export-custom-stock-movement/',
        'stock-movement-report.csv',
        state.parameters
    );
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type and date before proceeding.');
        return;
    }

    return exportRecords(
        'export-custom-stock-movement/',
        'stock-movement-report.xlsx',
        state.parameters
    );
};

const selectedCategories = (categoryIds) => {
    state.selectedCategories = categoryIds;
    state.parameters.category_ids = state.selectedCategories.map((category) => {
        return category.id;
    });
};

onMounted(() => {
    if (props.stockMovementFilters) {
        axios.get(route('store_manager.categories.get_categories_list')).then((response) => {
            state.categories = response.data.categories;
        });
    }
});

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
};
</script>
