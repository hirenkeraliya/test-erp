<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\CreditNote\Services\CreditNoteService;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\Member\Jobs\NewMemberBenefitsJob;
use App\Domains\MergeProductTransaction\MergeProductTransactionQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\DataObjects\PaginatedRegularAndCompletedSalesDataForPos;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Mail\SendSaleConfirmationUserMail;
use App\Domains\Sale\Resources\PosSaleListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaveSaleDetailsService;
use App\Domains\Sale\Services\SaveSaleReturnDetailsService;
use App\Domains\SaleReturn\Resources\PosSaleReturnResource;
use App\Http\Controllers\Api\Pos\SaleController;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Member;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

test('It calls the saveDetails method and returns a proper response', function (): void {
    Queue::fake();

    Mail::fake();

    $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];
    $employee = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['employee'];

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'type_id' => Types::REGULAR->value,
        'mobile_number' => '1234567890',
        'email' => 'member@test.com',
        'status' => true,
    ]);

    $company = Company::factory()->make([
        'id' => 1,
        'send_sale_email_to_member' => true,
        'allow_exchange_to_different_store' => true,
        'default_country_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'has_mismatch' => false,
    ]);

    $member->company = $company;

    $sale->member = $member;

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
        'email' => 'test@store.com',
    ]);

    $products = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
    ]);

    $saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => $employee->id,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'items' => [
            [
                'id' => $products->id,
                'price' => $products->retail_price,
                'quantity' => '1',
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '300',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => null,
        'is_layaway' => false,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $saleData = new SaleData(...$saleDetails);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getCompanyDetails')
            ->once()
            ->with(1)
            ->andReturn($company);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($products): void {
        $mock->shouldReceive('getByIdsWithBrandAndCategories')
            ->once()
            ->andReturn(new Collection([$products]));
    });

    $this->mock(BatchQueries::class, function ($mock): void {
        $mock->shouldReceive('getByProductIds')
            ->once()
            ->andReturn(new EloquentCollection([]));
    });

    $this->mock(CheckSaleDetailsService::class, function ($mock) use ($location, $saleData): void {
        $mock->saleData = $saleData;
        $mock->shouldReceive('getCurrentLocation')
            ->once()
            ->andReturn($location);
        $mock->shouldReceive('setDetails')
            ->once();
        $mock->shouldReceive('checkRequestDetails')
            ->once();
    });

    $this->mock(SaveSaleReturnDetailsService::class, function ($mock): void {
        $mock->shouldReceive('saveSaleReturnDetails')
            ->once()
            ->andReturn(new SaleReturn([]));
    });

    $this->mock(CreditNoteService::class, function ($mock): void {
        $mock->shouldReceive('getCreditNotes')
            ->once()
            ->andReturn(new Collection([]));
    });

    $this->mock(SaveSaleDetailsService::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('saveDetails')
            ->once()
            ->andReturn($sale);

        if (config('services.share_sale_details_to_third_party.share_sale_details_to_third_party_enabled')) {
            $mock->shouldReceive('shareSaleDetailsThirdParty')
                ->once();
        }
    });

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(MergeProductTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('getByOldProductId')
            ->once()
            ->andReturn(collect([]));
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $saleController = new SaleController();
    $response = $saleController->saveDetails($saleData, $request);
    Mail::assertQueued(SendSaleConfirmationUserMail::class, 1);

    expect($response['sale'])->toBeInstanceOf(PosSaleListResource::class);
    expect($response['sale_return'])->toBeInstanceOf(PosSaleReturnResource::class);
    expect($response['credit_notes'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test('It calls the getPaginatedRegularAndCompletedSales method and returns regular sales records', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $paginatedRegularAndCompletedSalesData = [
        'page' => 1,
        'member_id' => 1,
        'employee_id' => 1,
        'counter_id' => 1,
        'from_date' => '',
        'to_date' => '',
        'per_page' => 10,
        'sort_by' => '',
        'sort_direction' => '',
        'search_text' => '',
        'after_updated_at' => null,
        'status_id' => null,
    ];

    $paginatedRegularAndCompletedSalesDataForPos = new PaginatedRegularAndCompletedSalesDataForPos(
        ...$paginatedRegularAndCompletedSalesData
    );

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
        $mock->shouldReceive('getPaginatedRegularAndCompletedLayawaySalesWithItemsPaymentsAndMismatches')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $saleController = new SaleController();
    $saleController->getPaginatedRegularAndCompletedSales($request, $paginatedRegularAndCompletedSalesDataForPos);
});

test(
    'getSalesByPromoter method returns the sales record by promoter id',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $request = new Request([
            'employee_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
        });

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getSalesByPromoter')
            ->once()
            ->andReturn(new Collection([]));
        });

        $saleController = new SaleController();
        $saleController->getSalesByPromoter($request, $promoter->id);
    }
);

