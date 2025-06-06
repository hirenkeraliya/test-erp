<template>
    <PageTitle title="Sale Analysis Grade Report" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sale Analysis Grade Report
        </h2>
    </div>

    <div
        v-if="state.displayProductFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.location_id"
                    :records="locations"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    class="w-full"
                    @update:selected-record="updateLocationId"
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
                <FormAjaxSelect
                    :selected-record="state.selectedStyles"
                    :search-records="searchStyle"
                    :multi-select="true"
                    input-label="Styles"
                    placeholder="Please type the name of the style to search."
                    @update:selected-record="selectStyles"
                />
            </div>

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.grade_filter"
                    :records="saleThroughRatios"
                    input-label="Grade Filters"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateGradeFilter"
                />
            </div>

            <div>
                <JDatePicker
                    :input-value="state.parameters.date"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Date"
                    :max-date="new Date()"
                    :required="true"
                    @update:input-value="updateDate($event)"
                />
            </div>
        </div>

        <div class="mt-3">
            <OutlinePrimaryButton
                type="button"
                text="Clear"
                class="btn-sm w-24 h-10"
                @click="clearParameters()"
            />
        </div>
    </div>

    <div
        v-if="validationCheck()"
        class="bg-white rounded-lg p-4 mt-6"
    >
        <div
            class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-10 gap-x-0 gap-y-0 sm:gap-y-4 sm:gap-x-4"
        >
            <div
                v-for="(total, index) in state.totals"
                :key="index"
            >
                <div class="flex">
                    <div
                        class="w-2.5 h-2.5 rounded mr-2"
                        :class="colorsForChart(index)"
                    />
                    <div>
                        <p class="font-bold text-sm">
                            {{ total.name }}({{ truncateDecimal(total.percentage) }}%)
                        </p>
                        <p>
                            {{ displayAmountWithCurrencySymbol(total.amount) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div
            id="progress-bar-sale-grade"
            class="flex items-center mt-5 overflow-x-auto"
        />
    </div>

    <div
        v-if="validationCheck()"
        class="bg-white rounded-lg p-4 mt-6"
    >
        <div class="block sm:flex justify-between items-center">
            <p class="text-lg font-medium mb-3 sm:mb-0">
                Sale Analysis Grade Information
            </p>

            <a
                class="text-primary text-base cursor-pointer border py-1.5 px-6 font-medium hover:bg-primary hover:text-white rounded-lg"
                @click="state.displaySaleAnalysisInformation = !state.displaySaleAnalysisInformation"
            >
                {{ state.displaySaleAnalysisInformation ? 'Hide' : 'Show' }}
            </a>
        </div>

        <div
            v-if="Object.keys(state.saleThroughRatios).length > 0 && state.displaySaleAnalysisInformation"
            class="bg-slate-100 p-5 mt-3 rounded-md"
        >
            <ul class="list-disc grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-0 sm:gap-x-5">
                <li
                    v-for="(saleThroughRatio, index) in state.saleThroughRatios"
                    :key="index"
                    class="ml-3 pb-5"
                >
                    <div>
                        <p>
                            <b>
                                {{ saleThroughRatio.name }}
                            </b>
                            products make up {{ saleThroughRatio.percentage }}% of revenue
                        </p>

                        <p>
                            {{ saleThroughRatio.description }}
                        </p>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <JTable
        v-if="validationCheck()"
        :columns="state.columns"
        :fetch-url="route('admin.sale_analysis_by_grade.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        search-title="Can Search Product Name, Upc, And Article Number"
        @get-search-text="getSearchText($event)"
    >
        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md mb-2 sm:mb-0"
                    @click="state.displayProductFilter = !state.displayProductFilter"
                />
            </p>
        </template>

        <template #upc="record">
            <button
                class="underline underline-offset-2"
                @click="showProductData(record.item.id)"
            >
                {{ record.item.upc }}
            </button>
        </template>

        <template #article_number="record">
            <button
                v-if="record.item.article_number !== null"
                class="underline underline-offset-2"
                @click="showProductArticleNumberData(record.item.article_number)"
            >
                {{ record.item.article_number }}
            </button>

            <p v-else>
                N/A
            </p>
        </template>

        <template #total_sales="record">
            {{ displayAmountWithCurrencySymbol(record.item.total_sales) }}
        </template>

        <template #total_units_sold="record">
            {{ truncateDecimal(record.item.total_units_sold) }}
        </template>

        <template #color="record">
            {{ record.item.color?.name ?? 'N/A' }}
        </template>

        <template #size="record">
            {{ record.item.size?.name ?? 'N/A' }}
        </template>

        <template
            v-if="pageProps.product_variant"
            #attributes="data"
        >
            <span v-if="pageProps.product_variant">
                <p
                    v-for="(product_variant, index) in data.item.product_variant_values"
                    :key="index"
                    class="flex"
                >
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
                </p>
            </span>
        </template>
    </JTable>
</template>

<script setup>
import { onMounted, reactive, computed } from 'vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import { currentDate, displayAmountWithCurrencySymbol, exportRecords, printReport, truncateDecimal } from '@commonServices/helper';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import axios from 'axios';
import { route } from 'ziggy';
import JTable from '@commonComponents/JTable.vue';
import { router, usePage } from '@inertiajs/vue3';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import AttributesFilters from '@commonComponents/AttributesFilters.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    saleThroughRatios: {
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
    parameters: {
        location_id: props.dashboardFilterData.location_id,
        date: currentDate(),
        category_ids: [],
        brand_ids: [],
        department_ids: [],
        color_ids: [],
        size_ids: [],
        article_numbers: [],
        tag_ids: [],
        style_ids: [],
        grade_filter: null,
        product_id: props.dashboardFilterData.product_id,
        product_collection_id: null,
        attributes: null,
    },

    records: [],
    totals: [],
    saleThroughRatios: [],
    chartRecords: [],
    displaySaleAnalysisInformation: true,
    columns: [
        {
            key: 'name',
            sortable: true,
        }, {
            key: 'upc',
            sortable: true,
        }, {
            key: 'article_number',
            sortable: true,
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
                    sortable: true,
                }, {
                    key: 'size',
                    sortable: true,
                },
            ]),
        {
            key: 'accumulated_sale_through',
            sortable: true,
            label: 'Sell Through (%)',
            bodyClass: 'text-right',
            headerClass: 'text-right'
        }, {
            key: 'sale_analysis_grade',
            sortable: true,
            label: 'Product Grade'
        }, {
            key: 'total_units_sold',
            label: 'Units Sold',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        }, {
            key: 'total_sales',
            label: 'Sales',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        },
    ],

    selectedBrands: [],
    selectedCategories: [],
    selectedDepartments: [],
    selectedSizes: [],
    selectedColors: [],
    selectedTags: [],
    selectedArticleNumber: [],
    selectedProduct: props.dashboardFilterData.selectedProduct,
    selectedStyles: [],
    displayProductFilter: false,
    displayProgressBaGrades: false,
    refreshTableData: Math.random(),
});

