<template>
    <PageTitle title="Accumulated Sell Through Report" />

    <div>
        <div class="p-5">
            <div class="grid grid-cols-12 gap-0 sm:gap-6 mb-4 border-b pb-5">
                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                    <FormSelectBox
                        v-model:selected-record="state.parameters.report_type"
                        :records="accumulatedSellThroughReportTypes"
                        :required="true"
                        input-label="Report Types"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                    />
                </div>

                <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3">
                    <JDatePicker
                        v-model:input-value="state.parameters.date"
                        input-label="Date"
                        :required="true"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:input-value="updateDate($event)"
                    />
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <JTabs
                        :records="locationTypes"
                        :selected-record="state.type_id"
                        return-selected-record="id"
                        input-label="Location Selection"
                        label-class="block font-medium text-base text-primary-p3 mb-2 mt-3"
                        :required="true"
                        @update:selected-record="updateTypeId"
                    />

                    <TabPanel
                        v-if="state.type_id === staticLocationTypes.store"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.locations"
                            :records="stores"
                            placeholder="Please Select Stores"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="updateLocation"
                        />
                    </TabPanel>

                    <TabPanel
                        v-if="state.type_id === staticLocationTypes.warehouse"
                        class="active"
                    >
                        <JMultiSelect
                            :selected-records="state.locations"
                            :records="warehouses"
                            placeholder="Please Select Warehouses"
                            label-class="block font-medium text-base text-primary-p3 mb-2"
                            @update:selected-records="updateLocation"
                        />
                    </TabPanel>
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <div class="w-full md:w-1/2 px-3 mt-2 sm:mt-2 md:mt-8">
                        <OutlinePrimaryButton
                            type="button"
                            text="Select all"
                            class="w-24 md:w-1/1 mt-3"
                            @click="selectAllLocations"
                        />

                        <OutlineDangerButton
                            v-if="state.locations.length > 0 || state.locations.length > 0"
                            type="button"
                            text="Clear All"
                            class="w-24 md:w-1/1 mt-3"
                            @click="clearAllLocations"
                        />
                    </div>
                </div>
            </div>

            <div
                class="grid grid-cols-12 gap-0 sm:gap-6 mb-4 pb-5 border-b"
            >
                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormSelectBox
                        v-model:selected-record="state.parameters.filter_by"
                        :records="accumulatedSaleThroughFilterTypes"
                        input-label="Filter By"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                    />
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormAjaxSelect
                        input-label="Products"
                        :selected-record="state.selectedProduct"
                        :search-records="searchProducts"
                        placeholder="Product Name/UPC to search..."
                        @update:selected-record="selectProduct"
                    />
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormSelectBox
                        v-model:selected-record="state.parameters.product_collection_id"
                        :records="productCollections"
                        placeholder="Please select Product Collection"
                        input-label="Product Collection"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                    />
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormAjaxSelect
                        input-label="Categories"
                        :selected-record="state.selectedCategory"
                        :search-records="searchCategory"
                        placeholder="Please type the name of the category to search."
                        @update:selected-record="selectCategory"
                    />
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormAjaxSelect
                        input-label="Brands"
                        :selected-record="state.selectedBrand"
                        :search-records="searchBrand"
                        placeholder="Please type the name of the brand to search."
                        @update:selected-record="selectBrand"
                    />
                </div>

                <AttributesFilters
                    v-if="pageProps.product_variant"
                    :custom-class="'col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3'"
                    :attributes="attributes"
                    @update-params="updateParams($event, params)"
                />

                <div
                    v-if="!pageProps.product_variant"
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormAjaxSelect
                        input-label="Sizes"
                        :selected-record="state.selectedSize"
                        :search-records="searchSize"
                        placeholder="Please type the name of the size to search."
                        @update:selected-record="selectSize"
                    />
                </div>

                <div
                    v-if="!pageProps.product_variant"
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormAjaxSelect
                        input-label="Colors"
                        :selected-record="state.selectedColors"
                        :search-records="searchColor"
                        :multi-select="true"
                        placeholder="Please type the name of the color to search."
                        @update:selected-record="selectColors"
                    />
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormAjaxSelect
                        :selected-record="state.selectedDepartments"
                        :search-records="searchDepartment"
                        :multi-select="true"
                        input-label="Department"
                        placeholder="Please type the name of the department to search."
                        @update:selected-record="selectDepartments"
                    />
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
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

                <div
                    v-if="!pageProps.product_variant"
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
                    <FormAjaxSelect
                        :selected-record="state.selectedStyles"
                        :search-records="searchStyle"
                        :multi-select="true"
                        input-label="Styles"
                        placeholder="Please type the name of the style to search."
                        @update:selected-record="selectStyles"
                    />
                </div>

                <div
                    class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-3 xl:col-span-3"
                >
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
                </div>
            </div>

            <div
                class="input-form col-span-12"
            >
                <div>
                    <InclusionsForStockReceived
                        :stores="stores"
                        :warehouses="warehouses"
                        :location-types="locationTypes"
                        :static-location-types="staticLocationTypes"
                        :sell-through-include-types="accumulatedSaleThroughIncludeTypes"
                        :selected-sell-through-include-types="state.parameters.accumulated_sale_through_include_types"
                        :static-accumulated-sell-through-include-types="staticAccumulatedSaleThroughIncludeTypes"
                        @update-accumulated-sell-through-include-types="updateAccumulatedSaleThroughIncludeTypes"
                        @includes-by-goods-receive-note-in-location-ids="includesByGoodsReceiveNoteInLocationIds"
                        @clear-data-for-goods-received-note-in="clearDataForGoodsReceivedNoteIn"
                        @includes-by-goods-receive-note-out-location-ids="includesByGoodsReceiveNoteOutLocationIds"
                        @clear-data-for-goods-received-note-out="clearDataForGoodsReceivedNoteOut"
                        @includes-by-stock-adjustment-in-location-ids="includesByStockAdjustmentInLocationIds"
                        @clear-data-for-stock-adjustment-in="clearDataForStockAdjustmentIn"
                        @includes-by-stock-adjustment-out-location-ids="includesByStockAdjustmentOutLocationIds"
                        @clear-data-for-stock-adjustment-out="clearDataForStockAdjustmentOut"
                        @includes-by-stock-transfer-in-location-ids="includesByStockTransferInLocationIds"
                        @clear-data-for-stock-transfer-in="clearDataForStockTransferIn"
                        @includes-by-stock-transfer-out-location-ids="includesByStockTransferOutLocationIds"
                        @clear-data-for-stock-transfer-out="clearDataForStockTransferOut"
                        @includes-by-delivery-order-in-location-ids="includesByDeliveryOrderInLocationIds"
                        @clear-data-for-delivery-order-in="clearDataForDeliveryOrderIn"
                        @includes-by-delivery-order-out-location-ids="includesByDeliveryOrderOutLocationIds"
                        @clear-data-for-delivery-order-out="clearDataForDeliveryOrderOut"
                    />
                </div>
            </div>
        </div>
        <div class="input-form col-span-12 sm:col-span-12 md:col-span-12 lg:col-span-12 xl:col-span-12">
            <div v-if="state.isDataInProgress">
                <LoaderSvg />
            </div>

            <div v-else>
                <OutlineDangerButton
                    type="button"
                    text="Clear"
                    class="btn-sm w-24 h-10 mt-3 mr-1"
                    @click="clearData"
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
    </div>
