<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\CancelLayawaySale\Services\CancelLayawaySaleService;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Services\CheckCompanySettingService;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\DataObjects\CancelLayawaySaleData;
use App\Domains\Sale\DataObjects\CompleteLayawaySaleData;
use App\Domains\Sale\DataObjects\PendingLayawaySalesDataForPos;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\Resources\PosLayawaySaleListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\GenerateLoyaltyPointsService;
use App\Domains\Sale\Services\LayawayAndCreditSaleCashbackService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\DataObjects\GenerateVoucherData;
use App\Domains\Voucher\Services\LayawayAndCreditSaleGenerateVoucherService;
use App\Http\Controllers\Api\Pos\LayawaySaleController;
use App\Models\BookingPayment;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Country;
use App\Models\CreditNote;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\GiftCard;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->company->companySetting = CompanySetting::factory()->make([
        'company_id' => 1,
    ]);
    $this->country = Country::factory()->make([
        'id' => 1,
    ]);
    $this->currency = Currency::factory()->make([
        'id' => 1,
        'country_id' => $this->country->id,
        'name' => 'Malaysian Ringgit',
        'code' => 'MYR',
    ]);
    $this->currencyRate = CurrencyRate::factory()->make([
        'id' => 1,
        'currency_id' => $this->currency->id,
        'rate' => 1,
    ]);
});

test(
    'It calls the getPendingLayawaySalesWithItemsPaymentsAndMismatches method and returns pending layaway sales records',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $pendingLayawaySalesData = [
            'member_id' => 1,
            'employee_id' => 1,
            'from_date' => '',
            'to_date' => '',
            'search_text' => '',
            'after_updated_at' => null,
        ];

        $pendingLayawaySalesDataForPos = new PendingLayawaySalesDataForPos(...$pendingLayawaySalesData);

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
            $mock->shouldReceive('getPendingLayawaySalesWithItemsPaymentsAndMismatches')
                ->once()
                ->andReturn(new Collection([], 50, 15));
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->getPendingLayawaySales($request, $pendingLayawaySalesDataForPos);
        $this->assertEquals(collect([]), $response['pending_layaway_sales']->resource);
    }
);

test(
    'It calls the getPendingLayawaySale method and returns pending layaway sale by id records',
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
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getPendingLayawaySaleByIdWithItemsPaymentsAndMismatches')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($location);
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->getPendingLayawaySale($request, $sale->id);
        expect($response['sale']->resource->toArray())->toBeArray();
    }
);

test(
    'completeLayawaySale method throws an exception when a sale is not layaway or layaway_pending_amount null',
    function ($pendingAmount, $status): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $cashier->counter_update_id,
            'layaway_pending_amount' => $pendingAmount,
            'status' => $status,
        ]);

        $sale->counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $sale->counterUpdate->counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $location->company = $this->company;

        $sale->counterUpdate->counter->location = $location;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(HttpException::class)->with([
    [10, SaleStatus::REGULAR_SALE->value],
    [null, SaleStatus::COMPLETE_LAYAWAY_SALE->value],
]);

