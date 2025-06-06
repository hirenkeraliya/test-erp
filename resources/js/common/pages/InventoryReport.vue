<template>
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            {{ isStockPosition ? 'Stock Position' : 'Inventory' }}
        </h2>

        <div
            v-if="saleChannels.length > 1 && !state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Dropdown
                v-slot="{ dismiss }"
                class="flex items-center"
            >
                <DropdownToggle
                    tag="a"
                    href="javascript:;"
                >
                    <Tippy
                        content="Sync Data"
                        class="btn btn-outline-primary"
                    >
                        <RefreshCw class="text-primary w-5" />
                    </Tippy>
                </DropdownToggle>

                <DropdownMenu
                    class="w-60"
                >
                    <DropdownContent>
                        <DropdownItem
                            v-for="(saleChannel, index) in saleChannels"
                            :key="index"
                            class="flex items-center mr-3"
                            @click="syncData(saleChannel.id, dismiss)"
                        >
                            <span v-if="saleChannel.updated_at">
                                {{ saleChannel.name +' (' + saleChannel.updated_at+ ')' }}
                            </span>
                            <span v-else>
                                {{ saleChannel.name }}
                            </span>
                        </DropdownItem>
                    </DropdownContent>
                </DropdownMenu>
            </Dropdown>
        </div>

        <div
            v-if="saleChannels.length > 1 && state.disableRefreshButton"
            class="w-full sm:w-auto flex mt-4 sm:mt-0 mr-2"
        >
            <Tippy
                content="Sync In Progress"
                class="btn btn-outline-secondary"
            >
                <RefreshCw class="text-gray-400 w-5" />
            </Tippy>
        </div>
    </div>


    <div
        v-if="state.displayInventoryReportsFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div class="mt-3">
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.typeId"
                    return-selected-record="id"
                    input-label="Location Selection"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateLocationType"
                />
            </div>

            <div>
                <TabPanel v-if="state.typeId === staticLocationTypes.store">
                    <JMultiSelect
                        :selected-records="state.selectedLocations"
                        :records="stores"
                        validation-field-name="stores"
                        placeholder="Please select stores"
                        input-label="Stores"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-records="selectLocations"
                    />
                </TabPanel>

                <TabPanel v-if="state.typeId === staticLocationTypes.warehouse">
                    <JMultiSelect
                        :selected-records="state.selectedLocations"
                        :records="warehouses"
                        placeholder="Please select warehouse"
                        input-label="Warehouses"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        @update:selected-records="selectLocations"
                    />
                </TabPanel>
            </div>
            <div>
                <FormAjaxSelect
                    input-label="Products"
                    :selected-record="state.selectedProduct"
                    :search-records="searchProducts"
                    placeholder="Product Name/UPC to search..."
                    @update:selected-record="selectProduct"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.product_collection_id"
                    :records="productCollections"
                    placeholder="Please select Product Collection"
                    input-label="Product Collection"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateProductCollectionId"
                />
            </div>

            <div>
                <FormAjaxSelect
                    input-label="Categories"
                    :selected-record="state.selectedCategory"
                    :search-records="searchCategory"
                    placeholder="Please type the name of the category to search."
                    @update:selected-record="selectCategory"
                />
            </div>

            <div>
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
                :attributes="attributes"
                @update-params="updateParams($event, params)"
            />

            <div
                v-if="!pageProps.product_variant"
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
            >
                <FormAjaxSelect
                    input-label="Colors"
                    :selected-record="state.selectedColor"
                    :search-records="searchColor"
                    placeholder="Please type the name of the color to search."
                    @update:selected-record="selectColor"
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
                <FormAjaxSelect
                    :selected-record="state.selectedTags"
                    :search-records="searchTag"
                    :multi-select="true"
                    input-label="Tags"
                    placeholder="Please type the name of the tag to search."
                    @update:selected-record="selectTags"
                />
            </div>

            <div
                v-if="!pageProps.product_variant"
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
                v-if="state.typeId === staticLocationTypes.store"
            >
                <JMultiSelect
                    :selected-records="state.selectedRegions"
                    :records="regions"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    input-label="Regions"
                    validation-field-name="regions"
                    placeholder="Please select Regions"
                    @update:selected-records="selectRegions"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.stock_type"
                    :records="stockTypes"
                    placeholder="Please select stock type"
                    input-label="Stock Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStockType"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.selling_type"
                    :records="sellingTypes"
                    placeholder="Please select selling type"
                    input-label="Selling Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateSellingType"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="productStatuses"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    @update:selected-record="updateSelectedStatus($event)"
                />
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
    </div>

    <JTable
        :columns="computedColumns"
        :fetch-url="fetchUrl"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        :local-storage-key="localStorageKey"
        search-title="Search by item name, categories, brand, or location"
    >
        <template #categories="record">
            <span
                v-if="record.item.categories.length <= 0"
            >
                N/A
            </span>
            <span
                v-for="(category, index) in record.item.categories"
                v-else
                :key="index"
                class="text-primary"
            >
                {{ category.name }}

                <strong
                    v-if="index != record.item.categories.length - 1"
                    class="text-dark"
                >
                    >
                </strong>
            </span>
        </template>

        <template
            v-if="pageProps.product_variant"
            #attributes="data"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(attribute, index) in data.item.attributes"
                    :key="index"
                    class="flex"
                >
                    {{ attribute.name }} : {{ attribute.value }}
                </p>
            </span>
        </template>

        <template #unit_price="record">
            {{ displayAmountWithCurrencySymbol(record.item.unit_price) }}
        </template>

        <template #total_value="record">
            {{ displayAmountWithCurrencySymbol(record.item.total_value) }}
        </template>

        <template #stock="record">
            {{ truncateDecimal(record.item.stock) }}
        </template>

        <template #current_stock="record">
            {{ truncateDecimal(record.item.current_stock) }}
        </template>

        <template #reserved_stock="record">
            <a
                v-if="record.item.reserved_stock > 0"
                class="text-blue-700 underline font-bold"
                :href="record.item.reserved_inventory_url"
                target="_blank"
            >
                {{ truncateDecimal(record.item.reserved_stock) }}
            </a>

            <p v-else>
                {{ truncateDecimal(record.item.reserved_stock) }}
            </p>
        </template>

        <template #transit_stock="record">
            <a
                v-if="record.item.transit_stock > 0"
                class="text-blue-700 underline font-bold"
                :href="record.item.transit_inventory_url"
                target="_blank"
            >
                {{ truncateDecimal(record.item.transit_stock) }}
            </a>

            <p v-else>
                {{ truncateDecimal(record.item.transit_stock) }}
            </p>
        </template>

        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    :label="'Current: ' + truncateDecimal(record.data.total_current_stock)"
                />
                <JBadge
                    :label="'Available: ' + truncateDecimal(record.data.total_available_stock)"
                />
                <JBadge
                    :label="'Reserve: ' + truncateDecimal(record.data.total_reserved_stock)"
                />
                <JBadge
                    :label="'Transit: ' + truncateDecimal(record.data.total_transit_stock)"
                />
            </div>

            <p
                v-if="state.isClear"
                class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none"
            >
                <OutlinePrimaryButton
                    text="Clear"
                    class="text-sm shadow-md"
                    @click="refreshPage"
                />
            </p>

            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayInventoryReportsFilter = !state.displayInventoryReportsFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { computed, onMounted, reactive } from 'vue';