</template>

<script setup>
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { exportRecords, currentDate } from '@commonServices/helper';
import { showErrorNotification } from '@commonServices/notifier';
import { reactive, computed } from 'vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import axios from 'axios';
import { route } from 'ziggy';
import InclusionsForStockReceived from '@commonPages/InclusionsForStockReceived.vue';
import JTabs from '@commonComponents/JTabs.vue';
import { TabPanel } from '@commonVendor/tab';
import LoaderSvg from '@svg/LoaderSvg.vue';
import AttributesFilters from '@commonComponents/AttributesFilters.vue';
import { usePage } from "@inertiajs/vue3";

const pageProps = computed(() => usePage().props);

const props = defineProps({
    stores: {
        type: Object,
        required: true,
    },
    warehouses: {
        type: Object,
        required: true,
    },
    productCollections: {
        type: Object,
        required: true,
    },
    accumulatedSaleThroughFilterTypes: {
        type: Object,
        required: true,
    },
    staticAccumulatedSaleThroughFilterTypes: {
        type: Object,
        required: true,
    },
    accumulatedSaleThroughIncludeTypes: {
        type: Object,
        required: true,
    },
    staticAccumulatedSaleThroughIncludeTypes: {
        type: Object,
        required: true,
    },
    accumulatedSellThroughReportTypes: {
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
    },
    attributes: {
        type: Object,
        default: () => { },
    },
});

const emits = defineEmits([
    'update:clear-button',
]);