test(
    'completeLayawaySale method calls the respective methods of the SaleQueries class and returns updated layaway sale details',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'cashback_id' => 1,
            'cashback_amount' => 10.10,
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => 10,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

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

        $location->company = $this->company;

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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->counterUpdate = $counterUpdate;

        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
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

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
                ->once();
        });

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once();
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'completeLayawaySale method calls the respective methods of the GenerateLoyaltyPointsService class and sale details',
    function (): void {
        Queue::fake();
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => 10,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
            'loyalty_points' => [
                [
                    'loyalty_campaign_id' => 1,
                    'minimum_spend_amount' => 10,
                    'points' => 10,
                    'expired_at' => now()->format('Y-m-d'),
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

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

        $location->company = $this->company;

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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->counterUpdate = $counterUpdate;

        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once();
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'Effect of Layaway Sale Completion on Loyalty Point Balance when Paying with Loyalty Points',
    function (): void {
        Queue::fake();
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'loyalty_points' => 201,
            'membership_id' => 1,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 4,
                    'amount' => 100.5,
                    'loyalty_points' => 201,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 100.5,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member->membership = Membership::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_points_per_currency_unit' => 2,
            'min_loyalty_points_for_redemption' => 200,
            'max_loyalty_points_for_redemption' => 20000,
        ]);

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $location->company = $this->company;

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
            'layaway_pending_amount' => 100.5,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(LoyaltyPointService::class, function ($mock): void {
            $mock->shouldReceive('decreaseLoyaltyPoints')
                ->once();
        });

        $this->mock(PosMismatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(0);
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once();
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'Cannot Complete Layaway Without a Specified User Membership ID.',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 4,
                    'amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 0,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(HttpException::class, 'To redeem loyalty points, a membership must be associated with your user account.');

test(
    'Completing a Layaway Requires User Information.',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 4,
                    'amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 0,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = null;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(HttpException::class, 'To pay with loyalty points, a user account is required.');

test(
    'Completing a Layaway Requires Adding Loyalty Points to Payment Information.',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 4,
                    'amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = null;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(HttpException::class, 'To pay with loyalty points, a user account is required.');

test(
    'gift card ID required when selecting gift card as a payment',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 5,
                    'amount' => 10,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

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

        $location->company = $this->company;

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
            $mock->shouldNotReceive('updateLayawayAmountOf');
            $mock->shouldNotReceive('loadRelations');
        });

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNew');
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldNotReceive('generateLoyaltyPoints');
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldNotReceive('updateLayawayAmountOf');
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(
    HttpException::class,
    'Please ensure you enter a valid Gift Card ID when choosing Gift Card as the payment method.'
);

test(
    'Status and Type Validation and using the gift card of the expiry date',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 5,
                    'amount' => 10,
                    'gift_card_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

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

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

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
            $mock->shouldReceive('updateLayawayAmountOf')
                ->never();
            $mock->shouldReceive('loadRelations')
                ->never()
                ->andReturn($sale);
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->never();
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->never()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
                ->never();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->never();
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $giftCard = GiftCard::factory()->make([
            'company_id' => 1,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'status' => GiftCardStatuses::USED->value,
            'available_amount' => 1,
            'number' => 123456,
            'expiry_date' => Carbon::yesterday()->format('Y-m-d'),
        ]);

        $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
            $mock->shouldReceive('getById')
                ->times(1)
                ->andReturn($giftCard);
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->never();
        });

        $this->mock(GiftCardTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->never();
        });

        $this->mock(PosMismatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->never();
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);
    }
)->throws(
    HttpException::class,
    'The payment was made using an expired gift card (Number: [123456]). Please use a valid gift card to complete your transaction.'
);

test(
    'It can Calls the respective methods of the GiftCardQueries class.',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 5,
                    'amount' => 10,
                    'gift_card_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->counterUpdate = $counterUpdate;

        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once();
        });

        $giftCard = GiftCard::factory()->make([
            'company_id' => 1,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => Carbon::tomorrow()->format('Y-m-d'),
            'available_amount' => 1000,
        ]);

        $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
            $mock->shouldReceive('getById')
                ->times(2)
                ->andReturn($giftCard);
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(GiftCardTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(PosMismatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(0);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'Booking Payment ID required when selecting Booking Payment as a payment',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 3,
                    'amount' => 10,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldNotReceive('updateLayawayAmountOf');
            $mock->shouldNotReceive('loadRelations');
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNew');
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldNotReceive('generateLoyaltyPoints');
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldNotReceive('updateLayawayAmountOf');
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(HttpException::class, 'Please provide the Booking Payment ID when selecting the Booking Payment option.');

test(
    'Booking Payment Member Match and Status Validation',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 3,
                    'amount' => 10,
                    'booking_payment_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldNotReceive('updateLayawayAmountOf');
            $mock->shouldNotReceive('loadRelations');
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNew');
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldNotReceive('generateLoyaltyPoints');
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldNotReceive('updateLayawayAmountOf');
        });

        $bookingPayment = BookingPayment::factory()->make([
            'counter_update_id' => 1,
            'member_id' => 5,
            'status' => BookingPaymentStatuses::USED->value,
        ]);

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($bookingPayment);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(HttpException::class, 'Sorry, booking payment is currently inactive.');

test(
    'preventing mixing bookings from different companies error message',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 3,
                    'amount' => 10,
                    'booking_payment_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldNotReceive('updateLayawayAmountOf');
            $mock->shouldNotReceive('loadRelations');
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(2);
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNew');
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldNotReceive('generateLoyaltyPoints');
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldNotReceive('updateLayawayAmountOf');
        });

        $bookingPayment = BookingPayment::factory()->make([
            'counter_update_id' => 1,
            'member_id' => 1,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($bookingPayment);
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(HttpException::class, 'Sorry, you can`t mix bookings from different companies.');

test(
    'It can Calls the respective methods of the BookingPaymentQueries class.',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 3,
                    'amount' => 1,
                    'booking_payment_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 1,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(PosMismatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->never();
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn(1);
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
                ->once();
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once();
        });

        $bookingPayment = BookingPayment::factory()->make([
            'counter_update_id' => 1,
            'member_id' => 1,
            'status' => BookingPaymentStatuses::ACTIVE->value,
            'available_amount' => 1,
        ]);

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('getById')
                ->times(2)
                ->andReturn($bookingPayment);
            $mock->shouldReceive('markAsUsed')
                ->once();
        });

        $this->mock(BookingPaymentUseQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        expect($response['sale'])->toBeInstanceOf(PosLayawaySaleListResource::class);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'credit note ID required when selecting credit note as a payment',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 2,
                    'amount' => 10,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldNotReceive('updateLayawayAmountOf');
            $mock->shouldNotReceive('loadRelations')
                ->times(0);
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNew');
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldNotReceive('generateLoyaltyPoints');
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldNotReceive('updateLayawayAmountOf');
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(
    HttpException::class,
    'When using credit notes as a payment method, providing a valid credit note ID is mandatory. Without this information, the process cannot be completed as it serves as a crucial element in processing a credit note-based payment.'
);

test(
    'credit note Member Match',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 2,
                    'amount' => 10,
                    'credit_note_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldNotReceive('updateLayawayAmountOf');
            $mock->shouldNotReceive('loadRelations');
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNew');
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldNotReceive('generateLoyaltyPoints');
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldNotReceive('updateLayawayAmountOf');
        });

        $creditNote = CreditNote::factory()->make([
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 100,
        ]);

        $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($creditNote);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(
    HttpException::class,
    'We apologize, but the credit note you are attempting to use has expired and is no longer valid. Please contact customer support for further assistance.'
);

