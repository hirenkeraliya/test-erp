<template>
    <div
        v-if="state.displayProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x products-report-filters"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
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
                <JDatePicker
                    :range-picker="true"
                    :input-value="state.parameters.last_selling_date_range"
                    input-label="Last Selling Date"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
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

    <div class="mt-3">
        <div class="flex">
            <div
                v-if="state.totalQuantitySold === null"
                class="cp w-1/3 rounded"
            >
                <div class="animated-background" />
            </div>
            <div
                v-if="state.totalQuantityRemaining === null"
                class="cp w-1/3 rounded"
            >
                <div class="animated-background" />
            </div>
        </div>

        <div class="flex">
            <Tippy
                v-if="state.totalQuantitySold > 0"
                content="Sold Quantity"
            >
                <JBadge
                    :label="'Sold: ' + state.totalQuantitySold"
                    class="capitalize"
                />
            </Tippy>

            <Tippy
                v-if="state.totalQuantityRemaining > 0"
                content="Quantity Remaining"
            >
                <JBadge
                    :label="'Balance: ' + state.totalQuantityRemaining"
                    class="capitalize"
                />
            </Tippy>
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="route('admin.products_ageing_report.fetch_product_aging_by_article_number')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        :allow-column-customization="true"
        local-storage-key="admin-products-ageing-reports-columns"
        search-title="Search by product"
        @get-search-text="getSearchText"
    >
        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayProductFilter = !state.displayProductFilter"
                />
            </p>
        </template>

        <template #created_at="record">
            {{ record.item.created_at }}
        </template>

        <template #first_grn_date="record">
            {{ record.item.first_grn_date }}
        </template>

        <template #first_transfer_in_date="record">
            {{ record.item.first_transfer_in_date }}
        </template>

        <template #quantity_sold="record">
            {{ truncateDecimal(record.item.quantity_sold) }}
        </template>

        <template #quantity_remaining="record">
            {{ truncateDecimal(record.item.quantity_remaining) }}
        </template>
    </JTable>
</template>

