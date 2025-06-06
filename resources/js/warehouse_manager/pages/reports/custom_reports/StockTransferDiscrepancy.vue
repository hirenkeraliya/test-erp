<template>
    <PageTitle title="Stock Transfer Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_by"
                :records="stockTransferReportType"
                input-label="Report By"
                :required="true"
                placeholder="Report By"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="block sm:flex items-center">
                <FormSelectBox
                    v-model:selected-record="state.parameters.filter_by"
                    :records="stockTransferFilters"
                    input-label="Filter By"
                    placeholder="Filter By"
                    class="w-full"
                />
                <div
                    v-if="state.parameters.filter_by"
                    class="ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                >
                    <PrimaryButton
                        type="button"
                        text="Clear Filter"
                        class="btn-sm w-24 h-10"
                        @click="clearFilterBy"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="state.parameters.filter_by === stockTransferFilterStaticDetails.byProduct"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JProductFilter
                :product-search-url="route('warehouse_manager.get_filtered_inventory_products')"
                get-product-url-name="warehouse_manager.get_product"
                :selected-product-id="state.parameters.product_id"
                validation-field-name="product_id"
                input-label="Product"
                filter-button-class="mt-8"
                @update:product-selected="productSelected($event, itemIndex)"
                @update:display-product-filters="displayUpdateFilter(itemIndex)"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === stockTransferFilterStaticDetails.byProductCollection"
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
            v-if="state.parameters.filter_by === stockTransferFilterStaticDetails.byMasterProduct"
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

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.transfer_type"
                :records="stockTransferTransferType"
                input-label="Transfer Type"
                :required="true"
                placeholder="Transfer Type"
            />
        </div>

        <div
            v-if="state.parameters.report_by"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                v-model:selected-record="state.parameters.date_type"
                :records="stockTransferReportDateTypes"
                input-label="Date Range From"
                :required="true"
                placeholder="Date Range From"
            />
        </div>
        <div
            v-if="state.parameters.report_by"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                v-model:selected-record="state.parameters.display_date_type"
                :records="stockTransferReportDateTypes"
                input-label="Display Date"
                :required="true"
                placeholder="Display Date"
            />
        </div>

        <div
            v-if="state.parameters.report_by"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
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
                @click="exportStockTransfer"
            />

            <PrimaryButton
                v-if="stockTransferFilterStaticDetails.byDetails !== state.parameters.filter_by"
                type="button"
                text="Excel"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportStockTransferAsExcel"
            />

            <PrimaryButton
                v-if="stockTransferFilterStaticDetails.byDetails !== state.parameters.filter_by"
                type="button"
                text="CSV"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportStockTransferAsCSV"
            />
        </div>
    </div>

    <JProductFilterDetails
        :modal-show="state.displayInventoryUpdateFilterModal"
        :product-search-url="route('warehouse_manager.get_filtered_inventory_products_list')"
        :filtered-category-url="route('warehouse_manager.categories.get_filtered_categories')"
        :filtered-brand-url="route('warehouse_manager.brands.get_filtered_brands')"
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
import { exportRecords, printReport } from '@commonServices/helper';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import axios from 'axios';

defineProps({
    stockTransferFilters: {
        type: Object,
        required: true,
    },
    stockTransferTransferType: {
        type: Object,
        required: true,
    },
    stockTransferReportDateTypes: {
        type: Object,
        required: true,
    },
    stockTransferFilterStaticDetails: {
        type: Object,
        required: true,
    },
    stockTransferReportType: {
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
        date_type: null,
        display_date_type: null,
        transfer_type: null,
        filter_by: null,
        product_id: null,
        article_number: null,
        report_by: null,
        product_collection_id: null,
    },
    displayInventoryUpdateFilterModal: false,
    selectArticleNumbers: [],
});

const displayUpdateFilter = () => {
    state.displayInventoryUpdateFilterModal = true;
};
const clearFilterBy = () => {
    state.parameters.filter_by = null;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    state.parameters.product_collection_id = null;
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
        axios.post(route('warehouse_manager.products.get_filtered_article_number'), filterData).then((response) => {
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

const validationCheck = () => {
    if (state.parameters.transfer_type === null) {
        return true;
    }

    if (state.parameters.report_by === null) {
        return true;
    }

    if (state.parameters.date_range === null) {
        return true;
    }

    return false;
};

const exportStockTransfer = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report By, Transfer By, and date before proceeding.');
        return;
    }

    printReport(route('warehouse_manager.custom_reports.print_stock_transfer_discrepancy', state.parameters));
};

const exportStockTransferAsExcel = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a location, report by, transfer by, and date before proceeding..');
        return;
    }

    return exportRecords(
        'export-stock-transfer-discrepancy/',
        'stock-transfer-discrepancy.xlsx',
        state.parameters
    );
};

const exportStockTransferAsCSV = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a location, report by, transfer by, and date before proceeding..');
        return;
    }

    return exportRecords(
        'export-stock-transfer-discrepancy/',
        'stock-transfer-discrepancy.csv',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
};
</script>
