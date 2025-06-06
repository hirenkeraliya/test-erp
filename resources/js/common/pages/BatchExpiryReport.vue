<template>
    <PageTitle title="Batch Expiry" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Batch Expiry
        </h2>
    </div>

    <div
        v-if="state.displayBatchExpiryFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
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
                    input-label="Supplier"
                    :selected-record="state.selectedBrand"
                    :search-records="searchBrand"
                    placeholder="Please type the name of the supplier to search."
                    @update:selected-record="selectBrand"
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
                    :input-value="state.parameters.date_range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    input-label="Date Range"
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

    <JTable
        v-model:columns="state.columns"
        :fetch-url="fetchUrl"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-column-customization="true"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportListPageCsvRecords"
        :export-excel-records-callback="exportListPageExcelRecords"
        :local-storage-key="localStorageKey"
        search-title="Search by name, upc, batch number"
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

        <template #expired_by="record">
            <Tippy
                v-if="record.item.is_expired"
                tag="p"
                class="text-red-600 font-bold"
                content="This Product is expired"
            >
                {{ record.item.expired_by }}
            </Tippy>

            <Tippy
                v-else-if="record.item.is_expired_soon"
                tag="p"
                class="text-yellow-600 font-bold"
                content="This Product will be expired within 30 days"
            >
                {{ record.item.expired_by }}
            </Tippy>

            <span
                v-else
            >
                {{ record.item.expired_by }}
            </span>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-2">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayBatchExpiryFilter = !state.displayBatchExpiryFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import axios from 'axios';
import { reactive } from 'vue';
import { exportRecords } from '@commonServices/helper';
import JDatePicker from '@commonComponents/JDatePicker.vue';

const props = defineProps({
    exportPermission: {
        type: String,
        default: '',
    },
    fetchUrl: {
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
    fetchTags: {
        type: String,
        required: true,
    },
    localStorageKey: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'location',
            label: 'Location',
            isDisplay: true,
        }, {
            key: 'supplier',
            isDisplay: true,
        }, {
            key: 'product',
            isDisplay: true,
        }, {
            key: 'categories',
            label: 'Category',
            isDisplay: true,
        }, {
            key: 'upc',
            label: 'UPC',
            isDisplay: true,
        }, {
            key: 'batch',
            isDisplay: true,
        }, {
            key: 'quantity',
            label: 'Quantity',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        }, {
            key: 'expired_by',
            isDisplay: true,
        },
    ],
    displayBatchExpiryFilter: false,
    refreshTableData: Math.random(),
    selectedCategory: null,
    selectedBrand: null,
    selectedTags: null,
    isClear: false,
    parameters: {
        category_id: null,
        brand_id: null,
        tag_ids: null,
        date_range: null,
    },
});

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
    state.parameters.tag_ids = null;
    if (selectedTags !== null) {
        state.parameters.tag_ids = selectedTags.map(function (tag) {
            return tag.id;
        });
    }
    refreshTable();
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const clearAll = () => {
    state.parameters.category_id = null;
    state.parameters.brand_id = null;
    state.selectedCategory = null;
    state.selectedBrand = null;
    state.selectedTags = null;
    state.parameters.tag_ids = null;
    state.date_range = null;
    refreshTable();
};

const exportListPageCsvRecords = (params, columns) => {
    return exportRecords(
        'export-batch-expiry/',
        'batch-expiry.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportListPageExcelRecords = (params, columns) => {
    return exportRecords(
        'export-batch-expiry/',
        'batch-expiry.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

</script>
