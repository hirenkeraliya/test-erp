<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\GeneralSalesReportTypes;
use App\Domains\Sale\Services\GeneralSalesReportService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('renderPreparedGeneralSalesByProduct function returns expected sales data by product', function (): void {
    $filterData = [
        'location_ids' => [],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'brand_ids' => [],
        'promoter_ids' => [],
        'report_type' => GeneralSalesReportTypes::BY_PRODUCT->value,
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
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

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'test brand',
    ]);

    $promoter->employee = $employee;
    $saleItem->promoters = collect([$promoter]);

    $product->color = $color;
    $product->size = $size;
    $product->brand = $brand;

    $saleItem->sale = $sale;
    $saleItem->product = $product;

    $sale->counterUpdate = $counterUpdate;
    $counterUpdate->counter = $counter;

    $this->mock(CustomReportService::class, function ($mock): void {
        $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
    });
    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getGeneralSalesReportByProduct')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
            ->once()
            ->andReturn(collect([$location]));
    });

    $generalSalesReportService = new GeneralSalesReportService();

    $result = $generalSalesReportService->renderPreparedGeneralSalesByProduct($filterData, $company, false);

    expect($result)->toContain('General Sales Report');
    expect($result)->toContain('By Product');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
    expect($result)->toContain($product->upc);
});

it('renderPreparedGeneralSalesBySummary function returns expected sales data by summary', function (): void {
    $filterData = [
        'location_ids' => [],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'brand_ids' => [],
        'promoter_ids' => [],
        'report_type' => GeneralSalesReportTypes::BY_SUMMARY->value,
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
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

    $saleItem->sale = $sale;
    $saleItem->product = $product;

    $sale->counterUpdate = $counterUpdate;
    $counterUpdate->counter = $counter;

    $this->mock(CustomReportService::class, function ($mock): void {
        $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
    });

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getGeneralSalesReportBySummary')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
            ->once()
            ->andReturn(collect([$location]));
    });

    $generalSalesReportService = new GeneralSalesReportService();

    $result = $generalSalesReportService->renderPreparedGeneralSalesBySummary($filterData, $company, false);

    expect($result)->toContain('General Sales Report');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
});

it(
    'renderPreparedGeneralSalesByDateAndBrand function returns expected sales data by date and brand',
    function (): void {
        $filterData = [
            'location_ids' => [],
            'department_ids' => [],
            'date_range' => [now(), now()],
            'filter_by' => null,
            'brand_ids' => [],
            'promoter_ids' => [],
            'report_type' => GeneralSalesReportTypes::BY_DATE_AND_BRAND->value,
            'e_invoice_submitted' => null,
        ];

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
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

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $brand = Brand::factory()->make([
            'id' => 1,
            'name' => 'ABCD',
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => $brand->id,
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
        $product->brand = $brand;

        $saleItem->sale_date = now()->format('Y-m-d');
        $saleItem->sale = $sale;
        $saleItem->product = $product;

        $sale->counterUpdate = $counterUpdate;
        $counterUpdate->counter = $counter;

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('prepareDateRange')
                ->once()
                ->andReturn([now(), now()]);
        });
        $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('getGeneralSalesReportByDateAndBrand')
                ->once()
                ->andReturn(collect([$saleItem]));
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdsWithNameAndCode')
                    ->once()
                    ->andReturn(collect([$location]));
        });

        $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
            $mock->shouldReceive('getById')
                    ->once()
                    ->andReturn($brand);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
        });

        $generalSalesReportService = new GeneralSalesReportService();

        $result = $generalSalesReportService->renderPreparedGeneralSalesByDateAndBrand($filterData, $company, false);
        expect($result)->toContain('General Sales Report');
        expect($result)->toContain('Test Company');
        expect($result)->toContain('Test Store');
    }
);