test(
    'preventing mixing credit note from different companies error message',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 2,
                    'amount' => 10,
                    'credit_note_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldNotReceive('updateLayawayAmountOf');
            $mock->shouldNotReceive('loadRelations');
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNew');
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldNotReceive('generateLoyaltyPoints');
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldNotReceive('updateLayawayAmountOf');
        });

        $creditNote = CreditNote::factory()->make([
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
        ]);

        $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($creditNote);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->never()
                ->andReturn(2);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(
    HttpException::class,
    'We apologize, but the credit note you are attempting to use has expired and is no longer valid. Please contact customer support for further assistance.'
);

test(
    'It can Calls the respective methods of the CreditNoteQueries class.',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 2,
                    'amount' => 10,
                    'credit_note_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn(1);
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once();
        });

        $creditNote = CreditNote::factory()->make([
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'expiry_date' => Carbon::tomorrow(),
            'available_amount' => 5000,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
            $mock->shouldReceive('getById')
                ->times(2)
                ->andReturn($creditNote);
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(CreditNoteUseQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        expect($response['sale'])->toBeInstanceOf(PosLayawaySaleListResource::class);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'It can Calls the respective methods of the inventory update class.',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 2,
                    'amount' => 10,
                    'credit_note_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $saleResponse = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        $saleResponse->user = $member;

        $saleResponse->counterUpdate = $counterUpdate;
        $saleItem = SaleItem::factory()->make([
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'quantity' => 5,
            'returned_quantity' => 1,
        ]);

        $saleItem->product = Product::factory()->make([
            'id' => 1,
            'name' => 'Product 1',
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'has_batch' => false,
            'type_id' => 1,
            'is_non_inventory' => false,
        ]);

        $saleResponse->saleItems = collect([$saleItem]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale, $saleResponse): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $saleResponse->status = SaleStatus::COMPLETE_LAYAWAY_SALE->value;
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once()
                ->andReturn($saleResponse);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn(1);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
                ->once();
        });

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once();
        });

        $inventory = Inventory::factory()->make([
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $this->mock(SaleReservedStockService::class, function ($mock): void {
            $mock->shouldReceive('removeReservationStock')
                ->once();
        });

        $creditNote = CreditNote::factory()->make([
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'expiry_date' => Carbon::tomorrow(),
            'available_amount' => 5000,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
            $mock->shouldReceive('getById')
                ->times(2)
                ->andReturn($creditNote);
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(CreditNoteUseQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        expect($response['sale'])->toBeInstanceOf(PosLayawaySaleListResource::class);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'cancelLayawaySale method calls the respective methods of the CancelLayawaySaleService class and sale details',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $data = [
            'store_manager_id' => 1,
            'passcode' => '123',
            'happened_at' => '2022-01-04 04:20:50',
            'reason' => 'Test',
        ];

        $cancelLayawaySaleData = new CancelLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->never();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->never();
        });

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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
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
            $mock->shouldReceive('getPendingLayawaySaleByIdWithRelations')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('markAsCancelLayaway')
                ->once();
            $mock->shouldReceive('loadCancelLayawaySaleRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($location);
        });

        $this->mock(CancelLayawaySaleService::class, function ($mock): void {
            $mock->shouldReceive('checkRequestDetails')
                ->once();
            $mock->shouldReceive('saveDetails')
                ->once();
        });

        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
                ->once();
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->cancelLayawaySale($cancelLayawaySaleData, $request, 1);
        $this->assertEquals($sale, $response['sale']->resource);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'It can Calls the respective methods of the LayawaySaleGenerateVoucherService class.',
    function (): void {
        Queue::fake();

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->andReturn($this->company);
        });

        $this->company->countries = collect([$this->country]);
        foreach ($this->company->countries as $country) {
            $country->currency = $this->currency;
            $country->currency->currencyRate = $this->currencyRate;
        }

        LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 1,
            'status' => true,
        ]);

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
            'payments' => [
                [
                    'type_id' => 2,
                    'amount' => 10,
                    'credit_note_id' => 1,
                    'currency_id' => 1,
                    'current_currency_rate' => 1,
                    'currency_amount' => 10,
                ],
            ],
            'vouchers' => $generateVoucherData,
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $this->company;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->once();
        });

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
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
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->member = $member;

        $sale->counterUpdate = $counterUpdate;

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn(1);
        });

        $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('hasGenerateLoyaltyPointsForLayawaySale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('generateLoyaltyPointsForLayawaySale')
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

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLayawayAmountOf')
                ->once();
        });

        $creditNote = CreditNote::factory()->make([
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'expiry_date' => Carbon::tomorrow(),
            'available_amount' => 5000,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
            $mock->shouldReceive('getById')
                ->times(2)
                ->andReturn($creditNote);
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(CreditNoteUseQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new LayawaySaleController();
        $response = $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
        expect($response['sale'])->toBeInstanceOf(PosLayawaySaleListResource::class);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'completeLayawaySale method throws an exception when a sale complete in deferent location',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForCounterCloseApi')
                ->once()
                ->andReturn($cashier);
        });

        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $request = new Request();

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $cashier->counter_update_id,
            'layaway_pending_amount' => 10,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $sale->counterUpdate->counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 2,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $location->company = $this->company;

        $sale->counterUpdate->counter->location = $location;

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->never();
            $mock->shouldReceive('checkCompleteLayawaySaleSettings')
                ->never();
        });

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByIdWithSaleItems')
                ->once()
                ->andReturn($sale);
        });

        $saleController = new LayawaySaleController();
        $saleController->completeLayawaySale($completeLayawaySaleData, $request, 1);
    }
)->throws(HttpException::class, 'Layaway sale cannot be completed at a different location.');

