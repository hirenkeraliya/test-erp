<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Common\Enums\InventoryReport;
use App\Domains\Common\Enums\PurchasingReport;
use App\Domains\Common\Enums\ReportTypes;
use App\Domains\CustomReport\DataObjects\GoodReceivedNotesCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyCustomReportData;
use App\Domains\CustomReport\DataObjects\InterCompanyInvoiceCustomReportData;
use App\Domains\CustomReport\DataObjects\StockAdjustmentCustomReportData;
use App\Domains\CustomReport\DataObjects\StockCardCustomReportData;
use App\Domains\CustomReport\DataObjects\StockMovementsCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferCustomReportData;
use App\Domains\CustomReport\DataObjects\StockTransferDiscrepancyCustomReportData;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteFilterTypes;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteReportTypes;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteReportService;
use App\Domains\InventoryUpdate\Enums\StockCardFilterByReportTypes;
use App\Domains\InventoryUpdate\Enums\StockMovementFilters;
use App\Domains\InventoryUpdate\Enums\StockMovementReportTypes;
use App\Domains\InventoryUpdate\Services\StockCardReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\PurchaseOrder\Enums\InterCompanyCustomReportTypes;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferReportType;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Services\InterCompanyCustomReportService;
use App\Domains\PurchaseOrderInvoice\Services\InterCompanyInvoiceCustomReportService;
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
use App\Http\Controllers\Controller;
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

        $companyId = session('warehouse_manager_selected_location_company_id');
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        return Inertia::render('reports/custom_reports/Index', [
            'customReportMenus' => $customReportMenus,
            'reportTypesStaticDetails' => ReportTypes::getFormattedArrayForStaticUse(),
            'inventoryReports' => InventoryReport::formattedForSelection(),
            'purchasingReports' => PurchasingReport::formattedForSelection(),
            'inventoryReportsStaticDetails' => InventoryReport::generateStaticCasesArray(),
            'purchasingReportsStaticDetails' => PurchasingReport::generateStaticCasesArray(),
            'externalCompanies' => $externalCompanies,
            'stockTransferTransferType' => TransferTypeForReport::formattedForSelection(),
            'stockTransferFilters' => StockTransferCustomReportTypes::formattedForSelection(),
            'stockTransferReportType' => TransferReportType::formattedForSelection(),
            'stockTransferReportDateTypes' => StockTransferCustomReportDateTypes::formattedForSelection(),
            'stockTransferFilterStaticDetails' => StockTransferCustomReportTypes::getFormattedArrayForStaticUse(),
            'stockAdjustmentReportType' => StockAdjustmentReportType::formattedForSelection(),
            'stockAdjustmentFilterType' => StockAdjustmentFilterType::formattedForSelection(),
            'stockAdjustmentTypes' => StockAdjustmentTypes::formattedForSelection(),
            'staticStockAdjustmentFilterType' => StockAdjustmentFilterType::getFormattedArrayForStaticUse(),
            'goodsReceivedNoteFilters' => GoodsReceivedNoteFilterTypes::formattedForSelection(),
            'goodsReceivedNoteReportTypes' => GoodsReceivedNoteReportTypes::formattedForSelection(),
            'goodsReceivedNoteFilterStaticDetails' => GoodsReceivedNoteFilterTypes::getFormattedArrayForStaticUse(),
            'stockCardFilter' => StockCardFilterByReportTypes::formattedForSelection(),
            'stockCardFilterStaticDetails' => StockCardFilterByReportTypes::getFormattedArrayForStaticUse(),
            'stockMovementFilters' => StockMovementFilters::formattedForSelection(),
            'stockMovementReportTypes' => StockMovementReportTypes::formattedForSelection(),
            'stockMovementFilterStaticDetails' => StockMovementFilters::getFormattedArrayForStaticUse(),
            'stockTransferStatuses' => StatusTypes::formattedForSelection(),
            'transferDiscrepancyReportType' => TransferTypeDiscrepancyReport::formattedForSelection(),
            'stockTransferDiscrepancyTransferType' => TransferTypeForReport::getTransferInAndOutOnly(),
            'purchaseOrderFilters' => InterCompanyCustomReportTypes::formattedForSelection(),
            'interCompanyTransferType' => InterCompanyTransferType::formattedForSelection(),
            'interCompanyReportType' => InterCompanyTransferReportType::formattedForSelection(),
            'interCompanyFilterStaticDetails' => InterCompanyCustomReportTypes::getFormattedArrayForStaticUse(),
            'deliveryOrderStaticType' => InterCompanyTransferType::DELIVERY_ORDER->value,
            'transferTypeOut' => TransferTypeForReport::TRANSFER_OUT->value,
            'transferTypeIn' => TransferTypeForReport::TRANSFER_IN->value,
            'productCollections' => $productCollections,
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
        ]);
    }

    public function getCustomReportMenus(): array
    {
        $menus = [];
        $reportTypesLists = ReportTypes::formattedCustomReportForSelectionWarehouseManager();
        foreach ($reportTypesLists as $reportType) {
            $subMenu = [];

            $reportTypeMap = [
                ReportTypes::INVENTORY->value => collect(InventoryReport::getMenuForStoreManagerAndWarehouseManager())
                    ->filter(fn ($menu): bool => $menu['id'] !== InventoryReport::STOCK_SUMMARY_BY_MODULE->value)
                    ->toArray(),
                ReportTypes::PURCHASING->value => PurchasingReport::formattedForSelection(),
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
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];
        $filterData['company_id'] = session('warehouse_manager_selected_location_company_id');

        $stockMovementReportService = resolve(StockMovementReportService::class);

        return $stockMovementReportService->print($filterData);
    }

    public function exportStockMovementReport(
        StockMovementsCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];
        $filterData['company_id'] = session('warehouse_manager_selected_location_company_id');

        $stockMovementReportService = resolve(StockMovementReportService::class);

        return $stockMovementReportService->exportStockMovementReport($filterData, $filename);
    }

    public function printStockCard(StockCardCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('warehouse_manager_selected_location_id');
        $filterData['company_id'] = session('warehouse_manager_selected_location_company_id');

        $companyId = session('warehouse_manager_selected_location_company_id');
        $stockCardReportService = resolve(StockCardReportService::class);

        return $stockCardReportService->print($filterData, $companyId);
    }

    public function printStockTransfer(StockTransferCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];

        $companyId = session('warehouse_manager_selected_location_company_id');
        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return $stockTransferCustomReportService->print(
            $companyId,
            $filterData,
            (bool) $filterData['display_total_price']
        );
    }

    public function exportStockTransfer(StockTransferCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];

        $stockTransferCustomReportService = resolve(StockTransferCustomReportService::class);

        return $stockTransferCustomReportService->export(
            session('warehouse_manager_selected_location_company_id'),
            $filterData,
            $filename,
            (bool) $filterData['display_total_price']
        );
    }

    public function printGoodsReceivedNote(GoodReceivedNotesCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];

        $companyId = session('warehouse_manager_selected_location_company_id');
        $goodsReceivedNoteReportService = resolve(GoodsReceivedNoteReportService::class);

        return $goodsReceivedNoteReportService->print($companyId, $filterData);
    }

    public function exportStockCard(StockCardCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('warehouse_manager_selected_location_id');
        $filterData['company_id'] = session('warehouse_manager_selected_location_company_id');

        $companyId = session('warehouse_manager_selected_location_company_id');
        $stockCardReportService = resolve(StockCardReportService::class);

        return $stockCardReportService->exportStockCard($filterData, $companyId, $filename);
    }

    public function exportGoodsReceivedNote(
        GoodReceivedNotesCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];

        $goodsReceivedNoteReportService = resolve(GoodsReceivedNoteReportService::class);

        return $goodsReceivedNoteReportService->exportGoodsReceivedNote(
            session('warehouse_manager_selected_location_company_id'),
            $filterData,
            $filename
        );
    }

    public function printStockAdjustment(StockAdjustmentCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];

        $stockAdjustmentCustomReportService = resolve(StockAdjustmentCustomReportService::class);

        return $stockAdjustmentCustomReportService->print(
            session('warehouse_manager_selected_location_company_id'),
            $filterData,
        );
    }

    public function exportStockAdjustment(
        StockAdjustmentCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];

        $stockAdjustmentCustomReportService = resolve(StockAdjustmentCustomReportService::class);

        return $stockAdjustmentCustomReportService->export(
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            $filename
        );
    }

    public function printStockTransferDiscrepancy(StockTransferDiscrepancyCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];
        $filterData['status_type'] = [StatusTypes::CLOSED->value, StatusTypes::DISCREPANCY->value];
        $filterData['additional_location_id'] = null;

        $stockTransferDiscrepancyCustomReportService = resolve(StockTransferDiscrepancyCustomReportService::class);

        return $stockTransferDiscrepancyCustomReportService->print(
            session('warehouse_manager_selected_location_company_id'),
            $filterData
        );
    }

    public function exportStockTransferDiscrepancy(
        StockTransferDiscrepancyCustomReportData $request,
        string $filename
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_ids'] = [session('warehouse_manager_selected_location_id')];
        $filterData['status_type'] = [StatusTypes::CLOSED->value, StatusTypes::DISCREPANCY->value];
        $filterData['additional_location_id'] = null;

        $stockTransferDiscrepancyCustomReportService = resolve(StockTransferDiscrepancyCustomReportService::class);

        return $stockTransferDiscrepancyCustomReportService->export(
            session('warehouse_manager_selected_location_company_id'),
            $filterData,
            $filename
        );
    }

    public function printInterCompany(InterCompanyCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('warehouse_manager_selected_location_id');

        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return $interCompanyCustomReportService->print(
            session('warehouse_manager_selected_location_company_id'),
            $filterData,
            (bool) $filterData['display_purchase_cost']
        );
    }

    public function exportInterCompany(InterCompanyCustomReportData $request, string $filename): BinaryFileResponse
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('warehouse_manager_selected_location_id');

        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return $interCompanyCustomReportService->export(
            session('warehouse_manager_selected_location_company_id'),
            $filterData,
            $filename,
            (bool) $filterData['display_purchase_cost']
        );
    }

    public function printInterCompanyInvoiceReport(InterCompanyInvoiceCustomReportData $request): string
    {
        $filterData = $request->all();
        $filterData['location_id'] = session('warehouse_manager_selected_location_id');

        $interCompanyInvoiceCustomReportService = resolve(InterCompanyInvoiceCustomReportService::class);

        return $interCompanyInvoiceCustomReportService->print(
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
        );
    }

    public function exportInterCompanyInvoiceReport(
        InterCompanyInvoiceCustomReportData $request,
        string $fileName
    ): BinaryFileResponse {
        $filterData = $request->all();
        $filterData['location_id'] = session('warehouse_manager_selected_location_id');

        $interCompanyInvoiceCustomReportService = resolve(InterCompanyInvoiceCustomReportService::class);

        return $interCompanyInvoiceCustomReportService->export(
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            $fileName
        );
    }

    /**
     * @return mixed[]
     */
    public function getStoresAndWareHouses(): array
    {
        $companyId = session('warehouse_manager_selected_location_company_id');

        $customReportService = resolve(CustomReportService::class);

        return $customReportService->getStoresAndWareHousesByCompanyId($companyId);
    }
}