const refreshTable = () => {
    if (!validationCheck()) {
        return;
    }

    state.refreshTableData = Math.random();
    fetchTotals();
};

const updateLocationId = (locationId) => {
    state.parameters.location_id = null;
    if (locationId !== null) {
        state.parameters.location_id = parseInt(locationId);
    }
    refreshTable();
};

const updateDate = (date) => {
    state.parameters.date = date;
    refreshTable();
};

const updateGradeFilter = (saleThroughRatioId) => {
    state.parameters.grade_filter = null;
    if (saleThroughRatioId !== null) {
        state.parameters.grade_filter = parseInt(saleThroughRatioId);
    }
    refreshTable();
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
    refreshTable();
};

const clearParameters = () => {
    state.parameters.location_id = null;
    state.parameters.category_ids = [];
    state.parameters.brand_ids = [];
    state.parameters.department_ids = [];
    state.parameters.color_ids = [];
    state.parameters.size_ids = [];
    state.parameters.article_numbers = [];
    state.parameters.tag_ids = [];
    state.parameters.style_ids = [];
    state.parameters.product_id = null;
    state.parameters.grade_filter = null;
    state.parameters.date = currentDate();
    state.parameters.product_collection_id = null;
    refreshTable();
};

onMounted(() => {
    refreshTable();
});

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
    state.parameters.color_ids = selectedColors.map(function (color) {
        return color.id;
    });
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