it('renderPreparedGeneralSalesByPromoter function returns expected sales data by summary', function (): void {
    $filterData = [
        'location_ids' => [],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'brand_ids' => [],
        'promoter_ids' => [],
        'report_type' => GeneralSalesReportTypes::BY_PROMOTER_SUMMARY->value,
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'designation_id' => 1,
        'company_id' => $company->id,
        'first_name' => 'test',
        'last_name' => 'test',
    ]);

    $promoters = Promoter::factory(2)->make([
        'id' => 1,
        'employee_id' => $employee->id,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
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

    $saleItem->sale = $sale;
    $saleItem->product = $product;

    $saleItem->promoters = $promoters;

    $saleItem->promoters[0]->employee = $employee;
    $saleItem->promoters[1]->employee = $employee;

    $sale->counterUpdate = $counterUpdate;
    $counterUpdate->counter = $counter;

    $this->mock(CustomReportService::class, function ($mock): void {
        $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
    });
    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getGeneralSalesReportBySummary')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
                ->once()
                ->andReturn(collect([$location]));
    });

    $generalSalesReportService = new GeneralSalesReportService();

    $result = $generalSalesReportService->renderPreparedGeneralSalesByPromoterSummary($filterData, $company, false);

    expect($result)->toContain('General Sales Report');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
});

it(
    'renderPreparedGeneralSalesByItemAndReceipt function returns expected sales data by item and receipt',
    function (): void {
        $filterData = [
            'location_ids' => [],
            'department_ids' => [],
            'date_range' => [now(), now()],
            'filter_by' => null,
            'brand_ids' => [],
            'promoter_ids' => [],
            'report_type' => GeneralSalesReportTypes::BY_ITEM_AND_RECEIPT->value,
            'e_invoice_submitted' => null,
        ];

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
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

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => Carbon::now()->format('Y-m-d h:i:s'),
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

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'derivative_id' => 1,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'designation_id' => 1,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'group_id' => 1,
        ]);

        $promoter->employee = $employee;
        $saleItem->promoters = collect([$promoter]);

        $saleItem->sale = $sale;
        $saleItem->product = $product;

        $sale->counterUpdate = $counterUpdate;
        $counterUpdate->counter = $counter;

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
        });

        $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('getForGeneralSalesReportBySalesDate')
            ->once()
            ->andReturn(collect([$saleItem]));
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdsWithNameAndCode')
                ->once()
                ->andReturn(collect([$location]));
        });

        $generalSalesReportService = new GeneralSalesReportService();
        $result = $generalSalesReportService->renderPreparedGeneralSalesByItemAndReceipt($filterData, $company, false);

        expect($result)->toContain('General Sales Report');
        expect($result)->toContain('Test Company');
        expect($result)->toContain('Test Store');
        expect($result)->toContain($product->upc);
    }
);

it(
    'renderPreparedGeneralSalesByReceiptAndItem function returns expected sales data by receipt and item',
    function (): void {
        $filterData = [
            'location_ids' => [],
            'department_ids' => [],
            'date_range' => [now(), now()],
            'filter_by' => null,
            'brand_ids' => [],
            'promoter_ids' => [],
            'report_type' => GeneralSalesReportTypes::BY_RECEIPT_AND_ITEM->value,
            'e_invoice_submitted' => null,
        ];

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
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

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'happened_at' => Carbon::now()->format('Y-m-d h:i:s'),
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

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'derivative_id' => 1,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'designation_id' => 1,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'group_id' => 1,
        ]);

        $promoter->employee = $employee;
        $saleItem->promoters = collect([$promoter]);

        $saleItem->sale = $sale;
        $saleItem->product = $product;

        $sale->counterUpdate = $counterUpdate;
        $counterUpdate->counter = $counter;

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
        });

        $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('getForGeneralSalesReportBySalesDate')
            ->once()
            ->andReturn(collect([$saleItem]));
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdsWithNameAndCode')
                ->once()
                ->andReturn(collect([$location]));
        });

        $generalSalesReportService = new GeneralSalesReportService();
        $result = $generalSalesReportService->renderPreparedGeneralSalesByReceiptAndItem($filterData, $company, false);

        expect($result)->toContain('General Sales Report');
        expect($result)->toContain('Test Company');
        expect($result)->toContain('Test Store');
        expect($result)->toContain($product->upc);
    }
);

