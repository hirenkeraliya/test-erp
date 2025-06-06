<template>
    <PageTitle title="Products Report" />

    <div class="flex flex-col items-center mt-6 intro-y sm:flex-row">
        <h2 class="mr-auto text-lg font-medium">
            Products Report
        </h2>
    </div>

    <div
        v-if="state.displayProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x products-report-filters"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <JMultiSelect
                    :selected-records="state.selectedLocations"
                    :records="locations"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    input-label="Locations"
                    validation-field-name="locations"
                    placeholder="Please select locations"
                    @update:selected-records="selectLocations"
                />
            </div>

            <div>
                <JMultiSelect
                    :selected-records="state.counters"
                    :records="state.storeCounters === null ? [] : state.storeCounters"
                    :placeholder="state.storeCounters ? 'Please select Counter' : 'Please select a Location First'"
                    :disabled="null === state.storeCounters"
                    input-label="Counters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-records="updateCounterId"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedProduct"
                    :search-records="searchProducts"
                    input-label="Products"
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
                    :selected-record="state.selectedCategories"
                    :search-records="searchCategory"
                    :multi-select="true"
                    input-label="Categories"
                    placeholder="Please type the name of the category to search."
                    @update:selected-record="selectCategories"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedBrands"
                    :search-records="searchBrand"
                    :multi-select="true"
                    input-label="Brands"
                    placeholder="Please type the name of the brand to search."
                    @update:selected-record="selectBrands"
                />
            </div>

            <AttributesFilters
                v-if="pageProps.product_variant"
                :attributes="attributes"
                @update-params="updateParams($event, params)"
            />

            <div v-if="!pageProps.product_variant">
                <FormAjaxSelect
                    :selected-record="state.selectedSizes"
                    :search-records="searchSize"
                    :multi-select="true"
                    input-label="Sizes"
                    placeholder="Please type the name of the size to search."
                    @update:selected-record="selectSizes"
                />
            </div>

            <div v-if="!pageProps.product_variant">
                <FormAjaxSelect
                    :selected-record="state.selectedColors"
                    :search-records="searchColor"
                    :multi-select="true"
                    input-label="Colors"
                    placeholder="Please type the name of the color to search."
                    @update:selected-record="selectColors"
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
                <JDateTimePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    label-class="block mb-2 text-base font-medium text-primary-p3"
                    input-label="Date Range"
                    @update:input-value="updateDate($event)"
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

            <div>
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
                    :selected-record="state.parameters.purchase_type"
                    :records="purchaseTypes"
                    input-label="Purchase Type"
                    placeholder="Purchase Type"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updatePurchaseType"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="w-24 h-10 btn-sm"
                @click="clearAll()"
            />
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.products_report.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        :allow-column-customization="true"
        local-storage-key="admin-products-reports-columns"
        search-title="Search by product"
        :sort-direction="dashboardFilterData.sort_direction"
        :sort-by="dashboardFilterData.sort_by"
    >
        <template #verifications="data">
            <div class="flex items-center justify-center cursor-pointer">
                <div class="mr-1">
                    <p
                        @click="showProductVerificationReport(data.item)"
                    >
                        {{ data.item.verification_count }}
                    </p>
                </div>
            </div>
        </template>

        <template
            v-if="pageProps.product_variant"
            #attributes="record"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in record.item.attributes"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute_name }} : {{ product_variant.attribute_value }}
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
                    v-if="record.data.sales_collection"
                    :label="'Collection: ' + displayAmountWithCurrencySymbol(record.data.sales_collection)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_units_sold"
                    :label="'Units Sold: ' + truncateDecimal(record.data.total_units_sold)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_sales"
                    :label="'Sales: ' + displayAmountWithCurrencySymbol(record.data.total_sales)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_units_return"
                    :label="'Returns: ' + truncateDecimal(record.data.total_units_return)"
                    class="mb-1 lg:mb-1 xl:mb-0"
                />

                <JBadge
                    v-if="record.data.total_sale_returns"
                    :label="'Return: ' + displayAmountWithCurrencySymbol(record.data.total_sale_returns)"
                />
                <JBadge
                    v-if="record.data.total_verification"
                    :label="'Verifications: ' + record.data.total_verification"
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
                    @click="state.displayProductFilter = !state.displayProductFilter"
                />
            </p>
        </template>

        <template #categories="data">
            <span v-if="data.item.categories.length">
                <span
                    v-for="(category, index) in data.item.categories"
                    :key="index"
                    class="inline-block"
                >
                    {{ category }}

                    <ChevronRight
                        v-if="index != data.item.categories.length - 1"
                        class="inline-block w-4 h-4 form-check text-slate-400"
                    />
                </span>
            </span>
            <span v-else>
                N/A
            </span>
        </template>

        <template #total_sales="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_sales) }}
        </template>

        <template #units_sold="data">
            {{ truncateDecimal(data.item.units_sold) }}
        </template>

        <template #units_returned="data">
            {{ truncateDecimal(data.item.units_returned) }}
        </template>

        <template #total_sale_returns="data">
            {{ displayAmountWithCurrencySymbol(data.item.total_sale_returns) }}
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { ChevronRight } from 'lucide-vue-next';
import { displayAmountWithCurrencySymbol, exportRecords, truncateDecimal, currentDateTime, printReport } from '@commonServices/helper';
import { reactive, onMounted, computed } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JBadge from '@commonComponents/JBadge.vue';
import { router } from '@inertiajs/vue3';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import AttributesFilters from '@commonComponents/AttributesFilters.vue';
import { usePage } from "@inertiajs/vue3";