test(
    'it calls the getSaleDetails method and returns the sale details of given offline sale id',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $request = new Request([
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
        });

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getSaleWithRelations')
            ->once()
            ->andReturn(new Sale([]));
        });

        $saleController = new SaleController();
        $saleController->getSaleDetails($request, '12345');
    }
);

test(
    'The employee will not receive the email regarding sales if the company has configured the send_sale_email_to_member setting to be disabled.',
    function (): void {
        Queue::fake();
        Mail::fake();

        $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'has_mismatch' => false,
        ]);

        $sale->member = $member;

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
            'email' => 'test@store.com',
        ]);

        $products = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => 1,
        ]);

        $saleDetails = [
            'offline_sale_id' => '1',
            'employee_id' => null,
            'return_items' => null,
            'vouchers' => null,
            'cashback_id' => null,
            'cashback_amount' => null,
            'items' => [
                [
                    'id' => $products->id,
                    'price' => $products->retail_price,
                    'quantity' => '1',
                ],
            ],
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => '300',
                ],
            ],
            'sale_notes' => 'Notes goes here',
            'happened_at' => '2022-01-04 04:20:50',
            'member_id' => 1,
            'is_layaway' => false,
            'cart_promotion_id' => null,
            'sale_round_off_amount' => 0.01,
        ];

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $saleData = new SaleData(...$saleDetails);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
        });

        $company = Company::factory()->make([
            'id' => 1,
            'send_sale_email_to_member' => false,
            'allow_exchange_to_different_store' => true,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getCompanyDetails')
                ->once()
                ->with(1)
                ->andReturn($company);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('getByIdsWithBrandAndCategories')
            ->once()
            ->andReturn(new Collection([$products]));
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('getByProductIds')
            ->once()
            ->andReturn(new EloquentCollection([]));
        });

        $this->mock(CheckSaleDetailsService::class, function ($mock) use ($location, $saleData, $member): void {
            $mock->saleData = $saleData;
            $mock->member = $member;
            $mock->shouldReceive('getCurrentLocation')
                ->once()
                ->andReturn($location);
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkRequestDetails')
                ->once();
        });

        $this->mock(SaveSaleReturnDetailsService::class, function ($mock): void {
            $mock->shouldReceive('saveSaleReturnDetails')
                ->once()
                ->andReturn(new SaleReturn([]));
        });

        $this->mock(CreditNoteService::class, function ($mock): void {
            $mock->shouldReceive('getCreditNotes')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(SaveSaleDetailsService::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('saveDetails')
                ->once()
                ->andReturn($sale);

            if (config('services.share_sale_details_to_third_party.share_sale_details_to_third_party_enabled')) {
                $mock->shouldReceive('shareSaleDetailsThirdParty')
                    ->once();
            }
        });

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(MergeProductTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('getByOldProductId')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->once()
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new SaleController();
        $response = $saleController->saveDetails($saleData, $request);
        Mail::assertNotQueued(SendSaleConfirmationUserMail::class, 1);

        expect($response['sale'])->toBeInstanceOf(PosSaleListResource::class);
        expect($response['sale_return'])->toBeInstanceOf(PosSaleReturnResource::class);
        expect($response['credit_notes'])->toBeInstanceOf(AnonymousResourceCollection::class);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
        Queue::assertPushed(NewMemberBenefitsJob::class);
    }
);