it(
    'renderPreparedGeneralSalesByColorAndSize function returns expected sales data by color and size',
    function (): void {
        $filterData = [
            'location_ids' => [],
            'department_ids' => [],
            'date_range' => [now(), now()],
            'filter_by' => null,
            'brand_ids' => [],
            'promoter_ids' => [],
            'report_type' => GeneralSalesReportTypes::BY_COLOR_AND_SIZE->value,
            'e_invoice_submitted' => null,
        ];

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
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

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
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

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'designation_id' => 1,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'group_id' => 1,
        ]);

        $promoter->employee = $employee;
        $saleItem->promoters = collect([$promoter]);

        $product->color = $color;
        $product->size = $size;

        $saleItem->sale = $sale;
        $saleItem->product = $product;

        $sale->counterUpdate = $counterUpdate;
        $counterUpdate->counter = $counter;

        $this->mock(CustomReportService::class, function ($mock): void {
            $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
        });

        $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('getForGeneralSalesReportBySalesDateColorAndSize')
            ->once()
            ->andReturn(collect([$saleItem]));
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getByIdsWithNameAndCode')
                ->once()
                ->andReturn(collect([$location]));
        });

        $generalSalesReportService = new GeneralSalesReportService();
        $result = $generalSalesReportService->renderPreparedGeneralSalesByColorAndSize($filterData, $company, false);

        expect($result)->toContain('General Sales Report');
        expect($result)->toContain('Test Company');
        expect($result)->toContain('Test Store');
        expect($result)->toContain($product->upc);
    }
);

