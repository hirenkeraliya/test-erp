<template>
    <PageTitle title="Verified Products Report" />

    <div class="flex flex-col items-center mt-6 intro-y sm:flex-row">
        <h2 class="mr-auto text-lg font-medium">
            Verified Products Report
        </h2>
    </div>
    <div
        v-if="state.displayFilters"
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
                <FormAjaxSelect
                    :selected-record="state.selectedProducts"
                    :search-records="searchProducts"
                    input-label="Products"
                    :multi-select="true"
                    placeholder="Product Name/UPC to search..."
                    @update:selected-record="selectProducts"
                />
            </div>
            <div>
                <FormSelectBox
                    :selected-record="state.parameters.is_genuine"
                    :records="state.isGenuineFilter"
                    input-label="Genuine Product"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateIsGenuine"
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
        :fetch-url="route('admin.product_verification_reports.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :allow-pdf-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :export-pdf-records-callback="exportPDFRecords"
        :allow-column-customization="true"
        search-title="Search by product"
    >
        <template #receipt_number="data">
            <div class="flex items-center justify-center cursor-pointer">
                <div class="mr-1">
                    <JBadge
                        :label="data.item.receipt_number"
                        type="primary"
                        @click="showSaleReport(data.item.receipt_number)"
                    />
                </div>
            </div>
        </template>

        <template #extra-header-data>
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
                    @click="state.displayFilters = !state.displayFilters"
                />
            </p>
        </template>

        <template
            v-if="pageProps.product_variant"
            #attributes="data"
        >
            <span>
                <p
                    v-for="(attribute, index) in data.item.attributes"
                    :key="index"
                    class="flex"
                >
                    {{ attribute.name }} : {{ attribute.value }}
                </p>
            </span>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { exportRecords, printReport, currentDateTime } from '@commonServices/helper';
import { reactive, computed } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormSelectBox from "@commonComponents/FormSelectBox.vue";
import JMultiSelect from '@commonComponents/JMultiSelect.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import JBadge from '@commonComponents/JBadge.vue';

const pageProps = computed(() => usePage().props);

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    filterData: {
        type: Object,
        required: true,
    },
    exportPermission: {
        type: String,
        required: true,
    },
});

const state = reactive({
    columns: [
        {
            key: 'name',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'mobile_number',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'email',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'product_name',
            isDisplay: true,
        },
        {
            key: 'upc',
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
                },
                {
                    key: 'size',
                    isDisplay: true,
                },
            ]),
        {
            key: 'is_genuine',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'qr_code',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'receipt_number',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'created_at',
            isDisplay: true,
            sortable: true,
        },
        {
            key: 'remarks',
            isDisplay: true,
        },
    ],
    refreshTableData: Math.random(),
    parameters: {
        product_ids: props.filterData.productIds,
        location_ids: props.filterData.locationIds,
        date_range: props.filterData.dateRange,
        is_genuine: null,
    },
    selectedProducts: props.filterData.selectedProducts,
    displayFilters: false,
    selectedLocations: props.filterData.selectedLocations,
    isGenuineFilter: [
        {
            id: "1",
            name: "Genuine",
        },
        {
            id: "0",
            name: "Fake",
        },
    ],
});

const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateIsGenuine = (value) => {
    state.parameters.is_genuine = value;
    refreshTable();
};

const selectProducts = (selectedProducts) => {
    state.selectedProducts = selectedProducts;
    state.parameters.product_ids = state.selectedProducts.map(function (product) {
        return product.id;
    });
    refreshTable();
};

const selectLocations = (selectedLocations) => {
    state.selectedLocations = selectedLocations;
    const locationIds = selectedLocations.map((location) => {
        return location.id;
    });
    state.parameters.location_ids = locationIds;
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

const refreshPage = () => {
    router.get(route('admin.products_report.index'));
};

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.product_ids = null;
    state.parameters.location_ids = null;
    state.parameters.date_range = currentDateTime();
    state.selectedProducts = null;
    state.selectedLocations = null;

    refreshTable();
};

const showSaleReport = (offlineSaleId) => {
    const url = route('admin.sales.index', { offline_sale_id: offlineSaleId });
    window.open(url, '_blank');
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-products-verification-report/',
        'genuine_product_verification_report.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-products-verification-report/',
        'genuine_product_verification_report.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const exportPDFRecords = (params, columns) => {
    params['export_columns'] = columns;
    printReport(route('admin.product_verification_reports.print_products_verification_report', params), props.exportPermission);
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
