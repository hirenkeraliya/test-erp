<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\CashMovement\Enums\CashMovementFilterTypes;
use App\Domains\CashMovement\Services\CashMovementReportService;
use App\Domains\Common\Enums\InventoryReport;
use App\Domains\Common\Enums\MerchandisingReport;
use App\Domains\Common\Enums\OrdersReport;
use App\Domains\Common\Enums\PurchasingReport;
use App\Domains\Common\Enums\ReportTypes;
use App\Domains\Common\Enums\SalesReport;
use App\Domains\CustomReport\DataObjects\CashMovementCustomReportData;
use App\Domains\CustomReport\DataObjects\CreditSalesCustomReportData;
use App\Domains\CustomReport\DataObjects\DiscountSummaryCustomReportData;
use App\Domains\CustomReport\DataObjects\GeneralSalesCustomReportData;
use App\Domains\CustomReport\DataObjects\GoodReceivedNotesCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyInvoiceCustomReportData;
use App\Domains\CustomReport\DataObjects\LaywaySalesCustomReportData;
use App\Domains\CustomReport\DataObjects\OrderCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleExchangeCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleHourCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleOverallByStoreCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleReturnAndExchangeCustomReportData;
use App\Domains\CustomReport\DataObjects\SaleReturnCustomReportData;
use App\Domains\CustomReport\DataObjects\SalesByPromoterCustomReportData;
use App\Domains\CustomReport\DataObjects\SalesCollectionCustomReportData;
use App\Domains\CustomReport\DataObjects\StockAdjustmentCustomReportData;
use App\Domains\CustomReport\DataObjects\StockCardCustomReportData;
use App\Domains\CustomReport\DataObjects\StockDiscountCustomReportData;
use App\Domains\CustomReport\DataObjects\StockMovementsCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferDiscrepancyCustomReportData;
use App\Domains\CustomReport\DataObjects\SuspendAndResumeCustomReportData;
use App\Domains\CustomReport\DataObjects\TopTwentyCustomReportData;
use App\Domains\CustomReport\DataObjects\VoidReportCustomReportData;
use App\Domains\CustomReport\DataObjects\WorstTwentyCustomReportData;
use App\Domains\CustomReport\Services\CustomReportService;
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
use App\Domains\Order\Enums\OrderFilterTypes;
use App\Domains\Order\Enums\OrderReportTypes;
use App\Domains\Order\Services\OrdersCustomReportService;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\Promoter\Enums\SalesByPromoterFilterTypes;
use App\Domains\Promoter\Enums\SalesByPromoterReportTypes;
use App\Domains\Promoter\services\SalesByPromoterReportService;
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
use App\Domains\Sale\Enums\SaleDiscountTypeReports;
use App\Domains\Sale\Enums\SaleReturnAndSaleExchangeFilterTypes;
use App\Domains\Sale\Enums\SalesCollectionFilterTypes;
use App\Domains\Sale\Enums\SalesCollectionReportTypes;
use App\Domains\Sale\Enums\SalesExchangeFilterTypes;
use App\Domains\Sale\Enums\SalesOverallReportTypes;
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
use App\Domains\Sale\Services\TopTwentyReportService;
use App\Domains\Sale\Services\WorstTwentyReportService;
use App\Domains\SaleDiscount\Enums\SaleDiscountTypes;
use App\Domains\SaleReturn\Enums\SaleReturnFilterTypes;
use App\Domains\SaleReturn\Services\SaleReturnReportService;
use App\Domains\StockAdjustment\Enums\StockAdjustmentFilterType;
use App\Domains\StockAdjustment\Enums\StockAdjustmentReportType;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Domains\StockAdjustment\Services\StockAdjustmentCustomReportService;
use App\Domains\StockMovement\Services\StockMovementReportService;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use App\Domains\StockTransfer\Enums\StockTransferCustomReportTypes;
use App\Domains\StockTransfer\Enums\TransferReportType;
use App\Domains\StockTransfer\Enums\TransferTypeDiscrepancyReport;
use App\Domains\StockTransfer\Enums\TransferTypeForReport;
use App\Domains\StockTransfer\Services\StockTransferCustomReportService;
use App\Domains\StockTransfer\Services\StockTransferDiscrepancyCustomReportService;
use App\Domains\SuspendAndResume\Enums\SuspendAndResumeFilterTypes;
use App\Domains\VoidSale\Enums\VoidFilterTypes;
use App\Domains\VoidSale\Services\VoidReportService;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomReportController extends Controller
{
    public function index(): Response
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getAll();

        $customReportMenus = $this->getCustomReportMenus();

        $companyId = session('store_manager_selected_location_company_id');
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        return Inertia::render('reports/custom_reports/Index', [
            'customReportMenus' => $customReportMenus,
            'reportTypesStaticDetails' => ReportTypes::getFormattedArrayForStaticUse(),
            'salesReports' => SalesReport::formattedForSelection(),
            'inventoryReports' => InventoryReport::formattedForSelection(),
            'merchandisingReports' => MerchandisingReport::formattedForSelection(),
            'purchasingReports' => PurchasingReport::formattedForSelection(),
            'orderReports' => OrdersReport::formattedForSelection(),
            'salesReportsStaticDetails' => SalesReport::generateStaticCasesArray(),
            'inventoryReportsStaticDetails' => InventoryReport::generateStaticCasesArray(),
            'merchandisingReportsStaticDetails' => MerchandisingReport::generateStaticCasesArray(),
            'purchasingReportsStaticDetails' => PurchasingReport::generateStaticCasesArray(),
            'orderReportsStaticDetails' => OrdersReport::generateStaticCasesArray(),
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
            'stockAdjustmentReportType' => StockAdjustmentReportType::formattedForSelection(),
            'stockAdjustmentFilterType' => StockAdjustmentFilterType::formattedForSelection(),
            'stockAdjustmentTypes' => StockAdjustmentTypes::formattedForSelection(),
            'staticStockAdjustmentFilterType' => StockAdjustmentFilterType::getFormattedArrayForStaticUse(),
            'generalSalesReportStaticDetails' => GeneralSalesReportTypes::getFormattedArrayForStaticUse(),
            'generalSalesFilterStaticDetails' => GeneralSalesFilterTypes::getFormattedArrayForStaticUse(),
            'salesByPromoterFilters' => SalesByPromoterFilterTypes::formattedForSelection(),
            'salesByPromoterReports' => SalesByPromoterReportTypes::formattedForSelection(),
            'salesByPromoterFilterStaticDetails' => SalesByPromoterFilterTypes::getFormattedArrayForStaticUse(),
            'salesByPromoterReportStaticDetails' => [
                'byDetails' => SalesByPromoterReportTypes::BY_DETAILS->value,
            ],
            'stockTransferTransferType' => TransferTypeForReport::formattedForSelection(),
            'stockTransferReportDateTypes' => StockTransferCustomReportDateTypes::formattedForSelection(),
            'stockTransferFilters' => StockTransferCustomReportTypes::formattedForSelection(),
            'stockTransferReportType' => TransferReportType::formattedForSelection(),
            'stockTransferFilterStaticDetails' => StockTransferCustomReportTypes::getFormattedArrayForStaticUse(),
            'goodsReceivedNoteFilters' => GoodsReceivedNoteFilterTypes::formattedForSelection(),
            'goodsReceivedNoteReportTypes' => GoodsReceivedNoteReportTypes::formattedForSelection(),
            'goodsReceivedNoteFilterStaticDetails' => GoodsReceivedNoteFilterTypes::getFormattedArrayForStaticUse(),
            'stockCardFilter' => StockCardFilterByReportTypes::formattedForSelection(),
            'stockCardFilterStaticDetails' => StockCardFilterByReportTypes::getFormattedArrayForStaticUse(),
            'stockMovementFilters' => StockMovementFilters::formattedForSelection(),
            'stockMovementReportTypes' => StockMovementReportTypes::formattedForSelection(),
            'stockMovementFilterStaticDetails' => StockMovementFilters::getFormattedArrayForStaticUse(),
            'discountTypeFilter' => DiscountTypeFilters::formattedForSelection(),
            'discountTypeStaticFilters' => DiscountTypeFilters::getFormattedArrayForStaticUse(),
            'discountTypeReports' => DiscountTypeReports::formattedForSelection(),
            'topTwentyReportTypes' => TopTwentyReportTypes::formattedForSelection(),
            'topTwentyReportStaticTypes' => TopTwentyReportTypes::getFormattedArrayForStaticUse(),
            'topTwentyReportViewTypes' => TopTwentyReportViewTypes::formattedForSelection(),
            'topTwentyReportViewStaticTypes' => TopTwentyReportViewTypes::getFormattedArrayForStaticUse(),
            'topTwentyFilters' => TopTwentyFilterTypes::formattedForSelection(),
            'topTwentyFilterStaticDetails' => TopTwentyFilterTypes::getFormattedArrayForStaticUse(),
            'worstTwentyReportTypes' => WorstTwentyReportTypes::formattedForSelection(),
            'worstTwentyReportStaticTypes' => WorstTwentyReportTypes::getFormattedArrayForStaticUse(),
            'worstTwentyFilters' => WorstTwentyFilterTypes::formattedForSelection(),
            'worstTwentyFilterStaticDetails' => WorstTwentyFilterTypes::getFormattedArrayForStaticUse(),
            'worstTwentyReportViewTypes' => WorstTwentyReportViewTypes::formattedForSelection(),
            'worstTwentyReportViewStaticTypes' => WorstTwentyReportViewTypes::getFormattedArrayForStaticUse(),
            'stockTransferStatuses' => StatusTypes::formattedForSelection(),
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
            'layawayReportTypes' => LayawayReportTypes::formattedForSelection(),
            'orderFilterStaticTypes' => OrderFilterTypes::getFormattedArrayForStaticUse(),
            'creditReportTypes' => CreditReportTypes::formattedForSelection(),
            'deliveryOrderStaticType' => InterCompanyTransferType::DELIVERY_ORDER->value,
            'transferTypeOut' => TransferTypeForReport::TRANSFER_OUT->value,
            'transferTypeIn' => TransferTypeForReport::TRANSFER_IN->value,
            'productCollections' => $productCollections,
            'eInvoiceFilter' => EInvoiceFilter::formattedForSelection(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
            'saleDiscountTypes' => SaleDiscountTypes::formattedForSelection(),
            'saleDiscountTypesStaticFilters' => SaleDiscountTypes::getFormattedArrayForStaticUse(),
            'saleDiscountTypeReports' => SaleDiscountTypeReports::formattedForSelection(),
            'saleDiscountTypeReportStaticFilters' => SaleDiscountTypeReports::getFormattedArrayForStaticUse(),
        ]);
    }

    public function getCustomReportMenus(): array
    {
        $menus = [];
        $reportTypesLists = ReportTypes::formattedCustomReportForSelectionStoreManager();
        foreach ($reportTypesLists as $reportType) {
            $subMenu = [];

            $reportTypeMap = [
                ReportTypes::SALES->value => SalesReport::formattedForSelection(),
                ReportTypes::INVENTORY->value => collect(InventoryReport::getMenuForStoreManagerAndWarehouseManager())
                    ->filter(fn ($menu): bool => $menu['id'] !== InventoryReport::STOCK_SUMMARY_BY_MODULE->value)
                    ->toArray(),
                ReportTypes::MERCHANDISING->value => MerchandisingReport::formattedForSelection(),
                ReportTypes::PURCHASING->value => PurchasingReport::formattedForSelection(),
                ReportTypes::ORDERS->value => OrdersReport::formattedForSelection(),
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

    public function stockMovementReportPrint(StockMovementsCustomReportData $request): string
    {
        $filterData = $request->all();

        $filterData['location_ids'] = [session('store_manager_selected_location_id')];
        $filterData['company_id'] = session('store_manager_selected_location_company_id');

        $stockMovementReportService = resolve(StockMovementReportService::class);

        return $stockMovementReportService->print($filterData);
    }

    public function saleHourPrint(SaleHourCustomReportData $request): string
    {
        $filterData = $request->all();

        $filterData['location_id'] = session('store_manager_selected_location_id');

        $companyId = session('store_manager_selected_location_company_id');

        $saleHourReportService = resolve(SaleHourReportService::class);

        return $saleHourReportService->print($companyId, $filterData);
    }

    public function exportSaleHour(SaleHourCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();

        $filterData['location_id'] = session('store_manager_selected_location_id');
        $filterData['company_id'] = session('store_manager_selected_location_company_id');

        $saleHourReportService = resolve(SaleHourReportService::class);

        return $saleHourReportService->export($filterData, $filename);
    }

    public function print(SalesCollectionCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $salesCollectionReportService = resolve(SalesCollectionReportService::class);

        return $salesCollectionReportService->print($companyId, $filterData);
    }

    public function exportSaleCollection(SalesCollectionCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $salesCollectionReportService = resolve(SalesCollectionReportService::class);

        return $salesCollectionReportService->exportSaleCollection($companyId, $filterData, $filename);
    }

    public function exportStockMovementReport(
        StockMovementsCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $filterData['location_ids'] = [session('store_manager_selected_location_id')];
        $filterData['company_id'] = session('store_manager_selected_location_company_id');

        $stockMovementReportService = resolve(StockMovementReportService::class);

        return $stockMovementReportService->exportStockMovementReport($filterData, $filename);
    }

    public function printExchange(SaleExchangeCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $salesExchangeReportService = resolve(SalesExchangeReportService::class);

        return $salesExchangeReportService->print($companyId, $filterData);
    }

    public function printVoidReport(VoidReportCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $voidReportService = resolve(VoidReportService::class);

        return $voidReportService->print($companyId, $filterData);
    }

    public function exportVoidReport(VoidReportCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $voidReportService = resolve(VoidReportService::class);

        return $voidReportService->exportVoidSaleReport($companyId, $filterData, $filename);
    }

    public function printGeneralSale(GeneralSalesCustomReportData $request): string
    {
        $companyId = session('store_manager_selected_location_company_id');

        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $generalSalesReportService = resolve(GeneralSalesReportService::class);

        return $generalSalesReportService->print(
            $companyId,
            $filterData,
            (bool) $filterData['exclude_products_with_no_price']
        );
    }

    public function exportGeneralSalesReport(
        GeneralSalesCustomReportData $request,
        string $filename
    ): ?BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

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
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $topTwentyReportService = resolve(TopTwentyReportService::class);

        return $topTwentyReportService->print($companyId, $filterData);
    }

    public function printWorstTwenty(WorstTwentyCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $worstTwentyCategoryReportService = resolve(WorstTwentyReportService::class);

        return $worstTwentyCategoryReportService->print($companyId, $filterData);
    }

    public function printStockCard(StockCardCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('store_manager_selected_location_id');

        $companyId = session('store_manager_selected_location_company_id');

        $stockCardReportService = resolve(StockCardReportService::class);

        return $stockCardReportService->print($filterData, $companyId);
    }

    public function printCashMovement(CashMovementCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $customReportService = resolve(CashMovementReportService::class);

        return $customReportService->printCashMovement($companyId, $filterData);
    }

    public function exportCashMovementsReport(
        CashMovementCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $customReportService = resolve(CashMovementReportService::class);

        return $customReportService->exportCashMovement($companyId, $filterData, $filename);
    }

    public function printSalesByPromoter(SalesByPromoterCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

        $salesByPromoterReportService = resolve(SalesByPromoterReportService::class);

        return $salesByPromoterReportService->printSalesByPromoter($companyId, $filterData);
    }

    public function printStockTransfer(StockTransferCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return $stockTransferCustomReportService->print(
            $companyId,
            $filterData,
            (bool) $filterData['display_total_price'],
        );
    }

    public function exportStockTransfer(StockTransferCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return $stockTransferCustomReportService->export(
            session('store_manager_selected_location_company_id'),
            $filterData,
            $filename,
            (bool) $filterData['display_total_price'],
        );
    }

    public function printGoodsReceivedNote(GoodReceivedNotesCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $goodsReceivedNoteReportService = resolve(GoodsReceivedNoteReportService::class);

        return $goodsReceivedNoteReportService->print(
            session('store_manager_selected_location_company_id'),
            $filterData
        );
    }

    public function printReturnAndExchange(SaleReturnAndExchangeCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

        $saleReturnAndSaleExchangeReportService = resolve(SaleReturnAndSaleExchangeReportService::class);

        return $saleReturnAndSaleExchangeReportService->print($companyId, $filterData);
    }

    public function printSaleReturn(SaleReturnCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $saleReturnReportService = resolve(SaleReturnReportService::class);

        return $saleReturnReportService->print($companyId, $filterData);
    }

    public function exportStockCard(StockCardCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('store_manager_selected_location_id');

        $companyId = session('store_manager_selected_location_company_id');
        $stockCardReportService = resolve(StockCardReportService::class);

        return $stockCardReportService->exportStockCard($filterData, $companyId, $filename);
    }

    public function exportGoodsReceivedNote(
        GoodReceivedNotesCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $goodsReceivedNoteReportService = resolve(GoodsReceivedNoteReportService::class);

        return $goodsReceivedNoteReportService->exportGoodsReceivedNote(
            session('store_manager_selected_location_company_id'),
            $filterData,
            $filename
        );
    }

    public function exportWorstTwenty(WorstTwentyCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $worstTwentyReportService = resolve(WorstTwentyReportService::class);

        return $worstTwentyReportService->export($filterData, $companyId, $filename);
    }

    public function exportSalesByPromoter(
        SalesByPromoterCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

        $salesByPromoterReportService = resolve(SalesByPromoterReportService::class);

        return $salesByPromoterReportService->exportSalesByPromoter($companyId, $filterData, $filename);
    }

    public function exportTopTwenty(TopTwentyCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $topTwentyReportService = resolve(TopTwentyReportService::class);

        return $topTwentyReportService->exportTopTwenty($companyId, $filterData, $filename);
    }

    public function printSuspendAndResume(SuspendAndResumeCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

        $suspendAndResumeReportService = resolve(SuspendAndResumeReportService::class);

        return $suspendAndResumeReportService->print($companyId, $filterData);
    }

    public function exportSuspendAndResume(
        SuspendAndResumeCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');
        $suspendAndResumeReportService = resolve(SuspendAndResumeReportService::class);

        return $suspendAndResumeReportService->exportSuspendAndResume($companyId, $filterData, $filename);
    }

    public function exportSaleReturn(SaleReturnCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

        $saleReturnReportService = resolve(SaleReturnReportService::class);

        return $saleReturnReportService->export($companyId, $filterData, $filename);
    }

    public function exportExchange(SaleExchangeCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

        $salesExchangeReportService = resolve(SalesExchangeReportService::class);

        return $salesExchangeReportService->export($filterData, $companyId, $filename);
    }

    public function exportReturnAndExchange(
        SaleReturnAndExchangeCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

        $saleReturnAndSaleExchangeReportService = resolve(SaleReturnAndSaleExchangeReportService::class);

        return $saleReturnAndSaleExchangeReportService->export($filterData, $companyId, $filename);
    }

    public function printDiscount(StockDiscountCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $companyId = session('store_manager_selected_location_company_id');

        if ($filterData['sale_discount_type'] === SaleDiscountTypes::CART_WISE->value) {
            $saleDiscountCustomReportService = resolve(SaleDiscountCustomReportService::class);

            return $saleDiscountCustomReportService->print($filterData, $companyId);
        }

        $discountCustomReportService = resolve(DiscountCustomReportService::class);

        return $discountCustomReportService->print($filterData, session('store_manager_selected_location_company_id'));
    }

    public function exportDiscountReport(StockDiscountCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        if ($filterData['sale_discount_type'] === SaleDiscountTypes::CART_WISE->value) {
            $saleDiscountCustomReportService = resolve(SaleDiscountCustomReportService::class);

            return $saleDiscountCustomReportService->export(
                $filterData,
                session('store_manager_selected_location_company_id'),
                $filename
            );
        }

        $discountCustomReportService = resolve(DiscountCustomReportService::class);

        return $discountCustomReportService->export(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $filename
        );
    }

    public function printStockAdjustment(StockAdjustmentCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $stockAdjustmentCustomReportService = resolve(StockAdjustmentCustomReportService::class);

        return $stockAdjustmentCustomReportService->print(
            session('store_manager_selected_location_company_id'),
            $filterData,
        );
    }

    public function exportStockAdjustment(
        StockAdjustmentCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $stockAdjustmentCustomReportService = resolve(StockAdjustmentCustomReportService::class);

        return $stockAdjustmentCustomReportService->export(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $filename
        );
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
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        if ($filterData['sale_discount_type'] === SaleDiscountTypes::CART_WISE->value) {
            $saleDiscountSummaryCustomReportService = resolve(SaleDiscountSummaryCustomReportService::class);

            return $saleDiscountSummaryCustomReportService->print(
                $filterData,
                session('store_manager_selected_location_company_id')
            );
        }

        $discountSummaryReportService = resolve(DiscountSummaryReportService::class);

        return $discountSummaryReportService->print($filterData, session('store_manager_selected_location_company_id'));
    }

    public function exportDiscountSummaryReport(
        DiscountSummaryCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        if ($filterData['sale_discount_type'] === SaleDiscountTypes::CART_WISE->value) {
            $saleDiscountSummaryCustomReportService = resolve(SaleDiscountSummaryCustomReportService::class);

            return $saleDiscountSummaryCustomReportService->export(
                $filterData,
                session('store_manager_selected_location_company_id'),
                $filename
            );
        }

        $discountSummaryReportService = resolve(DiscountSummaryReportService::class);

        return $discountSummaryReportService->export(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $filename
        );
    }

    public function printSaleOverallByStore(SaleOverallByStoreCustomReportData $request): string
    {
        $filterData = $request->all();

        $companyId = session('store_manager_selected_location_company_id');
        $salesOverallReportService = resolve(SalesOverallReportService::class);

        return $salesOverallReportService->printSaleOverall(
            $companyId,
            $filterData,
            session('store_manager_selected_location_id')
        );
    }

    public function exportSaleOverallByStore(
        SaleOverallByStoreCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();

        $companyId = session('store_manager_selected_location_company_id');
        $salesOverallReportService = resolve(SalesOverallReportService::class);

        return $salesOverallReportService->exportSaleOverall(
            $companyId,
            $filterData,
            $filename,
            session('store_manager_selected_location_id')
        );
    }

    public function printStockTransferDiscrepancy(StockTransferDiscrepancyCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];
        $filterData['status_type'] = [StatusTypes::CLOSED->value, StatusTypes::DISCREPANCY->value];
        $filterData['additional_location_id'] = null;

        $stockTransferDiscrepancyCustomReportService = resolve(StockTransferDiscrepancyCustomReportService::class);

        return $stockTransferDiscrepancyCustomReportService->print(
            session('store_manager_selected_location_company_id'),
            $filterData
        );
    }

    public function exportStockTransferDiscrepancy(
        StockTransferDiscrepancyCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];
        $filterData['status_type'] = [StatusTypes::CLOSED->value, StatusTypes::DISCREPANCY->value];
        $filterData['additional_location_id'] = null;

        $stockTransferDiscrepancyCustomReportService = resolve(StockTransferDiscrepancyCustomReportService::class);

        return $stockTransferDiscrepancyCustomReportService->export(
            session('store_manager_selected_location_company_id'),
            $filterData,
            $filename
        );
    }

    public function printInterCompany(InterCompanyCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('store_manager_selected_location_id');

        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return $interCompanyCustomReportService->print(
            session('store_manager_selected_location_company_id'),
            $filterData,
            (bool) $filterData['display_purchase_cost']
        );
    }

    public function exportInterCompany(InterCompanyCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('store_manager_selected_location_id');

        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return $interCompanyCustomReportService->export(
            session('store_manager_selected_location_company_id'),
            $filterData,
            $filename,
            (bool) $filterData['display_purchase_cost']
        );
    }

    public function printOrderReport(OrderCustomReportData $request): string
    {
        /** @var StoreManager $storeManager */
        $storeManager = auth()->user();

        $filterData = $request->all();
        $filterData['location_id'] = session('store_manager_selected_location_id');
        $filterData['store_manager_id'] = $storeManager->getKey();

        $ordersCustomReportService = resolve(OrdersCustomReportService::class);

        return $ordersCustomReportService->print($filterData, session('store_manager_selected_location_company_id'));
    }

    public function exportOrderReport(OrderCustomReportData $request, string $fileName): BinaryFileResponse
    {
        /** @var StoreManager $storeManager */
        $storeManager = auth()->user();

        $filterData = $request->all();
        $filterData['location_id'] = session('store_manager_selected_location_id');
        $filterData['store_manager_id'] = $storeManager->getKey();

        $ordersCustomReportService = resolve(OrdersCustomReportService::class);

        return $ordersCustomReportService->export(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $fileName
        );
    }

    public function printInterCompanyInvoiceReport(InterCompanyInvoiceCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('store_manager_selected_location_id');

        $interCompanyInvoiceCustomReportService = resolve(InterCompanyInvoiceCustomReportService::class);

        return $interCompanyInvoiceCustomReportService->print(
            $filterData,
            session('store_manager_selected_location_company_id'),
        );
    }

    public function exportInterCompanyInvoiceReport(
        InterCompanyInvoiceCustomReportData $request,
        string $fileName
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_id'] = session('store_manager_selected_location_id');

        $interCompanyInvoiceCustomReportService = resolve(InterCompanyInvoiceCustomReportService::class);

        return $interCompanyInvoiceCustomReportService->export(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $fileName
        );
    }

    public function layawaySalesPrint(LaywaySalesCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $layawaySaleCustomReportService = resolve(LayawaySaleCustomReportService::class);

        return $layawaySaleCustomReportService->print(
            $filterData,
            session('store_manager_selected_location_company_id')
        );
    }

    public function layawaySalesExport(LaywaySalesCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $layawaySaleCustomReportService = resolve(LayawaySaleCustomReportService::class);

        return $layawaySaleCustomReportService->export(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $filename
        );
    }

    public function creditSalesPrint(CreditSalesCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $creditSaleCustomReportService = resolve(CreditSaleCustomReportService::class);

        return $creditSaleCustomReportService->print(
            $filterData,
            session('store_manager_selected_location_company_id')
        );
    }

    public function creditSalesExport(CreditSalesCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('store_manager_selected_location_id')];

        $creditSaleCustomReportService = resolve(CreditSaleCustomReportService::class);

        return $creditSaleCustomReportService->export(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $filename
        );
    }

    /**
     * @return mixed[]
     */
    public function getStoresAndWareHouses(): array
    {
        $companyId = session('store_manager_selected_location_company_id');

        $customReportService = resolve(CustomReportService::class);

        return $customReportService->getStoresAndWareHousesByCompanyId($companyId);
    }
}
