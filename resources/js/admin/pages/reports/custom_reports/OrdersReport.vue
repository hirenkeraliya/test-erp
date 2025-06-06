<template>
    <PageTitle title="Orders Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                :selected-record="state.parameters.location_id"
                :records="locations"
                input-label="Location"
                placeholder="Please select Location"
                :required="true"
                @update:selected-record="updateLocationId"
            />
        </div>

        <div
            v-if="state.storeManagers.length > 0"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                v-model:selected-record="state.parameters.store_manager_id"
                :records="state.storeManagers"
                input-label="Store Manager"
                placeholder="Please select Store Manager"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="orderReportTypes"
                input-label="Report Type"
                placeholder="Please select Report Type"
                :required="true"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.filter_by"
                :records="orderFilterTypes"
                input-label="Filter By"
                placeholder="Please select Filter By"
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

        <div
            v-if="state.parameters.filter_by === orderFilterStaticTypes.byProduct"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JProductFilter
                :product-search-url="route('admin.get_filtered_inventory_products')"
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
            v-if="state.parameters.filter_by === orderFilterStaticTypes.byProductCollection"
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
            v-if="state.parameters.filter_by === orderFilterStaticTypes.byMasterProduct"
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
                @click="exportOrdersReport"
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
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords, printReport } from '@commonServices/helper';
import { showErrorNotification } from '@commonServices/notifier';
import axios from 'axios';
import { reactive } from 'vue';
import { route } from 'ziggy';

defineProps({
    orderReportTypes: {
        type: Object,
        required: true,
    },
    orderFilterTypes: {
        type: Object,
        required: true,
    },
    orderFilterStaticTypes: {
        type: Object,
        required: true,
    },
    locations: {
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
        report_type: null,
        product_id: null,
        article_number: null,
        location_id: null,
        store_manager_id: null,
        filter_by: null,
        product_collection_id: null,
    },

    displayInventoryUpdateFilterModal: false,
    selectArticleNumbers: [],
    storeManagers: [],
});

const validationCheck = () => {
    if (state.parameters.date_range === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    if (state.parameters.location_id === null) {
        return true;
    }

    return false;
};

const exportOrdersReport = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date and report type before proceeding.');
        return;
    }

    printReport(route('admin.custom_reports.print_order_report', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date and report type before proceeding.');
        return;
    }
    return exportRecords(
        'export-order-report/',
        'order-report.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a date and report type before proceeding.');
        return;
    }
    return exportRecords(
        'export-order-report/',
        'order-report.csv',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};

const displayUpdateFilter = () => {
    state.displayInventoryUpdateFilterModal = true;
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
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

const clearFilterBy = () => {
    state.parameters.filter_by = null;
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    state.parameters.product_collection_id = null;
};

const updateLocationId = (locationId) => {
    state.parameters.location_id = locationId;
    state.parameters.store_manager_id = null;
    state.storeManagers = [];

    axios.post(route('admin.store_managers.get_stores_store_managers', { location_ids: [locationId] }))
        .then((response) => {
            state.storeManagers = response.data.store_managers;
        });
};
</script>