const pageProps = computed(() => usePage().props);

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    regions: {
        type: Array,
        required: true,
    },
    purchaseTypes: {
        type: Array,
        required: true,
    },
    dashboardFilterData: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    attributes: {
        type: Object,
        default: () => { },
    },
});

const state = reactive({
    columns: [
        {
            key: 'id',
            isDisplay: true,
        },
        {
            key: 'product',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'upc',
            isDisplay: true,
        },
        {
            key: 'verifications',
            isDisplay: true,
        },
        {
            key: 'article_number',
            isDisplay: true,
        },
        {
            key: 'categories',
            label: 'Categories',
            isDisplay: true,
        },
        {
            key: 'brand',
            isDisplay: true,
        },
        {
            key: 'season',
            isDisplay: true,
        },
        {
            key: 'department',
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
            key: 'sub_department',
            isDisplay: true,
        },
        {
            key: 'unit_of_measure',
            label: 'UOM',
            isDisplay: true,
        },
        {
            key: 'location',
            isDisplay: true,
        },
        {
            key: 'units_sold',
            isDisplay: true,
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'total_sales',
            isDisplay: true,
            label: 'Sales',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'units_returned',
            isDisplay: true,
            sortable: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'total_sale_returns',
            isDisplay: true,
            label: 'Sale Returns',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ],
    refreshTableData: Math.random(),
    selectedProduct: props.dashboardFilterData.selectedProduct,
    selectedCategories: null,
    selectedBrands: null,
    selectedSizes: null,
    selectedColors: props.dashboardFilterData.selectedColors,
    selectedDepartments: null,
    selectedLocations: props.dashboardFilterData.selectedLocations,
    displayProductFilter: false,
    selectedTags: null,
    selectedRegions: null,
    storeCounters: null,
    counters: null,
    isClear: false,
    selectedArticleNumber: null,
    showProductVerificationDetailsModal: false,
    selectedProductVerificationId: null,

    parameters: {
        product_id: props.dashboardFilterData.product_id,
        category_ids: null,
        brand_ids: null,
        color_ids: props.dashboardFilterData.color_ids,
        size_ids: null,
        department_ids: null,
        location_ids: props.dashboardFilterData.location_ids,
        date_range: props.dashboardFilterData.dateRange,
        article_number: props.dashboardFilterData.articleNumber,
        tag_ids: null,
        region_ids: null,
        counter_ids: null,
        product_collection_id: null,
        article_numbers:[props.dashboardFilterData.articleNumber],
        purchase_type: null,
        attributes: null,
    },
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.product_id = null;
    state.parameters.category_ids = null;
    state.parameters.brand_ids = null;
    state.parameters.color_ids = null;
    state.parameters.size_ids = null;
    state.parameters.department_ids = null;
    state.parameters.location_ids = null;
    state.parameters.region_ids = null;
    state.parameters.date_range = currentDateTime();
    state.parameters.article_numbers = null;
    state.selectedProduct = null;
    state.selectedCategories = null;
    state.selectedBrands = null;
    state.selectedSizes = null;
    state.selectedColors = null;
    state.selectedDepartments = null;
    state.selectedLocations = null;
    state.selectedTags = null;
    state.selectedRegions = null;
    state.parameters.tag_ids = null;
    state.parameters.counter_ids = null;
    state.parameters.product_collection_id = null;
    state.counters = null;
    state.storeCounters = null;
    state.selectedArticleNumber = null;
    state.parameters.purchase_type = null;

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

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
    refreshTable();
};

const updatePurchaseType = (purchaseType) => {
    state.parameters.purchase_type = purchaseType;
    refreshTable();
};

const selectCategories = (selectedCategories) => {
    state.selectedCategories = selectedCategories;
    if (selectedCategories !== null) {
        state.parameters.category_ids = selectedCategories.map(function (category) {
            return category.id;
        });
    }
    refreshTable();
};

const selectBrands = (selectedBrands) => {
    state.selectedBrands = selectedBrands;
    if (selectedBrands !== null) {
        state.parameters.brand_ids = selectedBrands.map(function (brand) {
            return brand.id;
        });
    }
    refreshTable();
};

const updateParams = (params) => {
    state.parameters.attributes = params;
    refreshTable();
};

const selectSizes = (selectedSizes) => {
    state.selectedSizes = selectedSizes;
    if (selectedSizes !== null) {
        state.parameters.size_ids = selectedSizes.map(function (size) {
            return size.id;
        });
    }
    refreshTable();
};

const selectColors = (selectedColors) => {
    state.selectedColors = selectedColors;
    if (selectedColors !== null) {
        state.parameters.color_ids = selectedColors.map(function (color) {
            return color.id;
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

const selectLocations = (selectedLocations) => {
    state.selectedLocations = selectedLocations;
    const locationIds = selectedLocations.map(function (location) {
        return location.id;
    });

    if (locationIds.length) {
        state.parameters.location_ids = locationIds;
        state.parameters.counter_ids = null;

        axios.post(route('admin.counters.get_counters_of_locations', { locations_ids: locationIds }))
            .then((response) => {
                state.storeCounters = response.data.counters;
            });

        refreshTable();

        return;
    }
    clearAll();
};

const selectRegions = (selectedRegions) => {
    state.selectedRegions = selectedRegions;
    state.parameters.region_ids = selectedRegions.map(function (region) {
        return region.id;
    });
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const searchProducts = (searchText, componentState) => {
    axios.get(route('admin.get_filtered_products'), {
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
    axios.post(route('admin.categories.get_filtered_categories'), filterData).then((response) => {
        componentState.records = response.data.categories;
        componentState.isLoading = false;
    });
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

const searchSize = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.sizes.get_filtered_sizes'), filterData).then((response) => {
        componentState.records = response.data.sizes;
        componentState.isLoading = false;
    });
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

const searchDepartment = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
    };

    axios.post(route('admin.departments.get_filtered_departments'), filterData).then((response) => {
        componentState.records = response.data.departments;
        componentState.isLoading = false;
    });
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-products-report/',
        'products_report.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-products-report/',
        'products_report.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('admin.products_report.print_products_report', params), props.exportPermission);
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
    state.selectedArticleNumber = selectedNumbers;
    if (selectArticleNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    }
    refreshTable();
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
    }
    refreshTable();
};

const updateCounterId = (counters) => {
    state.counters = counters;

    const counterIds = counters.map((counter) => {
        return counter.id;
    });
    state.parameters.counter_ids = counterIds;
    refreshTable();
};

const refreshPage = () => {
    router.get(route('admin.products_report.index'));
};

const showProductVerificationReport = (product) => {
    const url = route('admin.product_verification_reports.index', { location_id: product.location_id, product_id: product.id });
    window.open(url, '_blank');
};

onMounted(() => {
    if (props.dashboardFilterData.product_id || props.dashboardFilterData.articleNumber || props.dashboardFilterData.color_ids) {
        state.isClear = true;
        state.displayProductFilter = true;
        refreshTable();
    }
});

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