test('it calls the checkPaymentCurrency method currency id is not available in company', function (): void {
    $companyId = 1;
    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once()
            ->andReturn($this->company);
    });
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $payments = [
        'type_id' => 1,
        'amount' => 10,
        'currency_id' => 2,
        'current_currency_rate' => 1,
        'currency_amount' => 10,
    ];
    $layawaySaleController = new LayawaySaleController();

    $mismatches = collect([]);
    $layawaySaleController->checkPaymentCurrency(collect([$payments]), $mismatches, $companyId);
})->throws(HttpException::class, 'Payment currency id 2 is not available in this company.');

test('it calls the checkPaymentCurrency method currency rate is not available in company', function (): void {
    $companyId = 1;
    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once()
            ->andReturn($this->company);
    });
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $payments = [
        'type_id' => 1,
        'amount' => 10,
        'currency_id' => 1,
        'current_currency_rate' => 2,
        'currency_amount' => 10,
    ];
    $layawaySaleController = new LayawaySaleController();
    $mismatches = collect([]);
    $layawaySaleController->checkPaymentCurrency(collect([$payments]), $mismatches, $companyId);
})->throws(
    HttpException::class,
    'Payment currency rate 2 does not match with the actual currency rate of 1 for the currency id 1'
);

test('it calls the checkPaymentCurrency method currency amount is not matching', function (): void {
    $companyId = 1;
    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once()
            ->andReturn($this->company);
    });
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $payments = [
        'type_id' => 1,
        'amount' => 10,
        'currency_id' => 1,
        'current_currency_rate' => 2,
        'currency_amount' => 10,
    ];
    $layawaySaleController = new LayawaySaleController();
    $mismatches = collect([]);
    $layawaySaleController->checkPaymentCurrency(collect([$payments]), $mismatches, $companyId);
})->throws(
    HttpException::class,
    'Payment currency rate 2 does not match with the actual currency rate of 1 for the currency id 1'
);
