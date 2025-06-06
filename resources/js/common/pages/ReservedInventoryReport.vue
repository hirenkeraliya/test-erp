<template>
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Reserved Inventory
        </h2>
    </div>

    <div
        v-if="state.displayReservedInventoryReportFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div class="mt-3">
                <JTabs
                    :records="locationTypes"
                    :selected-record="state.parameters.type_id"
                    return-selected-record="id"
                    input-label="Location Selection"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateLocationType"
                />
            </div>

            <div>
                <TabPanel
                    v-if="state.parameters.type_id === staticLocationTypes.store"
                    class="active"
                >
                    <FormSelectBox
                        v-model:selected-record="state.store"
                        :records="stores"
                        input-label="Stores"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        placeholder="Please select store"
                        @update:selected-record="updateStore"
                    />
                </TabPanel>

                <TabPanel
                    v-if="state.parameters.type_id === staticLocationTypes.warehouse"
                    class="active"
                >
                    <FormSelectBox
                        v-model:selected-record="state.warehouse"
                        :records="warehouses"
                        input-label="Warehouses"
                        label-class="block font-medium text-base text-primary-p3 mb-2"
                        placeholder="Please select warehouse"
                        @update:selected-record="updateWarehouse"
                    />
                </TabPanel>
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
        </div>
    </div>

    <JTable
        v-model:columns="state.columns"
        :fetch-url="fetchUrl"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        :local-storage-key="localStorageKey"
        search-title="Search by item name, article number, color, size and upc"
    >
        <template #opening_stock="data">
            {{ truncateDecimal(data.item.opening_stock) }}
        </template>

        <template #reference="data">
            <a
                v-if="data.item.reference.url"
                class="text-blue-700 underline font-bold"
                :href="data.item.reference.url"
                target="_blank"
            >
                {{ data.item.reference.message }}
            </a>

            <p v-else>
                {{ data.item.reference.message }}
            </p>
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
                    {{ product_variant.attribute.name }} : {{ product_variant.value }}
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
                    :label="'Stock: ' + truncateDecimal(record.data.total_stock ? record.data.total_stock : 0)"
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
                    @click="state.displayReservedInventoryReportFilter = !state.displayReservedInventoryReportFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { TabPanel } from '@commonVendor/tab';
import JTabs from '@commonComponents/JTabs.vue';
import { reactive, onMounted, computed } from 'vue';
import { exportRecords, truncateDecimal } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { router } from '@inertiajs/vue3';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import { route } from 'ziggy';
import axios from 'axios';
import JBadge from '@commonComponents/JBadge.vue';
import { usePage } from "@inertiajs/vue3";

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
    filterData: {
        type: Object,
        required: true,
    },
    fetchUrl: {
        type: String,
        required: true,
    },
    redirectUrl: {
        type: String,
        required: true,
    },
    localStorageKey: {
        type: String,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
    getFilteredProductUrl: {
        type: String,
        required: true,
    },
    productCollections: {
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
});

const state = reactive({
    store: parseInt(props.filterData.location_id) ?? null,
    warehouse: parseInt(props.filterData.location_id) ?? null,
    isClear: false,
    selectedProduct: props.filterData.selectedProduct,

    parameters: {
        type_id: props.staticLocationTypes.store,
        location_id: props.filterData.location_id ?? null,
        product_id: props.filterData.product_id,
        product_collection_id: null,
    },

    columns: [
        {
            key: 'item_name',
            isDisplay: true,
        },
        {
            key: 'article_number',
            isDisplay: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
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
                },
                {
                    key: 'size',
                    isDisplay: true,
                },
            ]),
        {
            key: 'upc',
            isDisplay: true,
        },
        {
            key: 'reference',
            isDisplay: true,
            bodyClass: 'text-left',
            headerClass: 'text-left',
        },
        {
            key: 'stock',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        },
    ],
    refreshTableData: Math.random(),
    displayReservedInventoryReportFilter: false,
});

const refreshPage = () => {
    router.get(props.redirectUrl);
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const updateLocationType = (locationType) => {
    state.parameters.type_id = locationType;
    state.parameters.location_id = null;
};

const updateStore = (store) => {
    state.parameters.location_id = store;
    refreshTable();
};

const updateWarehouse = (warehouses) => {
    state.parameters.location_id = warehouses;
    refreshTable();
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
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

const searchProducts = (searchText, componentState) => {
    axios.get(route(props.getFilteredProductUrl), {
        params: {
            search_text: searchText,
            number_of_records: 5,
        }
    }).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-reserved-inventory/',
        'reserved_inventory.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-reserved-inventory/',
        'reserved_inventory.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

onMounted(() => {
    if (props.filterData.product_id != null) {
        state.parameters.location_id = props.filterData.location_id;
        state.parameters.type_id = parseInt(props.filterData.type_id);
        state.isClear = true;
        state.displayReservedInventoryReportFilter = true;
        state.parameters.product_id = props.filterData.product_id;
        refreshTable();
    }
});
</script>
