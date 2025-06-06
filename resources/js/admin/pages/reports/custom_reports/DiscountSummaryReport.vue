<template>
    <PageTitle title="Discount Summary Report" />

    <div class="grid grid-cols-12 gap-0 sm:gap-6">
        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3">
            <JMultiSelect
                :selected-records="state.locations"
                :records="locations"
                input-label="Location"
                placeholder="Please select location"
                @update:selected-records="updateLocations"
            />
        </div>
        <div class="w-full lg:w-1/2 px-3 mt-2 sm:mt-2 lg:mt-8">
            <PrimaryButton
                type="button"
                text="Select all"
                class="w-auto sm:w-24 md:w-1/1"
                @click="selectAllLocations"
            />

            <OutlinePrimaryButton
                v-if="state.displayClearButton"
                type="button"
                text="Clear All"
                class="w-auto sm:w-24 md:w-1/1 mt-2"
                @click="clearAllLocations"
            />
        </div>

        <div
            v-if="state.parameters.location_ids !== null"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-6"
        >
            <FormSelectBox

                :selected-record="state.parameters.sale_discount_type"
                :records="saleDiscountTypes"
                input-label="Discount Type"
                placeholder="Please select Discount Type"
                :required="true"
                @update:selected-record="updateDiscountType"
            />
        </div>

        <div
            v-if="state.parameters.location_ids !== null && state.parameters.sale_discount_type === saleDiscountTypesStaticFilters.cartWise"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-2"
        >
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="state.saleDiscountTypeReports"
                input-label="Report Type"
                placeholder="Please select Report Type"
                :required="true"
            />
        </div>

        <div
            v-if="state.parameters.location_ids !== null && state.parameters.sale_discount_type === saleDiscountTypesStaticFilters.itemWise"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                v-model:selected-record="state.parameters.report_type"
                :records="state.discountTypeReports"
                input-label="Report Type"
                placeholder="Please select Report Type"
            />
        </div>

        <div
            v-show="state.parameters.report_type !== null && state.parameters.sale_discount_type === saleDiscountTypesStaticFilters.itemWise"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <div class="block sm:flex items-center">
                <FormSelectBox
                    v-show="state.parameters.location_ids !== null"
                    :selected-record="state.parameters.filter_by"
                    :records="discountTypeFilter"
                    input-label="Filter By"
                    placeholder="Filter By"
                    class="w-full"
                    @update:selected-record="updateTheFilterBy"
                />
                <div
                    v-if="state.parameters.filter_by"
                    class="ml-0 sm:ml-2 flex flex-col sm:flex-row mt-2 sm:mt-7"
                >
                    <PrimaryButton
                        type="button"
                        text="Clear"
                        class="btn-sm w-24 h-10"
                        @click="clearFilters"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="state.parameters.filter_by === discountTypeStaticFilters.byBrand"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.brandIds"
                :records="state.brands"
                input-label="Brands"
                placeholder="Please select brand(s)"
                :required="true"
                @update:selected-records="updateBrandId"
            />
        </div>

        <div
            v-if="state.departments && state.parameters.filter_by === discountTypeStaticFilters.byDepartment"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.departmentIds"
                :records="state.departments"
                input-label="Departments"
                :placeholder="'Please select Department(s)'"
                :required="true"
                @update:selected-records="updateDepartmentIds"
            />
        </div>

        <div
            v-if="state.styles && state.parameters.filter_by === discountTypeStaticFilters.byStyle"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.styleIds"
                :records="state.styles"
                input-label="Styles"
                :placeholder="'Please select Style(s)'"
                :required="true"
                @update:selected-records="updateStyleId"
            />
        </div>

        <div
            v-if="state.tags && state.parameters.filter_by === discountTypeStaticFilters.byTag"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                :selected-records="state.tagIds"
                :records="state.tags"
                input-label="Tags"
                :placeholder="'Please select Tag(s)'"
                :required="true"
                @update:selected-records="updateTagId"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === discountTypeStaticFilters.byProduct"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JProductFilter
                :product-search-url="route('admin.get_filtered_inventory_products')"
                get-product-url-name="admin.get_product"
                :selected-product-id="state.parameters.product_id"
                :show-product-filters="false"
                validation-field-name="product_id"
                input-label="Product"
                @update:product-selected="productSelected($event, itemIndex)"
            />
        </div>

        <div
            v-if="state.parameters.filter_by === discountTypeStaticFilters.byProductCollection"
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
            v-if="state.parameters.filter_by === discountTypeStaticFilters.byMasterProduct"
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
            v-if="state.parameters.filter_by === discountTypeStaticFilters.byAttributes"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <FormSelectBox
                :selected-record="state.parameters.attribute_type"
                :records="attributes"
                :required="true"
                input-label="Attribute Types"
                label-class=""
                @update:selected-record="updateAttributeBy"
            />
        </div>

        <div
            v-if="(state.parameters.filter_by === discountTypeStaticFilters.byAttributes) && state.parameters.attribute_type"
            class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3"
        >
            <JMultiSelect
                v-if="state.attributeOptions && state.attributeOptions.options"
                :selected-records="state.attributeIds"
                :records="state.attributeOptions.options"
                :input-label="state.attributeOptions.name"
                :placeholder="state.attributeOptions.name"
                :required="true"
                @update:selected-records="updateAttributeId"
            />
        </div>

        <div class="input-form col-span-12 sm:col-span-6 md:col-span-6 lg:col-span-4 xl:col-span-3 ml-6">
            <JDatePicker
                v-if="state.parameters.location_ids !== null"
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
                @click="exportSalesCollection"
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
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { route } from 'ziggy';
import JDatePicker from '@commonComponents/JDatePicker.vue';
import OutlineDangerButton from '@commonComponents/OutlineDangerButton.vue';
import PrimaryButton from '@commonComponents/PrimaryButton.vue';
import { showErrorNotification } from '@commonServices/notifier';
import { exportRecords, printReport } from '@commonServices/helper';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import axios from 'axios';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    discountTypeFilter: {
        type: Object,
        required: true,
    },
    discountTypeStaticFilters: {
        type: Object,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    saleDiscountTypes: {
        type: Object,
        required: true,
    },
    saleDiscountTypesStaticFilters: {
        type: Object,
        required: true,
    },
    saleDiscountTypeReports: {
        type: Object,
        required: true,
    },
    saleDiscountTypeReportStaticFilters: {
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
    parameters: {
        location_ids: null,
        date_range: null,
        filter_by: null,
        brand_ids: null,
        tag_ids: null,
        style_ids: null,
        department_ids: null,
        report_type: null,
        article_number: null,
        product_id: null,
        product_collection_id: null,
        sale_discount_type: null,
        attribute_type: null,
        attribute_values: null,
    },
    locations: null,
    brands: [],
    brandIds: [],
    discountTypeReports: [],
    saleDiscountTypeReports: [],
    departments: null,
    departmentIds: null,
    styles: null,
    styleIds: null,
    tags: null,
    tagIds: null,
    selectArticleNumbers: null,
    displayClearButton: false,
    attributeOptions: [],
    attributeIds:[],
});

