<template>
    <PageTitle title="Stock Movement Ledger Report" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Stock Movement Ledger Report
        </h2>

        <InfoAlert
            v-if="!state.parameters.product_id"
            color="primary"
            class="mb-3 mt-5"
        >
            Please choose a product first, and then you can view the Stock Movement Ledger Report for that specific product.
        </InfoAlert>
    </div>

    <div
        v-if="state.displayStockMovementReportFilter"
        class="mt-2 px-5 py-5 pt-0.5 bg-slate-200 rounded-2xl intro-x"
    >
        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-x-5">
            <div>
                <JProductFilter
                    :product-search-url="route('store_manager.get_filtered_inventory_products')"
                    get-product-url-name="store_manager.get_product"
                    :selected-product-id="state.parameters.product_id"
                    input-label="Product"
                    :required="true"
                    label-class="block font-medium text-base text-primary-p3 mb-2"
                    filter-button-class="mt-11"
                    @update:product-selected="productSelected($event)"
                    @update:display-product-filters="displayUpdateFilter()"
                />
            </div>
        </div>
    </div>
    <div v-if="state.parameters.product_id !== null">
        <JTable
            v-model:columns="state.columns"
            :fetch-url="route('store_manager.stock_movement_ledger_report.fetch')"
            :refresh-table-data="state.refreshTableData"
            :additional-query-params="state.parameters"
            :allow-csv-export="true"
            :allow-excel-export="true"
            :export-csv-records-callback="exportCsvRecords"
            :export-excel-records-callback="exportExcelRecords"
            :allow-column-customization="true"
            local-storage-key="store-manager-stock-movement-ledger-reports-columns"
            search-title="Search by location details"
        >
            <template #opening_stock="data">
                {{ truncateDecimal(data.item.opening_stock) }}
            </template>

            <template #closing_stock="data">
                {{ truncateDecimal(data.item.closing_stock) }}
            </template>

            <template #reference_number="data">
                <a
                    v-if="data.item.reference_number.url"
                    class="text-blue-700 underline font-bold"
                    :href="data.item.reference_number.url"
                    target="_blank"
                >
                    {{ data.item.reference_number.message }}
                </a>

                <p v-else>
                    {{ data.item.reference_number.message }}
                </p>
            </template>

            <template #extra-header-data>
                <p class="text-lg font-bold mr-1 sm:mr-2 float-left sm:float-none">
                    <OutlinePrimaryButton
                        text="Filters"
                        class="text-sm shadow-md"
                        @click="state.displayStockMovementReportFilter = !state.displayStockMovementReportFilter"
                    />
                </p>
            </template>
        </JTable>

        <JProductFilterDetails
            :modal-show="state.displayFilterModal"
            :product-search-url="route('store_manager.get_filtered_inventory_products_list')"
            :filtered-category-url="route('store_manager.categories.get_filtered_categories')"
            :filtered-brand-url="route('store_manager.brands.get_filtered_brands')"
            @update:product-selected="filteredProductSelected"
            @close-modal="state.displayFilterModal = false"
        />
    </div>
</template>

<script setup>
import JTable from '@commonComponents/JTable.vue';
import JProductFilter from '@commonComponents/JProductFilter.vue';
import JProductFilterDetails from '@commonComponents/JProductFilterDetails.vue';
import { reactive } from 'vue';
import { route } from 'ziggy';
import { exportRecords, truncateDecimal } from '@commonServices/helper';
import OutlinePrimaryButton from '@commonComponents/OutlinePrimaryButton.vue';
import InfoAlert from '@commonComponents/InfoAlert.vue';
import { useHelpCenterStore } from '@commonStores/helpCenter';

const props = defineProps({
    exportPermission: {
        type: String,
        required: true,
    },
    helpCenterMessages: {
        type: String,
        required: true,
    },
});

const state = reactive({
    parameters: {
        product_id: null,
    },

    columns: [
        {
            key: 'date',
            isDisplay: true,
        },
        {
            key: 'opening_stock',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'from_location',
            label: 'From',
            isDisplay: true,
        },
        {
            key: 'to_location',
            label: 'To',
            isDisplay: true,
        },
        {
            key: 'location_details',
            label: 'Location Code',
            isDisplay: true,
        },
        {
            key: 'updates',
            isDisplay: true,
            label: 'Stock Update',
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
        {
            key: 'reference_number',
            label: 'Reference',
            isDisplay: true,
        },
        {
            key: 'closing_stock',
            isDisplay: true,
            bodyClass: 'text-right',
            headerClass: 'text-right',
        },
    ],

    displayFilterModal: false,
    refreshTableData: Math.random(),
    displayStockMovementReportFilter: true,
});

const refreshTable = () => {
    state.refreshTableData = Math.random();
};

const productSelected = (selectedProduct) => {
    if (selectedProduct) {
        state.parameters.product_id = selectedProduct.id;
        refreshTable();
    }
};

const displayUpdateFilter = () => {
    state.displayFilterModal = true;
};

const filteredProductSelected = (selectedProduct) => {
    state.displayFilterModal = false;
    productSelected(selectedProduct);
};

const exportCsvRecords = (params, columns) => {
    return exportRecords(
        'export-stock-movement-ledger/',
        'stock_movement_ledger.csv',
        params,
        props.exportPermission,
        columns
    );
};

const exportExcelRecords = (params, columns) => {
    return exportRecords(
        'export-stock-movement-ledger/',
        'stock_movement_ledger.xlsx',
        params,
        props.exportPermission,
        columns
    );
};

const helpStore = useHelpCenterStore();
helpStore.setHelpData(props.helpCenterMessages);
</script>
