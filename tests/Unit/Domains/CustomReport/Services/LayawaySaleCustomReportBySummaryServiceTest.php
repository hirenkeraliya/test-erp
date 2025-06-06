<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\LayawayReportTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\LayawaySaleCustomReportBySummaryService;
use App\Models\Cashier;
use App\Models\Color;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
use App\Models\StoreManager;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('export function exports sales data by summary and item to expected format', function (): void {
    $filterData = [
        'location_ids' => [1],
        'date_range' => [now(), now()],
        'report_type' => LayawayReportTypes::BY_DETAILS->value,
        'counter_ids' => [],
        'cashier_ids' => [],
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $cashier->employee = $employee;

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $counterUpdate->cashier = $cashier;
    $counterUpdate->counter = $counter;

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'happened_at' => Carbon::now()->format('Y-m-d h:i:s'),
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $storeManager->employee = $employee;

    $sale->layawayAuthorizer = $storeManager;

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'derivative_id' => 1,
    ]);

    $color = Color::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'test color',
    ]);

    $size = Size::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'test color',
    ]);

    $product->color = $color;
    $product->size = $size;
    $saleItem->product = $product;

    $sale->saleItems = collect([$saleItem]);

    $sale->counterUpdate = $counterUpdate;
    $counterUpdate->counter = $counter;

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

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getLayawaySalesWithItemsData')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $layawaySaleCustomReportBySummaryService = new LayawaySaleCustomReportBySummaryService();
    $result = $layawaySaleCustomReportBySummaryService->export($filterData, $company->id, 'demo.csv');

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});
