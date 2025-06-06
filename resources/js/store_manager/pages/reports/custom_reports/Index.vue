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
                >
                    <StockMovement
                        :stock-movement-filters="stockMovementFilters"
                        :product-collections="productCollections"
                        :stock-movement-report-types="stockMovementReportTypes"
                        :stock-movement-filter-static-details="stockMovementFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.hourlySalesReport"
                >
                    <SaleHour
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.salesCollection"
                >
                    <SalesCollection
                        :sales-collection-filters="salesCollectionFilters"
                        :sales-collection-reports="salesCollectionReports"
                        :sales-collection-report-static-details="salesCollectionReportStaticDetails"
                        :sales-collection-filter-static-details="salesCollectionFilterStaticDetails"
                        :e-invoice-filters="eInvoiceFilter"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.salesExchange"
                >
                    <SalesExchange
                        :sales-exchange-filters="salesExchangeFilters"
                        :sales-exchange-filter-static-details="salesExchangeFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.voidReport"
                >
                    <VoidReport
                        :void-filters="voidFilters"
                        :void-filter-static-details="voidFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.generalSales"
                >
                    <GeneralSales
                        :general-sales-filters="generalSalesFilters"
                        :general-sales-reports="generalSalesReports"
                        :general-sales-filter-static-details="generalSalesFilterStaticDetails"
                        :general-sales-report-static-details="generalSalesReportStaticDetails"
                        :e-invoice-filters="eInvoiceFilter"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === merchandisingReportsStaticDetails.top20Products"
                >
                    <TopTwenty
                        :top-twenty-report-types="topTwentyReportTypes"
                        :top-twenty-report-static-types="topTwentyReportStaticTypes"
                        :top-twenty-report-view-types="topTwentyReportViewTypes"
                        :top-twenty-report-view-static-types="topTwentyReportViewStaticTypes"
                        :top-twenty-filters="topTwentyFilters"
                        :top-twenty-filter-static-details="topTwentyFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === merchandisingReportsStaticDetails.worst20Products"
                >
                    <WorstTwenty
                        :worst-twenty-report-types="worstTwentyReportTypes"
                        :worst-twenty-report-static-types="worstTwentyReportStaticTypes"
                        :worst-twenty-report-view-types="worstTwentyReportViewTypes"
                        :worst-twenty-report-view-static-types="worstTwentyReportViewStaticTypes"
                        :worst-twenty-filters="worstTwentyFilters"
                        :worst-twenty-filter-static-details="worstTwentyFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockCard"
                >
                    <StockCard
                        :stock-card-filter="stockCardFilter"
                        :product-collections="productCollections"
                        :stock-card-filter-static-details="stockCardFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.cashMovement"
                >
                    <CashMovement
                        :cash-movement-filters="cashMovementFilters"
                        :cash-movement-filter-static-details="cashMovementFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div>
                    <SalesByPromoter
                        v-if="state.customReportSelected.name === salesReportsStaticDetails.salesByPromoter"
                        :sales-by-promoter-filters="salesByPromoterFilters"
                        :sales-by-promoter-filter-static-details="salesByPromoterFilterStaticDetails"
                        :sales-by-promoter-reports="salesByPromoterReports"
                        :sales-by-promoter-report-static-details="salesByPromoterReportStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockTransfer ||
                        state.customReportSelected.name === inventoryReportsStaticDetails.stockTransferByStatus"
                >
                    <StockTransfer
                        :stores="state.stores"
                        :warehouses="state.warehouses"
                        :product-collections="productCollections"
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
                >
                    <StockTransferDiscrepancy
                        :stock-transfer-filters="stockTransferFilters"
                        :product-collections="productCollections"
                        :stock-transfer-transfer-type="stockTransferDiscrepancyTransferType"
                        :stock-transfer-report-date-types="stockTransferReportDateTypes"
                        :stock-transfer-report-type="transferDiscrepancyReportType"
                        :stock-transfer-filter-static-details="stockTransferFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.goodsReceivedNotes"
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
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.returnAndExchange"
                >
                    <SaleReturnAndSaleExchange
                        :sale-return-and-sale-exchange-filters="saleReturnAndSaleExchangeFilters"
                        :sale-return-and-sale-exchange-filter-static-details="saleReturnAndSaleExchangeFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.salesReturn"
                >
                    <SaleReturn
                        :sale-return-filters="saleReturnFilters"
                        :sale-return-filter-static-details="saleReturnFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>
                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.holdAndResume"
                >
                    <SuspendAndResume
                        :suspend-and-resume-filters="suspendAndResumeFilters"
                        :suspend-and-resume-filter-static-details="suspendAndResumeFilterStaticDetails"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === merchandisingReportsStaticDetails.discountReport"
                >
                    <DiscountReport
                        :discount-type-filter="discountTypeFilter"
                        :product-collections="productCollections"
                        :discount-type-static-filters="discountTypeStaticFilters"
                        :discount-type-reports="discountTypeReports"
                        :sale-discount-types="saleDiscountTypes"
                        :sale-discount-types-static-filters="saleDiscountTypesStaticFilters"
                        :sale-discount-type-reports="saleDiscountTypeReports"
                        :sale-discount-type-report-static-filters="saleDiscountTypeReportStaticFilters"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockAdjustment"
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
                    v-if="state.customReportSelected.name === merchandisingReportsStaticDetails.discountSummaryReport"
                >
                    <DiscountSummaryReport
                        :discount-type-filter="discountTypeFilter"
                        :product-collections="productCollections"
                        :discount-type-static-filters="discountTypeStaticFilters"
                        :sale-discount-types="saleDiscountTypes"
                        :sale-discount-types-static-filters="saleDiscountTypesStaticFilters"
                        :sale-discount-type-reports="saleDiscountTypeReports"
                        :sale-discount-type-report-static-filters="saleDiscountTypeReportStaticFilters"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === salesReportsStaticDetails.summaryOfSalesByStores"
                >
                    <SaleOverallByStoreReport
                        :sales-overall-by-location-filters="salesOverallByLocationFilters"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === purchasingReportsStaticDetails.interCompanyTransfer"
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
                    v-if="state.customReportSelected.name === orderReportsStaticDetails.ordersReport"
                >
                    <OrdersReport
                        :order-report-types="orderReportTypes"
                        :order-filter-types="orderFilterTypes"
                        :product-collections="productCollections"
                        :order-filter-static-types="orderFilterStaticTypes"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === orderReportsStaticDetails.layawaySales"
                >
                    <LayawaySales
                        :layaway-report-types="layawayReportTypes"
                        @update:clear-button="clearData"
                    />
                </div>

                <div
                    v-if="state.customReportSelected.name === purchasingReportsStaticDetails.interCompanyTransferInvoices"
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

                <div
                    v-if="state.customReportSelected.name === orderReportsStaticDetails.creditSales"
                >
                    <CreditSales
                        :credit-report-types="creditReportTypes"
                        @update:clear-button="clearData"
                    />
                </div>
            </template>
        </CustomReportModal>
    </div>
