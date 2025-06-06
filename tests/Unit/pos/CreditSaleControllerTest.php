<?php

declare(strict_types=1);

use App\Domains\CancelCreditSale\Services\CancelCreditSaleService;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\Services\CheckCompanySettingService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\Sale\DataObjects\CancelCreditSaleData;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\DataObjects\PendingCreditSalesDataForPos;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CompleteCreditSaleService;
use App\Domains\Sale\Services\GenerateLoyaltyPointsService;
use App\Domains\Sale\Services\LayawayAndCreditSaleCashbackService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\DataObjects\GenerateVoucherData;
use App\Domains\Voucher\Services\LayawayAndCreditSaleGenerateVoucherService;
use App\Http\Controllers\Api\Pos\CreditSaleController;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelData\DataCollection;

test(
    'getPendingCreditSales calls the getPendingCreditSalesWithRelations method and returns pending layaway sales records',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $pendingCreditSalesData = [
            'member_id' => 1,
            'employee_id' => 1,
            'from_date' => '',
            'to_date' => '',
            'search_text' => '',
            'after_updated_at' => null,
        ];
        $pendingCreditSalesDataForPos = new PendingCreditSalesDataForPos(...$pendingCreditSalesData);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->with($cashier->counter_update_id)
                ->andReturn($location);
        });

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getPendingCreditSalesWithRelations')
                ->once()
                ->andReturn(new Collection([], 50, 15));
        });

        $creditSaleController = new CreditSaleController();
        $response = $creditSaleController->getPendingCreditSales($request, $pendingCreditSalesDataForPos);
        $this->assertEquals(collect([]), $response['pending_credit_sales']->resource);
    }
);

test(
    'It calls the getPendingCreditSale method and returns pending layaway sale by id records',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $request = new Request([
            'employee_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $cashier->counter_update_id,
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getPendingCreditSaleByIdWithRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($location);
        });

        $creditSaleController = new CreditSaleController();
        $response = $creditSaleController->getPendingCreditSale($request, $sale->id);
        expect($response['sale']->resource->toArray())->toBeArray();
    }
);

test(
    'completeLayawaySale method calls the respective methods of the SaleQueries class and returns updated sale details',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $vouchersArray = [
            [
                'voucher_configuration_id' => 1,
                'discount_type' => 1,
                'number' => '123',
                'minimum_spend_amount' => 1.1,
            ],
        ];

        $generateVoucherData = new DataCollection(GenerateVoucherData::class, $vouchersArray);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'cashback_id' => 1,
            'cashback_amount' => 10.10,
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => 10,
                ],
            ],
            'vouchers' => $generateVoucherData,
        ];

        $completeCreditSaleData = new CompleteCreditSaleData(...$data);

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $company->companySetting = new CompanySetting();

        $location->company = $company;

        $counter->location = $location;

        $counterUpdate = CounterUpdate::factory()->make([
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $counterUpdate->counter = $counter;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateCreditAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForCreditSale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForCreditSale')
                ->once();
        });

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteCreditSaleSettings')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateCreditAmountOf')
                ->once();
        });

        $this->mock(LayawayAndCreditSaleCashbackService::class, function ($mock): void {
            $mock->shouldReceive('hasCashback')
                ->times(2)
                ->andReturn(true);
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkForApplicability')
                ->once();
            $mock->shouldReceive('saveCashback')
                ->once();
        });

        $this->mock(CompleteCreditSaleService::class, function ($mock): void {
            $mock->shouldReceive('checkRequestDetails')
                ->once();
            $mock->shouldReceive('saveDetails')
                ->once();
        });

        $this->mock(LayawayAndCreditSaleGenerateVoucherService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkVouchers')
                ->once();
            $mock->shouldReceive('saveVouchers')
                ->once();
        });

        $creditSaleController = new CreditSaleController();
        $response = $creditSaleController->completeCreditSale($completeCreditSaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'completeLayawaySale method check EmployeeUpdatePointsAndTotalSalesJob dispatch or not',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => 10,
                ],
            ],
        ];

        $completeCreditSaleData = new CompleteCreditSaleData(...$data);

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $company->companySetting = new CompanySetting();

        $location->company = $company;

        $counter->location = $location;

        $counterUpdate = CounterUpdate::factory()->make([
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $counterUpdate->counter = $counter;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateCreditAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForCreditSale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForCreditSale')
                ->once();
        });

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteCreditSaleSettings')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateCreditAmountOf')
                ->once();
        });

        $this->mock(CompleteCreditSaleService::class, function ($mock): void {
            $mock->shouldReceive('checkRequestDetails')
                ->once();
            $mock->shouldReceive('saveDetails')
                ->once();
        });

        $creditSaleController = new CreditSaleController();
        $response = $creditSaleController->completeCreditSale($completeCreditSaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);
    }
);

test(
    'getTotalCreditPendingAmount method and returns total credit pending amount',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];
        $companyId = 1;

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $counter->location = $location;

        $counterUpdate = CounterUpdate::factory()->make([
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);
        $counterUpdate->counter = $counter;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'credit_pending_amount' => 10,
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        ]);

        $sale->counterUpdate = $counterUpdate;

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->with($cashier->counter_update_id)
                ->andReturn($location);
        });

        $this->mock(SaleQueries::class, function ($mock) use ($sale, $companyId, $location): void {
            $mock->shouldReceive('totalCreditSalePendingAmount')
                ->once()
                ->with($companyId, $location->id)
                ->andReturn($sale->credit_pending_amount);
        });

        $creditSaleController = new CreditSaleController();
        $response = $creditSaleController->getTotalCreditPendingAmount($request);
        $this->assertArrayHasKey('total_credit_sale_pending_amount', $response);
    }
);

test(
    'cancelCreditSale method calls the respective methods of the CancelCreditSaleService class and sale details',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $data = [
            'store_manager_id' => 1,
            'passcode' => '123',
            'happened_at' => '2022-01-04 04:20:50',
            'reason' => 'Test',
        ];

        $cancelCreditSaleData = new CancelCreditSaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $counter->location = $location;

        $counterUpdate = CounterUpdate::factory()->make([
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $counterUpdate->counter = $counter;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'credit_pending_amount' => 10,
            'status' => SaleStatus::CANCEL_CREDIT_SALE->value,
        ]);

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getPendingCreditSaleByIdAndRelations')
                ->once()
                ->with($sale->id)
                ->andReturn($sale);
            $mock->shouldReceive('markAsCancelCredit')
                ->once();
            $mock->shouldReceive('loadCancelCreditSaleRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($location);
        });

        $this->mock(CancelCreditSaleService::class, function ($mock): void {
            $mock->shouldReceive('checkRequestDetails')
                ->once();
            $mock->shouldReceive('saveDetails')
                ->once();
        });

        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
                ->once();
        });

        $creditSaleController = new CreditSaleController();
        $response = $creditSaleController->cancelCreditSale($cancelCreditSaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);