const updateLocations = (locations) => {
    if (locations.length <= 0) {
        state.locations = null;
        state.parameters.location_ids = null;
        return;
    }

    state.locations = locations;
    const locationIds = locations.map((location) => {
        return location.id;
    });

    state.parameters.location_ids = locationIds;
};

const clearFilters = () => {
    state.parameters.filter_by = null;
    state.parameters.brand_ids = [];
    state.parameters.department_ids = [];
    state.parameters.tag_ids = [];
    state.parameters.style_ids = [];
    state.parameters.article_number = null;
    state.parameters.product_id = null;
    state.parameters.product_collection_id = null;
};

const updateAttributeBy = (attributeType) => {
    state.parameters.attribute_type = attributeType;
    state.attributeIds = [];
    state.attributeOptions = [];
    state.parameters.attribute_values = null;
    if (attributeType !== null && attributeType !== undefined) {
        axios.get(route('admin.attributes.fetch-attribute-options',attributeType))
            .then((response) => {
                state.attributeOptions = response.data.attributeOptions;
            });
    }
};
const updateAttributeId = (attributeIds) => {
    state.attributeIds = attributeIds;
    state.parameters.attribute_values = [];
    if (state.attributeIds.length) {
        state.parameters.attribute_values = state.attributeIds.map((attribute) => {
            return attribute.id;
        });
    }
};

