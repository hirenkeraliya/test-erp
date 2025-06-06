<template>
    <PageTitle title="Employee Sales Report" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Employee Sales Report
        </h2>
    </div>

    <div
        v-if="state.displayEmployeeSalesReportFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedEmployee"
                    :search-records="searchEmployees"
                    placeholder="Employee Name to search..."
                    input-label="Employee"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateEmployee"
                />
            </div>

            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedProduct"
                    :search-records="searchProducts"
                    placeholder="Product Name/UPC to search..."
                    input-label="UPC"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
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
                <JDateTimePicker
                    :range-picker="true"
                    :input-value="state.parameters.date_range"
                    input-label="Date Range"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:input-value="updateDate($event)"
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
        v-model:columns="state.columns"
        :fetch-url="route('admin.employee_sales_report.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="admin-employee-sales-reports-columns"
        search-title="Search by product"
    >
        <template #id="data">
            <div class="flex justify-left items-center">
                <span>
                    {{ data.item.id }}
                </span>
                <Tippy
                    v-if="data.item.sale_mismatches.length > 0"
                    :content="'There are ' + data.item.sale_mismatches.length + ' mismatches on this member sale.'"
                >
                    <Info
                        class="text-danger ml-2"
                        :size="15"
                    />
                </Tippy>
            </div>
        </template>

        <template #product="data">
            <div class="flex items-center">
                {{ data.item.product }}

                <Tippy
                    v-if="data.item.upc"
                    :content="'UPC: ' + data.item.upc"
                >
                    <Info
                        class="text-cyan-400 ml-2"
                        :size="15"
                    />
                </Tippy>
            </div>
        </template>

        <template #price="data">
            {{ displayAmountWithCurrencySymbol(data.item.price) }}
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

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayEmployeeSalesReportFilter = !state.displayEmployeeSalesReportFilter"
                />
            </p>
        </template>
    </JTable>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import { displayAmountWithCurrencySymbol, exportRecords, currentDateTime } from '@commonServices/helper';
import { Info } from 'lucide-vue-next';
import { reactive, computed } from 'vue';
import { route } from 'ziggy';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import axios from 'axios';
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import { usePage } from "@inertiajs/vue3";

const pageProps = computed(() => usePage().props);

const props = defineProps({
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
});

const state = reactive({
    columns: [
        {
            key: 'id',
            isDisplay: true,
        },
        {
            key: 'employee',
            isDisplay: true,
        },
        {
            key: 'mobile_number',
            isDisplay: true,
        },
        {
            key: 'product',
            bodyClass: 'text-left',
            headerClass: 'text-left',
            isDisplay: true,
            sortable: true,
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
            key: 'units_sold',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'units_returned',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'price',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            isDisplay: true,
        },
    ],
    refreshTableData: Math.random(),
    selectedEmployee: null,
    selectedProduct: null,
    displayEmployeeSalesReportFilter: false,
    parameters: {
        employee_id: null,
        product_id: null,
        product_collection_id: null,
        date_range: currentDateTime(),
    },
});
const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    state.parameters.employee_id = null;
    state.parameters.product_id = null;
    state.selectedEmployee = null;
    state.selectedProduct = null;
    state.parameters.product_collection_id = null;

    refreshTable();
};
const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};

const updateEmployee = (selectEmployee) => {
    state.selectedEmployee = selectEmployee;
    state.parameters.employee_id = selectEmployee ? selectEmployee.id : null;
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

const searchEmployees = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };
    axios.get(route('admin.employees.get_filtered_employees', filterData)).then((response) => {
        componentState.records = response.data.employees;
        componentState.isLoading = false;
    });
};

const searchProducts = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };
    axios.get(route('admin.get_filtered_inventory_products', filterData)).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
    refreshTable();
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-employee-sales/',
        'employee_sales.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-employee-sales/',
        'employee_sales.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