test(
    'The employee will not receive the email regarding sales if the user has not specified the email',
    function (): void {
        Queue::fake();
        Mail::fake();

        $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'has_mismatch' => false,
        ]);

        $member = Member::factory()->make([
            'company_id' => 1,
            'email' => '',
            'created_location_id' => 1,
        ]);

        $sale->member = $member;

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
            'email' => 'test@store.com',
        ]);

        $products = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => 1,
        ]);

        $saleDetails = [
            'offline_sale_id' => '1',
            'employee_id' => null,
            'return_items' => null,
            'vouchers' => null,
            'cashback_id' => null,
            'cashback_amount' => null,
            'items' => [
                [
                    'id' => $products->id,
                    'price' => $products->retail_price,
                    'quantity' => '1',
                ],
            ],
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => '300',
                ],
            ],
            'sale_notes' => 'Notes goes here',
            'happened_at' => '2022-01-04 04:20:50',
            'member_id' => 1,
            'is_layaway' => false,
            'cart_promotion_id' => null,
            'sale_round_off_amount' => 0.01,
        ];

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $saleData = new SaleData(...$saleDetails);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $company = Company::factory()->make([
            'id' => 1,
            'send_sale_email_to_member' => true,
            'allow_exchange_to_different_store' => true,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getCompanyDetails')
                ->once()
                ->with(1)
                ->andReturn($company);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($products): void {
            $mock->shouldReceive('getByIdsWithBrandAndCategories')
                ->once()
                ->andReturn(new Collection([$products]));
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('getByProductIds')
                ->once()
                ->andReturn(new EloquentCollection([]));
        });

        $this->mock(CheckSaleDetailsService::class, function ($mock) use ($location, $saleData, $member): void {
            $mock->saleData = $saleData;
            $mock->member = $member;
            $mock->shouldReceive('getCurrentLocation')
                ->once()
                ->andReturn($location);
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('checkRequestDetails')
                ->once();
        });

        $this->mock(SaveSaleReturnDetailsService::class, function ($mock): void {
            $mock->shouldReceive('saveSaleReturnDetails')
                ->once()
                ->andReturn(new SaleReturn([]));
        });

        $this->mock(CreditNoteService::class, function ($mock): void {
            $mock->shouldReceive('getCreditNotes')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->mock(SaveSaleDetailsService::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('saveDetails')
                ->once()
                ->andReturn($sale);

            if (config('services.share_sale_details_to_third_party.share_sale_details_to_third_party_enabled')) {
                $mock->shouldReceive('shareSaleDetailsThirdParty')
                    ->once();
            }
        });

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(MergeProductTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('getByOldProductId')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->once()
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $saleController = new SaleController();
        $response = $saleController->saveDetails($saleData, $request);
        Mail::assertNotQueued(SendSaleConfirmationUserMail::class, 1);

        expect($response['sale'])->toBeInstanceOf(PosSaleListResource::class);
        expect($response['sale_return'])->toBeInstanceOf(PosSaleReturnResource::class);
        expect($response['credit_notes'])->toBeInstanceOf(AnonymousResourceCollection::class);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
        Queue::assertPushed(NewMemberBenefitsJob::class);
    }
);

test('It returns the price override types list', function (): void {
    $saleController = new SaleController();
    $response = $saleController->getPriceOverrideTypes();
    expect($response['price_override_types'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Percentage')
        ->toHaveKey('key', 'PERCENTAGE');
});
