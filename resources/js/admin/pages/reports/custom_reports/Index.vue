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
                <div>
                    <div>
                        <div
                            v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockMovements"
                            class="mt-4"
                        >
                            <StockMovement
                                :stores="stores"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :static-location-types="staticLocationTypes"
                                :product-collections="productCollections"
                                :stock-movement-filters="stockMovementFilters"
                                :stock-movement-report-types="stockMovementReportTypes"
                                :stock-movement-filter-static-details="stockMovementFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.hourlySalesReport"
                            class="mt-4"
                        >
                            <SaleHour
                                :locations="stores"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === othersReportsStaticDetails.seasonalSales"
                            class="mt-4"
                        >
                            <SeasonalSales
                                :locations="stores"
                                :brands="brands"
                                :sale-seasons="saleSeasons"
                                :seasonal-report-types="seasonalReportTypes"
                                :seasonal-report-static-types="seasonalReportStaticTypes"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.salesCollection"
                            class="mt-4"
                        >
                            <SalesCollection
                                :locations="stores"
                                :sales-collection-filters="salesCollectionFilters"
                                :sales-collection-reports="salesCollectionReports"
                                :sales-collection-filter-static-details="salesCollectionFilterStaticDetails"
                                :sales-collection-report-static-details="salesCollectionReportStaticDetails"
                                :e-invoice-filters="eInvoiceFilter"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.salesExchange"
                            class="mt-4"
                        >
                            <SalesExchange
                                :locations="stores"
                                :sales-exchange-filters="salesExchangeFilters"
                                :sales-exchange-filter-static-details="salesExchangeFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.returnAndExchange"
                            class="mt-4"
                        >
                            <SaleReturnAndSaleExchange
                                :locations="stores"
                                :sale-return-and-sale-exchange-filters="saleReturnAndSaleExchangeFilters"
                                :sale-return-and-sale-exchange-filter-static-details="saleReturnAndSaleExchangeFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === merchandisingReportsStaticDetails.top20Products"
                            class="mt-4"
                        >
                            <TopTwenty
                                :locations="stores"
                                :top-twenty-report-types="topTwentyReportTypes"
                                :top-twenty-report-static-types="topTwentyReportStaticTypes"
                                :top-twenty-report-view-types="topTwentyReportViewTypes"
                                :top-twenty-report-view-static-types="topTwentyReportViewStaticTypes"
                                :top-twenty-filters="topTwentyFilters"
                                :top-twenty-filter-static-details="topTwentyFilterStaticDetails"
                                :attributes="attributes"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === merchandisingReportsStaticDetails.worst20Products"
                            class="mt-4"
                        >
                            <WorstTwenty
                                :locations="stores"
                                :worst-twenty-report-types="worstTwentyReportTypes"
                                :worst-twenty-report-static-types="worstTwentyReportStaticTypes"
                                :worst-twenty-report-view-types="worstTwentyReportViewTypes"
                                :worst-twenty-report-view-static-types="worstTwentyReportViewStaticTypes"
                                :worst-twenty-filters="worstTwentyFilters"
                                :worst-twenty-filter-static-details="worstTwentyFilterStaticDetails"
                                :attributes="attributes"
                                @update:clear-button="clearData"
                            />
                        </div>
                        <div class="mt-4">
                            <VoidReport
                                v-if="state.customReportSelected.name === salesReportsStaticDetails.voidReport"
                                :locations="stores"
                                :void-filters="voidFilters"
                                :void-filter-static-details="voidFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.generalSales"
                            class="mt-4"
                        >
                            <GeneralSales
                                :locations="stores"
                                :general-sales-filters="generalSalesFilters"
                                :general-sales-reports="generalSalesReports"
                                :general-sales-filter-static-details="generalSalesFilterStaticDetails"
                                :general-sales-report-static-details="generalSalesReportStaticDetails"
                                :e-invoice-filters="eInvoiceFilter"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockCard"
                            class="mt-4"
                        >
                            <StockCard
                                :stores="stores"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :static-location-types="staticLocationTypes"
                                :product-collections="productCollections"
                                :stock-card-filter="stockCardFilter"
                                :stock-card-filter-static-details="stockCardFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.cashMovement"
                            class="mt-4"
                        >
                            <CashMovement
                                :locations="stores"
                                :cash-movement-filters="cashMovementFilters"
                                :cash-movement-filter-static-details="cashMovementFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === othersReportsStaticDetails.promoterCommission"
                            class="mt-4"
                        >
                            <PromoterCommission
                                :locations="stores"
                                :promoter-commission-filters="promoterCommissionFilters"
                                :promoter-commission-filter-static-details="promoterCommissionFilterStaticDetails"
                                :promoter-commission-reports="promoterCommissionReports"
                                :promoter-commission-report-static-details="promoterCommissionReportStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.salesReturn"
                            class="mt-4"
                        >
                            <SaleReturn
                                :locations="stores"
                                :sale-return-filters="saleReturnFilters"
                                :sale-return-filter-static-details="saleReturnFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div class="mt-4">
                            <SalesByPromoter
                                v-if="state.customReportSelected.name === salesReportsStaticDetails.salesByPromoter"
                                :locations="stores"
                                :sales-by-promoter-filters="salesByPromoterFilters"
                                :sales-by-promoter-reports="salesByPromoterReports"
                                :sales-by-promoter-filter-static-details="salesByPromoterFilterStaticDetails"
                                :sales-by-promoter-report-static-details="salesByPromoterReportStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockTransfer ||
                                state.customReportSelected.name === inventoryReportsStaticDetails.stockTransferByStatus"
                            class="mt-4"
                        >
                            <StockTransfer
                                :stores="stores"
                                :product-collections="productCollections"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :static-location-types="staticLocationTypes"
                                :stock-transfer-filters="stockTransferFilters"
                                :stock-transfer-transfer-type="stockTransferTransferType"
                                :stock-transfer-report-type="stockTransferReportType"
                                :stock-transfer-report-date-types="stockTransferReportDateTypes"
                                :stock-transfer-statuses="stockTransferStatuses"
                                :is-status-allowed="state.customReportSelected.name === inventoryReportsStaticDetails.stockTransferByStatus ? true : false"
                                :stock-transfer-filter-static-details="stockTransferFilterStaticDetails"
                                :transfer-type-out="transferTypeOut"
                                :transfer-type-in="transferTypeIn"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockTransferDiscrepancy"
                            class="mt-4"
                        >
                            <StockTransferDiscrepancy
                                :stores="stores"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :static-location-types="staticLocationTypes"
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
                                :stores="stores"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :static-location-types="staticLocationTypes"
                                :product-collections="productCollections"
                                :goods-received-note-filters="goodsReceivedNoteFilters"
                                :goods-received-note-report-types="goodsReceivedNoteReportTypes"
                                :goods-received-note-filter-static-details="goodsReceivedNoteFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.holdAndResume"
                            class="mt-4"
                        >
                            <SuspendAndResume
                                :locations="stores"
                                :suspend-and-resume-filters="suspendAndResumeFilters"
                                :suspend-and-resume-filter-static-details="suspendAndResumeFilterStaticDetails"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === merchandisingReportsStaticDetails.discountReport"
                            class="mt-4"
                        >
                            <DiscountReport
                                :locations="stores"
                                :discount-type-filter="discountTypeFilter"
                                :product-collections="productCollections"
                                :discount-type-static-filters="discountTypeStaticFilters"
                                :discount-type-reports="discountTypeReports"
                                :sale-discount-types="saleDiscountTypes"
                                :sale-discount-types-static-filters="saleDiscountTypesStaticFilters"
                                :sale-discount-type-reports="saleDiscountTypeReports"
                                :sale-discount-type-report-static-filters="saleDiscountTypeReportStaticFilters"
                                :attributes="attributes"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === merchandisingReportsStaticDetails.discountSummaryReport"
                            class="mt-4"
                        >
                            <DiscountSummaryReport
                                :locations="stores"
                                :product-collections="productCollections"
                                :discount-type-filter="discountTypeFilter"
                                :discount-type-static-filters="discountTypeStaticFilters"
                                :sale-discount-types="saleDiscountTypes"
                                :sale-discount-types-static-filters="saleDiscountTypesStaticFilters"
                                :sale-discount-type-reports="saleDiscountTypeReports"
                                :sale-discount-type-report-static-filters="saleDiscountTypeReportStaticFilters"
                                :attributes="attributes"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockAdjustment"
                            class="mt-4"
                        >
                            <StockAdjustmentReport
                                :stores="stores"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :static-location-types="staticLocationTypes"
                                :product-collections="productCollections"
                                :stock-adjustment-types="stockAdjustmentTypes"
                                :stock-adjustment-report-type="stockAdjustmentReportType"
                                :stock-adjustment-filter-type="stockAdjustmentFilterType"
                                :static-stock-adjustment-filter-type="staticStockAdjustmentFilterType"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockTransferStatusSummary"
                            class="mt-4"
                        >
                            <StockTransferStatusSummaryReport
                                :stores="stores"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :statuses="statusStockTransferStatusSummary"
                                :static-location-types="staticLocationTypes"
                                :stock-transfer-status-summary-report-type="stockTransferStatusSummaryReportType"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === inventoryReportsStaticDetails.stockSummaryByModule"
                            class="mt-4"
                        >
                            <StockSummaryByModule
                                :all-locations="allLocations"
                                :brands="brands"
                                :departments="state.departments"
                                :stock-summary-by-module-report-by="stockSummaryByModuleReportBy"
                                :stock-summary-by-module-report-type="stockSummaryByModuleReportType"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === salesReportsStaticDetails.summaryOfSalesByStores"
                            class="mt-4"
                        >
                            <SaleOverallByStoreReport
                                :sales-overall-by-location-filters="salesOverallByLocationFilters"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === purchasingReportsStaticDetails.interCompanyTransfer"
                            class="mt-4"
                        >
                            <InterCompanyStockTransfer
                                :stores="stores"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :static-location-types="staticLocationTypes"
                                :product-collections="productCollections"
                                :external-companies="externalCompanies"
                                :purchase-order-filters="purchaseOrderFilters"
                                :inter-company-transfer-type="interCompanyTransferType"
                                :inter-company-report-type="interCompanyReportType"
                                :inter-company-filter-static-details="interCompanyFilterStaticDetails"
                                :delivery-order-static-type="deliveryOrderStaticType"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === orderReportsStaticDetails.ordersReport"
                            class="mt-4"
                        >
                            <OrdersReport
                                :locations="stores"
                                :order-report-types="orderReportTypes"
                                :product-collections="productCollections"
                                :order-filter-types="orderFilterTypes"
                                :order-filter-static-types="orderFilterStaticTypes"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === purchasingReportsStaticDetails.interCompanyTransferInvoices"
                            class="mt-4"
                        >
                            <InterCompanyInvoices
                                :stores="stores"
                                :warehouses="warehouses"
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
                            v-if="state.customReportSelected.name === orderReportsStaticDetails.layawaySales"
                            class="mt-4"
                        >
                            <LayawaySales
                                :locations="stores"
                                :layaway-report-types="layawayReportTypes"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === orderReportsStaticDetails.creditSales"
                            class="mt-4"
                        >
                            <CreditSales
                                :locations="stores"
                                :credit-report-types="creditReportTypes"
                                @update:clear-button="clearData"
                            />
                        </div>

                        <div
                            v-if="state.customReportSelected.name === othersReportsStaticDetails.accumulatedSellThrough"
                            class="mt-4"
                        >
                            <AccumulatedSellThrough
                                :stores="stores"
                                :warehouses="warehouses"
                                :location-types="locationTypes"
                                :static-location-types="staticLocationTypes"
                                :product-collections="productCollections"
                                :accumulated-sale-through-filter-types="accumulatedSaleThroughFilterTypes"
                                :static-accumulated-sale-through-filter-types="staticAccumulatedSaleThroughFilterTypes"
                                :accumulated-sale-through-include-types="accumulatedSaleThroughIncludeTypes"
                                :static-accumulated-sale-through-include-types="staticAccumulatedSaleThroughIncludeTypes"
                                :accumulated-sell-through-report-types="accumulatedSellThroughReportTypes"
                                :attributes="attributes"
                                @update:clear-button="clearData"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </CustomReportModal>
    </div>