</template>

<script setup>
import StockAdjustmentReport from '@storeManagerPages/reports/custom_reports/StockAdjustmentReport.vue';
import StockMovement from '@storeManagerPages/reports/custom_reports/StockMovement.vue';
import SaleHour from '@storeManagerPages/reports/custom_reports/SaleHour.vue';
import SalesCollection from '@storeManagerPages/reports/custom_reports/SalesCollection.vue';
import SalesExchange from '@storeManagerPages/reports/custom_reports/SalesExchange.vue';
import VoidReport from '@storeManagerPages/reports/custom_reports/VoidReport.vue';
import GeneralSales from '@storeManagerPages/reports/custom_reports/GeneralSales.vue';
import TopTwenty from '@storeManagerPages/reports/custom_reports/TopTwenty.vue';
import WorstTwenty from '@storeManagerPages/reports/custom_reports/WorstTwenty.vue';
import StockCard from '@storeManagerPages/reports/custom_reports/StockCard.vue';
import CashMovement from '@storeManagerPages/reports/custom_reports/CashMovement.vue';
import SalesByPromoter from '@storeManagerPages/reports/custom_reports/SalesByPromoter.vue';
import StockTransfer from '@storeManagerPages/reports/custom_reports/StockTransfer.vue';
import StockTransferDiscrepancy from '@storeManagerPages/reports/custom_reports/StockTransferDiscrepancy.vue';
import GoodsReceivedNotes from '@storeManagerPages/reports/custom_reports/GoodsReceivedNotes.vue';
import SaleReturn from '@storeManagerPages/reports/custom_reports/SaleReturn.vue';
import SaleReturnAndSaleExchange from '@storeManagerPages/reports/custom_reports/SaleReturnAndSaleExchange.vue';
import SuspendAndResume from '@storeManagerPages/reports/custom_reports/SuspendAndResume.vue';
import DiscountReport from '@storeManagerPages/reports/custom_reports/DiscountReport.vue';
import DiscountSummaryReport from '@storeManagerPages/reports/custom_reports/DiscountSummaryReport.vue';
import SaleOverallByStoreReport from '@storeManagerPages/reports/custom_reports/SaleOverallByStoreReport.vue';
import InterCompanyStockTransfer from '@storeManagerPages/reports/custom_reports/InterCompanyStockTransfer.vue';
import OrdersReport from '@storeManagerPages/reports/custom_reports/OrdersReport.vue';
import InterCompanyInvoices from '@storeManagerPages/reports/custom_reports/InterCompanyInvoices.vue';
import CreditSales from '@storeManagerPages/reports/custom_reports/CreditSales.vue';
import LayawaySales from '@storeManagerPages/reports/custom_reports/LayawaySales.vue';
import { reactive } from 'vue';
import axios from 'axios';
import ObjectStorage from '@commonServices/storage.js';
import { route } from 'ziggy';
import { Clipboard } from 'lucide-vue-next';
import CustomReportModal from '@commonComponents/CustomReportModal.vue';

