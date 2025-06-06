<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate\Services;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\InventoryUpdate\Enums\StockCardFilterByReportTypes;
use App\Domains\InventoryUpdate\Exports\StockCardReportExport;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Domains\StockTransfer\Services\StockTransferService;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Sale;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockCardReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $location = $locationQueries->getLocationNameById((int) $filterData['location_id'], $company->id);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdates = $inventoryUpdateQueries->getByProductIdAndLocationForStockCardPrint($filterData);

        return $this->stockCardDetails($inventoryUpdates->groupBy('product.upc'), $filterData, $company, $location);
    }

    public function exportStockCard(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $location = $locationQueries->getLocationNameById((int) $filterData['location_id'], $company->id);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdates = $inventoryUpdateQueries->getByProductIdAndLocationForStockCardPrint($filterData);

        [$storeInventories, $product, $storeInventoriesTotals] = $this->preparedStockCardReport(
            $inventoryUpdates->groupBy('product.upc'),
            $filterData
        );
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $articleNumber = $this->getArticleNumber($filterData, $product?->article_number);

        $filterBy = $this->filterBy($filterData, $company->id);

        return Excel::download(
            new StockCardReportExport(
                $storeInventories,
                $location,
                $company,
                $dateRange,
                $articleNumber,
                $storeInventoriesTotals,
                $filterBy
            ),
            $filename
        );
    }

    private function stockCardDetails(
        Collection $inventoryUpdatesGroupsWise,
        array $filterData,
        Company $company,
        string $location
    ): string {
        $customReportService = resolve(CustomReportService::class);
        [$storeInventories, $product, $storeInventoriesTotals] = $this->preparedStockCardReport(
            $inventoryUpdatesGroupsWise,
            $filterData
        );

        return view('prints.stock_card', [
            'locationName' => $location,
            'articleNumber' => $this->getArticleNumber($filterData, $product?->article_number),
            'company' => $company,
            'storeInventories' => $storeInventories,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'grandTotals' => $storeInventoriesTotals,
            'filterBy' => $this->filterBy($filterData, $company->id),
        ])->render();
    }

    private function preparedStockCardReport(Collection $inventoryUpdatesGroupsWise, array $filterData): array
    {
        $productService = resolve(ProductService::class);
        $product = null;
        $storeInventories = [];
        $storeInventoriesTotals = [
            'total_opening_balance' => 0,
            'total_qty_in' => 0,
            'total_qty_out' => 0,
            'total_balance' => 0,
        ];
        foreach ($inventoryUpdatesGroupsWise as $inventoryUpdateGroupWise) {
            $inventory = $inventoryUpdateGroupWise->first();
            $product = $inventory->product;

            $storeInventories[$inventory->product_id]['name'] = $product->name;
            $storeInventories[$inventory->product_id]['upc'] = $product->upc;
            if (config('app.product_variant')) {
                $storeInventories[$inventory->product_id]['attributes'] = $productService->getAttributesForPrint(
                    $product
                );
            } else {
                $storeInventories[$inventory->product_id]['color'] = $product->color->name ?? 'N/A';
                $storeInventories[$inventory->product_id]['size'] = $product->size->name ?? 'N/A';
            }

            $filterDate = '';

            if (isset($filterData['date_range'][0]) && is_string(
                $filterData['date_range'][0]
            ) && $carbonDate = Carbon::createFromFormat('Y-m-d', $filterData['date_range'][0])) {
                $filterDate = $carbonDate->format('d-M-Y');
            }

            $inventoryUpdate = $inventory;
            $storeInventory = [];
            $inventoryUpdateData = [];
            $storeInventory['transaction_date'] = $filterDate;
            $storeInventory['post_date'] = $filterDate;
            $storeInventory['type'] = 'OP';
            $storeInventory['document_no'] = 'Opening';
            $storeInventory['reference_number'] = '';
            $storeInventory['description'] = 'Daily Opening';
            $storeInventory['qty_in'] = '';
            $storeInventory['qty_out'] = '';
            $storeInventory['balance'] = 0;
            if ($inventoryUpdate) {
                $storeInventory['balance'] = $inventoryUpdate->closing_stock - (float) $inventoryUpdate->quantity;
            }

            $inventoryUpdateData[] = $storeInventory;

            $totalIn = 0;
            $totalOut = 0;
            $balance = $storeInventory['balance'];
            $openingBalance = $storeInventory['balance'];

            foreach ($inventoryUpdateGroupWise as $inventoryUpdate) {
                /** @var Carbon $date */
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $inventoryUpdate->happened_at);
                $storeInventory['transaction_date'] = $date->format('d-M-Y');
                if (
                    ModelMapping::getCaseName(ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    $quantityIn = CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    $quantityOut = 0;
                    $documentNumber = 'GRN: ' . ($inventoryUpdate->affectedBy->goodsReceivedNote->grn_reference ?: 'N/A');
                    if ($inventoryUpdate->quantity < 0) {
                        $quantityIn = 0;
                        $quantityOut = CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                        $documentNumber .= ' (CANCELLED)';
                    }

                    $goodsReceivedNote = $inventoryUpdate->affectedBy->goodsReceivedNote;
                    $storeInventory['post_date'] = $goodsReceivedNote->created_at->format('d-M-Y');
                    $storeInventory['type'] = 'GRN';
                    $storeInventory['document_no'] = $documentNumber;
                    $storeInventory['description'] = $goodsReceivedNote->notes;
                    $storeInventory['qty_in'] = $quantityIn;
                    $storeInventory['qty_out'] = $quantityOut;
                    $totalIn += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                }

                if (
                    ModelMapping::getCaseName(ModelMapping::STOCK_ADJUSTMENT_ITEM->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    $stockAdjustment = $inventoryUpdate->affectedBy->stockAdjustment;
                    $reason = $stockAdjustment->reason;
                    $type = StockAdjustmentTypes::STI->value === $stockAdjustment->type_id ? 'STI: ' : 'STO: ';

                    $adjustmentDate = '';
                    if ($stockAdjustment->adjustment_date) {
                        /** @var Carbon $adjustmentDateFormat */
                        $adjustmentDateFormat = Carbon::createFromFormat('Y-m-d', $stockAdjustment->adjustment_date);
                        $adjustmentDate = $adjustmentDateFormat->format('d-M-Y');
                    }

                    $storeInventory['post_date'] = $stockAdjustment->adjustment_date ? $adjustmentDate : $stockAdjustment->created_at->format(
                        'd-M-Y'
                    );

                    $storeInventory['type'] = StockAdjustmentTypes::tryFrom($stockAdjustment->type_id)?->name;

                    $storeInventory['document_no'] = 'Stock Adjustment: ' . $type . $reason . '(' . $stockAdjustment->id . ')';
                    $storeInventory['description'] = $stockAdjustment->reason;

                    if ($stockAdjustment->type_id === StockAdjustmentTypes::STO->value) {
                        $storeInventory['qty_in'] = 0;
                        $storeInventory['qty_out'] = CommonFunctions::truncateDecimal(
                            (float) $inventoryUpdate->quantity
                        );
                        $totalOut += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    }

                    if ($stockAdjustment->type_id === StockAdjustmentTypes::STI->value) {
                        $storeInventory['qty_in'] = CommonFunctions::truncateDecimal(
                            (float) $inventoryUpdate->quantity
                        );
                        $storeInventory['qty_out'] = 0;
                        $totalIn += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    }
                }

                if (
                    ModelMapping::getCaseName(ModelMapping::STOCK_TRANSFER_ITEM->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    /** @var Carbon $happenedAtFormat */
                    $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $inventoryUpdate->happened_at);
                    $happenedAt = $happenedAtFormat->format('d-M-Y');

                    $stockTransfer = $inventoryUpdate->affectedBy->stockTransfer;
                    $storeInventory['post_date'] = $happenedAt;

                    $stockTransferService = resolve(StockTransferService::class);

                    $storeInventory['type'] = $stockTransferService->getTransferType($stockTransfer, $filterData);

                    $storeInventory['document_no'] = 'Stock Transfer: ' . $stockTransferService->getStockTransferNumber(
                        $stockTransfer,
                        $filterData
                    );

                    $storeInventory['description'] = $stockTransfer->remarks . ' Location:' . $this->getLocationName(
                        $stockTransfer,
                        $filterData,
                    );

                    if ($inventoryUpdate->quantity > 0) {
                        $storeInventory['qty_in'] = CommonFunctions::truncateDecimal(
                            (float) $inventoryUpdate->quantity
                        );
                        $storeInventory['qty_out'] = 0;
                        $totalIn += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    }

                    if ($inventoryUpdate->quantity < 0) {
                        $storeInventory['qty_in'] = 0;
                        $storeInventory['qty_out'] = CommonFunctions::truncateDecimal(
                            (float) $inventoryUpdate->quantity
                        );
                        $totalOut += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    }
                }

                if (
                    ModelMapping::getCaseName(ModelMapping::SALE_ITEM->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    $saleItem = $inventoryUpdate->affectedBy;

                    /** @var Sale $sale */
                    $sale = $saleItem->sale;

                    $happenedAt = '';

                    if ($sale->happened_at) {
                        /** @var Carbon $happenedAtFormat */
                        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
                        $happenedAt = $happenedAtFormat->format('d-M-Y');
                    }

                    $storeInventory['post_date'] = $happenedAt;
                    $storeInventory['type'] = 'SL';
                    $storeInventory['document_no'] = 'Sale: ' . ($sale->offline_sale_id ?: 'N/A');
                    $storeInventory['description'] = '';
                    $storeInventory['qty_in'] = 0;
                    $storeInventory['qty_out'] = CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    $totalOut += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                }

                if (
                    ModelMapping::getCaseName(ModelMapping::SALE_RETURN_ITEM->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    $saleReturnItem = $inventoryUpdate->affectedBy;

                    $happenedAt = '';
                    if ($inventoryUpdate->happened_at) {
                        /** @var Carbon $happenedAtFormat */
                        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $inventoryUpdate->happened_at);
                        $happenedAt = $happenedAtFormat->format('d-M-Y');
                    }

                    $storeInventory['post_date'] = $happenedAt;
                    $storeInventory['type'] = 'SLR';
                    $storeInventory['document_no'] = 'Sale Return: ' . ($saleReturnItem->saleReturn->offline_sale_return_id ?: 'N/A');
                    $storeInventory['description'] = $saleReturnItem->saleReturn->notes;
                    $storeInventory['qty_in'] = CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    $storeInventory['qty_out'] = 0;
                    $totalIn += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                }

                if (
                    ModelMapping::getCaseName(ModelMapping::VOID_SALE->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    $voidSale = $inventoryUpdate->affectedBy;
                    $storeInventory['post_date'] = $voidSale->created_at->format('d-M-Y');
                    $storeInventory['type'] = 'VSL';
                    $storeInventory['document_no'] = 'Void Sale: ' . ($voidSale->void_sale_number ?: 'N/A');
                    $storeInventory['description'] = '';
                    $storeInventory['qty_in'] = CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    $storeInventory['qty_out'] = 0;
                    $totalIn += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                }

                if (
                    ModelMapping::getCaseName(ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    $happenedAt = '';
                    if ($inventoryUpdate->happened_at) {
                        /** @var Carbon $happenedAtFormat */
                        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $inventoryUpdate->happened_at);
                        $happenedAt = $happenedAtFormat->format('d-M-Y');
                    }

                    $purchaseOrderFulfillmentItem = $inventoryUpdate->affectedBy;

                    $purchaseOrder = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment->purchaseOrder;

                    $orderType = OrderTypes::getFormattedCaseName($purchaseOrder->order_type);

                    $quantityIn = $inventoryUpdate->quantity > 0 ? $inventoryUpdate->quantity : 0;
                    $quantityOut = $inventoryUpdate->quantity <= 0 ? $inventoryUpdate->quantity : 0;

                    $storeInventory['post_date'] = $happenedAt;
                    $storeInventory['type'] = $purchaseOrder->order_type === OrderTypes::SALES_ORDER->value ? 'SO' : 'PO';
                    $storeInventory['document_no'] = $orderType . ':' . $purchaseOrder->order_number;
                    $storeInventory['description'] = '';
                    $storeInventory['qty_in'] = CommonFunctions::truncateDecimal((float) $quantityIn);
                    $storeInventory['qty_out'] = CommonFunctions::truncateDecimal((float) $quantityOut);
                    $totalIn += CommonFunctions::truncateDecimal((float) $quantityIn);
                    $totalOut += CommonFunctions::truncateDecimal((float) $quantityOut);
                }

                if (
                    ModelMapping::getCaseName(ModelMapping::ORDER_ITEM->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    $orderItem = $inventoryUpdate->affectedBy;

                    /** @var Order $order */
                    $order = $orderItem->order;

                    $happenedAt = '';

                    if ($order->happened_at) {
                        /** @var Carbon $happenedAtFormat */
                        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $order->happened_at);
                        $happenedAt = $happenedAtFormat->format('d-M-Y');
                    }

                    $storeInventory['post_date'] = $happenedAt;
                    $storeInventory['type'] = 'ORD';
                    $storeInventory['document_no'] = 'Order: ' . ($order->receipt_number ?: 'N/A');
                    $storeInventory['description'] = '';
                    $storeInventory['qty_in'] = 0;
                    $storeInventory['qty_out'] = CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    $totalOut += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                }

                if (
                    ModelMapping::getCaseName(ModelMapping::ORDER_RETURN_ITEM->value)
                    === $inventoryUpdate->affected_by_type
                ) {
                    $orderReturnItem = $inventoryUpdate->affectedBy;

                    /** @var OrderReturn $orderReturn */
                    $orderReturn = $orderReturnItem->orderReturn;

                    $happenedAt = '';
                    if ($inventoryUpdate->happened_at) {
                        /** @var Carbon $happenedAtFormat */
                        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $inventoryUpdate->happened_at);
                        $happenedAt = $happenedAtFormat->format('d-M-Y');
                    }

                    $storeInventory['post_date'] = $happenedAt;
                    $storeInventory['type'] = 'ORDR';
                    $storeInventory['document_no'] = 'Order Return: ' . ($orderReturn->receipt_number ?: 'N/A');
                    $storeInventory['description'] = '';
                    $storeInventory['qty_in'] = CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                    $storeInventory['qty_out'] = 0;
                    $totalIn += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                }

                $storeInventory['balance'] += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                $balance += CommonFunctions::truncateDecimal((float) $inventoryUpdate->quantity);
                $inventoryUpdateData[] = $storeInventory;
            }

            $filterDate = '';
            if (isset($filterData['date_range'][1]) && is_string(
                $filterData['date_range'][1]
            ) && $carbonDate = Carbon::createFromFormat('Y-m-d', $filterData['date_range'][1])) {
                $filterDate = $carbonDate->format('d-M-Y');
            }

            $storeInventoriesTotals['total_opening_balance'] += CommonFunctions::truncateDecimal(
                (float) $openingBalance
            );
            $storeInventoriesTotals['total_qty_in'] += CommonFunctions::truncateDecimal((float) $totalIn);
            $storeInventoriesTotals['total_qty_out'] += CommonFunctions::truncateDecimal((float) $totalOut);
            $storeInventoriesTotals['total_balance'] += CommonFunctions::truncateDecimal((float) $balance);
            $storeInventory['transaction_date'] = $filterDate;
            $storeInventory['post_date'] = $filterDate;
            $storeInventory['type'] = 'ZZ';
            $storeInventory['document_no'] = 'Total';
            $storeInventory['description'] = 'Balance: ' . $balance;
            $storeInventory['qty_in'] = CommonFunctions::truncateDecimal((float) $totalIn);
            $storeInventory['qty_out'] = CommonFunctions::truncateDecimal((float) $totalOut);
            $storeInventory['balance'] = CommonFunctions::truncateDecimal((float) $balance);
            $inventoryUpdateData[] = $storeInventory;
            $storeInventories[$inventory->product_id]['inventories'] = $inventoryUpdateData;
        }

        return [$storeInventories, $product, $storeInventoriesTotals];
    }

    private function getArticleNumber(array $filterData, ?string $articleNumber): ?string
    {
        if (array_key_exists('article_number', $filterData)) {
            return $filterData['article_number'];
        }

        return $articleNumber;
    }

    private function getLocationName(StockTransfer $stockTransfer, array $filterData): string
    {
        if ((int) $filterData['location_id'] === $stockTransfer->source_location_id) {
            /** @var Location $destinationLocation */
            $destinationLocation = $stockTransfer->destinationLocation;

            return $destinationLocation->name . ' (' . $destinationLocation->code . ')';
        }

        /** @var Location $sourceLocation */
        $sourceLocation = $stockTransfer->sourceLocation;

        return $sourceLocation->name . ' (' . $sourceLocation->code . ')';
    }

    private function filterBy(array $filterData, int $companyId): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === StockCardFilterByReportTypes::BY_PRODUCT->value && isset($filterData['product_id']) && '' !== $filterData['product_id']) {
            $product = $productQueries->getByIdOnlyName((int) $filterData['product_id'], $companyId);

            return $this->formatFilterResult(
                StockCardFilterByReportTypes::BY_PRODUCT->value,
                $product->compound_product_name
            );
        }

        if ($filterBy === StockCardFilterByReportTypes::BY_BRAND->value && isset($filterData['brand_id']) && '' !== $filterData['brand_id']) {
            $brand = $brandQueries->getById((int) $filterData['brand_id']);

            return $this->formatFilterResult(StockCardFilterByReportTypes::BY_BRAND->value, $brand->name);
        }

        if ($filterBy === StockCardFilterByReportTypes::BY_DEPARTMENT->value && isset($filterData['department_id']) && '' !== $filterData['department_id']) {
            $department = $departmentQueries->getById((int) $filterData['department_id'], $companyId);

            return $this->formatFilterResult(
                StockCardFilterByReportTypes::BY_DEPARTMENT->value,
                $department->name
            );
        }

        if ($filterBy === StockCardFilterByReportTypes::BY_CATEGORY->value && isset($filterData['category_id']) && '' !== $filterData['category_id']) {
            $category = $categoryQueries->getCategoryByIdAndCompanyId((int) $filterData['category_id'], $companyId);

            return $this->formatFilterResult(StockCardFilterByReportTypes::BY_CATEGORY->value, $category->name);
        }

        if ($filterBy !== StockCardFilterByReportTypes::BY_MASTER_PRODUCT->value) {
            return '';
        }

        if (! isset($filterData['article_number'])) {
            return '';
        }

        if ('' === $filterData['article_number']) {
            return '';
        }

        return $this->formatFilterResult(
            StockCardFilterByReportTypes::BY_MASTER_PRODUCT->value,
            $filterData['article_number']
        );
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return StockCardFilterByReportTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