import { displayAmountWithCurrencySymbol, exportRecords, truncateDecimal } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JBadge from '@commonComponents/JBadge.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import { TabPanel } from '@commonVendor/tab';
import JTabs from '@commonComponents/JTabs.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import { usePage, router } from '@inertiajs/vue3';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { showSuccessNotification } from '@commonServices/notifier';
import { route } from 'ziggy';
import {
    Dropdown,
    DropdownContent,
    DropdownItem,
    DropdownMenu,
    DropdownToggle,
} from '@commonVendor/dropdown';
import { RefreshCw } from 'lucide-vue-next';
import AttributesFilters from '@commonComponents/AttributesFilters.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    stores: {
        type: Array,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
    regions: {
        type: Array,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    fetchUrl: {
        type: String,
        required: true,
    },
    localStorageKey: {
        type: String,
        required: true,
    },
    fetchSizesUrl: {
        type: String,
        required: true,
    },
    fetchColorsUrl: {
        type: String,
        required: true,
    },
    getFilteredInventoryProducts: {
        type: String,
        required: true,
    },
    fetchCategories: {
        type: String,
        required: true,
    },
    fetchBrands: {
        type: String,
        required: true,
    },
    fetchDepartments: {
        type: String,
        required: true,
    },
    fetchTags: {
        type: String,
        required: true,
    },
    fetchStyles: {
        type: String,
        required: true,
    },
    fetchArticleNumbers: {
        type: String,
        required: true,
    },

    preSelectedLocations: {
        type: Array,
        default: () => [],
    },

    preSelectedLocationIds: {
        type: Array,
        default: () => [],
    },

    preSelectedLocationType: {
        type: Number,
        default: null,
    },

    redirectUrl: {
        type: String,
        required: true,
    },

    stockTypes: {
        type: Array,
        required: true,
    },

    sellingTypes: {
        type: Array,
        required: true,
    },

    exportPermission: {
        type: String,
        required: true,
    },

    productStatuses: {
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
    },
    checkInventoryExportLimitUrl: {
        type: String,
        required: true,
    },
    isStockPosition: {
        type: Boolean,
        default: false,
    },
    saleChannels: {
        type: Array,
        default: () => [],
    },
    hasPendingSyncTransaction: {
        type: Boolean,
        default: false
    },
    attributes: {
        type: Object,
        default: () => { },
    },
});

