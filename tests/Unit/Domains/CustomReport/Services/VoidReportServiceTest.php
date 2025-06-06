<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\VoidSale\Services\VoidReportService;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreManager;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('renderPreparedVoidReport function returns expected void sales data by date', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'date_range' => [],
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'name' => 'Test Store',
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'status' => SaleStatus::VOID_SALE->value,
        'happened_at' => Carbon::now()->format('Y-m-d h:i:s'),
    ]);

    $voidSaleReason = VoidSaleReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $voidSale = VoidSale::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'voided_by_store_manager_id' => $storeManager->id,
        'void_sale_reason_id' => $voidSaleReason->id,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'company_id' => 1,
        'designation_id' => 1,
    ]);

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

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'derivative_id' => 1,
        'price_paid_per_unit' => 10,
    ]);

    $saleItem->product = $product;
    $saleItem->promoters = collect([$promoter]);
    $promoter->employee = $employee;

    $storeManager->employee = $employee;

    $voidSale->voidedByStoreManager = $storeManager;
    $voidSale->voidSaleReason = $voidSaleReason;
    $sale->saleItems = collect([$saleItem]);
    $sale->voidSale = $voidSale;

    $this->mock(CustomReportService::class, function ($mock): void {
        $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getVoidSaleNumberPrefix')
            ->once();
    });

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getByStoreIdForSalesVoidReportExport')
            ->once()
            ->andReturn(collect([$sale]));
    });

    $voidReportService = new VoidReportService();
    $result = $voidReportService->renderPreparedVoidReport($filterData, $company, collect([$location]));

    expect($result)->toContain('Void Sales Report');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
    expect($result)->toContain($product->name);
    expect($result)->toContain($employee->getFullName());
});

it('exportSaleCollection function exports void sale data by date to expected BinaryFileResponse', function (): void {
    $filterData = [
        'location_ids' => [],
        'counter_ids' => [],
        'date_range' => [],
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
        $mock->shouldReceive('getVoidSaleNumberPrefix')
            ->once();
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
            ->once()
            ->andReturn(collect([$location]));
    });

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('getByStoreIdForSalesVoidReportExport')
            ->once()
            ->andReturn(collect([]));
    });

    $voidReportService = new VoidReportService();

    $result = $voidReportService->exportVoidSaleReport(1, $filterData, 'demo.csv');

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});
