<template>
    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="stockAdjustmentReportType"
                input-label="Report By"
                :required="true"
                placeholder="Report By"
            />
        </div>

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
                <JMultiSelect
                    :selected-records="state.stores"
                    :records="stores"
                    :required="true"
                    input-label="Stores"
                    placeholder="Please select store"
                    @update:selected-records="updateStores"
                />
            </TabPanel>

            <TabPanel
                v-if="state.typeId === staticLocationTypes.warehouse"
                class="active"
            >
                <JMultiSelect
                    :selected-records="state.warehouses"
                    :records="warehouses"
                    :required="true"
                    input-label="Warehouses"
                    placeholder="Please select warehouse"
                    @update:selected-records="updateWarehouses"
                />
            </TabPanel>
        </div>

        <div class="w-full lg:w-1/2 px-3 mt-2 sm:mt-2 lg:mt-8">
            <PrimaryButton
                type="button"
                text="Select all"
                class="w-auto sm:w-24 md:w-1/1"
                @click="selectAllLocations"
            />

            <OutlinePrimaryButton
                v-if="state.displayClearButton"
                type="button"
                text="Clear All"
                class="w-auto sm:w-24 md:w-1/1 mt-2"
                @click="clearAllLocations"
            />
        </div>
    </div>

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                :selected-record="state.parameters.filter_by"
                :records="stockAdjustmentFilterType"
                input-label="Filter By"
                placeholder="Filter By"
                @update:selected-record="updateTheFilterBy"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === staticStockAdjustmentFilterType.byProduct"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JProductFilter
                :product-search-url="route('admin.get_filtered_products')"
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
            v-if="state.parameters.filter_by === staticStockAdjustmentFilterType.byMasterProduct"
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
            v-if="state.parameters.filter_by === staticStockAdjustmentFilterType.byProductCollection"
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

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.stock_adjustment_type"
                :records="stockAdjustmentTypes"
                input-label="Stock Adjustment Type"
                placeholder="Stock Adjustment Type"
            />
        </div>

        <div
            v-if="state.parameters.report_type"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
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
                @click="exportStockAdjustment"
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
        :product-search-url="route('admin.get_filtered_products_list')"
        :filtered-category-url="route('admin.categories.get_filtered_categories')"
        :filtered-brand-url="route('admin.brands.get_filtered_brands')"
        @update:product-selected="filteredProductSelected"
        @close-modal="state.displayInventoryUpdateFilterModal = false"
    />
</template>

<script setup>
import { reactive } from 'vue';
import { TabPanel } from '@commonVendor/tab';
import { showErrorNotification } from '@commonServices/notifier';
import JTabs from '@commonComponents/JTabs.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { route } from 'ziggy';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import { exportRecords, printReport } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    stockAdjustmentFilterType: {
        type: Object,
        required: true,
    },
    stockAdjustmentReportType: {
        type: Object,
        required: true,
    },
    staticStockAdjustmentFilterType: {
        type: Object,
        required: true,
    },
    stockAdjustmentTypes: {
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
        location_ids: null,
        date_range: null,
        filter_by: null,
        report_type: null,
        product_id: null,
        article_number: null,
        stock_adjustment_type: null,
        product_collection_id: null,
    },
    stores: [],
    warehouses: [],
    displayInventoryUpdateFilterModal: false,
    selectArticleNumbers: [],
});

const updateLocationType = (typeId) => {
    state.typeId = typeId;
    state.parameters.location_ids = null;
    state.parameters.stock_adjustment_type = null;
    state.stores = null;
    state.warehouses = null;
};

const clearData = () => {
    emits('update:clear-button');
};

const validationCheck = () => {
    if (!state.parameters.location_ids || !state.parameters.location_ids.length) {
        return true;
    }

    if (state.parameters.date_range === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    return false;
};

const updateTheFilterBy = (filterBy) => {
    state.parameters.filter_by = filterBy;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    state.parameters.stock_adjustment_type = null;
    state.parameters.product_collection_id = null;
    state.selectArticleNumbers = [];
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

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
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

const exportStockAdjustment = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, store and a date before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byMasterProduct && state.parameters.article_number === null) {
        showErrorNotification('Please select article number.');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byProduct && state.parameters.product_id === null) {
        showErrorNotification('Please select product.');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byProductCollection && state.parameters.product_collection_id === null) {
        showErrorNotification('Please select product collection.');
        return;
    }

    printReport(route('admin.custom_reports.print_stock_adjustment', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, store and a date before proceeding..');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byMasterProduct && state.parameters.article_number === null) {
        showErrorNotification('Please select article number.');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byProduct && state.parameters.product_id === null) {
        showErrorNotification('Please select product.');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byProductCollection && state.parameters.product_collection_id === null) {
        showErrorNotification('Please select product collection.');
        return;
    }

    return exportRecords(
        'export-stock-adjustment-report/',
        'stock-adjustment.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, store and a date before proceeding..');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byMasterProduct && state.parameters.article_number === null) {
        showErrorNotification('Please select article number.');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byProduct && state.parameters.product_id === null) {
        showErrorNotification('Please select product.');
        return;
    }

    if (state.parameters.filter_by === props.staticStockAdjustmentFilterType.byProductCollection && state.parameters.product_collection_id === null) {
        showErrorNotification('Please select product collection.');
        return;
    }

    return exportRecords(
        'export-stock-adjustment-report/',
        'stock-adjustment.csv',
        state.parameters
    );
};

const updateStores = (stores) => {
    state.stores = stores;
    state.parameters.location_ids = stores.map((store) => {
        return store.id;
    });
};

const updateWarehouses = (warehouses) => {
    state.warehouses = warehouses;
    state.parameters.location_ids = warehouses.map((warehouse) => {
        return warehouse.id;
    });
};

const selectAllLocations = () => {
    if (state.typeId === props.staticLocationTypes.store) {
        updateStores(props.stores);
        state.displayClearButton = true;
        return;
    }

    if (state.typeId === props.staticLocationTypes.warehouse) {
        updateWarehouses(props.warehouses);
        state.displayClearButton = true;
    }
};

const clearAllLocations = () => {
    state.warehouses = [];
    state.stores = [];
    state.parameters.location_ids = null;
    state.typeId = props.staticLocationTypes.store;
    state.displayClearButton = false;
};

</script>