it('exportGeneralSalesReport function exports expected sales data by item and receipt to a file', function (): void {
    $filterData = [
        'location_ids' => [],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'report_type' => GeneralSalesReportTypes::BY_ITEM_AND_RECEIPT->value,
        'brand_ids' => [],
        'promoter_ids' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'happened_at' => Carbon::now()->format('Y-m-d h:i:s'),
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

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $promoter->employee = $employee;
    $saleItem->promoters = collect([$promoter]);

    $product->color = $color;
    $product->size = $size;

    $saleItem->sale = $sale;
    $saleItem->product = $product;

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

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getForGeneralSalesReportBySalesDate')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $generalSalesReportService = new GeneralSalesReportService();
    $result = $generalSalesReportService->exportGeneralSalesReport($company->id, $filterData, 'demo.csv', false);

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it('exportGeneralSalesReport function exports sales data by receipt and item to expected format', function (): void {
    $filterData = [
        'location_ids' => [],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'report_type' => GeneralSalesReportTypes::BY_RECEIPT_AND_ITEM->value,
        'brand_ids' => [],
        'promoter_ids' => [],
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'happened_at' => Carbon::now()->format('Y-m-d h:i:s'),
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

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $promoter->employee = $employee;
    $saleItem->promoters = collect([$promoter]);

    $product->color = $color;
    $product->size = $size;

    $saleItem->sale = $sale;
    $saleItem->product = $product;

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

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getForGeneralSalesReportBySalesDate')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $generalSalesReportService = new GeneralSalesReportService();
    $result = $generalSalesReportService->exportGeneralSalesReport($company->id, $filterData, 'demo.csv', false);

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it('exportGeneralSalesReport function exports sales data by product to expected format', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $filterData = [
        'location_ids' => [$location->id],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'report_type' => GeneralSalesReportTypes::BY_PRODUCT->value,
        'brand_ids' => [],
        'promoter_ids' => [],
        'e_invoice_submitted' => null,
    ];

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
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

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'test brand',
    ]);

    $promoter->employee = $employee;
    $saleItem->promoters = collect([$promoter]);

    $product->color = $color;
    $product->size = $size;
    $product->brand = $brand;

    $saleItem->sale = $sale;
    $saleItem->product = $product;

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

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getGeneralSalesReportByProduct')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $generalSalesReportService = new GeneralSalesReportService();
    $result = $generalSalesReportService->exportGeneralSalesReport($company->id, $filterData, 'demo.csv', false);

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it('exportGeneralSalesReport function exports sales data by summary to expected format', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $filterData = [
        'location_ids' => [$location->id],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'report_type' => GeneralSalesReportTypes::BY_SUMMARY->value,
        'brand_ids' => [],
        'promoter_ids' => [],
        'e_invoice_submitted' => null,
    ];

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
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

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $promoter->employee = $employee;
    $saleItem->promoters = collect([$promoter]);

    $product->color = $color;
    $product->size = $size;

    $saleItem->sale = $sale;
    $saleItem->product = $product;

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

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getGeneralSalesReportBySummary')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $generalSalesReportService = new GeneralSalesReportService();
    $result = $generalSalesReportService->exportGeneralSalesReport($company->id, $filterData, 'demo.csv', false);

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it('exportGeneralSalesReport function exports sales data by date and brand to expected format', function (): void {
    $filterData = [
        'location_ids' => [],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'brand_ids' => [],
        'promoter_ids' => [],
        'report_type' => GeneralSalesReportTypes::BY_DATE_AND_BRAND->value,
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => $brand->id,
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
    $product->brand = $brand;

    $saleItem->sale_date = now()->format('Y-m-d');
    $saleItem->sale = $sale;
    $saleItem->product = $product;

    $sale->counterUpdate = $counterUpdate;
    $counterUpdate->counter = $counter;

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(CustomReportService::class, function ($mock): void {
        $mock->shouldReceive('prepareDateRange')
            ->once()
            ->andReturn([now(), now()]);
    });
    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getGeneralSalesReportByDateAndBrand')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdsWithNameAndCode')
                ->once()
                ->andReturn(collect([$location]));
    });

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getById')
                ->once()
                ->andReturn($brand);
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $generalSalesReportService = new GeneralSalesReportService();
    $result = $generalSalesReportService->exportGeneralSalesReport($company->id, $filterData, 'demo.csv', false);

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it('exportGeneralSalesReport function exports sales data by promoter to expected format', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'designation_id' => 1,
        'company_id' => $company->id,
        'first_name' => 'test',
        'last_name' => 'test',
    ]);

    $promoters = Promoter::factory(2)->make([
        'id' => 1,
        'employee_id' => $employee->id,
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

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $filterData = [
        'location_ids' => [$location->id],
        'department_ids' => [],
        'date_range' => [now(), now()],
        'filter_by' => null,
        'report_type' => GeneralSalesReportTypes::BY_PROMOTER_SUMMARY->value,
        'brand_ids' => [],
        'promoter_ids' => [],
        'e_invoice_submitted' => null,
    ];

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
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

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'derivative_id' => 1,
    ]);

    $saleItem->promoters = $promoters;

    $saleItem->promoters[0]->employee = $employee;
    $saleItem->promoters[1]->employee = $employee;

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

    $saleItem->sale = $sale;
    $saleItem->product = $product;

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

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getGeneralSalesReportBySummary')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $generalSalesReportService = new GeneralSalesReportService();
    $result = $generalSalesReportService->exportGeneralSalesReport($company->id, $filterData, 'demo.csv', false);

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it(
    'renderPreparedGeneralSalesByCurrentDayVsPreviousDay function returns expected sales data by brand and region wise',
    function (): void {
        $filterData = [
            'location_ids' => [],
            'department_ids' => [],
            'date_range' => [],
            'date' => now()->format('Y-m-d'),
            'filter_by' => null,
            'brand_ids' => [],
            'promoter_ids' => [],
            'report_type' => GeneralSalesReportTypes::BY_CURRENT_DAY_VS_PREVIOUS_DAY->value,
            'e_invoice_submitted' => null,
        ];

        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
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

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
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

        $saleItem->sale = $sale;
        $saleItem->product = $product;

        $sale->counterUpdate = $counterUpdate;
        $counterUpdate->counter = $counter;

        $brandWiseRegionWiseData = [];

        $this->mock(BrandQueries::class, function ($mock) use ($brandWiseRegionWiseData): void {
            $mock->shouldReceive('getSalesRecordsGroupedByBrandAndRegion')
                ->once()
                ->andReturn(collect([$brandWiseRegionWiseData]));
        });

        $generalSalesReportService = new GeneralSalesReportService();

        $result = $generalSalesReportService->renderPreparedGeneralSalesByCurrentDayVsPreviousDay(
            $company,
            $filterData,
            false
        );

        expect($result)->toContain('General Sales');
        expect($result)->toContain('Test Company');
    }
);

it('renderPreparedGeneralSalesBySummaryMonth function returns expected sales data by summary month', function (): void {
    $filterData = [
        'location_ids' => [],
        'department_ids' => [],
        'date_range' => [now()->format('Y-m-d'), now()->format('Y-m-d')],
        'filter_by' => null,
        'brand_ids' => [],
        'promoter_ids' => [],
        'counter_ids' => [],
        'report_type' => GeneralSalesReportTypes::BY_SUMMARY_MONTH->value,
        'e_invoice_submitted' => null,
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
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

    $saleItem->sale = $sale;
    $saleItem->product = $product;

    $sale->counterUpdate = $counterUpdate;
    $counterUpdate->counter = $counter;

    $preparedSale = [
        'location_name' => 'Test Store',
        'location_id' => 1,
        'month' => 1,
        'sales_amount' => 12,
    ];

    $this->mock(SaleItemQueries::class, function ($mock) use ($preparedSale): void {
        $mock->shouldReceive('getGeneralSalesReportBySummaryWithMonthQuery')
            ->once()
            ->andReturn(collect([$preparedSale]));
    });

    $generalSalesReportService = new GeneralSalesReportService();

    $result = $generalSalesReportService->renderPreparedGeneralSalesBySummaryMonth($company, $filterData, false);

    expect($result)->toContain('General Sales Report');
    expect($result)->toContain('Test Company');
    expect($result)->toContain('Test Store');
});

it('exportGeneralSalesReport function exports sales data by summary month to expected format', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'name' => 'Test Store',
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $filterData = [
        'location_ids' => [$location->id],
        'department_ids' => [],
        'date_range' => [now()->format('Y-m-d'), now()->format('Y-m-d')],
        'filter_by' => null,
        'report_type' => GeneralSalesReportTypes::BY_SUMMARY_MONTH->value,
        'brand_ids' => [],
        'promoter_ids' => [],
        'e_invoice_submitted' => null,
    ];

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
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

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $preparedSale = [
        'location_name' => 'Test Store',
        'location_id' => 1,
        'month' => 1,
        'sales_amount' => 12,
    ];

    $promoter->employee = $employee;
    $saleItem->promoters = collect([$promoter]);

    $product->color = $color;
    $product->size = $size;

    $saleItem->sale = $sale;
    $saleItem->product = $product;

    $sale->counterUpdate = $counterUpdate;
    $counterUpdate->counter = $counter;

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(SaleItemQueries::class, function ($mock) use ($preparedSale): void {
        $mock->shouldReceive('getGeneralSalesReportBySummaryWithMonthQuery')
            ->once()
            ->andReturn(collect([$preparedSale]));
    });

    $generalSalesReportService = new GeneralSalesReportService();
    $result = $generalSalesReportService->exportGeneralSalesReport($company->id, $filterData, 'demo.csv', false);

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});
