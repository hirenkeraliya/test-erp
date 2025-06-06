<template>
    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <div class="mt-3">
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.parameters.type_id"
                    return-selected-record="id"
                    input-label="Location Selection"
                    :required="true"
                    @update:selected-record="updateLocationType"
                />
            </div>
        </div>
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <TabPanel
                v-if="state.parameters.type_id === staticLocationTypes.store"
                class="active"
            >
                <JMultiSelect
                    :selected-records="state.stores"
                    :records="stores"
                    input-label="Stores"
                    placeholder="Please select store"
                    @update:selected-records="updateStores"
                />
            </TabPanel>

            <TabPanel
                v-if="state.parameters.type_id === staticLocationTypes.warehouse"
                class="active"
            >
                <JMultiSelect
                    :selected-records="state.warehouses"
                    :records="warehouses"
                    input-label="Warehouses"
                    placeholder="Please select warehouse"
                    @update:selected-records="updateWarehouses"
                />
            </TabPanel>
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="goodsReceivedNoteReportTypes"
                input-label="Report By"
                :required="true"
                placeholder="Report By"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <FormSelectBox
                :selected-record="state.parameters.filter_by"
                :records="goodsReceivedNoteFilters"
                input-label="Filter By"
                placeholder="Filter By"
                @update:selected-record="updateTheFilterBy"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === goodsReceivedNoteFilterStaticDetails.byProduct"
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
            v-if="state.parameters.filter_by === goodsReceivedNoteFilterStaticDetails.byArticleNumber"
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
            v-if="state.parameters.filter_by === goodsReceivedNoteFilterStaticDetails.byBrand"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
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
            v-if="state.parameters.filter_by === goodsReceivedNoteFilterStaticDetails.byDepartment"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
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
            v-if="state.parameters.filter_by === goodsReceivedNoteFilterStaticDetails.byVendor"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.vendorIds"
                :records="state.vendors"
                input-label="Vendors"
                :placeholder="'Please select Vendor(s)'"
                @update:selected-records="updateVendorIds"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === goodsReceivedNoteFilterStaticDetails.byProductCollection"
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
                @click="exportGoodsReceivedNotes"
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
import { TabPanel } from '@commonVendor/tab';
import { showErrorNotification } from '@commonServices/notifier';
import JTabs from '@commonComponents/JTabs.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { route } from 'ziggy';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
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
    goodsReceivedNoteFilters: {
        type: Object,
        required: true,
    },
    goodsReceivedNoteReportTypes: {
        type: Object,
        required: true,
    },
    goodsReceivedNoteFilterStaticDetails: {
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
    parameters: {
        type_id: props.staticLocationTypes.store,
        location_ids: null,
        date_range: null,
        filter_by: null,
        report_type: null,
        brand_ids: [],
        department_ids: [],
        vendor_ids: [],
        product_id: null,
        article_number: null,
        product_collection_id: null,
    },
    brands: [],
    brandIds: [],
    departments: [],
    departmentIds: null,
    vendors: [],
    stores: [],
    warehouses: [],
    vendorIds: null,
    displayInventoryUpdateFilterModal: false,
    selectArticleNumbers: [],
});

const updateLocationType = (typeId) => {
    state.parameters.type_id = typeId;
    state.parameters.location_ids = null;
    state.stores = null;
    state.warehouses = null;
};

const updateStores = (stores) => {
    state.stores = stores;
    const storeIds = stores.map((store) => {
        return store.id;
    });

    state.parameters.location_ids = storeIds;
};

const updateWarehouses = (warehouses) => {
    state.warehouses = warehouses;
    const warehouseIds = warehouses.map((warehouse) => {
        return warehouse.id;
    });

    state.parameters.location_ids = warehouseIds;
};

const clearData = () => {
    emits('update:clear-button');
};

