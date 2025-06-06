<template>
    <PageTitle title="External Inventory Report" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            External Inventory
        </h2>
    </div>
    <div class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.external_company_main_id"
                    :records="externalCompanies"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="External Companies"
                    @update:selected-record="updateSelectedExternalCompany($event)"
                />
            </div>
            <slot />
            <div v-if="state.isClear">
                <FormAjaxSelect
                    input-label="Products"
                    :selected-record="state.selectedProduct"
                    :search-records="searchProducts"
                    placeholder="Product Name/UPC to search..."
                    @update:selected-record="selectProduct"
                />
            </div>

            <div v-if="state.isClear">
                <FormAjaxSelect
                    input-label="Categories"
                    :selected-record="state.selectedCategory"
                    :search-records="searchCategory"
                    placeholder="Please type the name of the category to search."
                    @update:selected-record="selectCategory"
                />
            </div>

            <div v-if="state.isClear">
                <FormAjaxSelect
                    input-label="Brands"
                    :selected-record="state.selectedBrand"
                    :search-records="searchBrand"
                    placeholder="Please type the name of the brand to search."
                    @update:selected-record="selectBrand"
                />
            </div>

            <AttributesFilters
                v-if="state.isClear && pageProps.product_variant && state.attributes != []"
                :attributes="state.attributes"
                @update-params="updateParams($event, params)"
            />

            <div v-if="state.isClear && !pageProps.product_variant">
                <FormAjaxSelect
                    input-label="Sizes"
                    :selected-record="state.selectedSize"
                    :search-records="searchSize"
                    placeholder="Please type the name of the size to search."
                    @update:selected-record="selectSize"
                />
            </div>

            <div v-if="state.isClear && !pageProps.product_variant">
                <FormAjaxSelect
                    input-label="Colors"
                    :selected-record="state.selectedColor"
                    :search-records="searchColor"
                    placeholder="Please type the name of the color to search."
                    @update:selected-record="selectColor"
                />
            </div>

            <div v-if="state.isClear">
                <FormAjaxSelect
                    :selected-record="state.selectedDepartments"
                    :search-records="searchDepartment"
                    :multi-select="true"
                    input-label="Department"
                    placeholder="Please type the name of the department to search."
                    @update:selected-record="selectDepartments"
                />
            </div>

            <div v-if="state.isClear">
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

            <div v-if="state.isClear">
                <FormAjaxSelect
                    :selected-record="state.selectedTags"
                    :search-records="searchTag"
                    :multi-select="true"
                    input-label="Tags"
                    placeholder="Please type the name of the tag to search."
                    @update:selected-record="selectTags"
                />
            </div>

            <div v-if="state.isClear && !pageProps.product_variant">
                <FormAjaxSelect
                    :selected-record="state.selectedStyles"
                    :search-records="searchStyle"
                    :multi-select="true"
                    input-label="Styles"
                    placeholder="Please type the name of the style to search."
                    @update:selected-record="selectStyles"
                />
            </div>

            <div v-if="state.isClear">
                <JMultiSelect
                    :selected-records="state.selectedRegions"
                    :records="state.regions"
                    placeholder="Please select regions"
                    input-label="Regions"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="selectRegions"
                />
            </div>

            <div v-if="state.isClear">
                <FormSelectBox
                    :selected-record="state.parameters.stock_type"
                    :records="stockTypes"
                    placeholder="Please select stock type"
                    input-label="Stock Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateStockType"
                />
            </div>

            <div v-if="state.isClear">
                <FormSelectBox
                    :selected-record="state.parameters.status"
                    :records="productStatuses"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Status"
                    @update:selected-record="updateSelectedStatus($event)"
                />
            </div>

            <div v-if="state.isClear">
                <FormSelectBox
                    :selected-record="state.parameters.selling_type"
                    :records="sellingTypes"
                    placeholder="Please select selling type"
                    input-label="Selling Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateSellingType"
                />
            </div>

            <div
                v-if="state.isClear"
                class="mt-3"
            >
                <OutlinePrimaryButton
                    type="button"
                    text="Clear"
                    class="btn-sm w-24 h-10"
                    @click="clearAll()"
                />
            </div>
        </div>
    </div>

    <JTable
        v-if="state.parameters.external_company_main_id && state.parameters.location_ids.length > 0"
        v-model:columns="state.columns"
        :fetch-url="route(externalInventoryReportsFetchUrl)"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-column-customization="true"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :local-storage-key="localStorageKey"
        search-title="Search by item name, article number, color, size, categories, upc, or item code"
    >
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
        <template #categories="record">
            <span
                v-for="(category, index) in record.item.categories"
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
            {{ truncateDecimal(record.item.reserved_stock) }}
        </template>

        <template
            v-if="pageProps.product_variant"
            #product_variant_values="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.attribute"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>

        <template
            v-if="!pageProps.product_variant"
            #color="record"
        >
            {{ record.item.color }}
        </template>

        <template
            v-if="!pageProps.product_variant"
            #size="record"
        >
            {{ record.item.size }}
        </template>

        <template #extra-header-data="record">
            <div class="block items-center xl:flex ml-0 sm:ml-3 mr-0 sm:mr-3 mb-2 sm:mb-0">
                <JBadge
                    :label="'Current Stock: ' + truncateDecimal(record.data.total_current_stock)"
                />
            </div>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { displayAmountWithCurrencySymbol, truncateDecimal, exportRecords } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { watch, reactive, computed } from 'vue';