const props = defineProps({
    customReportMenus: {
        type: Object,
        required: true,
    },
    salesReports: {
        type: Object,
        required: true,
    },
    inventoryReports: {
        type: Object,
        required: true,
    },
    merchandisingReports: {
        type: Object,
        required: true,
    },
    purchasingReports: {
        type: Object,
        required: true,
    },
    orderReports: {
        type: Object,
        required: true,
    },
    salesReportsStaticDetails: {
        type: Object,
        required: true,
    },
    inventoryReportsStaticDetails: {
        type: Object,
        required: true,
    },
    merchandisingReportsStaticDetails: {
        type: Object,
        required: true,
    },
    purchasingReportsStaticDetails: {
        type: Object,
        required: true,
    },
    orderReportsStaticDetails: {
        type: Object,
        required: true,
    },
    reportTypesStaticDetails: {
        type: Object,
        required: true,
    },
    salesCollectionFilters: {
        type: Object,
        required: true,
    },
    salesCollectionReports: {
        type: Object,
        required: true,
    },
    salesCollectionFilterStaticDetails: {
        type: Object,
        required: true,
    },
    salesCollectionReportStaticDetails: {
        type: Object,
        required: true,
    },
    saleReturnAndSaleExchangeFilters: {
        type: Object,
        required: true,
    },
    saleReturnAndSaleExchangeFilterStaticDetails: {
        type: Object,
        required: true,
    },
    saleReturnFilters: {
        type: Object,
        required: true,
    },
    saleReturnFilterStaticDetails: {
        type: Object,
        required: true,
    },
    suspendAndResumeFilters: {
        type: Object,
        required: true,
    },
    suspendAndResumeFilterStaticDetails: {
        type: Object,
        required: true,
    },
    cashMovementFilters: {
        type: Object,
        required: true,
    },
    cashMovementFilterStaticDetails: {
        type: Object,
        required: true,
    },
    generalSalesReports: {
        type: Object,
        required: true,
    },
    generalSalesFilters: {
        type: Object,
        required: true,
    },
    generalSalesFilterStaticDetails: {
        type: Object,
        required: true,
    },
    generalSalesReportStaticDetails: {
        type: Object,
        required: true,
    },
    salesByPromoterFilters: {
        type: Object,
        required: true,
    },
    salesByPromoterFilterStaticDetails: {
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
    discountTypeFilter: {
        type: Object,
        required: true,
    },
    discountTypeStaticFilters: {
        type: Object,
        required: true,
    },
    stockTransferReportType: {
        type: Object,
        required: true,
    },
    topTwentyReportTypes: {
        type: Object,
        required: true,
    },
    topTwentyFilters: {
        type: Object,
        required: true,
    },
    topTwentyFilterStaticDetails: {
        type: Object,
        required: true,
    },
    topTwentyReportStaticTypes: {
        type: Object,
        required: true,
    },
    worstTwentyReportTypes: {
        type: Object,
        required: true,
    },
    worstTwentyReportStaticTypes: {
        type: Object,
        required: true,
    },
    worstTwentyFilters: {
        type: Object,
        required: true,
    },
    worstTwentyFilterStaticDetails: {
        type: Object,
        required: true,
    },
    salesByPromoterReports: {
        type: Object,
        required: true,
    },
    salesByPromoterReportStaticDetails: {
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
    discountTypeReports: {
        type: Object,
        required: true,
    },
    stockTransferStatuses: {
        type: Object,
        required: true,
    },
    salesOverallByLocationFilters: {
        type: Object,
        required: true,
    },
    salesExchangeFilters: {
        type: Object,
        required: true,
    },
    salesExchangeFilterStaticDetails: {
        type: Object,
        required: true,
    },
    voidFilters: {
        type: Object,
        required: true,
    },
    voidFilterStaticDetails: {
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
    topTwentyReportViewTypes: {
        type: Object,
        required: true,
    },
    topTwentyReportViewStaticTypes: {
        type: Object,
        required: true,
    },
    worstTwentyReportViewTypes: {
        type: Object,
        required: true,
    },
    worstTwentyReportViewStaticTypes: {
        type: Object,
        required: true,
    },
    orderReportTypes: {
        type: Object,
        required: true,
    },
    orderFilterTypes: {
        type: Object,
        required: true,
    },
    orderFilterStaticTypes: {
        type: Object,
        required: true,
    },
    layawayReportTypes: {
        type: Array,
        required: true,
    },
    externalCompanies: {
        type: Array,
        required: true,
    },
    creditReportTypes: {
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
    eInvoiceFilter: {
        type: Object,
        required: true,
    },
    locationTypes: {
        type: Object,
        required: true,
    },
    staticLocationTypes: {
        type: Object,
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
        axios.get(route('store_manager.custom_reports.get_stores_and_warehouses')).then((response) => {
            if (response.status === httpStatusOk) {
                filterStores(response.data.stores);
                state.warehouses = response.data.warehouses;
            }
        });
    }
};

function filterStores (stores) {
    state.stores = stores.filter((store) => {
        return store.id !== ObjectStorage.get('store-manager-store-id');
    });
}
</script>