const validationCheck = () => {
    if (state.parameters.location_ids === null || state.parameters.location_ids.length <= 0) {
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
    state.brands = [];
    state.departments = [];
    state.vendors = [];
    state.parameters.product_id = null;
    state.parameters.product_collection_id = null;
    state.parameters.article_number = null;
    state.selectArticleNumbers = [];

    if (filterBy === props.goodsReceivedNoteFilterStaticDetails.byBrand) {
        axios.post(route('admin.brands.get_brands'))
            .then((response) => {
                state.brands = response.data.brands;
            });
    }

    if (filterBy === props.goodsReceivedNoteFilterStaticDetails.byDepartment) {
        axios.get(route('admin.departments.get_departments_list'))
            .then((response) => {
                state.departments = response.data.departments;
            });
    }
    if (filterBy === props.goodsReceivedNoteFilterStaticDetails.byVendor) {
        axios.get(route('admin.vendors.get_vendors_list'))
            .then((response) => {
                state.vendors = response.data.vendors;
            });
    }
};

const updateBrandId = (brandIds) => {
    state.brandIds = brandIds;

    state.parameters.brand_ids = state.brandIds.map((brand) => {
        return brand.id;
    });
};

const updateDepartmentIds = (departmentIds) => {
    state.departmentIds = departmentIds;
    state.parameters.department_ids = state.departmentIds.map((department) => {
        return department.id;
    });
};

const updateVendorIds = (vendorIds) => {
    state.vendorIds = vendorIds;
    state.parameters.vendor_ids = state.vendorIds.map((vendor) => {
        return vendor.id;
    });
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
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

const exportGoodsReceivedNotes = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, store and a date before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byBrand && state.parameters.brand_ids.length <= 0) {
        showErrorNotification('Please select brand.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byArticleNumber && state.parameters.article_number === null) {
        showErrorNotification('Please select article number.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byDepartment && state.parameters.department_ids.length <= 0) {
        showErrorNotification('Please select departments.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byProduct && state.parameters.product_id === null) {
        showErrorNotification('Please select product.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byProductCollection && state.parameters.product_collection_id === null) {
        showErrorNotification('Please select product collection.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byVendor && state.parameters.vendor_ids.length <= 0) {
        showErrorNotification('Please select vendors.');
        return;
    }

    printReport(route('admin.custom_reports.print_goods_received_note', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, store and a date before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byBrand && state.parameters.brand_ids.length <= 0) {
        showErrorNotification('Please select brand.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byArticleNumber && state.parameters.article_number === null) {
        showErrorNotification('Please select article number.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byDepartment && state.parameters.department_ids.length <= 0) {
        showErrorNotification('Please select departments.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byProduct && state.parameters.product_id === null) {
        showErrorNotification('Please select product.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byProductCollection && state.parameters.product_collection_id === null) {
        showErrorNotification('Please select product collection.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byVendor && state.parameters.vendor_ids.length <= 0) {
        showErrorNotification('Please select vendors.');
        return;
    }

    return exportRecords(
        'export-goods-received-note-report/',
        'goods-received-note.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a report by, store and a date before proceeding.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byBrand && state.parameters.brand_ids.length <= 0) {
        showErrorNotification('Please select brand.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byArticleNumber && state.parameters.article_number === null) {
        showErrorNotification('Please select article number.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byDepartment && state.parameters.department_ids.length <= 0) {
        showErrorNotification('Please select departments.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byProduct && state.parameters.product_id === null) {
        showErrorNotification('Please select product.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byVendor && state.parameters.vendor_ids.length <= 0) {
        showErrorNotification('Please select vendors.');
        return;
    }

    if (state.parameters.filter_by === props.goodsReceivedNoteFilterStaticDetails.byProductCollection && state.parameters.product_collection_id === null) {
        showErrorNotification('Please select product collection.');
        return;
    }

    return exportRecords(
        'export-goods-received-note-report/',
        'goods-received-note.csv',
        state.parameters
    );
};
</script>