import { route } from 'ziggy';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import { usePage } from "@inertiajs/vue3";
import AttributesFilters from '@commonComponents/AttributesFilters.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    externalCompanies: {
        type: Array,
        required: true,
    },
    stockTypes: {
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
    locationType: {
        type: String,
        required: true,
    },
    locationIds: {
        type: Array,
        required: true,
    },
    regions: {
        type: Array,
        default: () => [],
    },
    refreshTableData: {
        type: Number,
        default: null,
    },
    isClear: {
        type: Boolean,
        default: false,
    },
    externalLocationsUrl: {
        type: String,
        required: true,
    },
    externalInventoryProductsUrl: {
        type: String,
        required: true,
    },
    externalInventoryCategoriesUrl: {
        type: String,
        required: true,
    },
    externalInventoryBrandsUrl: {
        type: String,
        required: true,
    },
    externalInventorySizesUrl: {
        type: String,
        required: true,
    },
    externalInventoryColorsUrl: {
        type: String,
        required: true,
    },
    externalInventoryDepartmentsUrl: {
        type: String,
        required: true,
    },
    externalInventoryArticleNumbersUrl: {
        type: String,
        required: true,
    },
    externalInventoryTagsUrl: {
        type: String,
        required: true,
    },
    externalInventoryStylesUrl: {
        type: String,
        required: true,
    },
    externalInventoryAttributesUrl: {
        type: String,
        default: null,
    },
    localStorageKey: {
        type: String,
        required: true,
    },
    externalInventoryReportsFetchUrl: {
        type: String,
        required: true,
    },
    sellingTypes: {
        type: Array,
        required: true,
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
                    bodyClass: 'text-left',
                    headerClass: 'text-left',
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
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'current_stock',
            label: 'Current Stock',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
            isDisplay: true,
        }, {
            key: 'reserved_stock',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'available_stock',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
            sortable: true,
        }, {
            key: 'total_value',
            label: 'Value',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }
    ],
    refreshTableData: Math.random(),
    isClear: props.isClear,
    stores: [],
    warehouses: [],
    regions: [],
    selectedLocations: null,
    selectedProduct: null,
    selectedCategory: null,
    selectedBrand: null,
    selectedSize: null,
    selectedColor: null,
    selectedDepartments: null,
    selectedArticleNumber: null,
    selectedTags: null,
    selectedStyles: null,
    selectedRegions: null,
    attributes: null,
    parameters: {
        location_ids: [],
        external_company_main_id: null,
        product_id: null,
        category_id: null,
        brand_id: null,
        size_id: null,
        color_id: null,
        department_ids: null,
        article_numbers: null,
        tag_ids: null,
        style_ids: null,
        stock_type: null,
        region_ids: null,
        selling_type: null,
        status: null,
        attributes: null,
    },
});

const clearAll = () => {
    state.isClear = false;
    state.stores = [];
    state.warehouses = [];
    state.regions = [];
    state.selectedLocations = null;
    state.parameters.location_ids = [];
    state.parameters.external_company_main_id = null;
    state.parameters.product_id = null;
    state.parameters.category_id = null;
    state.parameters.brand_id = null;
    state.parameters.size_id = null;
    state.parameters.color_id = null;
    state.parameters.department_ids = null;
    state.parameters.article_numbers = null;
    state.parameters.tag_ids = null;
    state.parameters.style_ids = null;
    state.parameters.region_ids = null;
    state.parameters.status = null;
    state.selectedCategory = null;
    state.selectedBrand = null;
    state.selectedSize = null;
    state.selectedColor = null;
    state.selectedDepartments = null;
    state.selectedArticleNumber = null;
    state.selectedTags = null;
    state.selectedStyles = null;
    state.selectedRegions = null;
    state.selectedProduct = null;
    state.parameters.selling_type = null;
    emits('clear-all');
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateSelectedExternalCompany = (externalCompanyMainId) => {
    state.parameters.external_company_main_id = externalCompanyMainId;

    axios.get(route(props.externalLocationsUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
        }
    }).then((response) => {
        state.stores = response.data.stores;
        state.warehouses = response.data.warehouses;
        state.regions = response.data.regions;

        emits('update:stores', response.data.stores);
        emits('update:warehouses', response.data.warehouses);
        emits('update:regions', response.data.regions);
    });

    emits('update:external-company-id', externalCompanyMainId);
    if (props.externalInventoryAttributesUrl && pageProps.value.product_variant) {
        axios.get(route(props.externalInventoryAttributesUrl), {
            params: {
                external_company_main_id: state.parameters.external_company_main_id,
            }
        }).then((response) => {
            state.attributes = response.data.attributes;
        });
    }
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-external-inventories/',
        'external-inventories.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-external-inventories/',
        'external-inventories.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const searchProducts = (searchText, componentState) => {
    axios.get(route(props.externalInventoryProductsUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
            search_text: searchText,
            number_of_records: 5,
        }
    }).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};