const state = reactive({
    isDataInProgress: false,
    parameters: {
        filter_by: props.staticAccumulatedSaleThroughFilterTypes.all,
        report_type: null,
        date: currentDate(),
        product_id: null,
        product_collection_id: null,
        category_id: null,
        brand_id: null,
        size_id: null,
        color_ids: null,
        department_ids: null,
        article_numbers: [],
        tag_ids: null,
        style_ids: null,
        location_ids: [],
        accumulated_sale_through_include_types: [
            props.staticAccumulatedSaleThroughIncludeTypes.goodsReceiveNoteIn,
            props.staticAccumulatedSaleThroughIncludeTypes.goodsReceiveNoteOut,
            props.staticAccumulatedSaleThroughIncludeTypes.stockAdjustmentIn,
            props.staticAccumulatedSaleThroughIncludeTypes.stockAdjustmentOut
        ],
        includes_by_goods_receive_note_in_location_ids: [],
        includes_by_goods_receive_note_out_location_ids: [],
        includes_by_stock_adjustment_in_location_ids: [],
        includes_by_stock_adjustment_out_location_ids: [],
        includes_by_stock_transfer_in_location_ids: [],
        includes_by_stock_transfer_out_location_ids: [],
        includes_by_delivery_order_in_location_ids: [],
        includes_by_delivery_order_out_location_ids: [],
        attributes: null,
    },
    selectedProduct: null,
    selectedCategory: null,
    selectedBrand: null,
    selectedSize: null,
    selectedColors: null,
    selectedDepartments: null,
    selectedArticleNumber: [],
    selectedTags: null,
    locations: [],
    selectedStyles: null,
    accumulatedSaleThroughIncludeTypes: [
        props.staticAccumulatedSaleThroughIncludeTypes.goodsReceiveNoteIn,
        props.staticAccumulatedSaleThroughIncludeTypes.goodsReceiveNoteOut,
        props.staticAccumulatedSaleThroughIncludeTypes.stockAdjustmentIn,
        props.staticAccumulatedSaleThroughIncludeTypes.stockAdjustmentOut
    ],
    type_id: props.staticLocationTypes.store,

    includesByGoodsReceiveNoteInLocationIds: [],
    includesByGoodsReceiveNoteOutLocationIds: [],
    includesByStockAdjustmentInLocationIds: [],
    includesByStockAdjustmentOutLocationIds: [],
    includesByStockTransferInLocationIds: [],
    includesByStockTransferOutLocationIds: [],
    includesByDeliveryOrderInLocationIds: [],
    includesByDeliveryOrderOutLocationIds: []
});

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, filter by, store and a date before proceeding.');
        return;
    }

    state.isDataInProgress = true;

    return exportRecords(
        'accumulated-sell-through-export/',
        'accumulated-sell-through-export.csv',
        state.parameters
    ).then(() => {
        state.isDataInProgress = false;
    });
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report type, filter by, store and a date before proceeding.');
        return;
    }

    state.isDataInProgress = true;

    return exportRecords(
        'accumulated-sell-through-export/',
        'accumulated-sell-through-export.xlsx',
        state.parameters
    ).then(() => {
        state.isDataInProgress = false;
    });
};

const validationCheck = () => {
    if (state.type_id === props.staticLocationTypes.store && state.parameters.location_ids.length === 0) {
        return true;
    }

    if (state.type_id === props.staticLocationTypes.warehouse && state.parameters.location_ids.length === 0) {
        return true;
    }

    if (state.parameters.date === null) {
        return true;
    }

    if (state.parameters.report_type === null) {
        return true;
    }

    if (state.parameters.filter_by === null) {
        return true;
    }

    return false;
};

const clearData = () => {
    emits('update:clear-button');
};

const searchProducts = (searchText, componentState) => {
    axios.get(route('admin.get_filtered_inventory_products'), {
        params: {
            search_text: searchText,
            number_of_records: 5,
        }
    }).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};

const selectProduct = (selectedProduct) => {
    state.selectedProduct = selectedProduct;
    state.parameters.product_id = selectedProduct !== null ? selectedProduct.id : null;
};

const searchCategory = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('admin.categories.get_filtered_categories'), filterData).then((response) => {
        componentState.records = response.data.categories;
        componentState.isLoading = false;
    });
};

const selectCategory = (selectedCategory) => {
    state.selectedCategory = selectedCategory;
    state.parameters.category_id = selectedCategory !== null ? selectedCategory.id : null;
};

const searchBrand = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(route('admin.brands.get_filtered_brands'), filterData).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const selectBrand = (selectedBrand) => {
    state.selectedBrand = selectedBrand;
    state.parameters.brand_id = null;
    if (selectedBrand !== null) {
        state.parameters.brand_id = selectedBrand.id;
    }
};

const updateParams = (params) => {
    state.parameters.attributes = params;
};

const searchSize = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.sizes.get_filtered_sizes'), filterData).then((response) => {
        componentState.records = response.data.sizes;
        componentState.isLoading = false;
    });
};

const selectSize = (selectedSizes) => {
    state.selectedSize = selectedSizes;
    state.parameters.size_id = null;
    if (selectedSizes !== null) {
        state.parameters.size_id = selectedSizes.id;
    }
};

