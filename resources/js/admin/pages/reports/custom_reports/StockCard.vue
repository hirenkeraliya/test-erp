<template>
    <PageTitle title="Stock Card Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="mt-3">
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.typeId"
                    input-label="Location Selection"
                    :required="true"
                    return-selected-record="id"
                    @update:selected-record="updateLocationType"
                />
            </div>
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <TabPanel
                v-if="state.typeId === staticLocationTypes.store"
                class="active"
            >
                <FormSelectBox
                    :selected-record="state.parameters.location_id"
                    :records="stores"
                    input-label="Store"
                    placeholder="Please select store"
                    :required="true"
                    @update:selected-record="updateLocationId($event)"
                />
            </TabPanel>
            <TabPanel
                v-if="state.typeId === staticLocationTypes.warehouse"
                class="active"
            >
                <FormSelectBox
                    :selected-record="state.parameters.location_id"
                    :records="warehouses"
                    input-label="Warehouse"
                    placeholder="Please select warehouse"
                    :required="true"
                    @update:selected-record="updateLocationId($event)"
                />
            </TabPanel>
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                :selected-record="state.parameters.filter_by"
                :records="stockCardFilter"
                input-label="Filter By"
                :required="true"
                @update:selected-record="updateSelectedFilterId($event)"
            />
        </div>
        <div
            v-if="state.parameters.filter_by === stockCardFilterStaticDetails.byProduct"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JProductFilter
                :product-search-url="route('admin.get_filtered_inventory_products')"
                get-product-url-name="admin.get_product"
                :selected-product-id="state.parameters.product_id"
                validation-field-name="product_id"
                input-label="Product"
                filter-button-class="mt-8"
                @update:product-selected="productSelected($event, itemIndex)"
                @update:display-product-filters="displayUpdateFilter(itemIndex)"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockCardFilterStaticDetails.byProductCollection"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
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
            v-if="state.parameters.filter_by === stockCardFilterStaticDetails.byMasterProduct"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
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
            v-if="state.parameters.filter_by === stockCardFilterStaticDetails.byBrand"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                :selected-record="state.parameters.brand_id"
                :records="state.brands"
                input-label="Brands"
                placeholder="Please select brand"
                @update:selected-record="updateBrandId($event)"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockCardFilterStaticDetails.byDepartment"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                :selected-record="state.parameters.department_id"
                :records="state.departments"
                input-label="Departments"
                placeholder="Please select department"
                @update:selected-record="updateDepartmentId($event)"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockCardFilterStaticDetails.byCategory"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                :selected-record="state.parameters.category_id"
                :records="state.categories"
                input-label="Categories"
                placeholder="Please select category"
                @update:selected-record="updateCategoryId($event)"
            />
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <JDatePicker
                v-model:input-value="state.parameters.date_range"
                :range-picker="true"
                :required="true"
                input-label="Date Range"
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
                @click="exportPDFGeneralSalesReport"
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

    <JProductFilterDetails
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="route('admin.get_filtered_inventory_products_list')"
        :filtered-category-url="route('admin.categories.get_filtered_categories')"
        :filtered-brand-url="route('admin.brands.get_filtered_brands')"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />
</template>
<script setup>
import { reactive } from 'vue';
import { route } from 'ziggy';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import { TabPanel } from '@commonVendor/tab';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JTabs from '@commonComponents/JTabs.vue';
import { exportRecords, printReport } from '@commonServices/helper';

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    stockCardFilter: {
        type: Object,
        required: true,
    },
    stockCardFilterStaticDetails: {
        type: Object,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    }
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    typeId: props.staticLocationTypes.store,
    parameters: {
        date_range: null,
        product_id: null,
        article_number: null,
        brand_id: null,
        category_id: null,
        department_id: null,
        location_id: null,
        filter_by: null,
        product_collection_id: null,
    },

    displayInventoryUpdateFilterModal: false,
    selectArticleNumbers: [],
    brands: [],
    departments: [],
    categories: [],
});

const updateLocationType = (typeId) => {
    state.typeId = typeId;
    state.parameters.location_id = null;
};

const updateLocationId = (locationId) => {
    state.parameters.location_id = locationId;
};

const updateSelectedFilterId = (filterBy) => {
    state.parameters.filter_by = filterBy;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    state.parameters.category_id = null;
    state.parameters.brand_id = null;
    state.parameters.department_id = null;
    state.parameters.product_collection_id = null;
    state.selectArticleNumbers = [];
    state.brands = [];
    state.departments = [];
    state.categories = [];

    if (filterBy === props.stockCardFilterStaticDetails.byBrand) {
        axios.post(route('admin.brands.get_brands'))
            .then((response) => {
                state.brands = response.data.brands;
            });
    }

    if (filterBy === props.stockCardFilterStaticDetails.byDepartment) {
        axios.get(route('admin.departments.get_departments_list'))
            .then((response) => {
                state.departments = response.data.departments;
            });
    }

    if (filterBy === props.stockCardFilterStaticDetails.byCategory) {
        axios.get(route('admin.categories.get_categories_list')).then((response) => {
            state.categories = response.data.categories;
        });
    }
};

const validationCheck = () => {
    if (state.parameters.location_id === null) {
        return true;
    }

    if (state.parameters.date_range === null) {
        return true;
    }
    return false;
};

const exportPDFGeneralSalesReport = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a store and a date before proceeding.');
        return;
    }

    const validFilters = [
        props.stockCardFilterStaticDetails.byBrand,
        props.stockCardFilterStaticDetails.byCategory,
        props.stockCardFilterStaticDetails.byDepartment,
        props.stockCardFilterStaticDetails.byProductCollection
    ];

    if (
        !validFilters.includes(state.parameters.filter_by) &&
        state.parameters.article_number === null &&
        state.parameters.product_id === null
    ) {
        showErrorNotification('Please choose a product or article number.');
        return;
    }

    printReport(route('admin.custom_reports.print_stock_card', state.parameters));
};

const clearData = () => {
    emits('update:clear-button');
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
        axios.post(route('admin.products.get_filtered_article_number'), filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectArticleNumbers = selectedNumbers;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    if (selectedNumbers !== null) {
        state.parameters.article_number = selectedNumbers.article_number;
    }
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a store and a date before proceeding..');
        return;
    }

    return exportRecords(
        'export-stock-card/',
        'stock-card.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a store and a date before proceeding..');
        return;
    }

    return exportRecords(
        'export-stock-card/',
        'stock-card.csv',
        state.parameters
    );
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
};

const updateBrandId = (brandId) => {
    state.parameters.brand_id = brandId;
};

const updateDepartmentId = (departmentId) => {
    state.parameters.department_id = departmentId;
};

const updateCategoryId = (categoryId) => {
    state.parameters.category_id = categoryId;
};

</script>
