<template>
    <PageTitle title="Inter Company Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                :selected-record="state.parameters.external_company_id"
                :records="externalCompanies"
                placeholder="Please select external company"
                input-label="External Company"
                @update:selected-record="updateExternalCompanyId"
            />
        </div>

        <div
            v-if="state.parameters.external_company_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-3"
        >
            <JTabs
                :records="locationTypes"
                :selected-record="state.externalTypeId"
                input-label="External Location"
                return-selected-record="id"
                @update:selected-record="updateExternalLocationType"
            />
        </div>

        <div
            v-if="state.parameters.external_company_id"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <TabPanel
                v-if="state.externalTypeId === staticLocationTypes.store"
                class="active"
            >
                <FormSelectBox
                    :selected-record="state.parameters.external_location_id"
                    :records="state.externalStores"
                    placeholder="Please select store"
                    input-label="External Stores"
                    @update:selected-record="updateExternalLocationId"
                />
            </TabPanel>

            <TabPanel
                v-if="state.externalTypeId === staticLocationTypes.warehouse"
                class="active"
            >
                <FormSelectBox
                    :selected-record="state.parameters.external_location_id"
                    :records="state.externalWarehouses"
                    placeholder="Please select warehouse"
                    input-label="External Warehouses"
                    @update:selected-record="updateExternalLocationId"
                />
            </TabPanel>
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="block sm:flex items-center">
                <FormSelectBox
                    v-model:selected-record="state.parameters.filter_by"
                    :records="purchaseOrderFilters"
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
            v-if="state.parameters.filter_by === interCompanyFilterStaticDetails.byProduct"
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
            v-if="state.parameters.filter_by === interCompanyFilterStaticDetails.byProductCollection"
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
            v-if="state.parameters.filter_by === interCompanyFilterStaticDetails.byArticleNumber"
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
                v-model:selected-record="state.parameters.report_by"
                :records="interCompanyReportType"
                input-label="Report By"
                :required="true"
                placeholder="Report By"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.transfer_type"
                :records="interCompanyTransferType"
                input-label="Transfer Type"
                :required="true"
                placeholder="Transfer Type"
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

        <div
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 mt-10"
        >
            <strong>Show Purchase Cost</strong>
            <FormCheckbox
                :check-value="state.parameters.display_purchase_cost"
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
                @click="exportInterCompanyStockTransfer"
            />

            <PrimaryButton
                type="button"
                text="Excel"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportInterCompanyStockTransferAsExcel"
            />

            <PrimaryButton
                type="button"
                text="CSV"
                class="btn-sm w-24 h-10 mt-3 mr-1"
                @click="exportInterCompanyStockTransferAsCSV"
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
import JTabs from '@commonComponents/JTabs.vue';
import { TabPanel } from '@commonVendor/tab';
import { exportRecords, printReport } from '@commonServices/helper';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import axios from 'axios';
import FormCheckbox from '@commonComponents/FormCheckbox.vue';

const props = defineProps({
    purchaseOrderFilters: {
        type: Object,
        required: true,
    },
    interCompanyTransferType: {
        type: Object,
        required: true,
    },
    interCompanyFilterStaticDetails: {
        type: Object,
        required: true,
    },
    interCompanyReportType: {
        type: Object,
        required: true,
    },
    externalCompanies: {
        type: Array,
        required: true,
    },
    deliveryOrderStaticType: {
        type: Number,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    locationTypes: {
        type: Array,
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
    displayInventoryUpdateFilterModal: false,
    selectArticleNumbers: [],
    externalStores: [],
    externalWarehouses: [],
    externalTypeId: props.staticLocationTypes.store,
    parameters: {
        date_range: null,
        transfer_type: null,
        status_type: null,
        filter_by: null,
        product_id: null,
        article_number: null,
        report_by: null,
        display_purchase_cost: 1,
        external_location_id: null,
        external_company_id: null,
    },
});

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

    if (state.parameters.date_range === null) {
        return true;
    }

    return false;
};

const clearFilterBy = () => {
    state.parameters.filter_by = null;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    state.parameters.product_collection_id = null;
};

const exportInterCompanyStockTransfer = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, transfer by, and date before proceeding..');
        return;
    }

    printReport(route('warehouse_manager.custom_reports.print_inter_company', state.parameters));
};

const exportInterCompanyStockTransferAsExcel = () => {
    if (validationCheck()) {
        showErrorNotification('Please select report by, transfer by, and date before proceeding..');
        return;
    }

    return exportRecords(
        'export-inter-company/',
        'inter-company-stock-transfer.xlsx',
        state.parameters
    );
};

const exportInterCompanyStockTransferAsCSV = () => {
    if (validationCheck()) {
        showErrorNotification('Please select report by, transfer by, and date before proceeding..');
        return;
    }

    return exportRecords(
        'export-inter-company/',
        'inter-company-stock-transfer.csv',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};

const updateCheckbox = () => {
    if (state.parameters.display_purchase_cost === 1) {
        state.parameters.display_purchase_cost = 0;
        return;
    }

    state.parameters.display_purchase_cost = 1;
};

const updateExternalLocationType = (typeId) => {
    state.externalTypeId = typeId;
    state.parameters.external_location_id = null;
};

const updateExternalLocationId = (locationId) => {
    state.parameters.external_location_id = locationId;
};

const updateExternalCompanyId = (externalCompanyId) => {

    if (!externalCompanyId) {
        state.parameters.external_company_id = null;
        state.externalStores = [];
        state.externalWarehouses = [];
        state.parameters.external_location_id = null;
        return;
    }

    state.parameters.external_company_id = externalCompanyId;

    axios.get(route('warehouse_manager.external_locations.get_external_locations', externalCompanyId))
        .then((response) => {
            state.externalStores = response.data.externalStores;
            state.externalWarehouses = response.data.externalWarehouses;
        });
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
};
</script>