const searchColor = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.colors.get_filtered_colors'), filterData).then((response) => {
        componentState.records = response.data.colors;
        componentState.isLoading = false;
    });
};

const selectColors = (selectedColors) => {
    state.selectedColors = selectedColors;

    if (selectedColors !== null) {
        state.parameters.color_ids = selectedColors.map(function (color) {
            return color.id;
        });
    } else {
        state.parameters.color_ids = null;
    }
};

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.departments.get_filtered_departments'), filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const selectDepartments = (selectedDepartments) => {
    state.selectedDepartments = selectedDepartments;
    if (selectedDepartments !== null) {
        state.parameters.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    } else {
        state.parameters.department_ids = null;
    }
};

const minSearchLength = 3;

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(route('admin.products.get_filtered_article_number'), filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectedArticleNumber = selectedNumbers;
    if (selectedNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    } else {
        state.parameters.article_numbers = [];
    }
};

const searchStyle = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(route('admin.styles.get_filtered_styles'), filterData).then((response) => {
            componentState.records = response.data.styles;
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
    } else {
        state.parameters.style_ids = null;
    }
};

const searchTag = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.tags.get_filtered_tags'), filterData).then((response) => {
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
    } else {
        state.parameters.style_ids = null;
    }
};

const updateAccumulatedSaleThroughIncludeTypes = (accumulatedSaleThroughIncludeTypes) => {
    state.parameters.accumulated_sale_through_include_types = accumulatedSaleThroughIncludeTypes;
};

const updateLocation = (locations) => {
    state.locations = locations;
    state.parameters.location_ids = locations.map(function (location) {
        return location.id;
    });
};

const updateTypeId = (typeId) => {
    state.type_id = typeId;

    state.parameters.location_ids = [];
    state.locations = [];
};

const clearDataForGoodsReceivedNoteIn = () => {
    state.parameters.includes_by_goods_receive_note_in_location_ids = [];
};

const clearDataForGoodsReceivedNoteOut = () => {
    state.parameters.includes_by_goods_receive_note_out_location_ids = [];
};

const clearDataForStockAdjustmentIn = () => {
    state.parameters.includes_by_stock_adjustment_in_location_ids = [];
};

const clearDataForStockAdjustmentOut = () => {
    state.parameters.includes_by_stock_adjustment_out_location_ids = [];
};

const clearDataForStockTransferIn = () => {
    state.parameters.includes_by_stock_transfer_in_location_ids = [];
};

const clearDataForStockTransferOut = () => {
    state.parameters.includes_by_stock_transfer_out_location_ids = [];
};

const clearDataForDeliveryOrderIn = () => {
    state.parameters.includes_by_delivery_order_in_location_ids = [];
};

const clearDataForDeliveryOrderOut = () => {
    state.parameters.includes_by_delivery_order_out_location_ids = [];
};

const includesByGoodsReceiveNoteInLocationIds = (locationIds) => {
    state.parameters.includes_by_goods_receive_note_in_location_ids = locationIds;
};

const includesByGoodsReceiveNoteOutLocationIds = (locationIds) => {
    state.parameters.includes_by_goods_receive_note_out_location_ids = locationIds;
};

const includesByStockAdjustmentInLocationIds = (locationIds) => {
    state.parameters.includes_by_stock_adjustment_in_location_ids = locationIds;
};

const includesByDeliveryOrderInLocationIds = (locationIds) => {
    state.parameters.includes_by_delivery_order_in_location_ids = locationIds;
};

const includesByStockTransferInLocationIds = (locationIds) => {
    state.parameters.includes_by_stock_transfer_in_location_ids = locationIds;
};

const includesByStockAdjustmentOutLocationIds = (locationIds) => {
    state.parameters.includes_by_stock_adjustment_out_location_ids = locationIds;
};

const includesByDeliveryOrderOutLocationIds = (locationIds) => {
    state.parameters.includes_by_delivery_order_out_location_ids = locationIds;
};

const includesByStockTransferOutLocationIds = (locationIds) => {
    state.parameters.includes_by_stock_transfer_out_location_ids = locationIds;
};

const selectAllLocations = () => {
    if (state.type_id === props.staticLocationTypes.warehouse) {
        state.locations = props.warehouses;
        state.parameters.location_ids = props.warehouses.map((warehouse) => {
            return warehouse.id;
        });
        return;
    }

    state.locations = props.stores;
    state.parameters.location_ids = props.stores.map((store) => {
        return store.id;
    });
};

const clearAllLocations = () => {
    state.locations = [];
    state.parameters.location_ids = [];
};
</script>
