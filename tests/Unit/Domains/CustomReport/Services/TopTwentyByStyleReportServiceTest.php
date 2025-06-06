<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\TopTwentyReportTypes;
use App\Domains\Sale\Enums\TopTwentyReportViewTypes;
use App\Domains\Sale\Services\TopTwentyByStyleReportService;
use App\Domains\Style\StyleQueries;
use App\Domains\TopTwentyAggregateData\TopTwentyAggregateDataQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Style;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('exports the top twenty style report for a given company as an Excel file', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $style = Style::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counter->location = $location;

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 2,
    ]);

    $counterUpdate->counter = $counter;

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $sale->counterUpdate = $counterUpdate;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $saleItem->sale = $sale;
    $saleItem->product = $product;

    $filterData = [
        'location_ids' => [$location->id],
        'date_range' => [now(), now()],
        'cashier_ids' => [],
        'counter_ids' => [],
        'report_type' => [TopTwentyReportTypes::BY_STYLES->value],
        'report_view_type' => TopTwentyReportViewTypes::BY_AMOUNT->value,
        'combine_stock_by_selected_location' => null,
        'check_article_number' => null,
    ];

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
            ->once()
            ->andReturn(collect([$location]));
    });

    $this->mock(StyleQueries::class, function ($mock) use ($style): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(collect([$style]));
    });

    $this->mock(TopTwentyAggregateDataQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getByStoreForTopColorExport')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $topTwentyByStyleReportService = resolve(TopTwentyByStyleReportService::class);
    $result = $topTwentyByStyleReportService->export($company->id, $filterData, 'demo.xlsx');

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it('prints the top twenty style report for a given company and returns a string', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $style = Style::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counter->location = $location;

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 2,
    ]);

    $counterUpdate->counter = $counter;

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $sale->counterUpdate = $counterUpdate;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $saleItem->sale = $sale;
    $saleItem->product = $product;

    $filterData = [
        'location_ids' => [$location->id],
        'date_range' => [now(), now()],
        'cashier_ids' => [],
        'counter_ids' => [],
        'report_type' => [TopTwentyReportTypes::BY_STYLES->value],
        'report_view_type' => TopTwentyReportViewTypes::BY_AMOUNT->value,
        'combine_stock_by_selected_location' => null,
        'check_article_number' => null,
    ];

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
            ->once()
            ->andReturn(collect([$location]));
    });

    $this->mock(StyleQueries::class, function ($mock) use ($style): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(collect([$style]));
    });

    $this->mock(TopTwentyAggregateDataQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getByStoreForTopColorExport')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $topTwentyByStyleReportService = resolve(TopTwentyByStyleReportService::class);
    $response = $topTwentyByStyleReportService->print($company->id, $filterData);
    expect($response)->toBeString();
});