</template>

<script setup>
import AccumulatedSellThrough from '@adminPages/reports/custom_reports/AccumulatedSellThrough.vue';
import CashMovement from '@adminPages/reports/custom_reports/CashMovement.vue';
import CreditSales from '@adminPages/reports/custom_reports/CreditSales.vue';
import DiscountReport from '@adminPages/reports/custom_reports/DiscountReport.vue';
import DiscountSummaryReport from '@adminPages/reports/custom_reports/DiscountSummaryReport.vue';
import GeneralSales from '@adminPages/reports/custom_reports/GeneralSales.vue';
import GoodsReceivedNotes from '@adminPages/reports/custom_reports/GoodsReceivedNotes.vue';
import InterCompanyInvoices from '@adminPages/reports/custom_reports/InterCompanyInvoices.vue';
import InterCompanyStockTransfer from '@adminPages/reports/custom_reports/InterCompanyStockTransfer.vue';
import LayawaySales from '@adminPages/reports/custom_reports/LayawaySales.vue';
import OrdersReport from '@adminPages/reports/custom_reports/OrdersReport.vue';
import PromoterCommission from '@adminPages/reports/custom_reports/PromoterCommission.vue';
import SaleHour from '@adminPages/reports/custom_reports/SaleHour.vue';
import SaleOverallByStoreReport from '@adminPages/reports/custom_reports/SaleOverallByStoreReport.vue';
import SaleReturn from '@adminPages/reports/custom_reports/SaleReturn.vue';
import SaleReturnAndSaleExchange from '@adminPages/reports/custom_reports/SaleReturnAndSaleExchange.vue';
import SalesByPromoter from '@adminPages/reports/custom_reports/SalesByPromoter.vue';
import SalesCollection from '@adminPages/reports/custom_reports/SalesCollection.vue';
import SalesExchange from '@adminPages/reports/custom_reports/SalesExchange.vue';
import SeasonalSales from '@adminPages/reports/custom_reports/SeasonalSales.vue';
import StockAdjustmentReport from '@adminPages/reports/custom_reports/StockAdjustmentReport.vue';
import StockTransferStatusSummaryReport from '@adminPages/reports/custom_reports/StockTransferStatusSummaryReport.vue';
import StockSummaryByModule from '@adminPages/reports/custom_reports/StockSummaryByModule.vue';
import StockCard from '@adminPages/reports/custom_reports/StockCard.vue';
import StockMovement from '@adminPages/reports/custom_reports/StockMovement.vue';
import StockTransfer from '@adminPages/reports/custom_reports/StockTransfer.vue';
import StockTransferDiscrepancy from '@adminPages/reports/custom_reports/StockTransferDiscrepancy.vue';
import SuspendAndResume from '@adminPages/reports/custom_reports/SuspendAndResume.vue';
import TopTwenty from '@adminPages/reports/custom_reports/TopTwenty.vue';
import VoidReport from '@adminPages/reports/custom_reports/VoidReport.vue';
import WorstTwenty from '@adminPages/reports/custom_reports/WorstTwenty.vue';
import CustomReportModal from '@commonComponents/CustomReportModal.vue';
import { Clipboard } from 'lucide-vue-next';
import { reactive } from 'vue';