const state = reactive({
    columns: [
        {
            key: 'item_name',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'article_number',
            isDisplay: true,
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'attributes',
                    isDisplay: true,
                },
            ]
            : [
                {
                    key: 'color',
                    isDisplay: true,
                    sortable: true,
                },
                {
                    key: 'size',
                    isDisplay: true,
                    sortable: true,
                },
            ]),
        {
            key: 'categories',
            isDisplay: true,
        }, {
            key: 'brand',
            isDisplay: true,
        }, {
            key: 'upc',
            isDisplay: true,
        }, {
            key: 'item_code',
            isDisplay: true,
        }, {
            key: 'location',
            isDisplay: true,
        }, {
            key: 'unit_price',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'current_stock',
            label: 'Current Stock',
            bodyClass: 'text-right',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'reserved_stock',
            bodyClass: 'text-right',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'transit_stock',
            bodyClass: 'text-right',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'available_stock',
            bodyClass: 'text-right',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'total_value',
            label: 'Value',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'created_at',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'last_updated_at',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }
    ],
    stockPositionColumns: [
        {
            key: 'item_name',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'article_number',
            isDisplay: true,
        },
        ...(pageProps.value.product_variant
            ? [
                {
                    key: 'attributes',
                    isDisplay: true,
                },
            ]
            : [
                {
                    key: 'color',
                    isDisplay: true,
                    sortable: true,
                },
                {
                    key: 'size',
                    isDisplay: true,
                    sortable: true,
                },
            ]),
        {
            key: 'categories',
            isDisplay: true,
        }, {
            key: 'brand',
            isDisplay: true,
        }, {
            key: 'upc',
            isDisplay: true,
        }, {
            key: 'item_code',
            isDisplay: true,
        }, {
            key: 'location',
            isDisplay: true,
        }, {
            key: 'unit_price',
            bodyClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'current_stock',
            label: 'Current Stock',
            bodyClass: 'text-right',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'reserved_stock',
            bodyClass: 'text-right',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'transit_stock',
            bodyClass: 'text-right',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'available_stock',
            bodyClass: 'text-right',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'created_at',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'last_updated_at',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }
    ],
    refreshTableData: Math.random(),
    selectedProduct: null,
    selectedCategory: null,
    selectedBrand: null,
    selectedSize: null,
    selectedColor: null,
    selectedLocations: props.dashboardFilterData.selectedLocations ? props.dashboardFilterData.selectedLocations : props.preSelectedLocations,
    displayInventoryReportsFilter: false,
    selectedDepartments: null,
    selectedArticleNumber: null,
    selectedTags: null,
    selectedStyles: null,
    selectedRegions: null,
    isClear: false,
    typeId: props.dashboardFilterData.location_type ? props.dashboardFilterData.location_type : props.preSelectedLocationType,
    disableRefreshButton: props.hasPendingSyncTransaction,
    parameters: {
        product_id: props.dashboardFilterData.product_id ?? null,
        category_id: null,
        brand_id: null,
        department_ids: null,
        article_numbers: null,
        size_id: null,
        color_id: null,
        location_ids: props.dashboardFilterData.location_id ?? props.preSelectedLocationIds,
        tag_ids: null,
        stock_type: props.dashboardFilterData.stock_type,
        selling_type: props.dashboardFilterData.selling_type ?? null,
        style_ids: null,
        region_ids: null,
        status: props.dashboardFilterData.status,
        product_collection_id: null,
        attributes: null,
    },
    locationTypes: props.staticLocationTypes,
});
const refreshTable = () => {
    state.refreshTableData = Math.random();
};
const clearAll = () => {
    state.parameters.product_id = null;
    state.parameters.category_id = null;
    state.parameters.brand_id = null;
    state.parameters.size_id = null;
    state.parameters.color_id = null;
    state.parameters.style_ids = null;
    state.selectedStyles = null;
    state.parameters.location_ids = null;
    state.parameters.department_ids = null;
    state.selectedDepartments = null;
    state.selectedArticleNumber = null;
    state.parameters.article_numbers = null;
    state.selectedProduct = null;
    state.selectedCategory = null;
    state.selectedBrand = null;
    state.selectedColor = null;
    state.selectedSize = null;
    state.selectedLocations = null;
    state.selectedTags = null;
    state.parameters.tag_ids = null;
    state.parameters.region_ids = null;
    state.parameters.stock_type = null;
    state.parameters.selling_type = null;
    state.selectedRegions = null;
    state.status = null;
    state.parameters.product_collection_id = null;
    state.parameters.attributes = null,
    refreshTable();
};

