<template>
    <PageTitle title="Member Sales Report" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Member Sales Report
        </h2>
    </div>

    <div
        v-if="state.displayMemberSaleReportFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-5">
            <div>
                <FormAjaxSelect
                    :selected-record="state.selectedMember"
                    :search-records="searchMembers"
                    placeholder="Member Name to search..."
                    input-label="Member"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    @update:selected-record="updateMember"
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

            <div>
                <FormSelectBox
                    :selected-record="state.parameters.location_id"
                    :records="locations"
                    input-label="Locations"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    placeholder="Please select location"
                    @update:selected-record="updateLocation"
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
        :fetch-url="route('store_manager.member_sales_report.fetch')"
        :refresh-table-data="state.refreshTableData"
        :additional-query-params="state.parameters"
        :allow-csv-export="true"
        :allow-excel-export="true"
        :export-csv-records-callback="exportCsvRecords"
        :export-excel-records-callback="exportExcelRecords"
        :allow-column-customization="true"
        local-storage-key="store-manager-member-sales-reports-columns"
        search-title="Search by member,mobile number, product, color and size"
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

        <template #info="data">
            <div class="flex justify-center items-center cursor-pointer">
                <List
                    @click="showSaleDetailModal(data.item.id)"
                />
            </div>
        </template>

        <template #extra-header-data>
            <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                <OutlinePrimaryButton
                    text="Filters"
                    class="text-sm shadow-md"
                    @click="state.displayMemberSaleReportFilter = !state.displayMemberSaleReportFilter"
                />
            </p>
        </template>
    </JTable>
    <MemberReportSaleDetails
        v-if="state.displayMemberSalesReportDetails"
        :modal-show="state.displayMemberSalesReportDetails"
        :sale-details="state.saleDetails"
        @close-modal="closeModal"
    />
</template>

<script setup>
import FormAjaxSelect from '@commonComponents/FormAjaxSelect.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JDateTimePicker from '@commonComponents/JDateTimePicker.vue';
import JTable from '@commonComponents/JTable.vue';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import { currentDateTime, displayAmountWithCurrencySymbol, exportRecords } from '@commonServices/helper';
import axios from 'axios';
import { Info, List } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';
import MemberReportSaleDetails from '@adminPages/reports/member_sales_report/MemberReportSaleDetails.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    locations: {
        type: Array,
        required: true,
    },
    defaultSelectedLocationId: {
        type: Number,
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
});

const state = reactive({
    columns: [
        {
            key: 'id',
            isDisplay: true,
        },
        {
            key: 'member',
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
        {
            key: 'info',
            bodyClass: 'text-center',
            headerClass: 'text-center',
        }
    ],
    refreshTableData: Math.random(),
    selectedMember: null,
    selectedProduct: null,
    displayMemberSaleReportFilter: false,
    displayMemberSalesReportDetails: false,
    parameters: {
        member_id: null,
        product_id: null,
        date_range: currentDateTime(),
        location_id: props.defaultSelectedLocationId,
        product_collection_id: null,
    },
});
const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const clearAll = () => {
    state.parameters.date_range = currentDateTime();
    state.parameters.member_id = null;
    state.parameters.product_id = null;
    state.selectedMember = null;
    state.selectedProduct = null;
    state.parameters.product_collection_id = null;

    refreshTable();
};
const updateDate = (date) => {
    state.parameters.date_range = date;
    refreshTable();
};
const updateMember = (selectMember) => {
    state.selectedMember = selectMember;
    state.parameters.member_id = null;
    if (selectMember !== null) {
        state.parameters.member_id = selectMember.id;
    }
    refreshTable();
};

const selectProduct = (selectedProduct) => {
    state.selectedProduct = selectedProduct;
    if (selectProduct !== null) {
        state.parameters.product_id = selectedProduct.id;
    }

    refreshTable();
};

const searchMembers = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };
    axios.get(route('store_manager.members.get_filtered_members', filterData)).then((response) => {
        componentState.records = response.data.members;
        componentState.isLoading = false;
    });
};

const searchProducts = (searchText, componentState) => {
    const filterData = {
        search_text: searchText,
        number_of_records: 5,
    };
    axios.get(route('store_manager.get_filtered_inventory_products', filterData)).then((response) => {
        componentState.records = response.data.products;
        componentState.isLoading = false;
    });
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-member-sales/',
        'member_sales.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-member-sales/',
        'member_sales.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const updateLocation = (location) => {
    state.parameters.location_id = location;
    refreshTable();
};

const closeModal = () => {
    state.displayMemberSalesReportDetails = false;
};

const showSaleDetailModal = (saleItemId) => {
    state.saleDetails = null;
    axios.get(route('store_manager.member_sales_report.fetch_member_report_sale_details', saleItemId))
        .then((response) => {
            state.saleDetails = [response.data.sale_details];
            state.displayMemberSalesReportDetails = true;
        });
};

const updateProductCollectionId = (productCollectionId) => {
    state.parameters.product_collection_id = productCollectionId;
    refreshTable();
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