defineProps({
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
    othersReports: {
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
    othersReportsStaticDetails: {
        type: Object,
        required: true,
    },
    stores: {
        type: Object,
        required: true,
    },
    allLocations: {
        type: Object,
        required: true,
    },
    saleSeasons: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    },
    warehouses: {
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
    promoterCommissionReports: {
        type: Object,
        required: true,
    },
    promoterCommissionReportStaticDetails: {
        type: Object,
        required: true,
    },
    promoterCommissionFilters: {
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
    promoterCommissionFilterStaticDetails: {
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
    salesByPromoterReports: {
        type: Object,
        required: true,
    },
    salesByPromoterReportStaticDetails: {
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
    discountTypeReports: {
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
    stockTransferReportType: {
        type: Object,
        required: true,
    },
    stockTransferReportDateTypes: {
        type: Object,
        required: true,
    },
    stockTransferStatuses: {
        type: Object,
        required: true,
    },
    statusStockTransferStatusSummary: {
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
    stockAdjustmentReportType: {
        type: Object,
        required: true,
    },
    stockTransferStatusSummaryReportType: {
        type: Object,
        required: true,
    },
    stockSummaryByModuleReportBy: {
        type: Object,
        required: true,
    },
    stockSummaryByModuleReportType: {
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
    externalCompanies: {
        type: Array,
        required: true,
    },
    seasonalReportTypes: {
        type: Array,
        required: true,
    },
    seasonalReportStaticTypes: {
        type: Object,
        required: true,
    },
    layawayReportTypes: {
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
    accumulatedSaleThroughFilterTypes: {
        type: Object,
        required: true,
    },
    staticAccumulatedSaleThroughFilterTypes: {
        type: Object,
        required: true,
    },
    accumulatedSaleThroughIncludeTypes: {
        type: Object,
        required: true,
    },
    staticAccumulatedSaleThroughIncludeTypes: {
        type: Object,
        required: true,
    },
    accumulatedSellThroughReportTypes: {
        type: Object,
        required: true,
    },
    eInvoiceFilter: {
        type: Object,
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
    attributes: {
        type: Object,
        default: () => { },
    },
});

const state = reactive({
    customReportSelected: null,
    reportTypeSelected: null,
    displayReportModal: false,
    departments: [],
});

const hideReportModal = () => {
    state.displayReportModal = false;
};

const showReportModal = (reportType) => {
    state.displayReportModal = true;
    state.customReportSelected = reportType;
};

const clearData = () => {
    hideReportModal();
};

</script>
