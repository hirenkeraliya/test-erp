<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Region\Jobs\DailyTotalSalesMailToRegionJob;
use App\Domains\Region\Mail\SendDailySalesSummaryToRegionManagerMail;
use App\Domains\Region\RegionQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Models\Brand;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Region;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Mail;

test(
    'DailyTotalSalesMailToRegionJob job calls respective methods and send mail send successfully',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $region = Region::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'manager_name' => 'test',
            'manager_email' => 'test@gmail.com',
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Test Store',
            'type_id' => LocationTypes::STORE->value,
            'region_id' => $region->id,
        ]);

        $brand = Brand::factory()->make([
            'id' => 1,
        ]);

        $location->brands = $brand;
        $location->region = $region;

        $fromDate = now()->subDay()->startOfDay()->format('Y-m-d H:i:s');
        $toDate = now()->subDay()->endOfDay()->format('Y-m-d H:i:s');

        $counter = Counter::factory()->make([
            'location_id' => $location->id,
        ]);

        $employee = Employee::factory()->make([
            'company_id' => $company->id,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
        ]);

        $cashier->employee = $employee;

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => $cashier->id,
            'closed_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->make([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'layaway_pending_amount' => null,
            'happened_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'member_id' => 1,
        ]);

        $product = Product::factory()->make([
            'company_id' => $company->id,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);

        SaleItem::factory()->make([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'derivative_id' => 1,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($region, $fromDate, $toDate, $sale): void {
            $mock->shouldReceive('getSalesByRegionId')
                ->once()
                ->with($region->id, $fromDate, $toDate)
                ->andReturn(collect([$sale]));
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
        });

        $this->mock(RegionQueries::class, function ($mock) use ($region): void {
            $mock->shouldReceive('getRegionByIdWithStoresAndBrands')
                ->once()
                ->andReturn($region);
        });

        Mail::fake();

        DailyTotalSalesMailToRegionJob::dispatch($region->id, $fromDate, $toDate)->onQueue(
            config('horizon.default_queue_name')
        );

        Mail::assertSent(
            SendDailySalesSummaryToRegionManagerMail::class,
            fn ($mail): bool => $mail->hasTo($region->manager_email)
        );
    }
);
