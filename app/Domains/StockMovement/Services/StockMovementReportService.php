<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Services;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\InventoryUpdate\Enums\StockMovementFilters;
use App\Domains\InventoryUpdate\Enums\StockMovementReportTypes;
use App\Domains\InventoryUpdate\Exports\CustomStockMovementExport;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockMovementReportService
{
    public function print(array $filterData): string
    {
        if ((int) $filterData['report_type'] === StockMovementReportTypes::BY_DETAILS->value) {
            return $this->printByDetails($filterData);
        }

        if ((int) $filterData['report_type'] === StockMovementReportTypes::BY_SUMMARY->value) {
            return $this->printBySummary($filterData);
        }

        return '';
    }

    public function printByDetails(array $filterData): string
    {
        [$stockMovements, $company, $dateRange] = $this->getStockMovementData($filterData);

        return view('prints.stock_movement', [
            'company' => $company,
            'stockMovements' => $stockMovements,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filterBy' => $this->filterBy($filterData, $company->id),
            'reportType' => StockMovementReportTypes::getFormattedCaseName((int) $filterData['report_type']),
        ])->render();
    }

    public function printBySummary(array $filterData): string
    {
        [$stockMovements, $company, $dateRange] = $this->getStockMovementDataBySummary($filterData);

        return view('prints.stock_movement_by_summary', [
            'company' => $company,
            'stockMovements' => $stockMovements,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filterBy' => $this->filterBy($filterData, $company->id),
            'reportType' => StockMovementReportTypes::getFormattedCaseName((int) $filterData['report_type']),
        ])->render();
    }

    public function exportStockMovementReport(array $filterData, string $filename): BinaryFileResponse
    {
        [$stockMovements, $company, $dateRange] = $this->getStockMovementData($filterData);

        $reportType = StockMovementReportTypes::getFormattedCaseName((int) $filterData['report_type']);

        return Excel::download(
            new CustomStockMovementExport(
                $stockMovements,
                $company,
                $dateRange,
                $this->filterBy($filterData, $company->id),
                $reportType
            ),
            $filename
        );
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

        if ($filterBy === StockMovementFilters::BY_PRODUCT->value && isset($filterData['product_id']) && '' !== $filterData['product_id']) {
            $product = $productQueries->getByIdOnlyName((int) $filterData['product_id'], $companyId);

            return $this->formatFilterResult(StockMovementFilters::BY_PRODUCT->value, $product->compound_product_name);
        }

        if ($filterBy === StockMovementFilters::BY_MASTER_PRODUCT->value && isset($filterData['article_number']) && '' !== $filterData['article_number']) {
            return $this->formatFilterResult(
                StockMovementFilters::BY_MASTER_PRODUCT->value,
                $filterData['article_number']
            );
        }

        if ($filterBy === StockMovementFilters::BY_BRAND->value && isset($filterData['brand_ids']) && '' !== $filterData['brand_ids']) {
            $brands = $brandQueries->getByIds($filterData['brand_ids']);

            return $this->formatFilterResult(
                StockMovementFilters::BY_BRAND->value,
                $brands->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === StockMovementFilters::BY_DEPARTMENT->value && isset($filterData['department_ids']) && '' !== $filterData['department_ids']) {
            $departments = $departmentQueries->getByIds($filterData['department_ids']);

            return $this->formatFilterResult(
                StockMovementFilters::BY_DEPARTMENT->value,
                $departments->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === StockMovementFilters::BY_PRODUCTS->value && isset($filterData['product_ids']) && '' !== $filterData['product_ids']) {
            $products = $productQueries->getByIds($filterData['product_ids']);

            return $this->formatFilterResult(
                StockMovementFilters::BY_PRODUCTS->value,
                $products->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === StockMovementFilters::BY_CATEGORIES->value && isset($filterData['category_ids']) && '' !== $filterData['category_ids']) {
            $categories = $categoryQueries->getByIds($filterData['category_ids']);

            return $this->formatFilterResult(
                StockMovementFilters::BY_CATEGORIES->value,
                $categories->pluck('name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return StockMovementFilters::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }

    /**
     * @return array<int, mixed>
     */
    private function getStockMovementData(array $filterData): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $customReportService = resolve(CustomReportService::class);
        $inventoryUpdates = $inventoryUpdateQueries->getStockMovementsOfProductsForALocationForPrint($filterData);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($filterData['company_id']);

        $dateRange = $customReportService->prepareDateRange($filterData);

        $locations = $locationQueries->getByIdsWithNameAndCode(
            $filterData['company_id'],
            $filterData['location_ids'],
        );

        $productInventoryDetails = [];
        $stockMovementProducts = [];

        $total = [
            'location_name' => '',
            'total_opening_stock' => 0,
            'total_closing_stock' => 0,
            'total_good_receive_note_quantity' => 0,
            'total_good_receive_note_quantity_in' => 0,
            'total_good_receive_note_quantity_out' => 0,
            'total_sale_quantity' => 0,
            'total_sale_return_quantity' => 0,
            'total_order_quantity' => 0,
            'total_order_return_quantity' => 0,
            'total_stock_transfer_quantity_in' => 0,
            'total_stock_transfer_quantity_out' => 0,
            'total_purchase_order_quantity_in' => 0,
            'total_purchase_order_quantity_out' => 0,
            'total_positive_stock_adjustment_quantity' => 0,
            'total_negative_stock_adjustment_quantity' => 0,
        ];

        $inventoryUpdatesByOriginalProductIds = collect([]);

        if (isset($filterData['filter_by']) &&
            (int) $filterData['filter_by'] === StockMovementFilters::BY_MASTER_PRODUCT->value
        ) {
            $productQueries = resolve(ProductQueries::class);
            $originalProductIds = $productQueries->getFilteredProducts($filterData);

            $filterData['product_ids'] = $originalProductIds;

            $inventoryUpdatesByOriginalProductIds = $this->getProductsInventorUpdates($filterData);
        }

        $groupByArticleNumber = config('app.product_variant') ?
            'product.masterProduct.article_number' : 'product.article_number';

        foreach ($locations as $location) {
            $productInventoryDetails[$location->id]['location_name'] = $location->name . ' [' . $location->code . ']';

            $filterData['location_ids'] = [$location->id];

            $articleNumberWiseStockMovementData = $inventoryUpdates->where('location_id', $location->id)
                ->groupBy($groupByArticleNumber)
                ->sortBy($groupByArticleNumber);

            $articleNumberWiseStockMovementData = $articleNumberWiseStockMovementData->sortBy(
                fn ($item, $key) => $key
            );

            $exceptProductInventoryUpdates = $inventoryUpdatesByOriginalProductIds
                ->where('location_id', $location->id);

            if (isset($filterData['filter_by']) &&
                (int) $filterData['filter_by'] === StockMovementFilters::BY_MASTER_PRODUCT->value &&
                $exceptProductInventoryUpdates->isNotEmpty()
            ) {
                $exceptProductsArticleNumberWiseStockMovementData = $exceptProductInventoryUpdates->groupBy(
                    $groupByArticleNumber
                )->sortBy($groupByArticleNumber);

                $exceptProductsArticleNumberWiseStockMovementData = $exceptProductsArticleNumberWiseStockMovementData->sortBy(
                    fn ($item, $key): int|string => $key
                );

                $mergedStockMovements = $exceptProductsArticleNumberWiseStockMovementData->map(
                    fn ($item, $key) => $item->merge($articleNumberWiseStockMovementData->get($key, collect()))
                );
            } else {
                $mergedStockMovements = $articleNumberWiseStockMovementData;
            }

            [$stockMovementProducts, $total] = $this->processStockMovements(
                $mergedStockMovements,
                $exceptProductInventoryUpdates,
                $location,
                $total
            );

            $productInventoryDetails[$location->id]['products'] = array_key_exists(
                $location->id,
                $stockMovementProducts
            ) ? $stockMovementProducts[$location->id] : [];
        }

        $stockMovementDetailsArray = $productInventoryDetails;
        $stockMovementDetailsArray['grand_total'] = $total;

        return [$stockMovementDetailsArray, $company, $dateRange];
    }

    private function processStockMovements(
        Collection $mergedStockMovementsData,
        Collection $exceptProductInventoryUpdates,
        Location $location,
        array $total
    ): array {
        $stockMovementProducts = [];
        $customReportService = resolve(CustomReportService::class);
        $productService = resolve(ProductService::class);

        foreach ($mergedStockMovementsData as $articleNumber => $mergedStockMovementData) {
            foreach ($mergedStockMovementData->groupBy('product_id') as $stockMovement) {
                /** @var Product $product */
                $product = $stockMovement->first()->product;

                if ($stockMovement->where('source', 'except_product')->first()) {
                    $productInventory = $exceptProductInventoryUpdates
                        ->where('location_id', $location->id)
                        ->where('product_id', $product->id)
                        ->sortByDesc('happened_at')
                        ->first();

                    if (config('app.product_variant')) {
                        $variantColumns = [
                            'brand' => $product->masterProduct->brand->name ?? 'N/A',
                            'department' => $product->masterProduct->department->name ?? 'N/A',
                            'attributes' => $productService->getAttributesForPrint($product),
                        ];
                    } else {
                        $variantColumns = [
                            'brand' => $product->brand->name ?? 'N/A',
                            'department' => $product->department->name ?? 'N/A',
                            'color' => $product->color->name ?? 'N/A',
                            'size' => $product->size->name ?? 'N/A',
                        ];
                    }

                    $stockMovementProducts[$location->id][$articleNumber][] = [
                        'product_id' => $product->getKey(),
                        'product_name' => $product->name,
                        'article_number' => $articleNumber,
                        'upc' => $product->upc,
                        ...$variantColumns,
                        'opening_stock' => (float) $productInventory?->closing_stock,
                        'closing_stock' => (float) $productInventory?->closing_stock,
                        'good_receive_note_quantity' => 0,
                        'good_receive_note_quantity_in' => 0,
                        'good_receive_note_quantity_out' => 0,
                        'sale_quantity' => 0,
                        'sale_return_quantity' => 0,
                        'order_quantity' => 0,
                        'order_return_quantity' => 0,
                        'stock_transfer_quantity_in' => 0,
                        'stock_transfer_quantity_out' => 0,
                        'purchase_order_quantity_in' => 0,
                        'purchase_order_quantity_out' => 0,
                        'positive_stock_adjustment_quantity' => 0,
                        'negative_stock_adjustment_quantity' => 0,
                    ];
                    continue;
                }

                $productInventories = $stockMovement->where('product_id', $product->id);

                [$positiveGoodReceivedQuantity, $negativeGoodReceivedQuantity] = $customReportService->getGoodReceivedNote(
                    $productInventories,
                    ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
                );

                [$positiveStockAdjustment, $negativeStockAdjustment] = $customReportService->getStockAdjustment(
                    $productInventories,
                    ModelMapping::STOCK_ADJUSTMENT_ITEM->name
                );

                [$positiveStockTransfer, $negativeStockTransfer] = $customReportService->getStockTransfer(
                    $productInventories,
                    ModelMapping::STOCK_TRANSFER_ITEM->name
                );

                [$positivePurchaseOrder, $negativePurchaseOrder] = $customReportService->getStockTransfer(
                    $productInventories,
                    ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name
                );

                [$positivePartiallyReceiveFulfillment, $negativePartiallyReceiveFulfillment] = $customReportService->getStockTransfer(
                    $productInventories,
                    ModelMapping::PARTIALLY_RECEIVE_FULFILLMENT_ITEM->name
                );

                /** @var InventoryUpdate $inventoryUpdate */
                $inventoryUpdate = $productInventories->first();

                if (! $inventoryUpdate instanceof InventoryUpdate) {
                    continue;
                }

                /** @var InventoryUpdate $oldestFirstInventories */
                $oldestFirstInventories = $productInventories->sortBy('happened_at')->first();

                /** @var InventoryUpdate $newestFirstInventories */
                $newestFirstInventories = $productInventories->sortByDesc('happened_at')->first();

                if (config('app.product_variant')) {
                    $variantColumns = [
                        'brand' => $product->masterProduct->brand->name ?? 'N/A',
                        'department' => $product->masterProduct->department->name ?? 'N/A',
                        'attributes' => $productService->getAttributesForPrint($product),
                    ];
                } else {
                    $variantColumns = [
                        'brand' => $product->brand->name ?? 'N/A',
                        'department' => $product->department->name ?? 'N/A',
                        'color' => $product->color->name ?? 'N/A',
                        'size' => $product->size->name ?? 'N/A',
                    ];
                }

                $stockMovementProducts[$location->id][$articleNumber][] = [
                    'product_id' => $inventoryUpdate->product_id,
                    'product_name' => $product->name,
                    'article_number' => $articleNumber,
                    'upc' => $product->upc,
                    ...$variantColumns,
                    'opening_stock' => $oldestFirstInventories->getOpeningStock(),
                    'closing_stock' => $newestFirstInventories->getClosingStock(),
                    'good_receive_note_quantity_in' => $positiveGoodReceivedQuantity,
                    'good_receive_note_quantity_out' => abs($negativeGoodReceivedQuantity),
                    'good_receive_note_quantity' => $customReportService->getSum(
                        $productInventories,
                        ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
                    ),
                    'sale_quantity' => abs(
                        $customReportService->getSum(
                            $productInventories,
                            ModelMapping::SALE_ITEM->name
                        ) + $customReportService->getSum($productInventories, ModelMapping::VOID_SALE->name)
                    ),
                    'sale_return_quantity' => $customReportService->getSum(
                        $productInventories,
                        ModelMapping::SALE_RETURN_ITEM->name
                    ),
                    'order_quantity' => $customReportService->getSum(
                        $productInventories,
                        ModelMapping::ORDER_RETURN->name
                    ),
                    'order_return_quantity' => $customReportService->getSum(
                        $productInventories,
                        ModelMapping::ORDER_RETURN_ITEM->name
                    ),
                    'stock_transfer_quantity_in' => $positiveStockTransfer,
                    'stock_transfer_quantity_out' => abs($negativeStockTransfer),
                    'purchase_order_quantity_in' => ($positivePurchaseOrder + $positivePartiallyReceiveFulfillment),
                    'purchase_order_quantity_out' => abs($negativePurchaseOrder + $negativePartiallyReceiveFulfillment),
                    'positive_stock_adjustment_quantity' => $positiveStockAdjustment,
                    'negative_stock_adjustment_quantity' => abs($negativeStockAdjustment),
                ];
            }

            if ([] === $stockMovementProducts) {
                continue;
            }

            $articleNumberWiseTotal = collect($stockMovementProducts[$location->id][$articleNumber]);

            $variantColumns = config('app.product_variant')
                ? [
                    'attributes' => '',
                ]
                : [
                    'color' => '',
                    'size' => '',
                ];

            $stockMovementProducts[$location->id][$articleNumber][] = [
                'product_id' => '',
                'product_name' => 'Total',
                'article_number' => '',
                'upc' => '',
                'brand' => '',
                'department' => '',
                ...$variantColumns,
                'opening_stock' => $articleNumberWiseTotal->sum('opening_stock'),
                'closing_stock' => $articleNumberWiseTotal->sum('closing_stock'),
                'good_receive_note_quantity' => $articleNumberWiseTotal->sum('good_receive_note_quantity'),
                'good_receive_note_quantity_in' => $articleNumberWiseTotal->sum('good_receive_note_quantity_in'),
                'good_receive_note_quantity_out' => $articleNumberWiseTotal->sum('good_receive_note_quantity_out'),
                'sale_quantity' => $articleNumberWiseTotal->sum('sale_quantity'),
                'sale_return_quantity' => $articleNumberWiseTotal->sum('sale_return_quantity'),
                'order_quantity' => $articleNumberWiseTotal->sum('order_quantity'),
                'order_return_quantity' => $articleNumberWiseTotal->sum('order_return_quantity'),
                'stock_transfer_quantity_in' => $articleNumberWiseTotal->sum('stock_transfer_quantity_in'),
                'stock_transfer_quantity_out' => $articleNumberWiseTotal->sum('stock_transfer_quantity_out'),
                'purchase_order_quantity_in' => $articleNumberWiseTotal->sum('purchase_order_quantity_in'),
                'purchase_order_quantity_out' => $articleNumberWiseTotal->sum('purchase_order_quantity_out'),
                'positive_stock_adjustment_quantity' => $articleNumberWiseTotal->sum(
                    'positive_stock_adjustment_quantity'
                ),
                'negative_stock_adjustment_quantity' => $articleNumberWiseTotal->sum(
                    'negative_stock_adjustment_quantity'
                ),
            ];

            $total['total_opening_stock'] += $articleNumberWiseTotal->sum('opening_stock');
            $total['total_closing_stock'] += $articleNumberWiseTotal->sum('closing_stock');
            $total['total_good_receive_note_quantity'] += $articleNumberWiseTotal->sum(
                'good_receive_note_quantity'
            );
            $total['total_good_receive_note_quantity_in'] += $articleNumberWiseTotal->sum(
                'good_receive_note_quantity_in'
            );
            $total['total_good_receive_note_quantity_out'] += $articleNumberWiseTotal->sum(
                'good_receive_note_quantity_out'
            );
            $total['total_sale_quantity'] += $articleNumberWiseTotal->sum('sale_quantity');
            $total['total_sale_return_quantity'] += $articleNumberWiseTotal->sum('sale_return_quantity');
            $total['total_order_quantity'] += $articleNumberWiseTotal->sum('order_quantity');
            $total['total_order_return_quantity'] += $articleNumberWiseTotal->sum('order_return_quantity');
            $total['total_stock_transfer_quantity_in'] += $articleNumberWiseTotal->sum(
                'stock_transfer_quantity_in'
            );
            $total['total_stock_transfer_quantity_out'] += $articleNumberWiseTotal->sum(
                'stock_transfer_quantity_out'
            );
            $total['total_purchase_order_quantity_in'] += $articleNumberWiseTotal->sum(
                'purchase_order_quantity_in'
            );
            $total['total_purchase_order_quantity_out'] += $articleNumberWiseTotal->sum(
                'purchase_order_quantity_out'
            );
            $total['total_positive_stock_adjustment_quantity'] += $articleNumberWiseTotal->sum(
                'positive_stock_adjustment_quantity'
            );
            $total['total_negative_stock_adjustment_quantity'] += $articleNumberWiseTotal->sum(
                'negative_stock_adjustment_quantity'
            );
        }

        return [$stockMovementProducts, $total];
    }

    private function getProductsInventorUpdates(array $filterData): Collection
    {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        return $inventoryUpdateQueries->getStockMovementsByLocationsAndProductIdsForPrint($filterData);
    }

    private function getStockMovementDataBySummary(array $filterData): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $customReportService = resolve(CustomReportService::class);
        $productService = resolve(ProductService::class);
        $inventoryUpdates = $inventoryUpdateQueries->getStockMovementsOfProductsForALocationForPrint($filterData);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($filterData['company_id']);

        $dateRange = $customReportService->prepareDateRange($filterData);

        $locations = $locationQueries->getByIdsWithNameAndCode(
            $filterData['company_id'],
            $filterData['location_ids'],
        );

        $productInventoryDetails = [];
        $stockMovementParentProducts = [];
        $stockMovementProducts = [];

        $total = [
            'location_name' => '',
            'total_opening_stock' => 0,
            'total_closing_stock' => 0,
            'total_good_receive_note_quantity' => 0,
            'total_good_receive_note_quantity_in' => 0,
            'total_good_receive_note_quantity_out' => 0,
            'total_sale_quantity' => 0,
            'total_sale_return_quantity' => 0,
            'total_order_quantity' => 0,
            'total_order_return_quantity' => 0,
            'total_stock_transfer_quantity_in' => 0,
            'total_stock_transfer_quantity_out' => 0,
            'total_purchase_order_quantity_in' => 0,
            'total_purchase_order_quantity_out' => 0,
            'total_positive_stock_adjustment_quantity' => 0,
            'total_negative_stock_adjustment_quantity' => 0,
        ];

        if (config('app.product_variant')) {
            $articleNumberWiseStockMovementData = $inventoryUpdates->groupBy(
                'product.masterProduct.article_number'
            )->sortBy('product.masterProduct.article_number');
        } else {
            $articleNumberWiseStockMovementData = $inventoryUpdates->groupBy('product.article_number')->sortBy(
                'product.article_number'
            );
        }

        $articleNumberWiseStockMovementData = $articleNumberWiseStockMovementData->sortBy(
            fn ($item, $key) => $key
        );

        foreach ($locations as $location) {
            $productInventoryDetails[$location->id]['location_name'] = $location->name . ' [' . $location->code . ']';

            foreach ($articleNumberWiseStockMovementData as $articleNumber => $stockMovements) {
                $stockMovementProducts[$articleNumber] = [];
                $stockMovementParentProducts[$articleNumber] = [];
                foreach ($stockMovements->groupBy('product_id') as $stockMovement) {
                    /** @var Product $product */
                    $product = $stockMovement->first()->product;

                    $productInventories = $inventoryUpdates->where('product_id', $product->id)
                        ->where('location_id', $location->id);

                    [$positiveGoodReceivedQuantity, $negativeGoodReceivedQuantity] = $customReportService->getGoodReceivedNote(
                        $productInventories,
                        ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
                    );

                    [$positiveStockAdjustment, $negativeStockAdjustment] = $customReportService->getStockAdjustment(
                        $productInventories,
                        ModelMapping::STOCK_ADJUSTMENT_ITEM->name
                    );

                    [$positiveStockTransfer, $negativeStockTransfer] = $customReportService->getStockTransfer(
                        $productInventories,
                        ModelMapping::STOCK_TRANSFER_ITEM->name
                    );

                    [$positivePurchaseOrder, $negativePurchaseOrder] = $customReportService->getStockTransfer(
                        $productInventories,
                        ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name
                    );

                    [$positivePartiallyReceiveFulfillment, $negativePartiallyReceiveFulfillment] = $customReportService->getStockTransfer(
                        $productInventories,
                        ModelMapping::PARTIALLY_RECEIVE_FULFILLMENT_ITEM->name
                    );

                    /** @var InventoryUpdate $inventoryUpdate */
                    $inventoryUpdate = $productInventories->first();

                    if (! $inventoryUpdate instanceof InventoryUpdate) {
                        continue;
                    }

                    /** @var InventoryUpdate $oldestFirstInventories */
                    $oldestFirstInventories = $productInventories->sortBy('happened_at')->first();

                    /** @var InventoryUpdate $newestFirstInventories */
                    $newestFirstInventories = $productInventories->sortByDesc('happened_at')->first();

                    if (config('app.product_variant')) {
                        $variantColumns = [
                            'brand' => $product->masterProduct->brand->name ?? 'N/A',
                            'department' => $product->masterProduct->department->name ?? 'N/A',
                            'attributes' => $productService->getAttributesForPrint($product),
                        ];
                    } else {
                        $variantColumns = [
                            'brand' => $product->brand->name ?? 'N/A',
                            'department' => $product->department->name ?? 'N/A',
                            'color' => $product->color->name ?? 'N/A',
                            'size' => $product->size->name ?? 'N/A',
                        ];
                    }

                    $stockMovementProducts[$articleNumber][] = [
                        'product_id' => $inventoryUpdate->product_id,
                        'product_name' => $product->name,
                        'article_number' => $articleNumber,
                        'upc' => $product->upc,
                        ...$variantColumns,
                        'opening_stock' => $oldestFirstInventories->getOpeningStock(),
                        'closing_stock' => $newestFirstInventories->getClosingStock(),
                        'good_receive_note_quantity_in' => $positiveGoodReceivedQuantity,
                        'good_receive_note_quantity_out' => abs($negativeGoodReceivedQuantity),
                        'good_receive_note_quantity' => $customReportService->getSum(
                            $productInventories,
                            ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
                        ),
                        'sale_quantity' => abs(
                            $customReportService->getSum($productInventories, ModelMapping::SALE_ITEM->name)
                        ),
                        'sale_return_quantity' => $customReportService->getSum(
                            $productInventories,
                            ModelMapping::SALE_RETURN_ITEM->name
                        ),
                        'order_quantity' => abs(
                            $customReportService->getSum($productInventories, ModelMapping::ORDER_ITEM->name)
                        ),
                        'order_return_quantity' => $customReportService->getSum(
                            $productInventories,
                            ModelMapping::ORDER_RETURN_ITEM->name
                        ),
                        'stock_transfer_quantity_in' => $positiveStockTransfer,
                        'stock_transfer_quantity_out' => abs($negativeStockTransfer),
                        'purchase_order_quantity_in' => ($positivePurchaseOrder + $positivePartiallyReceiveFulfillment),
                        'purchase_order_quantity_out' => abs(
                            $negativePurchaseOrder + $negativePartiallyReceiveFulfillment
                        ),
                        'positive_stock_adjustment_quantity' => $positiveStockAdjustment,
                        'negative_stock_adjustment_quantity' => abs($negativeStockAdjustment),
                    ];
                }

                $articleNumberWiseTotal = collect($stockMovementProducts[$articleNumber]);
                $parentProduct = $articleNumberWiseTotal->first();

                $stockMovementParentProducts[$articleNumber][] = [
                    'product_id' => $parentProduct['product_id'] ?? 'N/A',
                    'product_name' => $parentProduct['product_name'] ?? 'N/A',
                    'article_number' => $parentProduct['article_number'] ?? 'N/A',
                    'upc' => $parentProduct['upc'] ?? 'N/A',
                    'opening_stock' => $articleNumberWiseTotal->sum('opening_stock'),
                    'closing_stock' => $articleNumberWiseTotal->sum('closing_stock'),
                    'good_receive_note_quantity_in' => $articleNumberWiseTotal->sum('good_receive_note_quantity_in'),
                    'good_receive_note_quantity_out' => $articleNumberWiseTotal->sum('good_receive_note_quantity_out'),
                    'good_receive_note_quantity' => $articleNumberWiseTotal->sum('good_receive_note_quantity'),
                    'sale_quantity' => $articleNumberWiseTotal->sum('sale_quantity'),
                    'sale_return_quantity' => $articleNumberWiseTotal->sum('sale_return_quantity'),
                    'order_quantity' => $articleNumberWiseTotal->sum('order_quantity'),
                    'order_return_quantity' => $articleNumberWiseTotal->sum('order_return_quantity'),
                    'stock_transfer_quantity_in' => $articleNumberWiseTotal->sum('stock_transfer_quantity_in'),
                    'stock_transfer_quantity_out' => $articleNumberWiseTotal->sum('stock_transfer_quantity_out'),
                    'purchase_order_quantity_in' => $articleNumberWiseTotal->sum('purchase_order_quantity_in'),
                    'purchase_order_quantity_out' => $articleNumberWiseTotal->sum('purchase_order_quantity_out'),
                    'positive_stock_adjustment_quantity' => $articleNumberWiseTotal->sum(
                        'positive_stock_adjustment_quantity'
                    ),
                    'negative_stock_adjustment_quantity' => $articleNumberWiseTotal->sum(
                        'negative_stock_adjustment_quantity'
                    ),
                ];

                $total['total_opening_stock'] += $articleNumberWiseTotal->sum('opening_stock');
                $total['total_closing_stock'] += $articleNumberWiseTotal->sum('closing_stock');
                $total['total_good_receive_note_quantity_in'] += $articleNumberWiseTotal->sum(
                    'good_receive_note_quantity_in'
                );
                $total['total_good_receive_note_quantity_out'] += $articleNumberWiseTotal->sum(
                    'good_receive_note_quantity_out'
                );
                $total['total_good_receive_note_quantity'] += $articleNumberWiseTotal->sum(
                    'good_receive_note_quantity'
                );
                $total['total_sale_quantity'] += $articleNumberWiseTotal->sum('sale_quantity');
                $total['total_sale_return_quantity'] += $articleNumberWiseTotal->sum('sale_return_quantity');
                $total['total_order_quantity'] += $articleNumberWiseTotal->sum('order_quantity');
                $total['total_order_return_quantity'] += $articleNumberWiseTotal->sum('order_return_quantity');
                $total['total_stock_transfer_quantity_in'] += $articleNumberWiseTotal->sum(
                    'stock_transfer_quantity_in'
                );
                $total['total_stock_transfer_quantity_out'] += $articleNumberWiseTotal->sum(
                    'stock_transfer_quantity_out'
                );
                $total['total_purchase_order_quantity_in'] += $articleNumberWiseTotal->sum(
                    'purchase_order_quantity_in'
                );
                $total['total_purchase_order_quantity_out'] += $articleNumberWiseTotal->sum(
                    'purchase_order_quantity_out'
                );
                $total['total_positive_stock_adjustment_quantity'] += $articleNumberWiseTotal->sum(
                    'positive_stock_adjustment_quantity'
                );
                $total['total_negative_stock_adjustment_quantity'] += $articleNumberWiseTotal->sum(
                    'negative_stock_adjustment_quantity'
                );
            }

            $productInventoryDetails[$location->id]['products'] = $stockMovementParentProducts;
        }

        $stockMovementDetailsArray = $productInventoryDetails;
        $stockMovementDetailsArray['grand_total'] = $total;

        return [$stockMovementDetailsArray, $company, $dateRange];
    }
}
