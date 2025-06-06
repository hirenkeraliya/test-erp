<template>
    <PageTitle title="Custom Reports" />

    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Custom Reports
        </h2>
    </div>

    <div class="intro-y col-span-12 lg:col-span-12">
        <div class="mb-12 grid gap-y-10 gap-x-6 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 mt-5">
            <template
                v-for="(menu, menuKey) in customReportMenus"
                :key="menuKey"
            >
                <div class="relative flex flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md">
                    <div
                        class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-primary to-primary text-white shadow-primary-500/40 shadow-lg absolute -mt-4 grid h-10 w-10 place-items-center right-0"
                    >
                        <component
                            :is="Clipboard"
                            class="h-5 w-5"
                        />
                    </div>
                    <div class="p-4 pr-20 text-left">
                        <h4
                            class="block antialiased tracking-normal text-xl font-semibold leading-snug text-gray-900"
                            v-text="menu.name"
                        />
                    </div>

                    <div class="border-t border-gray-200 p-4">
                        <ul>
                            <span
                                v-for="(subMenu, subMenuKey) in menu.subMenu"
                                :key="subMenuKey"
                            >
                                <li
                                    :class="{ 'mb-3 pb-3 border-b border-gray-200 test-1': subMenuKey < (menu.subMenu.length - 1) }"
                                    class="cursor-pointer flex items-center text-left"
                                    @click="showReportModal(subMenu)"
                                >
                                    <div class="top-menu__icon mr-2">
                                        <component
                                            :is="Clipboard"
                                            class="text-primary"
                                        />
                                    </div>
                                    <div
                                        class="top-menu__title"
                                        v-text="subMenu.name"
                                    />
                                </li>
                            </span>
                        </ul>
                    </div>
                </div>
            </template>
        </div>

        <CustomReportModal
            v-if="state.displayReportModal"
            :display-report-modal="state.displayReportModal"
            :custom-report-selected="state.customReportSelected"
            @update:hide-report-modal="hideReportModal"
        >
            <template #[state.customReportSelected.name]>
                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockMovements"
                    class="mt-4"
                >
                    <StockMovement
                        :stock-movement-filters="stockMovementFilters"
                        :stock-movement-report-types="stockMovementReportTypes"
                        :stock-movement-filter-static-details="stockMovementFilterStaticDetails"
                        :product-collections="productCollections"
                        @update:clear-button="clearData"
                    />
                </div>
                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockCard"
                    class="mt-4"
                >
                    <StockCard
                        :product-collections="productCollections"
                        :stock-card-filter="stockCardFilter"
                        :stock-card-filter-static-details="stockCardFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>
                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockTransfer ||
                        state.customReportSelected.name === inventoryReportsStaticDetails.stockTransferByStatus"
                    class="mt-4"
                >
                    <StockTransfer
                        :stores="state.stores"
                        :product-collections="productCollections"
                        :warehouses="state.warehouses"
                        :stock-transfer-filters="stockTransferFilters"
                        :stock-transfer-transfer-type="stockTransferTransferType"
                        :stock-transfer-report-date-types="stockTransferReportDateTypes"
                        :stock-transfer-report-type="stockTransferReportType"
                        :stock-transfer-statuses="stockTransferStatuses"
                        :is-status-allowed="state.customReportSelected.name === inventoryReportsStaticDetails.stockTransferByStatus ? true : false"
                        :stock-transfer-filter-static-details="stockTransferFilterStaticDetails"
                        :transfer-type-out="transferTypeOut"
                        :transfer-type-in="transferTypeIn"
                        :location-types="locationTypes"
                        :static-location-types="staticLocationTypes"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockTransferDiscrepancy"
                    class="mt-4"
                >
                    <StockTransferDiscrepancy
                        :product-collections="productCollections"
                        :stock-transfer-filters="stockTransferFilters"
                        :stock-transfer-transfer-type="stockTransferDiscrepancyTransferType"
                        :stock-transfer-report-date-types="stockTransferReportDateTypes"
                        :stock-transfer-report-type="transferDiscrepancyReportType"
                        :stock-transfer-filter-static-details="stockTransferFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.goodsReceivedNotes"
                    class="mt-4"
                >
                    <GoodsReceivedNotes
                        :product-collections="productCollections"
                        :goods-received-note-filters="goodsReceivedNoteFilters"
                        :goods-received-note-report-types="goodsReceivedNoteReportTypes"
                        :goods-received-note-filter-static-details="goodsReceivedNoteFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockAdjustment"
                    class="mt-4"
                >
                    <StockAdjustmentReport
                        :product-collections="productCollections"
                        :stock-adjustment-types="stockAdjustmentTypes"
                        :stock-adjustment-report-type="stockAdjustmentReportType"
                        :stock-adjustment-filter-type="stockAdjustmentFilterType"
                        :static-stock-adjustment-filter-type="staticStockAdjustmentFilterType"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === purchasingReportsStaticDetails.interCompanyTransfer"
                    class="mt-4"
                >
                    <InterCompanyStockTransfer
                        :external-companies="externalCompanies"
                        :product-collections="productCollections"
                        :purchase-order-filters="purchaseOrderFilters"
                        :inter-company-transfer-type="interCompanyTransferType"
                        :inter-company-report-type="interCompanyReportType"
                        :inter-company-filter-static-details="interCompanyFilterStaticDetails"
                        :delivery-order-static-type="deliveryOrderStaticType"
                        :location-types="locationTypes"
                        :static-location-types="staticLocationTypes"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === purchasingReportsStaticDetails.interCompanyTransferInvoices"
                    class="mt-4"
                >
                    <InterCompanyInvoices
                        :product-collections="productCollections"
                        :external-companies="externalCompanies"
                        :purchase-order-filters="purchaseOrderFilters"
                        :inter-company-transfer-type="interCompanyTransferType"
                        :inter-company-filter-static-details="interCompanyFilterStaticDetails"
                        :location-types="locationTypes"
                        :static-location-types="staticLocationTypes"
                        @update:clear-button="clearData"
                    />
                </div>
            </template>
        </CustomReportModal>
    </div>