const selectProduct = (selectedProduct) => {
    state.selectedProduct = selectedProduct;
    state.parameters.product_id = null;
    if (selectedProduct !== null) {
        state.parameters.product_id = selectedProduct.id;
    }
    refreshTable();
};
const selectCategory = (selectedCategory) => {
    state.selectedCategory = selectedCategory;
    state.parameters.category_id = null;
    if (selectedCategory !== null) {
        state.parameters.category_id = selectedCategory.id;
    }
    refreshTable();
};
const selectBrand = (selectedBrand) => {
    state.selectedBrand = selectedBrand;
    state.parameters.brand_id = null;
    if (selectedBrand !== null) {
        state.parameters.brand_id = selectedBrand.id;
    }
    refreshTable();
};

const selectSize = (selectedSizes) => {
    state.selectedSize = selectedSizes;
    state.parameters.size_id = null;
    if (selectedSizes !== null) {
        state.parameters.size_id = selectedSizes.id;
    }
    refreshTable();
};

const selectColor = (selectedColors) => {
    state.selectedColor = selectedColors;
    state.parameters.color_id = null;
    if (selectedColors !== null) {
        state.parameters.color_id = selectedColors.id;
    }
    refreshTable();
};

const selectRegions = (selectedRegions) => {
    state.selectedRegions = selectedRegions;
    state.parameters.region_ids = selectedRegions.map(function (region) {
        return region.id;
    });
    refreshTable();
};

const searchSize = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(props.fetchSizesUrl, filterData).then((response) => {
        componentState.records = response.data.sizes;
        componentState.isLoading = false;
    });
};

const searchColor = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(props.fetchColorsUrl, filterData).then((response) => {
        componentState.records = response.data.colors;
        componentState.isLoading = false;
    });
};
const searchProducts = (searchText, componentState) => {
    axios.get(props.getFilteredInventoryProducts, {
        params: {
            search_text: searchText,
            number_of_records: 5,
        }
    }).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};
