<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CustomReportService
{
    /**
     * @return mixed[]
     */
    public function getProductDetails(Collection $saleItems): array
    {
        return $saleItems->map(function ($saleItem): array {
            $saleItemProduct = [
                'upc' => $saleItem->product->upc,
                'name' => $saleItem->product->compound_product_name,
                'price' => $saleItem->total_price_paid,
                'quantity' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
            ];
            if ($saleItem instanceof SaleReturnItem) {
                /** @var SaleReturnReason $saleReturnReason */
                $saleReturnReason = $saleItem->saleReturnReason;
                $saleItemProduct['reason'] = $saleReturnReason->reason;
            }

            return $saleItemProduct;
        })->toArray();
    }

    /**
     * @return mixed[]
     */
    public function getProductDetailsWithReturnAndExchange(Collection $saleItems, bool $isExchange): array
    {
        return $saleItems->map(function ($saleItem) use ($isExchange): array {
            $productName = $saleItem->product->compound_product_name . ' (Return)';
            if ($isExchange) {
                $productName = $saleItem->product->compound_product_name . ' (Exchange)';
            }

            $saleItemProduct = [
                'upc' => $saleItem->product->upc,
                'name' => $productName,
                'price' => $saleItem->total_price_paid,
                'quantity' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
            ];
            if ($saleItem instanceof SaleReturnItem) {
                /** @var SaleReturnReason $saleReturnReason */
                $saleReturnReason = $saleItem->saleReturnReason;
                $saleItemProduct['reason'] = $saleReturnReason->reason;
            }

            return $saleItemProduct;
        })->toArray();
    }

    public function getSum(Collection $inventoryUpdate, string $affectedByTypeName): float
    {
        return $inventoryUpdate->where('affected_by_type', $affectedByTypeName)->sum('quantity');
    }

    /**
     * @return mixed[]
     */
    public function getStockTransfer(Collection $inventoryUpdate, string $affectedByTypeName): array
    {
        $positiveStockTransfer = $inventoryUpdate->where('affected_by_type', $affectedByTypeName)
            ->where('quantity', '>', 0)
            ->sum('quantity');

        $negativeStockTransfer = $inventoryUpdate->where('affected_by_type', $affectedByTypeName)
            ->where('quantity', '<', 0)
            ->sum('quantity');

        return [$positiveStockTransfer, $negativeStockTransfer];
    }

    /**
     * @return mixed[]
     */
    public function getGoodReceivedNote(Collection $inventoryUpdate, string $affectedByTypeName): array
    {
        $positiveGoodReceivedQuantity = $inventoryUpdate->where('affected_by_type', $affectedByTypeName)
            ->where('quantity', '>', 0)
            ->sum('quantity');

        $negativeGoodReceivedQuantity = $inventoryUpdate->where('affected_by_type', $affectedByTypeName)
            ->where('quantity', '<', 0)
            ->sum('quantity');

        return [$positiveGoodReceivedQuantity, $negativeGoodReceivedQuantity];
    }

    /**
     * @return mixed[]
     */
    public function getStockAdjustment(Collection $inventoryUpdate, string $affectedByTypeName): array
    {
        $positiveStockAdjustment = $inventoryUpdate
            ->where('affected_by_type', ModelMapping::STOCK_ADJUSTMENT_ITEM->name)
            ->where('affectedBy.stockAdjustment.type_id', StockAdjustmentTypes::STI->value)
            ->sum('quantity');

        $negativeStockAdjustment = $inventoryUpdate->where('affected_by_type', $affectedByTypeName)
            ->where('affectedBy.stockAdjustment.type_id', StockAdjustmentTypes::STO->value)
            ->sum('quantity');

        return [$positiveStockAdjustment, $negativeStockAdjustment];
    }

    /**
     * @return array<int, mixed[]>
     */
    public function preparedSalesCollectionByDateAndBrand(array $filterData): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        $counterUpdates = $counterUpdateQueries->getSalesCollectionReportByDateAndBrand($filterData);
        $brandLocationsSalesCollection = [];
        $columns = [
            0 => 'Location Name',
        ];
        $grandTotal = [
            'location_name' => 'Grand Total',
            'total' => 0,
        ];

        $totals = [];

        foreach ($counterUpdates as $brandId => $counterUpdateGroupedBtLocations) {
            $brandLocationsSalesCollection[$brandId] = [
                'brand_name' => '',
                'brand_total' => 0,
                'locations' => [],
            ];

            $locations = [];

            $totals[$brandId] = [
                'location_name' => 'Total',
                'total' => 0,
            ];

            foreach ($counterUpdateGroupedBtLocations as $locationId => $counterUpdateGroupedByDate) {
                $locations[$locationId] = [
                    'location_name' => '',
                    'total' => 0,
                ];

                foreach ($counterUpdateGroupedByDate as $counterUpdates) {
                    $counterUpdate = $counterUpdates->first();
                    $date = $counterUpdate->opened_by_pos_at;
                    $brandLocationsSalesCollection[$brandId]['brand_name'] = $counterUpdate->brand_name;
                    $locations[$locationId]['location_name'] = $counterUpdate->location_name;
                    $salesCollectionAmount = $counterUpdates->sum('sales_collection_amount');
                    $salesCollectionAmount += RoundOffConfiguration::roundOffCalculationFor(
                        (string) $salesCollectionAmount
                    );
                    $locations[$locationId]['total'] += $salesCollectionAmount;

                    if (array_key_exists($date, $locations[$locationId])) {
                        $locations[$locationId][$date] += $salesCollectionAmount;
                    } else {
                        $locations[$locationId][$date] = $salesCollectionAmount;
                    }

                    if (array_key_exists($date, $totals[$brandId])) {
                        $totals[$brandId][$date] += $salesCollectionAmount;
                    } else {
                        $totals[$brandId][$date] = $salesCollectionAmount;
                    }

                    if (array_key_exists($date, $grandTotal)) {
                        $grandTotal[$date] += $salesCollectionAmount;
                    } else {
                        $grandTotal[$date] = $salesCollectionAmount;
                    }

                    $totals[$brandId]['total'] += $salesCollectionAmount;

                    /** @var Carbon $dateFormat */
                    $dateFormat = Carbon::createFromFormat('Y-m-d', $date);
                    $columns[$date] = $dateFormat->format('d/m/Y');
                }

                $brandLocationsSalesCollection[$brandId]['locations'] = $locations;
            }

            $grandTotal['total'] += $totals[$brandId]['total'];
            $brandLocationsSalesCollection[$brandId]['locations'] = $locations;
        }

        foreach ($totals as $brandId => $total) {
            $brandLocationsSalesCollection[$brandId]['locations'][] = $total;
        }

        $columns[] = 'Total';

        $dateRange = $this->prepareDateRange($filterData);

        return [$brandLocationsSalesCollection, $grandTotal, $columns, $dateRange];
    }

    public function preparedSalesCollectionBySummaryMonthAndBrand(array $filterData): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdates = $counterUpdateQueries->getSalesCollectionReportByDateAndBrand($filterData);

        $brandLocationsSalesCollection = [];
        $columns = [
            0 => 'Location Name',
        ];
        $grandTotal = [
            'location_name' => 'Grand Total',
            'total' => 0,
        ];
        $totals = [];

        foreach ($counterUpdates as $brandId => $counterUpdateGroupedByLocations) {
            $brandLocationsSalesCollection[$brandId] = [
                'brand_name' => '',
                'brand_total' => 0,
                'locations' => [],
            ];

            $locations = [];
            $totals[$brandId] = [
                'location_name' => 'Total',
                'total' => 0,
            ];

            foreach ($counterUpdateGroupedByLocations as $locationId => $counterUpdateGroupedByDate) {
                $locations[$locationId] = [
                    'location_name' => '',
                    'total' => 0,
                ];

                foreach ($counterUpdateGroupedByDate as $counterUpdates) {
                    $counterUpdate = $counterUpdates->first();
                    $date = $counterUpdate->opened_by_pos_at;

                    $brandLocationsSalesCollection[$brandId]['brand_name'] = $counterUpdate->brand_name;
                    $locations[$locationId]['location_name'] = $counterUpdate->location_name;

                    $salesCollectionAmount = $counterUpdates->sum('sales_collection_amount');
                    $salesCollectionAmount += RoundOffConfiguration::roundOffCalculationFor(
                        (string) $salesCollectionAmount
                    );

                    $monthKey = Carbon::parse($date)->format('Y-m'); // e.g., 2024-05

                    if (! isset($locations[$locationId][$monthKey])) {
                        $locations[$locationId][$monthKey] = 0;
                    }

                    $locations[$locationId][$monthKey] += $salesCollectionAmount;
                    $locations[$locationId]['total'] += $salesCollectionAmount;

                    if (! isset($totals[$brandId][$monthKey])) {
                        $totals[$brandId][$monthKey] = 0;
                    }

                    $totals[$brandId][$monthKey] += $salesCollectionAmount;
                    $totals[$brandId]['total'] += $salesCollectionAmount;

                    if (! isset($grandTotal[$monthKey])) {
                        $grandTotal[$monthKey] = 0;
                    }

                    $grandTotal[$monthKey] += $salesCollectionAmount;
                    $grandTotal['total'] += $salesCollectionAmount;

                    $columns[$monthKey] = Carbon::parse($monthKey . '-01')->format('F');
                }

                $brandLocationsSalesCollection[$brandId]['locations'] = $locations;
            }

            $brandLocationsSalesCollection[$brandId]['brand_total'] = $totals[$brandId]['total'];
            $brandLocationsSalesCollection[$brandId]['locations'][] = $totals[$brandId];
        }

        $columns = collect($columns)
            ->filter(fn ($val, $key): bool => 0 !== $key && 'Total' !== $key)
            ->sortKeys()
            ->prepend('Location Name', 0)
            ->push('Total')
            ->all();

        $dateRange = $this->prepareDateRange($filterData);

        ksort($brandLocationsSalesCollection);

        return [$brandLocationsSalesCollection, $grandTotal, $columns, $dateRange];
    }

    public function preparedPaymentsByDate(array $filterData, Collection $locations): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $salePayments = $counterUpdateQueries->getForSalesCollectionByFilter($filterData);

        $dateRange = $this->prepareDateRange($filterData);

        $locationPayments = [];

        $columns = [
            'sales_date' => 'Sales Date',
            'orders' => 'Orders',
            'sales_collection' => 'Sales Collection',
        ];

        foreach ($locations as $location) {
            $totals = [
                'sales_date' => 'Grand Total',
                'orders' => 0,
                'sales_collection' => 0,
                'sales_round_off' => 0,
                'total_tax_amount' => 0,
            ];
            $locationSaleDetails = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'payment_details' => [],
            ];

            $groupByLocationSalePayments = $salePayments->where('counter.location_id', $location->id);

            foreach ($groupByLocationSalePayments->groupBy('opened_by_pos_at') as $locationSalePayments) {
                $datePaymentRecords = [];
                $datePaymentRecords['sales_date'] = '';
                $datePaymentRecords['orders'] = 0;
                $datePaymentRecords['sales_collection'] = 0;

                foreach ($locationSalePayments as $locationSalePayment) {
                    $date = Carbon::createFromFormat('Y-m-d', $locationSalePayment->opened_by_pos_at);
                    $totalSales = ($locationSalePayment->total_sales + $locationSalePayment->total_sale_returns);
                    $datePaymentRecords['sales_date'] = $date ? $date->format('d-m-Y') : null;
                    $datePaymentRecords['orders'] += $totalSales;
                    $salesCollectionAmount = $locationSalePayment->sales_collection_amount;
                    $salesCollectionAmount += RoundOffConfiguration::roundOffCalculationFor(
                        (string) $salesCollectionAmount
                    );
                    $datePaymentRecords['sales_collection'] += $salesCollectionAmount;

                    $totals['orders'] += $totalSales;
                    $totals['sales_collection'] += $salesCollectionAmount;
                    $totals['sales_round_off'] += $locationSalePayment->total_sales_round_off;
                    $totals['total_tax_amount'] += $locationSalePayment->total_tax_amount;

                    foreach ($locationSalePayment->payments as $payment) {
                        $paymentType = $payment->paymentType->name;
                        $paymentName = strtolower(str_replace(' ', '_', $paymentType));
                        $columns[$paymentName] = $paymentType;

                        if (! array_key_exists($paymentName, $datePaymentRecords)) {
                            $datePaymentRecords[$paymentName] = 0;
                        }

                        $totalAmount = $payment->total_amount;
                        $totalAmount += RoundOffConfiguration::roundOffCalculationFor((string) $totalAmount);

                        $datePaymentRecords[$paymentName] += $totalAmount;

                        if (! array_key_exists($paymentName, $totals)) {
                            $totals[$paymentName] = 0;
                        }

                        $totals[$paymentName] += $totalAmount;
                    }
                }

                $locationSaleDetails['payment_details'][] = $datePaymentRecords;
            }

            $locationSaleDetails['totals'] = $totals;
            $locationPayments[] = $locationSaleDetails;
        }

        return [$locationPayments, $columns, $dateRange];
    }

    /**
     * @return array<int, mixed[]>
     */
    public function preparedPaymentsByOnlyTotals(array $filterData, Collection $locations): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $salePayments = $counterUpdateQueries->getForSalesCollectionByFilter($filterData);

        $columns = [
            'location_name' => 'Location Name',
            'orders' => 'Orders',
            'collection' => 'Collection',
        ];
        $locationSaleDetails = [];
        $dateRange = $this->prepareDateRange($filterData);

        $totals = [
            'location_name' => 'Grand Total',
            'orders' => 0,
            'collection' => 0,
        ];

        foreach ($locations as $location) {
            $locationSaleDetails[$location->id] = [
                'location_name' => '',
                'orders' => 0,
                'collection' => 0,
            ];

            $locationSalePayments = $salePayments->where('counter.location_id', $location->id);

            foreach ($locationSalePayments as $locationSalePayment) {
                $totalSales = ($locationSalePayment->total_sales + $locationSalePayment->total_sale_returns);
                $locationSaleDetails[$location->id]['location_name'] = $location->name . ' [' . $location->code . ']';
                $locationSaleDetails[$location->id]['orders'] += $totalSales;
                $salesCollectionAmount = $locationSalePayment->sales_collection_amount;
                $salesCollectionAmount += RoundOffConfiguration::roundOffCalculationFor(
                    (string) $salesCollectionAmount
                );

                $locationSaleDetails[$location->id]['collection'] += $salesCollectionAmount;

                $totals['orders'] += $totalSales;
                $totals['collection'] += $salesCollectionAmount;

                foreach ($locationSalePayment->payments as $payment) {
                    $paymentType = $payment->paymentType->name;
                    $paymentName = strtolower(str_replace(' ', '_', $paymentType));
                    $columns[$paymentName] = $paymentType;

                    if (! array_key_exists($paymentName, $locationSaleDetails[$location->id])) {
                        $locationSaleDetails[$location->id][$paymentName] = 0;
                    }

                    $totalAmount = $payment->total_amount;
                    $totalAmount += RoundOffConfiguration::roundOffCalculationFor((string) $totalAmount);

                    $locationSaleDetails[$location->id][$paymentName] += $totalAmount;

                    if (! array_key_exists($paymentName, $totals)) {
                        $totals[$paymentName] = 0;
                    }

                    $totals[$paymentName] += $totalAmount;
                }
            }
        }

        $locationSaleDetails['totals'] = $totals;

        return [$locationSaleDetails, $columns, $dateRange];
    }

    /**
     * @return array<int, mixed[]>
     */
    public function preparedSalesByReceipt(array $filterData, Collection $locations): array
    {
        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getByStoreIdForSalesCollectionExport($filterData);

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getByStoreIdForSalesCollectionExport($filterData);
        $sales = $sales->merge($saleReturns);
        $dateRange = $this->prepareDateRange($filterData);

        $locationsSales = [];
        $totals = [];
        $columns = [];
        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sales' => [],
                'roundingAdjust' => 0,
                'totalTaxAmount' => 0,
                'grandTotalCollection' => 0,
                'grandTotalReceipt' => 0,
            ];

            $columns = [
                'receipt_no' => 'Receipt No',
                'receipt_date' => 'Receipt Date',
                'collection' => 'Collection',
            ];

            $totals = [
                'receipt_no' => 'Grand Total',
                'receipt_date' => '',
                'remark' => '',
                'collection' => 0,
            ];

            foreach ($sales->sortBy('happened_at')->where(
                'counterUpdate.counter.location_id',
                $location->id
            ) as $key => $sale) {
                $locationSales['roundingAdjust'] += $sale->round_off;
                $locationSales['totalTaxAmount'] += $sale->total_tax_amount;
                $locationSales['grandTotalReceipt'] += 1;
                if ($sale instanceof Sale && $sale->offline_sale_id) {
                    $locationSales['sales'][$key]['receipt_no'] = $sale->offline_sale_id;
                    $totals['collection'] += $sale->total_amount_paid ?? 0;
                    $locationSales['sales'][$key]['collection'] = $sale->total_amount_paid ?? 0;
                }

                if ($sale instanceof SaleReturn && $sale->offline_sale_return_id) {
                    $totalPricePaid = $sale->total_price_paid ?? 0;
                    $locationSales['sales'][$key]['receipt_no'] = $sale->offline_sale_return_id;
                    $totals['collection'] -= $totalPricePaid;
                    $locationSales['sales'][$key]['collection'] = '-' . $totalPricePaid;
                }

                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
                $happenedAt = $happenedAtFormat ? $happenedAtFormat->format('d-m-Y h:i:s A') : '';

                $locationSales['sales'][$key]['receipt_date'] = $happenedAt;
                if ($sale instanceof Sale) {
                    foreach ($sale->payments as $payment) {
                        $paymentType = isset($payment->paymentType) ? $payment->paymentType->name : '';
                        $paymentName = strtolower(str_replace(' ', '_', $paymentType));
                        $columns[$paymentName] = $paymentType;
                        $paymentAmount = $payment->amount;
                        $paymentAmount += RoundOffConfiguration::roundOffCalculationFor((string) $payment->amount);
                        $locationSales['sales'][$key][$paymentName] = $paymentAmount;

                        if (! array_key_exists($paymentName, $totals)) {
                            $totals[$paymentName] = 0;
                        }

                        if ($payment->payment_type_id === StaticPaymentTypes::LOYALTY_POINT->value) {
                            $totals['collection'] -= $payment->amount;
                            $locationSales['sales'][$key]['collection'] -= $payment->amount;
                        }

                        $totals[$paymentName] += $paymentAmount;
                    }
                }

                $locationSales['sales'][$key]['remark'] = $sale->notes;
            }

            $columns['remark'] = 'Remark';

            $locationSales['totals'] = $totals;
            $locationSales['grandTotalCollection'] = $totals['collection'];

            $locationsSales[] = $locationSales;
        }

        return [$locationsSales, $columns, $dateRange];
    }

    /**
     * @return array<int, mixed[]>
     */
    public function preparedSalesByCashier(array $filterData, Collection $locations): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $salePayments = $counterUpdateQueries->getForSalesCollectionByFilterCashier($filterData);

        $dateRange = $this->prepareDateRange($filterData);

        $locationPayments = [];

        $columns = [
            'cashier' => 'Cashier',
            'orders' => 'Orders',
            'sales_collection' => 'Sales Collection',
        ];

        foreach ($locations as $location) {
            $totals = [
                'cashier' => 'Grand Total',
                'orders' => 0,
                'sales_collection' => 0,
                'sales_round_off' => 0,
                'total_tax_amount' => 0,
            ];
            $locationSaleDetails = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'payment_details' => [],
            ];

            $groupByLocationSalePayments = $salePayments->where('counter.location_id', $location->id);

            foreach ($groupByLocationSalePayments->groupBy('cashier_id') as $locationSalePayments) {
                $datePaymentRecords = [];
                $datePaymentRecords['cashier'] = '';
                $datePaymentRecords['orders'] = 0;
                $datePaymentRecords['sales_collection'] = 0;

                foreach ($locationSalePayments as $locationSalePayment) {
                    $totalSales = ($locationSalePayment->total_sales + $locationSalePayment->total_sale_returns);

                    $datePaymentRecords['cashier'] = $locationSalePayment->cashier->employee->getFullName();
                    $datePaymentRecords['orders'] += $totalSales;

                    $salesCollectionAmount = $locationSalePayment->sales_collection_amount;
                    $salesCollectionAmount += RoundOffConfiguration::roundOffCalculationFor(
                        (string) $salesCollectionAmount
                    );

                    $datePaymentRecords['sales_collection'] += $salesCollectionAmount;

                    $totals['orders'] += $totalSales;
                    $totals['sales_collection'] += $salesCollectionAmount;
                    $totals['sales_round_off'] += $locationSalePayment->total_sales_round_off;
                    $totals['total_tax_amount'] += $locationSalePayment->total_tax_amount;

                    foreach ($locationSalePayment->payments as $payment) {
                        $paymentType = $payment->paymentType->name;
                        $paymentName = strtolower(str_replace(' ', '_', $paymentType));
                        $columns[$paymentName] = $paymentType;

                        if (! array_key_exists($paymentName, $datePaymentRecords)) {
                            $datePaymentRecords[$paymentName] = 0;
                        }

                        $totalAmount = $payment->total_amount;
                        $totalAmount += RoundOffConfiguration::roundOffCalculationFor((string) $totalAmount);

                        $datePaymentRecords[$paymentName] += $totalAmount;

                        if (! array_key_exists($paymentName, $totals)) {
                            $totals[$paymentName] = 0;
                        }

                        $totals[$paymentName] += $totalAmount;
                    }
                }

                $locationSaleDetails['payment_details'][] = $datePaymentRecords;
            }

            $locationSaleDetails['totals'] = $totals;
            $locationPayments[] = $locationSaleDetails;
        }

        return [$locationPayments, $columns, $dateRange];
    }

    /**
     * @return array<int, mixed[]>
     */
    public function preparedSalesByCounter(array $filterData, Collection $locations): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $salePayments = $counterUpdateQueries->getForSalesCollectionByFilter($filterData);
        $dateRange = $this->prepareDateRange($filterData);

        $locationPayments = [];

        $columns = [
            'counter' => 'Counter',
            'orders' => 'Orders',
            'sales_collection' => 'Sales Collection',
        ];

        foreach ($locations as $location) {
            $totals = [
                'counter' => 'Grand Total',
                'orders' => 0,
                'sales_collection' => 0,
                'sales_round_off' => 0,
                'total_tax_amount' => 0,
            ];
            $locationSaleDetails = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'payment_details' => [],
            ];

            $groupByLocationSalePayments = $salePayments->where('counter.location_id', $location->id);

            foreach ($groupByLocationSalePayments->groupBy('counter_id') as $locationSalePayments) {
                $datePaymentRecords = [];
                $datePaymentRecords['counter'] = '';
                $datePaymentRecords['orders'] = 0;
                $datePaymentRecords['sales_collection'] = 0;

                foreach ($locationSalePayments as $locationSalePayment) {
                    $totalSales = ($locationSalePayment->total_sales + $locationSalePayment->total_sale_returns);

                    $datePaymentRecords['counter'] = $locationSalePayment->counter->name;
                    $datePaymentRecords['orders'] += $totalSales;

                    $salesCollectionAmount = $locationSalePayment->sales_collection_amount;
                    $salesCollectionAmount += RoundOffConfiguration::roundOffCalculationFor(
                        (string) $salesCollectionAmount
                    );

                    $datePaymentRecords['sales_collection'] += $salesCollectionAmount;

                    $totals['orders'] += $totalSales;
                    $totals['sales_collection'] += $salesCollectionAmount;
                    $totals['sales_round_off'] += $locationSalePayment->total_sales_round_off;
                    $totals['total_tax_amount'] += $locationSalePayment->total_tax_amount;

                    foreach ($locationSalePayment->payments as $payment) {
                        $paymentType = $payment->paymentType->name;
                        $paymentName = strtolower(str_replace(' ', '_', $paymentType));
                        $columns[$paymentName] = $paymentType;

                        if (! array_key_exists($paymentName, $datePaymentRecords)) {
                            $datePaymentRecords[$paymentName] = 0;
                        }

                        $totalAmount = $payment->total_amount;
                        $totalAmount += RoundOffConfiguration::roundOffCalculationFor((string) $totalAmount);

                        $datePaymentRecords[$paymentName] += $totalAmount;

                        if (! array_key_exists($paymentName, $totals)) {
                            $totals[$paymentName] = 0;
                        }

                        $totals[$paymentName] += $totalAmount;
                    }
                }

                $locationSaleDetails['payment_details'][] = $datePaymentRecords;
            }

            $locationSaleDetails['totals'] = $totals;
            $locationPayments[] = $locationSaleDetails;
        }

        return [$locationPayments, $columns, $dateRange];
    }

    /**
     * @return array<int, mixed[]>
     */
    public function preparedSalesByTime(array $filterData, Collection $locations): array
    {
        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getByStoreIdForSalesCollectionExport($filterData);

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getByStoreIdForSalesCollectionExport($filterData);
        $sales = $sales->merge($saleReturns);
        $dateRange = $this->prepareDateRange($filterData);

        $locationsSales = [];
        $totals = [];
        $columns = [];
        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sales' => [],
                'roundingAdjust' => 0,
                'totalTaxAmount' => 0,
            ];

            $columns = [
                'time' => 'Time',
                'orders' => 'Orders',
                'collection' => 'Collection',
            ];

            $totals = [
                'time' => 'Grand Total',
                'orders' => 0,
                'collection' => 0,
            ];

            foreach ($sales->sortBy('happened_at')->where(
                'counterUpdate.counter.location_id',
                $location->id
            ) as $sale) {
                /** @var Carbon $happenedAt */
                $happenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
                $time = $happenedAt->format('ymdH');

                $locationSales['sales'][$time]['time'] = $happenedAt->format(
                    'd/m/Y h A'
                ) . ' to ' . $happenedAt->addHour()->format('h A');

                if (! array_key_exists($time, $locationSales['sales'])) {
                    $locationSales['sales'][$time] = [];
                }

                $locationSales['roundingAdjust'] += $sale->round_off;
                $locationSales['totalTaxAmount'] += $sale->total_tax_amount;

                $totals['orders'] += 1;

                if (! array_key_exists('collection', $locationSales['sales'][$time])) {
                    $locationSales['sales'][$time]['collection'] = 0;
                    $locationSales['sales'][$time]['orders'] = 0;
                }

                $locationSales['sales'][$time]['orders'] += 1;

                if ($sale instanceof Sale && $sale->offline_sale_id) {
                    $totals['collection'] += $sale->total_amount_paid ?? 0;
                    $locationSales['sales'][$time]['collection'] += $sale->total_amount_paid ?? 0;
                }

                if ($sale instanceof SaleReturn && $sale->offline_sale_return_id) {
                    $totals['collection'] -= $sale->total_price_paid ?? 0;
                    $locationSales['sales'][$time]['collection'] -= $sale->total_price_paid ?? 0;
                }

                if (! $sale instanceof Sale) {
                    continue;
                }

                foreach ($sale->payments as $payment) {
                    $paymentType = isset($payment->paymentType) ? $payment->paymentType->name : '';
                    $paymentName = strtolower(str_replace(' ', '_', $paymentType));
                    $columns[$paymentName] = $paymentType;

                    if (! array_key_exists($paymentName, $locationSales['sales'][$time])) {
                        $locationSales['sales'][$time][$paymentName] = 0;
                    }

                    if ($payment->payment_type_id === StaticPaymentTypes::LOYALTY_POINT->value) {
                        $totals['collection'] -= $payment->amount;
                        $locationSales['sales'][$time]['collection'] -= $payment->amount;
                    }

                    $locationSales['sales'][$time][$paymentName] += $payment->amount;

                    if (! array_key_exists($paymentName, $totals)) {
                        $totals[$paymentName] = 0;
                    }

                    $totals[$paymentName] += $payment->amount;
                }
            }

            $locationSales['totals'] = $totals;

            $locationsSales[] = $locationSales;
        }

        return [$locationsSales, $columns, $dateRange];
    }

    /**
     * @return mixed[]
     */
    public function prepareDateRange(array $filterData): array
    {
        if (isset($filterData['date_range'][0]) && is_string(
            $filterData['date_range'][0]
        ) && $carbonDate = Carbon::createFromFormat('Y-m-d', $filterData['date_range'][0])) {
            $filterData['date_range'][0] = $carbonDate->format('d-m-Y') . ' (' . $carbonDate->format('l') . ')';
        }

        if (isset($filterData['date_range'][1]) && is_string(
            $filterData['date_range'][1]
        ) && $carbonDate = Carbon::createFromFormat('Y-m-d', $filterData['date_range'][1])) {
            $filterData['date_range'][1] = $carbonDate->format('d-m-Y') . ' (' . $carbonDate->format('l') . ')';
        }

        return $filterData['date_range'];
    }

    public function prepareMonthRange(array $filterData): string
    {
        $date = $filterData['month_range'][1] . '-' . $filterData['month_range'][0] . '-' . now()->format('d');

        /** @var Carbon $format */
        $format = Carbon::createFromFormat('Y-m-d', $date);

        return $format->format('F Y');
    }

    public function getStoresAndWareHousesByCompanyId(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $stores = $locationQueries->getStoresForCustomReports($companyId);
        $warehouses = $locationQueries->getWarehousesForCustomReports($companyId);

        return [
            'stores' => $stores,
            'warehouses' => $warehouses,
        ];
    }
}