</template>

<script setup>
import StockAdjustmentReport from '@warehouseManagerPages/reports/custom_reports/StockAdjustmentReport.vue';
import StockMovement from '@warehouseManagerPages/reports/custom_reports/StockMovement.vue';
import StockCard from '@warehouseManagerPages/reports/custom_reports/StockCard.vue';
import StockTransfer from '@warehouseManagerPages/reports/custom_reports/StockTransfer.vue';
import StockTransferDiscrepancy from '@warehouseManagerPages/reports/custom_reports/StockTransferDiscrepancy.vue';
import GoodsReceivedNotes from '@warehouseManagerPages/reports/custom_reports/GoodsReceivedNotes.vue';
import InterCompanyStockTransfer from '@warehouseManagerPages/reports/custom_reports/InterCompanyStockTransfer.vue';
import InterCompanyInvoices from '@warehouseManagerPages/reports/custom_reports/InterCompanyInvoices.vue';
import { reactive } from 'vue';
import axios from 'axios';
import ObjectStorage from '@commonServices/storage.js';
import { route } from 'ziggy';
import { Clipboard } from 'lucide-vue-next';
import CustomReportModal from '@commonComponents/CustomReportModal.vue';

const props = defineProps({
    inventoryReports: {
        type: Object,
        required: true,
    },
    purchasingReports: {
        type: Object,
        required: true,
    },
    inventoryReportsStaticDetails: {
        type: Object,
        required: true,
    },
    purchasingReportsStaticDetails: {
        type: Object,
        required: true,
    },
    reportTypesStaticDetails: {
        type: Object,
        required: true,
    },
    customReportMenus: {
        type: Object,
        required: true,
    },
    stockTransferFilters: {
        type: Object,
        required: true,
    },
    stockTransferTransferType: {
        type: Object,
        required: true,
    },
    stockTransferReportDateTypes: {
        type: Object,
        required: true,
    },
    stockTransferFilterStaticDetails: {
        type: Object,
        required: true,
    },
    goodsReceivedNoteFilters: {
        type: Object,
        required: true,
    },
    goodsReceivedNoteReportTypes: {
        type: Object,
        required: true,
    },
    goodsReceivedNoteFilterStaticDetails: {
        type: Object,
        required: true,
    },
    stockCardFilter: {
        type: Object,
        required: true,
    },
    stockCardFilterStaticDetails: {
        type: Object,
        required: true,
    },
    stockMovementFilters: {
        type: Object,
        required: true,
    },
    stockMovementReportTypes: {
        type: Object,
        required: true,
    },
    stockMovementFilterStaticDetails: {
        type: Object,
        required: true,
    },
    stockTransferReportType: {
        type: Object,
        required: true,
    },
    stockAdjustmentReportType: {
        type: Object,
        required: true,
    },
    stockAdjustmentFilterType: {
        type: Object,
        required: true,
    },
    staticStockAdjustmentFilterType: {
        type: Object,
        required: true,
    },
    stockAdjustmentTypes: {
        type: Object,
        required: true,
    },
    stockTransferStatuses: {
        type: Object,
        required: true,
    },
    transferDiscrepancyReportType: {
        type: Object,
        required: true,
    },
    stockTransferDiscrepancyTransferType: {
        type: Object,
        required: true,
    },
    purchaseOrderFilters: {
        type: Object,
        required: true,
    },
    interCompanyTransferType: {
        type: Object,
        required: true,
    },
    interCompanyReportType: {
        type: Object,
        required: true,
    },
    interCompanyFilterStaticDetails: {
        type: Object,
        required: true,
    },
    externalCompanies: {
        type: Array,
        required: true,
    },
    transferTypeOut: {
        type: Number,
        required: true,
    },
    transferTypeIn: {
        type: Number,
        required: true,
    },
    deliveryOrderStaticType: {
        type: Number,
        required: true,
    },
    productCollections: {
        type: Array,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
        required: true,
    }
});

const state = reactive({
    customReportSelected: null,
    reportTypeSelected: null,
    stores: [],
    warehouses: [],
    displayReportModal: false,
});

const hideReportModal = () => {
    state.displayReportModal = false;
};

const showReportModal = (reportType) => {
    state.displayReportModal = true;
    state.customReportSelected = reportType;
    getStoresAndWareHouses();
};

const clearData = () => {
    hideReportModal();
};

const getStoresAndWareHouses = () => {
    const httpStatusOk = 200;
    if (state.customReportSelected.name === props.inventoryReportsStaticDetails.stockTransfer ||
        state.customReportSelected.name === props.inventoryReportsStaticDetails.stockTransferByStatus
    ) {
        axios.get(route('warehouse_manager.custom_reports.get_stores_and_warehouses')).then((response) => {
            if (response.status === httpStatusOk) {
                filterWarehouses(response.data.warehouses);
                state.stores = response.data.stores;
            }
        });
    }
};

function filterWarehouses (warehouses) {
    state.warehouses = warehouses.filter((warehouse) => {
        return warehouse.id !== ObjectStorage.get('warehouse-manager-warehouse-id');
    });
}
</script>