const searchCategory = (searchText, componentState) => {
    axios.get(route(props.externalInventoryCategoriesUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
            search_text: searchText,
            number_of_records: 5,
        }
    }).then((response) => {
        componentState.records = response.data.categories;
        componentState.isLoading = false;
    });
};

const searchBrand = (searchText, componentState) => {
    axios.get(route(props.externalInventoryBrandsUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
            search_text: searchText,
        }
    }).then((response) => {
        componentState.records = response.data.brands;
        componentState.isLoading = false;
    });
};

const searchSize = (searchText, componentState) => {
    axios.get(route(props.externalInventorySizesUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
            search_text: searchText,
        }
    }).then((response) => {
        componentState.records = response.data.sizes;
        componentState.isLoading = false;
    });
};

const searchColor = (searchText, componentState) => {
    axios.get(route(props.externalInventoryColorsUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
            search_text: searchText,
        }
    }).then((response) => {
        componentState.records = response.data.colors;
        componentState.isLoading = false;
    });
};

const searchDepartment = (searchText, componentState) => {
    axios.get(route(props.externalInventoryDepartmentsUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
            search_text: searchText,
        }
    }).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const searchArticleNumber = (searchText, componentState) => {
    axios.get(route(props.externalInventoryArticleNumbersUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
            search_text: searchText,
        }
    }).then((response) => {
        componentState.records = response.data.articleNumbers;
        componentState.isLoading = false;
    });
};

const searchTag = (searchText, componentState) => {
    axios.get(route(props.externalInventoryTagsUrl), {
        params: {
            external_company_main_id: state.parameters.external_company_main_id,
            search_text: searchText,
        }
    }).then((response) => {
        componentState.records = response.data.tags;
        componentState.isLoading = false;
    });
};

const searchStyle = (searchText, componentState) => {
    const minSearchLength = 3;
    if (searchText.length >= minSearchLength) {
        axios.get(route(props.externalInventoryStylesUrl), {
            params: {
                external_company_main_id: state.parameters.external_company_main_id,
                search_text: searchText,
            }
        }).then((response) => {
            componentState.records = response.data.styles;
            componentState.isLoading = false;
        });
    }
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

const selectSize = (selectedSize) => {
    state.selectedSize = selectedSize;
    state.parameters.size_id = null;
    if (selectedSize !== null) {
        state.parameters.size_id = selectedSize.id;
    }
    refreshTable();
};

const selectColor = (selectedColor) => {
    state.selectedColor = selectedColor;
    state.parameters.color_id = null;
    if (selectedColor !== null) {
        state.parameters.color_id = selectedColor.id;
    }
    refreshTable();
};

const selectDepartments = (selectedDepartments) => {
    state.selectedDepartments = selectedDepartments;
    state.parameters.department_ids = null;
    if (selectedDepartments !== null) {
        state.parameters.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    }
    refreshTable();
};

const selectArticleNumbers = (selectedNumbers) => {
    state.selectedArticleNumber = selectedNumbers;
    state.parameters.article_numbers = null;
    if (selectArticleNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    }
    refreshTable();
};

const selectTags = (selectedTags) => {
    state.selectedTags = selectedTags;
    state.parameters.tag_ids = null;
    if (selectedTags !== null) {
        state.parameters.tag_ids = selectedTags.map(function (tag) {
            return tag.id;
        });
    }
    refreshTable();
};

const selectStyles = (selectedStyles) => {
    state.selectedStyles = selectedStyles;
    state.parameters.style_ids = null;
    if (selectedStyles !== null) {
        state.parameters.style_ids = selectedStyles.map(function (selectedStyle) {
            return selectedStyle.id;
        });
    }
    refreshTable();
};

const selectRegions = (selectedRegions) => {
    state.selectedRegions = selectedRegions;
    state.parameters.region_ids = null;
    if (selectedRegions !== null) {
        state.parameters.region_ids = selectedRegions.map(function (selectedRegion) {
            return selectedRegion.id;
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

const updateSelectedStatus = (statuses) => {
    state.parameters.status = statuses;
    refreshTable();
};

const emits = defineEmits([
    'update:external-company-id',
    'update:stores',
    'update:warehouses',
    'update:regions',
    'clear-all',
]);

watch(() => props.refreshTableData,
    () => {
        state.isClear = props.isClear;
        state.parameters.location_ids = props.locationIds;
        state.regions = props.regions;

        if (props.locationType === 'Warehouse') {
            state.selectedRegions = null;
            state.parameters.region_ids = null;
        }

        refreshTable();
    }
);

const updateSellingType = (sellingType) => {
    state.parameters.selling_type = null;
    if (sellingType !== null) {
        state.parameters.selling_type = parseInt(sellingType);
    }
    refreshTable();
};

const updateParams = (params) => {
    state.parameters.attributes = params;
    refreshTable();
};

</script>
