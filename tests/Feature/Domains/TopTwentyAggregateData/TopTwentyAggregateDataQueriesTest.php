<?php

declare(strict_types=1);

use App\Domains\Sale\Enums\TopTwentyReportTypes;
use App\Domains\TopTwentyAggregateData\TopTwentyAggregateDataQueries;
use App\Models\Company;
use App\Models\CounterUpdate;
use App\Models\Product;
use App\Models\TopTwentyAggregateData;
use Illuminate\Support\Collection;

test('top twenty data aggregate can be added.', function (): void {
    $companyId = Company::factory()->create()->getKey();
    $productId = Product::factory()->create()->getKey([
        'company_id' => $companyId,
    ]);
    $counterUpdateId = CounterUpdate::factory()->create()->getKey();

    $topTwentyAggregateData = [
        'product_id' => $productId,
        'counter_update_id' => $counterUpdateId,
        'date' => now()->format('Y-m-d'),
        'quantity' => 1,
        'gross_sales' => 10,
        'discount' => 0,
        'net_sales' => 10,
        'tax' => 0,
        'total_amount' => 10,
    ];

    $topTwentyAggregateDataQueries = new TopTwentyAggregateDataQueries();
    $topTwentyAggregateDataQueries->addNew($topTwentyAggregateData);

    $this->assertDatabaseHas(TopTwentyAggregateData::class, [
        'product_id' => $productId,
        'counter_update_id' => $counterUpdateId,
    ]);
});

test(
    'getByStoreForTopProductExport it returns the collection of top twenty data aggregate.',
    function (): void {
        $companyId = Company::factory()->create()->getKey();
        $productId = Product::factory()->create()->getKey([
            'company_id' => $companyId,
        ]);
        $counterUpdateId = CounterUpdate::factory()->create()->getKey();
        $topTwentyAggregateData = [
            'combine_stock_by_selected_location' => null,
            'location_ids' => [],
            'date_range' => [now(), now()],
            'counter_ids' => [],
            'cashier_ids' => [],
            'check_article_number' => null,
            'report_type' => TopTwentyReportTypes::BY_CATEGORIES->value,
            'report_view_type' => null,
            'filter_by' => null,
        ];
        $topTwentyAggregateDataQueries = new TopTwentyAggregateDataQueries();
        $response = $topTwentyAggregateDataQueries->getByStoreForTopProductExport($topTwentyAggregateData);
        expect($response)->toBeInstanceOf(Collection::class);
    }
);

test('getByStoreForTopColorExport it returns the collection of top twenty data aggregate.', function (): void {
    $companyId = Company::factory()->create()->getKey();
    $productId = Product::factory()->create()->getKey([
        'company_id' => $companyId,
    ]);
    $counterUpdateId = CounterUpdate::factory()->create()->getKey();
    $topTwentyAggregateData = [
        'combine_stock_by_selected_location' => null,
        'location_ids' => [],
        'date_range' => [now(), now()],
        'counter_ids' => [],
        'cashier_ids' => [],
        'check_article_number' => null,
        'report_type' => TopTwentyReportTypes::BY_CATEGORIES->value,
        'report_view_type' => null,
        'filter_by' => null,
    ];
    $topTwentyAggregateDataQueries = new TopTwentyAggregateDataQueries();
    $response = $topTwentyAggregateDataQueries->getByStoreForTopColorExport($topTwentyAggregateData);
    expect($response)->toBeInstanceOf(Collection::class);
});
