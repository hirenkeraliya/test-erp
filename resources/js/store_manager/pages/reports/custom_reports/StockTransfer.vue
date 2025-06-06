<template>
    <PageTitle title="Stock Transfer Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
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
                    :selected-record="state.parameters.filter_by"
                    :records="stockTransferFilters"
                    input-label="Filter By"
                    placeholder="Filter By"
                    class="w-full"
                    @update:selected-record="updateFilterType"
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
            v-if="isStatusAllowed"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.statusTypes"
                :records="stockTransferStatuses"
                :required="isStatusAllowed"
                input-label="Status Type"
                @update:selected-records="updateStatusType"
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

        <div
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                :selected-record="state.parameters.transfer_type"
                :records="stockTransferTransferType"
                input-label="Transfer Type"
                :required="true"
                placeholder="Transfer Type"
                @update:selected-record="updateTransferType"
            />
        </div>

        <div
            v-if="state.parameters.transfer_type === transferTypeOut || state.parameters.transfer_type === transferTypeIn"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <div class="mt-3">
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.additionalTypeId"
                    return-selected-record="id"
                    input-label="Location Selection"
                    :required="true"
                    @update:selected-record="additionalUpdateLocationType"
                />
            </div>
        </div>
        <div
            v-if="state.parameters.transfer_type === transferTypeOut || state.parameters.transfer_type === transferTypeIn"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <TabPanel
                v-if="state.additionalTypeId === staticLocationTypes.store"
                class="active"
            >
                <FormSelectBox
                    :selected-record="state.parameters.additional_location_id"
                    :records="stores"
                    :required="true"
                    input-label="Stores"
                    placeholder="Please select store"
                    @update:selected-record="additionalUpdateLocationId"
                />
            </TabPanel>

            <TabPanel
                v-if="state.additionalTypeId === staticLocationTypes.warehouse"
                class="active"
            >
                <FormSelectBox
                    :selected-record="state.parameters.additional_location_id"
                    :records="warehouses"
                    :required="true"
                    input-label="Warehouses"
                    placeholder="Please select warehouse"
                    @update:selected-record="additionalUpdateLocationId"
                />
            </TabPanel>
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

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-10">
            <strong>Show Price</strong>
            <FormCheckbox
                :check-value="state.parameters.display_total_price"
                class="ml-2"
                @change="updateCheckbox"
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
        :product-search-url="route('store_manager.get_filtered_inventory_products_list')"
        :filtered-category-url="route('store_manager.categories.get_filtered_categories')"
        :filtered-brand-url="route('store_manager.brands.get_filtered_brands')"
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
import FormCheckbox from '@commonComponents/FormCheckbox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JTabs from '@commonComponents/JTabs.vue';
import { TabPanel } from '@commonVendor/tab';

const props = defineProps({
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
    stockTransferStatuses: {
        type: Object,
        default: null,
    },
    isStatusAllowed: {
        type: Boolean,
        default: false,
    },
    transferTypeOut: {
        type: Number,
        required: true,
    },
    transferTypeIn: {
        type: Number,
        required: true,
    },
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
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

const additionalUpdateLocationType = (additionalTypeId) => {
    state.additionalTypeId = additionalTypeId;
    state.parameters.additional_location_id = null;
};

const additionalUpdateLocationId = (additionalLocationId) => {
    state.parameters.additional_location_id = additionalLocationId;
};

const updateTransferType = (value) => {
    state.parameters.transfer_type = value;
    state.parameters.additional_location_id = null;
    state.additionalTypeId = null;

    if (value === props.transferTypeOut || value === props.transferTypeIn) {
        state.additionalTypeId = props.staticLocationTypes.store;
    }
};

const state = reactive({
    additionalTypeId: props.staticLocationTypes.store,
    parameters: {
        additional_location_id: null,
        date_range: null,
        date_type: null,
        display_date_type: null,
        transfer_type: null,
        filter_by: null,
        status_type: null,
        product_id: null,
        article_number: null,
        product_collection_id: null,
        report_by: null,
        display_total_price: 1,
    },
    statusType: null,
    statusTypes: null,
    displayInventoryUpdateFilterModal: false,
    selectArticleNumbers: [],
});

const clearFilterBy = () => {
    state.parameters.date_type = null;
    state.parameters.display_date_type = null;
    state.parameters.filter_by = null;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    state.parameters.product_collection_id = null;
    state.parameters.status_type = null;
    state.statusTypes = null;
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

    if (state.parameters.date_type === null) {
        return true;
    }

    if (state.parameters.display_date_type === null) {
        return true;
    }

    return false;
};

const exportStockTransfer = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a Report By, Transfer By, and date before proceeding.');
        return;
    }

    if (props.isStatusAllowed && state.statusType === null) {
        showErrorNotification('Please select status before proceeding..');
        return;
    }

    state.parameters.status_type = state.statusType;

    printReport(route('store_manager.custom_reports.print_stock_transfer', state.parameters));
};

const exportStockTransferAsExcel = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a store, Report By, transfer by, and date before proceeding..');
        return;
    }

    if (props.isStatusAllowed && state.statusType === null) {
        showErrorNotification('Please select status before proceeding..');
        return;
    }

    state.parameters.status_type = state.statusType;

    return exportRecords(
        'export-stock-transfer/',
        'stock-transfer.xlsx',
        state.parameters
    );
};

const exportStockTransferAsCSV = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a store, Report By, transfer by, and date before proceeding..');
        return;
    }

    if (props.isStatusAllowed && state.statusType === null) {
        showErrorNotification('Please select status before proceeding..');
        return;
    }

    state.parameters.status_type = state.statusType;

    return exportRecords(
        'export-stock-transfer/',
        'stock-transfer.csv',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};

const updateCheckbox = () => {
    state.parameters.display_total_price = !state.parameters.display_total_price;
};

const updateStatusType = (statusTypes) => {
    state.statusTypes = statusTypes;
    const statusTypeIds = statusTypes.map((statusType) => {
        return statusType.id;
    });
    state.statusType = statusTypeIds;
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
};

const updateFilterType = (filterBy) => {
    state.parameters.filter_by = filterBy;
    state.parameters.product_collection_id = null;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
};
</script>