const selectArticleNumbers = (selectedNumbers) => {
    state.selectedArticleNumber = selectedNumbers;
    if (selectedNumbers !== null) {
        state.parameters.article_numbers = selectedNumbers.map(function (articleNumber) {
            return articleNumber.article_number;
        });
    }
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

const fetchTotals = () => {
    axios.get(route('admin.sale_analysis_by_grade.fetch_total', state.parameters))
        .then((response) => {
            state.totals = response.data.totals;
            state.saleThroughRatios = response.data.saleThroughRatios;
            displayTheTotalsWithProgressBar();
        });
};

const displayTheTotalsWithProgressBar = () => {
    if (Object.keys(state.totals).length > 0) {
        const gradesContainer = document.getElementById('progress-bar-sale-grade');
        if (gradesContainer !== null) {
            gradesContainer.innerHTML = '';

            const gradesData = state.totals;

            const totalValue = gradesData.length > 0 ? Math.max(...gradesData.map(item => item.amount)) : 0;

            gradesData.forEach((grade, index) => {
                const value = grade.amount;
                const percentageMultiplier = 100;
                const percentage = (value / totalValue) * percentageMultiplier;

                const gradeElement = document.createElement('div');
                gradeElement.className = 'p-1 text-white rounded ml-1';
                gradeElement.style.width = `${percentage}%`;
                gradeElement.style.overflow = 'hidden';
                gradeElement.style.whiteSpace = 'nowrap';
                gradeElement.style.textOverflow = 'ellipsis';
                gradeElement.classList.add(colorsForChart(index));

                gradesContainer.appendChild(gradeElement);
            });
        }
    }
};

const colorsForChart = (index) => {
    const color = [
        'bg-green-800',
        'bg-danger',
        'bg-primary',
        'bg-red-800',
        'bg-blue-800',
        'bg-amber-800',
        'bg-lime-800',
        'bg-yellow-800',
        'bg-emerald-800',
        'bg-indigo-800',
        'bg-fuchsia-800',
        'bg-pink-800',
        'bg-sky-800',
        'bg-rose-800',
        'bg-success',
        'bg-violet-800',
        'bg-teal-800',
        'bg-info',
        'bg-cyan-800',
        'bg-warning',
        'bg-pending',
        'bg-purple-800',
        'bg-orange-800',
    ];

    return color[index % color.length];
};

const exportCsvRecords = (params) => {
    return exportRecords(
        'export-sale-analysis-by-grade-report/',
        'sale_analysis.csv',
        params,
        props.exportPermission
    );
};

const exportExcelRecords = (params) => {
    return exportRecords(
        'export-sale-analysis-by-grade-report/',
        'sale_analysis.xlsx',
        params,
        props.exportPermission
    );
};

const exportPDFRecords = (params) => {
    printReport(route('admin.sale_analysis_by_grade.print_sale_analysis', params), props.exportPermission);
};

const getSearchText = (value) => {
    state.parameters.search_text = value;
    fetchTotals();
};

const showProductData = (id) => {
    router.get(route('admin.products_report.index', { product_id: id, location_id: state.parameters.location_id, date: state.parameters.date }));
};

const showProductArticleNumberData = (articleNumber) => {
    router.get(route('admin.products_report.index', { product_article_number: articleNumber, location_id: state.parameters.location_id, date: state.parameters.date }));
};

const validationCheck = () => {
    if (state.parameters.date === null) {
        return false;
    }

    return true;
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