const searchCategory = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(props.fetchCategories, filterData).then((response) => {
        componentState.records = response.data.categories;
        componentState.isLoading = false;
    });
};

const searchBrand = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    axios.post(props.fetchBrands, filterData).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const selectLocations = (selectedLocations) => {
    state.selectedLocations = selectedLocations;
    state.parameters.location_ids = selectedLocations.map(function (location) {
        return location.id;
    });
    refreshTable();
};

const updateLocationType = (typeId) => {
    state.typeId = typeId;
    state.parameters.location_ids = null;
    state.selectedLocations = null;
    if (typeId === props.staticLocationTypes.warehouse) {
        state.selectedRegions = null;
        state.parameters.region_ids = null;
    }
};

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(props.fetchDepartments, filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const minSearchLength = 3;

const searchArticleNumber = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(props.fetchArticleNumbers, filterData).then((response) => {
            componentState.records = response.data.articleNumbers;
            componentState.isLoading = false;
        });
    }
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectedArticleNumber = selectedNumbers;
    if (selectArticleNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    }
    refreshTable();
};

const selectDepartments = (selectedDepartments) => {
    state.selectedDepartments = selectedDepartments;
    if (selectedDepartments !== null) {
        state.parameters.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    }
    refreshTable();
};

const exportCsvRecords = (params, columns) => {
    params['export_columns'] = columns;
    return axios.get(route(props.checkInventoryExportLimitUrl, params))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                if (props.isStockPosition) {
                    return exportRecords(
                        'export-stock-positions/',
                        'stock-positions.csv',
                        params,
                        props.exportPermission,
                        columns
                    );
                }
                return exportRecords(
                    'export-inventories/',
                    'inventories.csv',
                    params,
                    props.exportPermission,
                    columns
                );
            }

            showSuccessNotification(response.data.message);
        });
};

const computedColumns = computed(() => {
    return props.isStockPosition ? state.stockPositionColumns : state.columns;
});

const exportExcelRecords = (params, columns) => {
    params['export_columns'] = columns;
    return axios.get(route(props.checkInventoryExportLimitUrl, params))
        .then((response) => {
            if (!response.data.exceeds_limit) {
                if (props.isStockPosition) {
                    return exportRecords(
                        'export-stock-positions/',
                        'stock-positions.xlsx',
                        params,
                        props.exportPermission,
                        columns
                    );
                }
                return exportRecords(
                    'export-inventories/',
                    'inventories.xlsx',
                    params,
                    props.exportPermission,
                    columns
                );
            }

            showSuccessNotification(response.data.message);
        });
};

const searchTag = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(props.fetchTags, filterData).then((response) => {
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
    }
    refreshTable();
};

const searchStyle = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };
    if (searchText.length >= minSearchLength) {
        axios.post(props.fetchStyles, filterData).then((response) => {
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
    }
    refreshTable();
};

const updateStockType = (stockType) => {
    state.parameters.stock_type = null;
    if (stockType !== null) {
        state.parameters.stock_type = parseInt(stockType);
    }
    refreshTable();
};

const updateSellingType = (sellingType) => {
    state.parameters.selling_type = null;
    if (sellingType !== null) {
        state.parameters.selling_type = parseInt(sellingType);
    }
    refreshTable();
};

const updateSelectedStatus = (statuses) => {
    state.parameters.status = statuses;
    refreshTable();
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
    refreshTable();
};

const updateParams = (params) => {
    state.parameters.attributes = params;
    refreshTable();
};

const refreshPage = () => {
    router.get(props.redirectUrl);
};

onMounted(() => {
    if (props.dashboardFilterData.stock_type) {
        state.isClear = true;
        state.displayInventoryReportsFilter = true;
        refreshTable();
    }
});

const syncData = (id, dismiss) => {
    axios.get(route('admin.inventory_reports.sync_data', id)).then(() => {
        showSuccessNotification('Successfully Synchronized');
        state.disableRefreshButton = true;
    });

    dismiss();
};
</script>
