<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SellThroughSummaryServices
{
    public function printSellThroughDetails(array $filterData, int $companyId, array $getFilterLabels): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $products = $sellThroughAggregateQueries->sellThroughAggregateForSummaryGet($filterData, $companyId);

        $locationName = $products->pluck('location_name', 'location_id')->filter()->unique()->toArray();

        $records = collect();
        foreach ($products->groupBy('article_number') as $key => $products) {
            $subRecords = collect([]);
            $productByArticleNumbers = [];
            $locationRecords = collect([]);
            foreach ($products as $product) {
                $locationRecords->push($this->getLocationBasedData($product->toArray(), $locationName));
                $subRecords->push([
                    'name' => $product->name . ' ( ' . $product->article_number . ' )',
                    'locations' => null,
                    'received' => CommonFunctions::truncateDecimal($products->sum('received')),
                    'sold' => CommonFunctions::truncateDecimal($products->sum('sold')),
                    'balance' => CommonFunctions::truncateDecimal($products->sum('balance')),
                    'sell_through' => (float) $products->sum(
                        'received'
                    ) !== 0.0 ? CommonFunctions::displayAmountWithPercentageSymbol(
                        $products->sum('sold') * 100 / $products->sum('received')
                    ) : 0,
                ]);
            }

            $subRecord = $subRecords->first();

            $subRecord['locations'] = $locationRecords->toArray();

            $productByArticleNumbers[$key] = $subRecord;

            $records->push($productByArticleNumbers[$key]);
        }

        $columns = ['Name', 'Units Sold @ Branch', 'Received', 'Sold', 'Balance', 'Sell Through (%)'];

        $locationColumns = [
            config('app.product_variant') ? $attributeQueries->getById(
                $filterData['attribute_type'],
                $companyId
            )->name.' Name' : 'Color Name',
            ...$locationName,
            'Total',
        ];

        return view('prints.sell_through_by_summary', [
            'sellThroughRecords' => $records->toArray(),
            'reportType' => Str::of(SellThroughTypes::SUMMARY->name)->title()->replace('_', ' ')->value(),
            'filterDate' => $filterData['date'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'columns' => $columns,
            'locationColumns' => $locationColumns,
            'company' => $company,
            'locations' => $locations,
            'getFilterLabels' => $getFilterLabels,
        ])->render();
    }

    public function getLocationBasedData(array $product, array $locations): array
    {
        $locationRecords = collect([]);
        foreach (array_keys($locations) as $locationId) {
            if (array_key_exists('location_id', $product) && $product['location_id'] === $locationId) {
                $locationRecords->push([
                    'color_name' => config('app.product_variant') ? $product['variant_name'] : $product['color_name'],
                    'units_sold' => $product['sold'],
                ]);
            } else {
                $locationRecords->push([
                    'color_name' => config('app.product_variant') ? $product['variant_name'] : $product['color_name'],
                    'units_sold' => 0,
                ]);
            }
        }

        $locationRecords->push([
            'color_name' => 'Total',
            'units_sold' => $locationRecords->sum('units_sold'),
        ]);

        return $locationRecords->toArray();
    }
}