const updateTheFilterBy = (filterBy) => {
    state.parameters.filter_by = filterBy;
    state.brands = [];
    state.departments = [];
    state.tags = [];
    state.styles = [];
    state.parameters.product_id = null;
    state.parameters.article_number = null;
    state.selectArticleNumbers = [];
    state.parameters.product_collection_id = null;
    state.parameters.attribute_type = null;
    state.attributeOptions = [];
    state.parameters.attribute_values = null;
    state.parameters.department_ids = [];
    state.parameters.tag_ids = [];
    state.parameters.style_ids = [];
    state.parameters.brand_ids = [];

    removeSelectedFilter();

    if (filterBy === props.discountTypeStaticFilters.byBrand) {
        axios.post(route('admin.brands.get_brands'))
            .then((response) => {
                state.brands = response.data.brands;
            });
    }

    if (filterBy === props.discountTypeStaticFilters.byDepartment) {
        axios.get(route('admin.departments.get_departments_list'))
            .then((response) => {
                state.departments = response.data.departments;
            });
    }

    if (filterBy === props.discountTypeStaticFilters.byTag) {
        axios.get(route('admin.tags.get_tags_list'))
            .then((response) => {
                state.tags = response.data.tags;
            });
    }

    if (filterBy === props.discountTypeStaticFilters.byStyle) {
        axios.get(route('admin.styles.get_styles_list'))
            .then((response) => {
                state.styles = response.data.styles;
            });
    }
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

const productSelected = (selectedProduct) => {
    if (selectedProduct) {
        state.parameters.article_number = null;
        state.selectArticleNumbers = null;
        state.parameters.brand_ids = null;
        state.parameters.department_ids = null;
        state.parameters.tag_ids = null;
        state.parameters.style_ids = null;
        state.parameters.product_id = selectedProduct.id;

        return;
    }
    state.parameters.product_id = null;
};

const updateDepartmentIds = (departmentIds) => {
    state.departmentIds = departmentIds;
    state.parameters.brand_ids = [];
    state.parameters.tag_ids = [];
    state.parameters.style_ids = [];
    state.parameters.department_ids = state.departmentIds.map((department) => {
        return department.id;
    });
};

const updateBrandId = (brandIds) => {
    state.brandIds = brandIds;
    state.parameters.department_ids = [];
    state.parameters.tag_ids = [];
    state.parameters.style_ids = [];
    state.parameters.brand_ids = [];
    if (state.brandIds.length) {
        state.parameters.brand_ids = state.brandIds.map((brand) => {
            return brand.id;
        });
    }
};

const removeSelectedFilter = () => {
    state.brandIds = [];
    state.departmentIds = null;
    state.styleIds = null;
    state.tagIds = null;
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
};

const updateDiscountType = (discountType) => {
    state.parameters.sale_discount_type = discountType;
    clearFilters();
};

const updateStyleId = (styleIds) => {
    state.styleIds = styleIds;
    state.parameters.department_ids = [];
    state.parameters.brand_ids = [];
    state.parameters.tag_ids = [];
    state.parameters.style_ids = [];
    if (state.styleIds.length) {
        state.parameters.style_ids = state.styleIds.map((style) => {
            return style.id;
        });
    }
};

const updateTagId = (tagIds) => {
    state.tagIds = tagIds;
    state.parameters.department_ids = [];
    state.parameters.brand_ids = [];
    state.parameters.style_ids = [];
    state.parameters.tag_ids = [];
    if (state.tagIds.length) {
        state.parameters.tag_ids = state.tagIds.map((tag) => {
            return tag.id;
        });
    }
};

const validationCheck = () => {
    if (state.parameters.location_ids === null) {
        return true;
    }

    if (state.parameters.date_range === null) {
        return true;
    }

    return false;
};

const exportSalesCollection = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a location and a date before proceeding.');
        return;
    }

    printReport(route('admin.custom_reports.print_discount_summary_report', state.parameters));
};

const exportExcelRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a location and a date before proceeding..');
        return;
    }

    return exportRecords(
        'export-discount-summary-report/',
        'discount-summary-report.xlsx',
        state.parameters
    );
};

const exportCsvRecord = () => {
    if (validationCheck()) {
        showErrorNotification('Please select a location and a date before proceeding..');
        return;
    }

    return exportRecords(
        'export-discount-summary-report/',
        'discount-summary-report.csv',
        state.parameters
    );
};

const clearData = () => {
    emits('update:clear-button');
};

const selectAllLocations = () => {
    state.locations = props.locations;
    updateLocations(state.locations);
    state.displayClearButton = true;
};

const clearAllLocations = () => {
    state.locations = null;
    state.displayClearButton = false;
    state.parameters.location_ids = null;
    state.parameters.date_range = null;
    state.parameters.report_type = null;
    clearFilters();
};

onMounted(() => {
    axios.get(route('admin.custom_reports.get_discount_type_reports'))
        .then((response) => {
            state.discountTypeReports = response.data.discountTypeReports;
        });
    axios.get(route('admin.custom_reports.get_sale_discount_type_reports'))
        .then((response) => {
            state.saleDiscountTypeReports = response.data.saleDiscountTypeReports;
        });
});
</script>