<script setup>
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JBadge from '@commonComponents/JBadge.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import JTable from '@commonComponents/JTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { exportRecords, printReport, truncateDecimal } from '@commonServices/helper';
import axios from 'axios';
import { onMounted, onUnmounted, reactive, computed } from 'vue';
import { route } from 'ziggy';
import { showSuccessNotification } from '@commonServices/notifier';
import AttributesFilters from '@commonComponents/AttributesFilters.vue';
import { usePage } from '@inertiajs/vue3';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    ageOfProductTypes: {
        type: Object,
        required: true,
    },
    staticAgeOfProductTypes: {
        type: Object,
        required: true,
    },
    exportPermission: {
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
            key: 'article_number',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'created_at',
            isDisplay: true,
            sortable: true,
            bodyClass: 'text-center',
        },
        {
            key: 'first_grn_date',
            isDisplay: true,
            sortable: true,
            bodyClass: 'text-center',
        },
        {
            key: 'first_transfer_in_date',
            isDisplay: true,
            sortable: true,
            bodyClass: 'text-center',
        },
        {
            key: 'last_selling_date',
            isDisplay: true,
            sortable: true,
            label: 'Last Sold Date',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
        {
            key: 'quantity_sold',
            isDisplay: true,
            sortable: true,
            label: 'Units Sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'quantity_remaining',
            isDisplay: true,
            sortable: true,
            label: 'Balance Stock',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'age_of_the_product',
            isDisplay: true,
            sortable: true,
            label: 'Ageing Based On Created At',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
        {
            key: 'age_of_the_product_first_grn',
            isDisplay: true,
            sortable: true,
            label: 'Ageing Based On First GRN Date',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
        {
            key: 'age_of_the_product_first_transfer_in',
            isDisplay: true,
            sortable: true,
            label: 'Ageing Based On Transfer In Date',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        },
    ],
    refreshTableData: Math.random(),
    selectedProduct: null,
    selectedCategories: null,
    selectedBrands: null,
    selectedSizes: null,
    selectedColors: null,
    selectedDepartments: null,
    selectedArticleNumber: null,
    displayProductFilter: false,
    selectedTags: null,
    parameters: {
        product_id: null,
        category_ids: null,
        brand_ids: null,
        color_ids: null,
        size_ids: null,
        department_ids: null,
        article_numbers: null,
        tag_ids: null,
        last_selling_date_range: null,
        product_collection_id: null,
        attributes: null,
    },
    totalQuantitySold: null,
    totalQuantityRemaining: null,
    cancelBadgeController: new AbortController(),
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
    getBadgeData();
};

const clearAll = () => {
    state.parameters.product_id = null;
    state.parameters.category_ids = null;
    state.parameters.brand_ids = null;
    state.parameters.color_ids = null;
    state.parameters.size_ids = null;
    state.parameters.department_ids = null;
    state.parameters.article_numbers = null;
    state.selectedProduct = null;
    state.selectedCategories = null;
    state.selectedBrands = null;
    state.selectedSizes = null;
    state.selectedColors = null;
    state.selectedDepartments = null;
    state.selectedArticleNumber = null;
    state.selectedTags = null;
    state.parameters.tag_ids = null;
    state.parameters.last_selling_date_range = null;
    state.parameters.product_collection_id = null;
    state.parameters.attributes = null;
    refreshTable();
};

const selectProduct = (selectedProduct) => {
    state.parameters.product_id = null;
    state.selectedProduct = selectedProduct;
    if (selectedProduct !== null) {
        state.parameters.product_id = selectedProduct.id;
    }
    refreshTable();
};

const selectCategories = (selectedCategories) => {
    state.parameters.category_ids = null;
    state.selectedCategories = selectedCategories;
    if (selectedCategories !== null) {
        state.parameters.category_ids = selectedCategories.map(function (category) {
            return category.id;
        });
    }
    refreshTable();
};

const selectBrands = (selectedBrands) => {
    state.selectedBrands = null;
    state.selectedBrands = selectedBrands;
    if (selectedBrands !== null) {
        state.parameters.brand_ids = selectedBrands.map(function (brand) {
            return brand.id;
        });
    }
    refreshTable();
};

const selectSizes = (selectedSizes) => {
    state.parameters.size_ids = null;
    state.selectedSizes = selectedSizes;
    if (selectedSizes !== null) {
        state.parameters.size_ids = selectedSizes.map(function (size) {
            return size.id;
        });
    }
    refreshTable();
};

const selectColors = (selectedColors) => {
    state.parameters.color_ids = null;
    state.selectedColors = selectedColors;
    if (selectedColors !== null) {
        state.parameters.color_ids = selectedColors.map(function (color) {
            return color.id;
        });
    }
    refreshTable();
};

const updateParams = (params) => {
    state.parameters.attributes = params;
    refreshTable();
};

const selectDepartments = (selectedDepartments) => {
    state.parameters.department_ids = null;
    state.selectedDepartments = selectedDepartments;
    if (selectedDepartments !== null) {
        state.parameters.department_ids = selectedDepartments.map(function (department) {
            return department.id;
        });
    }
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
    params['export_columns'] = columns;
    return axios.get(route('admin.products_ageing_report.check_product_ageing_export_limit_by_article_number', params))
        .then((response) => {
            if (! response.data.exceeds_limit) {
                exportRecords(
                    'export-products-ageing-report-by-article-number/',
                    'products_ageing_report_by_article_number.csv',
                    params,
                    props.exportPermission,
                    columns
                );

                return;
            }

            showSuccessNotification(response.data.message);
        });
};

const exportExcelRecords = (params, columns) => {
    params['export_columns'] = columns;
    return axios.get(route('admin.products_ageing_report.check_product_ageing_export_limit_by_article_number', params))
        .then((response) => {
            if (! response.data.exceeds_limit) {
                exportRecords(
                    'export-products-ageing-report-by-article-number/',
                    'products_ageing_report_by_article_number.xlsx',
                    params,
                    props.exportPermission,
                    columns
                );

                return;
            }

            showSuccessNotification(response.data.message);
        });
};

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('admin.products_ageing_report.print_products_ageing_report_by_article_number', params), props.exportPermission);
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
    state.parameters.tag_ids = null;
    state.selectedTags = selectedTags;
    if (selectedTags !== null) {
        state.parameters.tag_ids = selectedTags.map(function (tag) {
            return tag.id;
        });
    }
    refreshTable();
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
    state.parameters.article_numbers = null;
    state.selectedArticleNumber = selectedNumbers;
    if (selectedNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    }
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.last_selling_date_range = date;
    refreshTable();
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
    refreshTable();
};

const getBadgeData = () => {
    state.totalQuantitySold = null;
    state.totalQuantityRemaining = null;

    if (state.cancelBadgeController) {
        state.cancelBadgeController.abort();
    }

    state.cancelBadgeController = new AbortController();

    axios.get(route('admin.products_ageing_report.fetch_consolidate_by_article_number'), {
        params: state.parameters,
        signal: state.cancelBadgeController.signal
    })
        .then((response) => {
            state.totalQuantitySold = response.data.total_quantity_sold;
            state.totalQuantityRemaining = response.data.total_quantity_remaining;
        });
};

onMounted(() => {
    getBadgeData();
});

onUnmounted(() => {
    if (state.cancelBadgeController) {
        state.cancelBadgeController.abort();
    }
});

const getSearchText = (searchText) => {
    state.totalQuantitySold = null;
    state.totalQuantityRemaining = null;
    state.parameters.search_text = searchText;
    getBadgeData();
};
</script>
