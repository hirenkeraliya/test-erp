<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\CashMovement\Enums\CashMovementFilterTypes;
use App\Domains\CashMovement\Services\CashMovementReportService;
use App\Domains\Common\Enums\InventoryReport;
use App\Domains\Common\Enums\MerchandisingReport;
use App\Domains\Common\Enums\OrdersReport;
use App\Domains\Common\Enums\OthersReport;
use App\Domains\Common\Enums\PurchasingReport;
use App\Domains\Common\Enums\ReportTypes;
use App\Domains\Common\Enums\SalesReport;
use App\Domains\CustomReport\DataObjects\AccumulatedSellThroughCustomReportData;
use App\Domains\CustomReport\DataObjects\CashMovementCustomReportData;
use App\Domains\CustomReport\DataObjects\CreditSalesCustomReportData;
use App\Domains\CustomReport\DataObjects\DiscountSummaryCustomReportData;
use App\Domains\CustomReport\DataObjects\GeneralSalesCustomReportData;
use App\Domains\CustomReport\DataObjects\GoodReceivedNotesCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyInvoiceCustomReportData;
use App\Domains\CustomReport\DataObjects\LaywaySalesCustomReportData;
use App\Domains\CustomReport\DataObjects\OrderCustomReportData;
use App\Domains\CustomReport\DataObjects\PromoterCommissionCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleExchangeCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleHourCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleOverallByStoreCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleReturnAndExchangeCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleReturnCustomReportData;
use App\Domains\CustomReport\DataObjects\SalesByPromoterCustomReportData;
use App\Domains\CustomReport\DataObjects\SalesCollectionCustomReportData;
use App\Domains\CustomReport\DataObjects\SeasonalSalesCustomReportData;
use App\Domains\CustomReport\DataObjects\StockAdjustmentCustomReportData;
use App\Domains\CustomReport\DataObjects\StockCardCustomReportData;
use App\Domains\CustomReport\DataObjects\StockDiscountCustomReportData;
use App\Domains\CustomReport\DataObjects\StockMovementsCustomReportData;
use App\Domains\CustomReport\DataObjects\StockSummaryByModuleReportData;
use App\Domains\CustomReport\DataObjects\StockTransferCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferDiscrepancyCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferStatusSummaryCustomReportData;
use App\Domains\CustomReport\DataObjects\SuspendAndResumeCustomReportData;
use App\Domains\CustomReport\DataObjects\TopTwentyCustomReportData;
use App\Domains\CustomReport\DataObjects\VoidReportCustomReportData;
use App\Domains\CustomReport\DataObjects\WorstTwentyCustomReportData;
use App\Domains\DigitalInvoice\Enums\EInvoiceFilter;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteFilterTypes;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteReportTypes;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteReportService;
use App\Domains\HoldSale\Services\SuspendAndResumeReportService;
use App\Domains\InventoryUpdate\Enums\StockCardFilterByReportTypes;
use App\Domains\InventoryUpdate\Enums\StockMovementFilters;
use App\Domains\InventoryUpdate\Enums\StockMovementReportTypes;
use App\Domains\InventoryUpdate\Services\StockCardReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Order\Enums\OrderFilterTypes;
use App\Domains\Order\Enums\OrderReportTypes;
use App\Domains\Order\Services\OrdersCustomReportService;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\Promoter\Enums\SalesByPromoterFilterTypes;
use App\Domains\Promoter\Enums\SalesByPromoterReportTypes;
use App\Domains\Promoter\services\SalesByPromoterReportService;
use App\Domains\PromoterCommissionUpdate\Services\PromoterCommissionUpdateReportService;
use App\Domains\PurchaseOrder\Enums\InterCompanyCustomReportTypes;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferReportType;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Domains\PurchaseOrderInvoice\Services\InterCompanyInvoiceCustomReportService;
use App\Domains\Sale\Enums\CreditReportTypes;
use App\Domains\Sale\Enums\DiscountTypeFilters;
use App\Domains\Sale\Enums\DiscountTypeReports;
use App\Domains\Sale\Enums\GeneralSalesFilterTypes;
use App\Domains\Sale\Enums\GeneralSalesReportTypes;
use App\Domains\Sale\Enums\LayawayReportTypes;
use App\Domains\Sale\Enums\PromoterCommissionFilterTypes;
use App\Domains\Sale\Enums\PromoterCommissionReportTypes;
use App\Domains\Sale\Enums\SaleDiscountTypeReports;
use App\Domains\Sale\Enums\SaleReturnAndSaleExchangeFilterTypes;
use App\Domains\Sale\Enums\SalesCollectionFilterTypes;
use App\Domains\Sale\Enums\SalesCollectionReportTypes;
use App\Domains\Sale\Enums\SalesExchangeFilterTypes;
use App\Domains\Sale\Enums\SalesOverallReportTypes;
use App\Domains\Sale\Enums\SeasonalReportTypes;
use App\Domains\Sale\Enums\TopTwentyFilterTypes;
use App\Domains\Sale\Enums\TopTwentyReportTypes;
use App\Domains\Sale\Enums\TopTwentyReportViewTypes;
use App\Domains\Sale\Enums\WorstTwentyFilterTypes;
use App\Domains\Sale\Enums\WorstTwentyReportTypes;
use App\Domains\Sale\Enums\WorstTwentyReportViewTypes;
use App\Domains\Sale\Services\CreditSaleCustomReportService;
use App\Domains\Sale\Services\DiscountCustomReportService;
use App\Domains\Sale\Services\DiscountSummaryReportService;
use App\Domains\Sale\Services\GeneralSalesReportService;
use App\Domains\Sale\Services\LayawaySaleCustomReportService;
use App\Domains\Sale\Services\SaleDiscountCustomReportService;
use App\Domains\Sale\Services\SaleDiscountSummaryCustomReportService;
use App\Domains\Sale\Services\SaleHourReportService;
use App\Domains\Sale\Services\SaleReturnAndSaleExchangeReportService;
use App\Domains\Sale\Services\SalesCollectionReportService;
use App\Domains\Sale\Services\SalesExchangeReportService;
use App\Domains\Sale\Services\SalesOverallReportService;
use App\Domains\Sale\Services\SeasonalCustomComparisonReportService;
use App\Domains\Sale\Services\SeasonalCustomDetailReportService;
use App\Domains\Sale\Services\SeasonalCustomReportService;
use App\Domains\Sale\Services\TopTwentyReportService;
use App\Domains\Sale\Services\WorstTwentyReportService;
use App\Domains\SaleDiscount\Enums\SaleDiscountTypes;
use App\Domains\SaleReturn\Enums\SaleReturnFilterTypes;
use App\Domains\SaleReturn\Services\SaleReturnReportService;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughReportTypes;
use App\Domains\SellThroughAggregate\Services\SellThroughProductForCustomReportServices;
use App\Domains\SellThroughAggregate\Services\SellThroughProductForLocationWiseCustomReportServices;
use App\Domains\StockAdjustment\Enums\StockAdjustmentFilterType;
use App\Domains\StockAdjustment\Enums\StockAdjustmentReportType;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Domains\StockAdjustment\Services\StockAdjustmentCustomReportService;
use App\Domains\StockMovement\Services\StockMovementReportService;
use App\Domains\StockSummary\Enums\StockSummaryByModuleReportBy;
use App\Domains\StockSummary\Enums\StockSummaryByModuleReportType;
use App\Domains\StockSummary\Services\StockSummaryByModuleReportService;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportTypes;
use App\Domains\StockTransfer\Enums\StockTransferStatusSummaryReportType;
use App\Domains\StockTransfer\Enums\TransferReportType;
use App\Domains\StockTransfer\Enums\TransferTypeDiscrepancyReport;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Services\StockTransferCustomReportService;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyCustomReportService;
use App\Domains\StockTransfer\Services\StockTransferStatusSummaryCustomReportService;
use App\Domains\SuspendAndResume\Enums\SuspendAndResumeFilterTypes;
use App\Domains\VoidSale\Enums\VoidFilterTypes;
use App\Domains\VoidSale\Services\VoidReportService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomReportController extends Controller
{
    public function index(): Response
    {
        $companyId = session('admin_company_id');

        [$stores, $warehouses] = $this->getStoresAndWarehouses($companyId);

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getCompanyBrands(session('admin_company_id'));

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getAll();

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeasons = $saleSeasonQueries->getWithBasicColumns(session('admin_company_id'));

        $customReportMenus = $this->getCustomReportMenus();

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('admin_company_id'))->all();
        }

        return Inertia::render('reports/custom_reports/Index', [
            'stores' => $stores,
            'productCollections' => $productCollections,
            'warehouses' => $warehouses,
            'allLocations' => array_merge($stores, $warehouses),
            'brands' => $brands,
            'saleSeasons' => $saleSeasons,
            'customReportMenus' => $customReportMenus,
            'reportTypesStaticDetails' => ReportTypes::getFormattedArrayForStaticUse(),
            'salesReports' => SalesReport::formattedForSelection(),
            'inventoryReports' => InventoryReport::formattedForSelection(),
            'merchandisingReports' => MerchandisingReport::formattedForSelection(),
            'purchasingReports' => PurchasingReport::formattedForSelection(),
            'orderReports' => OrdersReport::formattedForSelection(),
            'othersReports' => OthersReport::formattedForSelection(),
            'salesReportsStaticDetails' => SalesReport::generateStaticCasesArray(),
            'inventoryReportsStaticDetails' => InventoryReport::generateStaticCasesArray(),
            'merchandisingReportsStaticDetails' => MerchandisingReport::generateStaticCasesArray(),
            'purchasingReportsStaticDetails' => PurchasingReport::generateStaticCasesArray(),
            'orderReportsStaticDetails' => OrdersReport::generateStaticCasesArray(),
            'othersReportsStaticDetails' => OthersReport::generateStaticCasesArray(),
            'externalCompanies' => $externalCompanies,
            'salesCollectionReports' => SalesCollectionReportTypes::formattedForSelection(),
            'salesCollectionFilters' => SalesCollectionFilterTypes::formattedForSelection(),
            'salesCollectionFilterStaticDetails' => SalesCollectionFilterTypes::getFormattedArrayForStaticUse(),
            'salesCollectionReportStaticDetails' => SalesCollectionReportTypes::getFormattedArrayForStaticUse(),
            'saleReturnAndSaleExchangeFilters' => SaleReturnAndSaleExchangeFilterTypes::formattedForSelection(),
            'saleReturnAndSaleExchangeFilterStaticDetails' => SaleReturnAndSaleExchangeFilterTypes::getFormattedArrayForStaticUse(),
            'saleReturnFilters' => SaleReturnFilterTypes::formattedForSelection(),
            'saleReturnFilterStaticDetails' => SaleReturnFilterTypes::getFormattedArrayForStaticUse(),
            'suspendAndResumeFilters' => SuspendAndResumeFilterTypes::formattedForSelection(),
            'suspendAndResumeFilterStaticDetails' => SuspendAndResumeFilterTypes::getFormattedArrayForStaticUse(),
            'cashMovementFilters' => CashMovementFilterTypes::formattedForSelection(),
            'cashMovementFilterStaticDetails' => CashMovementFilterTypes::getFormattedArrayForStaticUse(),
            'generalSalesReports' => GeneralSalesReportTypes::formattedForSelection(),
            'generalSalesFilters' => GeneralSalesFilterTypes::formattedForSelection(),
            'salesByPromoterFilters' => SalesByPromoterFilterTypes::formattedForSelection(),
            'salesByPromoterReports' => SalesByPromoterReportTypes::formattedForSelection(),
            'stockTransferTransferType' => TransferTypeForReport::formattedForSelection(),
            'stockTransferReportType' => TransferReportType::formattedForSelection(),
            'stockTransferStatuses' => StatusTypes::formattedForSelection(),
            'stockTransferReportDateTypes' => StockTransferCustomReportDateTypes::formattedForSelection(),
            'stockAdjustmentReportType' => StockAdjustmentReportType::formattedForSelection(),
            'stockTransferStatusSummaryReportType' => StockTransferStatusSummaryReportType::formattedForSelection(),
            'stockSummaryByModuleReportBy' => StockSummaryByModuleReportBy::formattedForSelection(),
            'stockSummaryByModuleReportType' => StockSummaryByModuleReportType::formattedForSelection(),
            'statusStockTransferStatusSummary' => StatusTypes::getStockTransferStatusSummary(),
            'stockAdjustmentFilterType' => StockAdjustmentFilterType::formattedForSelection(),
            'stockAdjustmentTypes' => StockAdjustmentTypes::formattedForSelection(),
            'staticStockAdjustmentFilterType' => StockAdjustmentFilterType::getFormattedArrayForStaticUse(),
            'stockTransferFilters' => StockTransferCustomReportTypes::formattedForSelection(),
            'goodsReceivedNoteFilters' => GoodsReceivedNoteFilterTypes::formattedForSelection(),
            'goodsReceivedNoteReportTypes' => GoodsReceivedNoteReportTypes::formattedForSelection(),
            'stockCardFilter' => StockCardFilterByReportTypes::formattedForSelection(),
            'stockCardFilterStaticDetails' => StockCardFilterByReportTypes::getFormattedArrayForStaticUse(),
            'stockMovementFilters' => StockMovementFilters::formattedForSelection(),
            'stockMovementReportTypes' => StockMovementReportTypes::formattedForSelection(),
            'stockMovementFilterStaticDetails' => StockMovementFilters::getFormattedArrayForStaticUse(),
            'promoterCommissionFilters' => PromoterCommissionFilterTypes::formattedForSelection(),
            'promoterCommissionReports' => PromoterCommissionReportTypes::formattedForSelection(),
            'promoterCommissionFilterStaticDetails' => PromoterCommissionFilterTypes::getFormattedArrayForStaticUse(),
            'promoterCommissionReportStaticDetails' => PromoterCommissionReportTypes::getFormattedArrayForStaticUse(),
            'generalSalesReportStaticDetails' => GeneralSalesReportTypes::getFormattedArrayForStaticUse(),
            'generalSalesFilterStaticDetails' => GeneralSalesFilterTypes::getFormattedArrayForStaticUse(),
            'salesByPromoterFilterStaticDetails' => SalesByPromoterFilterTypes::getFormattedArrayForStaticUse(),
            'salesByPromoterReportStaticDetails' => SalesByPromoterReportTypes::getFormattedArrayForStaticUse(),
            'stockTransferFilterStaticDetails' => StockTransferCustomReportTypes::getFormattedArrayForStaticUse(),
            'goodsReceivedNoteFilterStaticDetails' => GoodsReceivedNoteFilterTypes::getFormattedArrayForStaticUse(),
            'discountTypeFilter' => DiscountTypeFilters::formattedForSelection(),
            'discountTypeStaticFilters' => DiscountTypeFilters::getFormattedArrayForStaticUse(),
            'discountTypeReports' => DiscountTypeReports::formattedForSelection(),
            'saleDiscountTypes' => SaleDiscountTypes::formattedForSelection(),
            'saleDiscountTypesStaticFilters' => SaleDiscountTypes::getFormattedArrayForStaticUse(),
            'saleDiscountTypeReports' => SaleDiscountTypeReports::formattedForSelection(),
            'saleDiscountTypeReportStaticFilters' => SaleDiscountTypeReports::getFormattedArrayForStaticUse(),
            'topTwentyReportTypes' => TopTwentyReportTypes::formattedForSelection(),
            'topTwentyReportStaticTypes' => TopTwentyReportTypes::getFormattedArrayForStaticUse(),
            'topTwentyFilters' => TopTwentyFilterTypes::formattedForSelection(),
            'topTwentyFilterStaticDetails' => TopTwentyFilterTypes::getFormattedArrayForStaticUse(),
            'topTwentyReportViewTypes' => TopTwentyReportViewTypes::formattedForSelection(),
            'topTwentyReportViewStaticTypes' => TopTwentyReportViewTypes::getFormattedArrayForStaticUse(),
            'worstTwentyReportTypes' => WorstTwentyReportTypes::formattedForSelection(),
            'worstTwentyReportStaticTypes' => WorstTwentyReportTypes::getFormattedArrayForStaticUse(),
            'worstTwentyReportViewTypes' => WorstTwentyReportViewTypes::formattedForSelection(),
            'worstTwentyReportViewStaticTypes' => WorstTwentyReportViewTypes::getFormattedArrayForStaticUse(),
            'worstTwentyFilters' => WorstTwentyFilterTypes::formattedForSelection(),
            'worstTwentyFilterStaticDetails' => WorstTwentyFilterTypes::getFormattedArrayForStaticUse(),
            'salesOverallByLocationFilters' => SalesOverallReportTypes::formattedForSelection(),
            'salesExchangeFilters' => SalesExchangeFilterTypes::formattedForSelection(),
            'salesExchangeFilterStaticDetails' => SalesExchangeFilterTypes::getFormattedArrayForStaticUse(),
            'voidFilters' => VoidFilterTypes::formattedForSelection(),
            'voidFilterStaticDetails' => VoidFilterTypes::getFormattedArrayForStaticUse(),
            'transferDiscrepancyReportType' => TransferTypeDiscrepancyReport::formattedForSelection(),
            'stockTransferDiscrepancyTransferType' => TransferTypeForReport::getTransferInAndOutOnly(),
            'purchaseOrderFilters' => InterCompanyCustomReportTypes::formattedForSelection(),
            'interCompanyTransferType' => InterCompanyTransferType::formattedForSelection(),
            'interCompanyReportType' => InterCompanyTransferReportType::formattedForSelection(),
            'interCompanyFilterStaticDetails' => InterCompanyCustomReportTypes::getFormattedArrayForStaticUse(),
            'orderReportTypes' => OrderReportTypes::formattedForSelection(),
            'orderFilterTypes' => OrderFilterTypes::formattedForSelection(),
            'orderFilterStaticTypes' => OrderFilterTypes::getFormattedArrayForStaticUse(),
            'seasonalReportTypes' => SeasonalReportTypes::formattedForSelection(),
            'seasonalReportStaticTypes' => SeasonalReportTypes::getFormattedArrayForStaticUse(),
            'deliveryOrderStaticType' => InterCompanyTransferType::DELIVERY_ORDER->value,
            'layawayReportTypes' => LayawayReportTypes::formattedForSelection(),
            'creditReportTypes' => CreditReportTypes::formattedForSelection(),
            'transferTypeOut' => TransferTypeForReport::TRANSFER_OUT->value,
            'transferTypeIn' => TransferTypeForReport::TRANSFER_IN->value,
            'accumulatedSellThroughReportTypes' => SellThroughReportTypes::getList(),
            'accumulatedSaleThroughFilterTypes' => SellThroughFilterTypes::getList(),
            'staticAccumulatedSaleThroughFilterTypes' => SellThroughFilterTypes::getFormattedArrayForStaticUse(),
            'accumulatedSaleThroughIncludeTypes' => SellThroughIncludeTypes::getList(),
            'staticAccumulatedSaleThroughIncludeTypes' => SellThroughIncludeTypes::getFormattedArrayForStaticUse(),
            'eInvoiceFilter' => EInvoiceFilter::formattedForSelection(),
            'locationTypes' => LocationTypes::getList(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    public function getCustomReportMenus(): array
    {
        $menus = [];
        $reportTypesLists = ReportTypes::getList();
        foreach ($reportTypesLists as $reportType) {
            $subMenu = [];

            $reportTypeMap = [
                ReportTypes::SALES->value => SalesReport::formattedForSelection(),
                ReportTypes::INVENTORY->value => InventoryReport::formattedForSelection(),
                ReportTypes::MERCHANDISING->value => MerchandisingReport::formattedForSelection(),
                ReportTypes::PURCHASING->value => PurchasingReport::formattedForSelection(),
                ReportTypes::ORDERS->value => OrdersReport::formattedForSelection(),
                ReportTypes::OTHERS->value => OthersReport::formattedForSelection(),
            ];

            if (array_key_exists($reportType['id'], $reportTypeMap)) {
                $subMenu = $reportTypeMap[$reportType['id']];
            }

            $menus[] = [
                'name' => $reportType['name'],
                'subMenu' => $subMenu,
            ];
        }

        return $menus;
    }

    public function printCashMovement(CashMovementCustomReportData $cashMovementCustomReportData): string
    {
        $validateData = $cashMovementCustomReportData->all();

        $filterData = [
            'location_ids' => $validateData['location_ids'],
            'date_range' => $validateData['date_range'],
            'counter_ids' => $validateData['counter_ids'],
            'cashier_ids' => $validateData['cashier_ids'],
            'filter_by' => $validateData['filter_by'],
        ];

        $customReportService = resolve(CashMovementReportService::class);

        return $customReportService->printCashMovement(session('admin_company_id'), $filterData);
    }

    public function exportCashMovementsReport(
        CashMovementCustomReportData $cashMovementCustomReportData,
        string $filename
    ): BinaryFileResponse {
        $validateData = $cashMovementCustomReportData->all();

        $filterData = [
            'location_ids' => $validateData['location_ids'],
            'date_range' => $validateData['date_range'],
            'counter_ids' => $validateData['counter_ids'],
            'cashier_ids' => $validateData['cashier_ids'],
            'filter_by' => $validateData['filter_by'],
        ];

        $customReportService = resolve(CashMovementReportService::class);

        return $customReportService->exportCashMovement(session('admin_company_id'), $filterData, $filename);
    }

    public function stockMovementReportPrint(StockMovementsCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['company_id'] = session('admin_company_id');

        $stockMovementReportService = resolve(StockMovementReportService::class);

        return $stockMovementReportService->print($filterData);
    }

    public function saleHourPrint(SaleHourCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');

        $saleHourReportService = resolve(SaleHourReportService::class);

        return $saleHourReportService->print($companyId, $filterData);
    }

    public function print(SalesCollectionCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $salesCollectionReportService = resolve(SalesCollectionReportService::class);

        return $salesCollectionReportService->print($companyId, $filterData);
    }

    public function printGeneralSale(GeneralSalesCustomReportData $request): string
    {
        $companyId = session('admin_company_id');

        $filterData = $request->all();

        $generalSalesReportService = resolve(GeneralSalesReportService::class);

        return $generalSalesReportService->print(
            $companyId,
            $filterData,
            (bool) $filterData['exclude_products_with_no_price']
        );
    }

    public function exportSaleCollection(SalesCollectionCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $salesCollectionReportService = resolve(SalesCollectionReportService::class);

        return $salesCollectionReportService->exportSaleCollection($companyId, $filterData, $filename);
    }

    public function exportStockMovementReport(
        StockMovementsCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $filterData['company_id'] = session('admin_company_id');
        $stockMovementReportService = resolve(StockMovementReportService::class);

        return $stockMovementReportService->exportStockMovementReport($filterData, $filename);
    }

    public function exportSaleHour(SaleHourCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $filterData['company_id'] = session('admin_company_id');

        $saleHourReportService = resolve(SaleHourReportService::class);

        return $saleHourReportService->export($filterData, $filename);
    }

    public function printExchange(SaleExchangeCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $salesExchangeReportService = resolve(SalesExchangeReportService::class);

        return $salesExchangeReportService->print($companyId, $filterData);
    }

    public function exportExchange(SaleExchangeCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $salesExchangeReportService = resolve(SalesExchangeReportService::class);

        return $salesExchangeReportService->export($filterData, $companyId, $filename);
    }

    public function printReturnAndExchange(SaleReturnAndExchangeCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $saleReturnAndSaleExchangeReportService = resolve(SaleReturnAndSaleExchangeReportService::class);

        return $saleReturnAndSaleExchangeReportService->print($companyId, $filterData);
    }

    public function exportReturnAndExchange(
        SaleReturnAndExchangeCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $saleReturnAndSaleExchangeReportService = resolve(SaleReturnAndSaleExchangeReportService::class);

        return $saleReturnAndSaleExchangeReportService->export($filterData, $companyId, $filename);
    }

    public function exportVoidReport(VoidReportCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $voidReportService = resolve(VoidReportService::class);

        return $voidReportService->exportVoidSaleReport($companyId, $filterData, $filename);
    }

    public function printVoidReport(VoidReportCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $voidReportService = resolve(VoidReportService::class);

        return $voidReportService->print($companyId, $filterData);
    }

    public function exportGeneralSalesReport(
        GeneralSalesCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $companyId = session('admin_company_id');

        $generalSalesReportService = resolve(GeneralSalesReportService::class);

        return $generalSalesReportService->exportGeneralSalesReport(
            $companyId,
            $filterData,
            $filename,
            (bool) $filterData['exclude_products_with_no_price']
        );
    }

    public function printTopTwenty(TopTwentyCustomReportData $request): string
    {
        $filterData = $request->all();
        $companyId = session('admin_company_id');
        $topTwentyReportService = resolve(TopTwentyReportService::class);

        return $topTwentyReportService->print($companyId, $filterData);
    }

    public function printWorstTwenty(WorstTwentyCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');

        $worstTwentyReportService = resolve(WorstTwentyReportService::class);

        return $worstTwentyReportService->print($companyId, $filterData);
    }

    public function printStockCard(StockCardCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $stockCardReportService = resolve(StockCardReportService::class);

        return $stockCardReportService->print($filterData, $companyId);
    }

    public function printPromoterCommission(PromoterCommissionCustomReportData $request): string
    {
        $filterData = $request->all();

        $promoterCommissionUpdateReportService = resolve(PromoterCommissionUpdateReportService::class);

        return $promoterCommissionUpdateReportService->printPromoterCommission($filterData);
    }

    public function printSalesByPromoter(SalesByPromoterCustomReportData $request): string
    {
        $filterData = $request->all();

        $salesByPromoterReportService = resolve(SalesByPromoterReportService::class);

        return $salesByPromoterReportService->printSalesByPromoter(session('admin_company_id'), $filterData);
    }

    public function printSaleReturn(SaleReturnCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $saleReturnReportService = resolve(SaleReturnReportService::class);

        return $saleReturnReportService->print($companyId, $filterData);
    }

    public function exportSaleReturn(SaleReturnCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $saleReturnReportService = resolve(SaleReturnReportService::class);

        return $saleReturnReportService->export($companyId, $filterData, $filename);
    }

    public function printGoodsReceivedNote(GoodReceivedNotesCustomReportData $request): string
    {
        $filterData = $request->all();
        $goodsReceivedNoteReportService = resolve(GoodsReceivedNoteReportService::class);

        return $goodsReceivedNoteReportService->print(session('admin_company_id'), $filterData);
    }

    public function printStockTransfer(StockTransferCustomReportData $request): string
    {
        $filterData = $request->all();

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return $stockTransferCustomReportService->print(
            session('admin_company_id'),
            $filterData,
            (bool) $filterData['display_total_price']
        );
    }

    public function printStockTransferDiscrepancy(StockTransferDiscrepancyCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['status_type'] = [StatusTypes::CLOSED->value, StatusTypes::DISCREPANCY->value];
        $filterData['additional_location_id'] = null;

        $stockTransferDiscrepancyCustomReportService = resolve(StockTransferDiscrepancyCustomReportService::class);

        return $stockTransferDiscrepancyCustomReportService->print(session('admin_company_id'), $filterData);
    }

    public function printStockAdjustment(StockAdjustmentCustomReportData $request): string
    {
        $filterData = $request->all();

        $stockAdjustmentCustomReportService = resolve(StockAdjustmentCustomReportService::class);

        return $stockAdjustmentCustomReportService->print(session('admin_company_id'), $filterData);
    }

    public function printDiscount(StockDiscountCustomReportData $request): string
    {
        $filterData = $request->all();

        if ($filterData['sale_discount_type'] === SaleDiscountTypes::CART_WISE->value) {
            $saleDiscountCustomReportService = resolve(SaleDiscountCustomReportService::class);

            return $saleDiscountCustomReportService->print($filterData, session('admin_company_id'));
        }

        $discountCustomReportService = resolve(DiscountCustomReportService::class);

        return $discountCustomReportService->print($filterData, session('admin_company_id'));
    }

    public function exportDiscountReport(StockDiscountCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        if ($filterData['sale_discount_type'] === SaleDiscountTypes::CART_WISE->value) {
            $saleDiscountCustomReportService = resolve(SaleDiscountCustomReportService::class);

            return $saleDiscountCustomReportService->export($filterData, session('admin_company_id'), $filename);
        }

        $discountCustomReportService = resolve(DiscountCustomReportService::class);

        return $discountCustomReportService->export($filterData, session('admin_company_id'), $filename);
    }

    public function exportStockAdjustment(
        StockAdjustmentCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $stockAdjustmentCustomReportService = resolve(StockAdjustmentCustomReportService::class);

        return $stockAdjustmentCustomReportService->export($filterData, session('admin_company_id'), $filename);
    }

    public function exportStockTransfer(StockTransferCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return $stockTransferCustomReportService->export(
            session('admin_company_id'),
            $filterData,
            $filename,
            (bool) $filterData['display_total_price'],
        );
    }

    public function exportStockTransferDiscrepancy(
        StockTransferDiscrepancyCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['status_type'] = [StatusTypes::CLOSED->value, StatusTypes::DISCREPANCY->value];
        $filterData['additional_location_id'] = null;

        $stockTransferDiscrepancyCustomReportService = resolve(StockTransferDiscrepancyCustomReportService::class);

        return $stockTransferDiscrepancyCustomReportService->export(
            session('admin_company_id'),
            $filterData,
            $filename
        );
    }

    public function exportStockCard(StockCardCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $stockCardReportService = resolve(StockCardReportService::class);

        return $stockCardReportService->exportStockCard($filterData, $companyId, $filename);
    }

    public function exportGoodsReceivedNote(
        GoodReceivedNotesCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $goodsReceivedNoteReportService = resolve(GoodsReceivedNoteReportService::class);

        return $goodsReceivedNoteReportService->exportGoodsReceivedNote(
            session('admin_company_id'),
            $filterData,
            $filename
        );
    }

    public function exportWorstTwenty(WorstTwentyCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $worstTwentyReportService = resolve(WorstTwentyReportService::class);

        return $worstTwentyReportService->export($filterData, $companyId, $filename);
    }

    public function exportSalesByPromoter(
        SalesByPromoterCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $salesByPromoterReportService = resolve(SalesByPromoterReportService::class);

        return $salesByPromoterReportService->exportSalesByPromoter(
            session('admin_company_id'),
            $filterData,
            $filename
        );
    }

    public function exportTopTwenty(TopTwentyCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');

        $topTwentyReportService = resolve(TopTwentyReportService::class);

        return $topTwentyReportService->exportTopTwenty($companyId, $filterData, $filename);
    }

    public function exportPromoterCommission(
        PromoterCommissionCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $promoterCommissionUpdateReportService = resolve(PromoterCommissionUpdateReportService::class);

        return $promoterCommissionUpdateReportService->exportPromoterCommissionData($filterData, $filename);
    }

    public function printSuspendAndResume(SuspendAndResumeCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $suspendAndResumeReportService = resolve(SuspendAndResumeReportService::class);

        return $suspendAndResumeReportService->print($companyId, $filterData);
    }

    public function exportSuspendAndResume(
        SuspendAndResumeCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $companyId = session('admin_company_id');
        $suspendAndResumeReportService = resolve(SuspendAndResumeReportService::class);

        return $suspendAndResumeReportService->exportSuspendAndResume($companyId, $filterData, $filename);
    }

    public function getDiscountTypeReports(): array
    {
        $discountTypeReports = collect(DiscountTypeReports::formattedForSelection());
        $discountTypeReports->prepend([
            'id' => 0,
            'name' => 'All Discount',
        ]);

        return [
            'discountTypeReports' => $discountTypeReports,
        ];
    }

    public function getSaleDiscountTypeReports(): array
    {
        $saleDiscountTypeReports = collect(SaleDiscountTypeReports::formattedForSelection());
        $saleDiscountTypeReports->prepend([
            'id' => 0,
            'name' => 'All Discount',
        ]);

        return [
            'saleDiscountTypeReports' => $saleDiscountTypeReports,
        ];
    }

    public function printDiscountSummaryReport(DiscountSummaryCustomReportData $request): string
    {
        $filterData = $request->all();

        if ($filterData['sale_discount_type'] === SaleDiscountTypes::CART_WISE->value) {
            $saleDiscountSummaryCustomReportService = resolve(SaleDiscountSummaryCustomReportService::class);

            return $saleDiscountSummaryCustomReportService->print($filterData, session('admin_company_id'));
        }

        $discountSummaryReportService = resolve(DiscountSummaryReportService::class);

        return $discountSummaryReportService->print($filterData, session('admin_company_id'));
    }

    public function exportDiscountSummaryReport(
        DiscountSummaryCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        if ($filterData['sale_discount_type'] === SaleDiscountTypes::CART_WISE->value) {
            $saleDiscountSummaryCustomReportService = resolve(SaleDiscountSummaryCustomReportService::class);

            return $saleDiscountSummaryCustomReportService->export($filterData, session('admin_company_id'), $filename);
        }

        $discountSummaryReportService = resolve(DiscountSummaryReportService::class);

        return $discountSummaryReportService->export($filterData, session('admin_company_id'), $filename);
    }

    public function printSaleOverallByStore(SaleOverallByStoreCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $salesOverallReportService = resolve(SalesOverallReportService::class);

        return $salesOverallReportService->printSaleOverall($companyId, $filterData);
    }

    public function exportSaleOverallByStore(
        SaleOverallByStoreCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $companyId = session('admin_company_id');
        $salesOverallReportService = resolve(SalesOverallReportService::class);

        return $salesOverallReportService->exportSaleOverall($companyId, $filterData, $filename);
    }

    public function printInterCompany(InterCompanyCustomReportData $request): string
    {
        $filterData = $request->all();

        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return $interCompanyCustomReportService->print(
            session('admin_company_id'),
            $filterData,
            (bool) $filterData['display_purchase_cost']
        );
    }

    public function exportInterCompany(InterCompanyCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return $interCompanyCustomReportService->export(
            session('admin_company_id'),
            $filterData,
            $filename,
            (bool) $filterData['display_purchase_cost']
        );
    }

    /**
     * @return mixed[]
     */
    private function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        return [$stores, $warehouses];
    }

    public function printOrderReport(OrderCustomReportData $request): string
    {
        $filterData = $request->all();
        $ordersCustomReportService = resolve(OrdersCustomReportService::class);

        return $ordersCustomReportService->print($filterData, session('admin_company_id'));
    }

    public function exportOrderReport(OrderCustomReportData $request, string $fileName): BinaryFileResponse
    {
        $filterData = $request->all();

        $ordersCustomReportService = resolve(OrdersCustomReportService::class);

        return $ordersCustomReportService->export($filterData, session('admin_company_id'), $fileName);
    }

    public function printInterCompanyInvoiceReport(InterCompanyInvoiceCustomReportData $request): string
    {
        $filterData = $request->all();

        $interCompanyInvoiceCustomReportService = resolve(InterCompanyInvoiceCustomReportService::class);

        return $interCompanyInvoiceCustomReportService->print($filterData, session('admin_company_id'));
    }

    public function exportInterCompanyInvoiceReport(
        InterCompanyInvoiceCustomReportData $request,
        string $fileName
    ): BinaryFileResponse {
        $filterData = $request->all();

        $interCompanyInvoiceCustomReportService = resolve(InterCompanyInvoiceCustomReportService::class);

        return $interCompanyInvoiceCustomReportService->export(
            $filterData,
            session('admin_company_id'),
            $fileName
        );
    }

    public function seasonalSalesPrint(SeasonalSalesCustomReportData $request): string
    {
        $filterData = $request->all();

        if ((int) $filterData['report_type_id'] === SeasonalReportTypes::BY_SUMMARY->value) {
            $seasonalCustomReportService = resolve(SeasonalCustomReportService::class);

            return $seasonalCustomReportService->print($filterData, session('admin_company_id'));
        }

        if ((int) $filterData['report_type_id'] === SeasonalReportTypes::BY_SEASON->value) {
            $seasonalCustomDetailsReportService = resolve(SeasonalCustomDetailReportService::class);

            return $seasonalCustomDetailsReportService->print($filterData, session('admin_company_id'));
        }

        return '';
    }

    public function exportSeasonalSales(SeasonalSalesCustomReportData $request, string $fileName): BinaryFileResponse
    {
        $filterData = $request->all();

        if ((int) $filterData['report_type_id'] === SeasonalReportTypes::BY_SUMMARY->value) {
            $seasonalCustomReportService = resolve(SeasonalCustomReportService::class);

            return $seasonalCustomReportService->export($filterData, session('admin_company_id'), $fileName);
        }

        if ((int) $filterData['report_type_id'] === SeasonalReportTypes::BY_COMPARISON->value) {
            $seasonalCustomComparisonReportService = resolve(SeasonalCustomComparisonReportService::class);

            return $seasonalCustomComparisonReportService->export($filterData, session('admin_company_id'), $fileName);
        }

        $seasonalCustomDetailsReportService = resolve(SeasonalCustomDetailReportService::class);

        return $seasonalCustomDetailsReportService->export($filterData, session('admin_company_id'), $fileName);
    }

    public function layawaySalesPrint(LaywaySalesCustomReportData $request): string
    {
        $filterData = $request->all();

        $layawaySaleCustomReportService = resolve(LayawaySaleCustomReportService::class);

        return $layawaySaleCustomReportService->print($filterData, session('admin_company_id'));
    }

    public function layawaySalesExport(LaywaySalesCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $layawaySaleCustomReportService = resolve(LayawaySaleCustomReportService::class);

        return $layawaySaleCustomReportService->export($filterData, session('admin_company_id'), $filename);
    }

    public function creditSalesPrint(CreditSalesCustomReportData $request): string
    {
        $filterData = $request->all();

        $creditSaleCustomReportService = resolve(CreditSaleCustomReportService::class);

        return $creditSaleCustomReportService->print($filterData, session('admin_company_id'));
    }

    public function creditSalesExport(CreditSalesCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $creditSaleCustomReportService = resolve(CreditSaleCustomReportService::class);

        return $creditSaleCustomReportService->export($filterData, session('admin_company_id'), $filename);
    }

    public function exportAccumulatedReport(
        AccumulatedSellThroughCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['include_by'] = $request->accumulated_sale_through_include_types;

        if ((int) $filterData['report_type'] === SellThroughReportTypes::BY_PRODUCT->value) {
            $accumulatedSellThroughProductForCustomReportServices = resolve(
                SellThroughProductForCustomReportServices::class
            );

            return $accumulatedSellThroughProductForCustomReportServices->export(
                $filterData,
                session('admin_company_id'),
                $filename
            );
        }

        $accumulatedSellThroughProductForLocationWiseCustomReportServices = resolve(
            SellThroughProductForLocationWiseCustomReportServices::class
        );

        return $accumulatedSellThroughProductForLocationWiseCustomReportServices->export(
            $filterData,
            session('admin_company_id'),
            $filename
        );
    }

    public function printStockTransfersStatusSummary(StockTransferStatusSummaryCustomReportData $request): string
    {
        $filterData = $request->all();

        $stockTransferStatusSummaryCustomReportService = resolve(StockTransferStatusSummaryCustomReportService::class);

        return $stockTransferStatusSummaryCustomReportService->print($filterData, session('admin_company_id'));
    }

    public function exportStockTransfersStatusSummary(
        StockTransferStatusSummaryCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $stockTransferStatusSummaryCustomReportService = resolve(StockTransferStatusSummaryCustomReportService::class);

        return $stockTransferStatusSummaryCustomReportService->export(
            $filterData,
            session('admin_company_id'),
            $filename
        );
    }

    public function printStockSummaryByModule(StockSummaryByModuleReportData $request): string
    {
        $filterData = $request->all();
        $companyId = session('admin_company_id');
        $locations = (array) ($filterData['location_ids'] ?? []);

        $stockSummaryService = resolve(StockSummaryByModuleReportService::class);

        return $stockSummaryService->print($filterData, $companyId, collect($locations));
    }

    public function exportStockSummaryByModule(
        StockSummaryByModuleReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $stockSummaryService = resolve(StockSummaryByModuleReportService::class);
        $locations = (array) ($filterData['location_ids'] ?? []);

        return $stockSummaryService->export(
            $filterData,
            session('admin_company_id'),
            $filename,
            collect($locations),
        );
    }
}
