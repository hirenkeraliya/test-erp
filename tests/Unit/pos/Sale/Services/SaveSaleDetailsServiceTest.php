<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\SaleInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\MemberQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\MembershipAssignment\MembershipAssignmentQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\GenerateLoyaltyPointsService;
use App\Domains\Sale\Services\SaleCashbackService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Domains\Sale\Services\SaleItemExchangeService;
use App\Domains\Sale\Services\SaleTaxService;
use App\Domains\Sale\Services\SaveSaleDetailsService;
use App\Domains\Sale\Services\SaveSaleReturnDetailsService;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemAssemblyChildProduct\SaleItemAssemblyChildProductQueries;
use App\Domains\SaleItemComplimentary\SaleItemComplimentaryQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleItemPriceOverride\SaleItemPriceOverrideQueries;
use App\Domains\SaleLoyaltyPoint\SaleLoyaltyPointQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SalePriceOverride\SalePriceOverrideQueries;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\Services\GenerateVoucherService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\AssemblyChildProduct;
use App\Models\BookingPayment;
use App\Models\BoxProduct;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\GiftCard;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemPriceOverride;
use App\Models\SaleLoyaltyPoint;
use App\Models\SalePayment;
use App\Models\SalePriceOverride;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Sequence;
use App\Models\Voucher;

beforeEach(function (): void {
    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => 1,
        'cashback_amount' => 12,
        'cashback_round_off_amount' => 0,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promotion_id' => 1,
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'is_layaway' => false,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->product = Product::factory()->make([
        'id' => 1,
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
    ]);

    $this->cartItems = collect($this->saleData->items);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->companyId = 1;

    $this->cashier = makeCashierForPosWithCounterUpdateId();

    $this->saleMismatches = collect([]);
    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->saveSaleDetailsService = new SaveSaleDetailsService();
});

test('saveSaleDetails method returns null when cart items is not available', function (): void {
    $this->checkSaleDetailsService->cartItems = collect([]);
    $response = $this->saveSaleDetailsService->saveDetails(
        $this->cashier,
        $this->checkSaleDetailsService,
        1,
        new SaleReturn(),
    );

    $this->assertNull($response);
});

test('saveSaleDetails method calls the same class methods as expected', function (): void {
    $product = Product::factory()->make([
        'id' => 1,
        'name' => 'test',
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'is_non_inventory' => false,
    ]);

    $this->checkSaleDetailsService->cartItems = $this->cartItems;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->products = collect([$product]);
    $this->checkSaleDetailsService->location = $this->location;
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->companyId = 1;

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => $product->id,
        'derivative_id' => null,
    ]);

    $sale->saleItems = collect([$saleItem]);

    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => null,
    ]);
    $this->checkSaleDetailsService->saleDiscountService = $this->mock(
        SaleDiscountService::class,
        function ($mock): void {
            $mock->shouldReceive('getCartDiscountAmountFor')
                ->once()
                ->andReturn([
                    'total_discount' => 10.00,
                    'cart_wide_discount' => 10.00,
                    'voucher_discount' => 10.00,
                    'price_override_discount' => 10.00,
                    'cart_wide_loyalty_point_discount' => 10.00,
                ]);
            $mock->shouldReceive('getItemCartDiscountAmount')
                ->once();
            $mock->shouldReceive('getItemDiscountAmountFor')
                ->once()
                ->andReturn([
                    'total_discount' => 0.00,
                ]);
            $mock->shouldReceive('getTotalItemDiscountAmount')
                ->once();
        }
    );

    $this->checkSaleDetailsService->generateVoucherService = $this->mock(
        GenerateVoucherService::class,
        function ($mock): void {
            $mock->shouldReceive('saveVouchers')
                ->once();
        }
    );

    $this->checkSaleDetailsService->saleCashbackService = $this->mock(
        SaleCashbackService::class,
        function ($mock): void {
            $mock->shouldReceive('saveCashback')
                ->once();
        }
    );

    $this->checkSaleDetailsService->saleTaxService = $this->mock(SaleTaxService::class, function ($mock): void {
        $mock->shouldReceive('getItemTaxAmountFor')
            ->once();
        $mock->shouldReceive('getTotalTaxAmountFor')
            ->once();
    });

    $this->checkSaleDetailsService->generateLoyaltyPointsService = $this->mock(
        GenerateLoyaltyPointsService::class,
        function ($mock): void {
            $mock->shouldReceive('saveGenerateLoyaltyPoints')
                ->once();
        }
    );

    $this->checkSaleDetailsService->bookingPayments = collect([]);

    $this->checkSaleDetailsService->creditNotes = collect([]);

    $mock = $this->createPartialMock(
        SaveSaleDetailsService::class,
        [
            'updateInventory',
            'savePayments',
            'saveSaleMismatches',
            'saveItemDiscounts',
            'saveCartWideDiscount',
            'updateLayawayDetails',
            'updateSpentTillNow',
            'updateMembership',
            'saveItemPriceOverride',
            'saveCartPriceOverride',
            'saveCartWideLoyaltyPointsDiscount',
        ]
    );

    $mock->expects($this->once())
        ->method('updateInventory');

    $mock->expects($this->once())
        ->method('savePayments');

    $mock->expects($this->once())
        ->method('saveSaleMismatches');

    $mock->expects($this->once())
        ->method('saveItemDiscounts');

    $mock->expects($this->once())
        ->method('saveCartWideDiscount');

    $mock->expects($this->once())
        ->method('updateLayawayDetails');

    $mock->expects($this->once())
        ->method('saveItemPriceOverride');

    $mock->expects($this->once())
        ->method('saveCartPriceOverride');

    $mock->expects($this->once())
        ->method('saveCartWideLoyaltyPointsDiscount');

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($sale);
        $mock->shouldReceive('updateTotals')
            ->once();
        $mock->shouldReceive('loadRelations')
            ->times(4)
            ->andReturn($sale);
    });

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($saleItem);
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('updateLastPurchaseDate')
            ->once();
        $mock->shouldReceive('updateSalesQuantity')
            ->once();
    });

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
        'company_id' => 1,
    ]);
    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'company_id' => 1,
    ]);
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'country_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $sequence = Sequence::factory()->make([
        'number' => 0o000001,
        'location_id' => 1,
    ]);
    $counterUpdate->counter = $counter;
    $counterUpdate->counter->location = $location;

    $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($sequence);
    });

    $mock->saveDetails($this->cashier, $this->checkSaleDetailsService, 1, new SaleReturn());
});

test(
    'saveSaleDetails method calls the same class methods as expected but when product is non inventory it will not call the updateInventory method',
    function (): void {
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
            'is_non_inventory' => true,
        ]);

        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->products = collect([$product]);
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->companyId = 1;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => $product->id,
            'derivative_id' => null,
        ]);

        $sale->saleItems = collect([$saleItem]);

        Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => null,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('getCartDiscountAmountFor')
                    ->once()
                    ->andReturn([
                        'total_discount' => 10.00,
                        'cart_wide_discount' => 10.00,
                        'voucher_discount' => 10.00,
                        'price_override_discount' => 10.00,
                        'cart_wide_loyalty_point_discount' => 10.00,
                    ]);
                $mock->shouldReceive('getItemCartDiscountAmount')
                    ->once();
                $mock->shouldReceive('getItemDiscountAmountFor')
                    ->once()
                    ->andReturn([
                        'total_discount' => 0.00,
                    ]);
                $mock->shouldReceive('getTotalItemDiscountAmount')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->generateVoucherService = $this->mock(
            GenerateVoucherService::class,
            function ($mock): void {
                $mock->shouldReceive('saveVouchers')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->saleCashbackService = $this->mock(
            SaleCashbackService::class,
            function ($mock): void {
                $mock->shouldReceive('saveCashback')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->saleTaxService = $this->mock(
            SaleTaxService::class,
            function ($mock): void {
                $mock->shouldReceive('getItemTaxAmountFor')
                    ->once();
                $mock->shouldReceive('getTotalTaxAmountFor')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->bookingPayments = collect([]);

        $this->checkSaleDetailsService->creditNotes = collect([]);

        $mock = $this->createPartialMock(
            SaveSaleDetailsService::class,
            [
                'savePayments',
                'saveSaleMismatches',
                'saveItemDiscounts',
                'saveCartWideDiscount',
                'updateLayawayDetails',
                'updateSpentTillNow',
                'updateMembership',
                'saveItemPriceOverride',
                'saveCartPriceOverride',
                'saveCartWideLoyaltyPointsDiscount',
            ]
        );

        $mock->expects($this->once())
            ->method('savePayments');

        $mock->expects($this->once())
            ->method('saveSaleMismatches');

        $mock->expects($this->once())
            ->method('saveItemDiscounts');

        $mock->expects($this->once())
            ->method('saveCartWideDiscount');

        $mock->expects($this->once())
            ->method('updateLayawayDetails');

        $mock->expects($this->once())
            ->method('saveItemPriceOverride');

        $mock->expects($this->once())
            ->method('saveCartPriceOverride');

        $mock->expects($this->once())
            ->method('saveCartWideLoyaltyPointsDiscount');

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateTotals')
                ->once();
            $mock->shouldReceive('loadRelations')
                ->times(4)
                ->andReturn($sale);
        });

        $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($saleItem);
        });

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLastPurchaseDate')
                ->once();
            $mock->shouldReceive('updateSalesQuantity')
            ->once();
        });

        $this->checkSaleDetailsService->generateLoyaltyPointsService = $this->mock(
            GenerateLoyaltyPointsService::class,
            function ($mock): void {
                $mock->shouldReceive('saveGenerateLoyaltyPoints')
                    ->once();
            }
        );

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
            'company_id' => 1,
        ]);
        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
            'company_id' => 1,
        ]);
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $sequence = Sequence::factory()->make([
            'number' => 0o000001,
            'location_id' => 1,
        ]);
        $counterUpdate->counter = $counter;
        $counterUpdate->counter->location = $location;

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });

        $mock->saveDetails($this->cashier, $this->checkSaleDetailsService, 1, new SaleReturn());
    }
);

test(
    'saveSaleDetails method calls the same class methods as expected but when offline generate loyalty point',
    function (): void {
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
            'is_non_inventory' => true,
        ]);

        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->saleDetails['loyalty_points'] = [
            [
                'loyalty_campaign_id' => 1,
                'minimum_spend_amount' => 10,
                'points' => 10,
                'expired_at' => now()->addDays(2)->format('Y-m-d'),
            ],
        ];
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->products = collect([$product]);
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->companyId = 1;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => $product->id,
            'derivative_id' => null,
        ]);

        $sale->saleItems = collect([$saleItem]);

        Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => null,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('getCartDiscountAmountFor')
                    ->once()
                    ->andReturn([
                        'total_discount' => 10.00,
                        'cart_wide_discount' => 10.00,
                        'voucher_discount' => 10.00,
                        'price_override_discount' => 10.00,
                        'cart_wide_loyalty_point_discount' => 10.00,
                    ]);
                $mock->shouldReceive('getItemCartDiscountAmount')
                    ->once();
                $mock->shouldReceive('getItemDiscountAmountFor')
                    ->once()
                    ->andReturn([
                        'total_discount' => 0.00,
                    ]);
                $mock->shouldReceive('getTotalItemDiscountAmount')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->generateVoucherService = $this->mock(
            GenerateVoucherService::class,
            function ($mock): void {
                $mock->shouldReceive('saveVouchers')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->saleCashbackService = $this->mock(
            SaleCashbackService::class,
            function ($mock): void {
                $mock->shouldReceive('saveCashback')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->saleTaxService = $this->mock(
            SaleTaxService::class,
            function ($mock): void {
                $mock->shouldReceive('getItemTaxAmountFor')
                    ->once();
                $mock->shouldReceive('getTotalTaxAmountFor')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->bookingPayments = collect([]);

        $this->checkSaleDetailsService->creditNotes = collect([]);

        $mock = $this->createPartialMock(
            SaveSaleDetailsService::class,
            [
                'savePayments',
                'saveSaleMismatches',
                'saveItemDiscounts',
                'saveCartWideDiscount',
                'updateLayawayDetails',
                'updateSpentTillNow',
                'updateMembership',
                'saveItemPriceOverride',
                'saveCartPriceOverride',
                'saveCartWideLoyaltyPointsDiscount',
            ]
        );

        $mock->expects($this->once())
            ->method('savePayments');

        $mock->expects($this->once())
            ->method('saveSaleMismatches');

        $mock->expects($this->once())
            ->method('saveItemDiscounts');

        $mock->expects($this->once())
            ->method('saveCartWideDiscount');

        $mock->expects($this->once())
            ->method('updateLayawayDetails');

        $mock->expects($this->once())
            ->method('saveItemPriceOverride');

        $mock->expects($this->once())
            ->method('saveCartPriceOverride');

        $mock->expects($this->once())
            ->method('saveCartWideLoyaltyPointsDiscount');

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateTotals')
                ->once();
            $mock->shouldReceive('loadRelations')
                ->times(4)
                ->andReturn($sale);
        });

        $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($saleItem);
        });

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLastPurchaseDate')
                ->once();
            $mock->shouldReceive('updateSalesQuantity')
                ->once();
        });

        $this->checkSaleDetailsService->generateLoyaltyPointsService = $this->mock(
            GenerateLoyaltyPointsService::class,
            function ($mock): void {
                $mock->shouldReceive('saveGenerateLoyaltyPoints')
                    ->once();
            }
        );
        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
            'company_id' => 1,
        ]);
        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
            'company_id' => 1,
        ]);
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $sequence = Sequence::factory()->make([
            'number' => 0o000001,
            'location_id' => 1,
        ]);
        $counterUpdate->counter = $counter;
        $counterUpdate->counter->location = $location;

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });
        $mock->saveDetails($this->cashier, $this->checkSaleDetailsService, 1, new SaleReturn());
    }
);

test('updateInventory method calls the same class methods as expected', function (): void {
    $this->checkSaleDetailsService->products = collect([$this->product]);
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->location = $this->location;

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('fetchOrCreate')
            ->once()
            ->andReturn(new Inventory([
                'stock' => 50,
            ]));
        $mock->shouldReceive('decreaseStock')
            ->once();
    });

    $this->mock(SaleInventoryService::class, function ($mock): void {
        $mock->shouldReceive('updateInventoryUnits')
            ->once();
    });

    $this->saveSaleDetailsService->updateInventory(
        new SaleItem(),
        $this->saleDetails['items'][0],
        $this->cashier,
        $this->checkSaleDetailsService
    );
});

test('it calls addNew method of SalePaymentQueries class', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->mock(SalePaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $mock = $this->createPartialMock(SaveSaleDetailsService::class, ['useLoyaltyPoints']);

    $mock->expects($this->once())
        ->method('useLoyaltyPoints');

    $this->checkSaleDetailsService->saleData = $this->saleData;

    $mock->savePayments($this->checkSaleDetailsService, new Sale(), null, 1);
});

test(
    'it calls respective methods of bookingPaymentUseQueries class and mark booking payment as used.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $salePayment = SalePayment::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'counter_update_id' => null,
            'payment_type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
            'amount' => 10,
        ]);

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->bookingPayments = collect([
            '0' => $bookingPayment,
        ]);

        $this->saleData->payments = [
            [
                'type_id' => 1,
                'amount' => '100',
                'booking_payment_id' => 1,
            ],
        ];

        $this->mock(SalePaymentQueries::class, function ($mock) use ($salePayment): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($salePayment->id);
        });

        $this->mock(BookingPaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsUsed')
                ->once();
        });

        $this->mock(BookingPaymentUseQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->saveSaleDetailsService->savePayments($this->checkSaleDetailsService, new Sale(), null, 1);
    }
);

test(
    'it calls respective methods of creditNoteUseQueries class and mark credit note as used.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $salePayment = SalePayment::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'counter_update_id' => null,
            'payment_type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'amount' => 10,
        ]);

        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->creditNotes = collect([
            '0' => $creditNote,
        ]);

        $this->saleData->payments = [
            [
                'type_id' => 1,
                'amount' => '100',
                'credit_note_id' => 1,
            ],
        ];

        $this->mock(SalePaymentQueries::class, function ($mock) use ($salePayment): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($salePayment->id);
        });

        $this->mock(CreditNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(CreditNoteUseQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->saveSaleDetailsService->savePayments($this->checkSaleDetailsService, new Sale(), null, 1);
    }
);

test('it calls the updateLayawayDetails method of SaleQueries class', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->saleData->is_layaway = true;

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('updateLayawayPendingAmountAndStatus')
            ->once();
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale->saleItems = collect([]);

    $this->saveSaleDetailsService->updateLayawayDetails($sale, $this->checkSaleDetailsService, null);
});

test('it calls addNew method of PosMismatchQueries class', function (): void {
    $this->checkSaleDetailsService->saleMismatches = collect(['Test', 'test 1']);
    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->twice();
    });

    $this->saveSaleDetailsService->saveSaleMismatches(new Sale(), $this->checkSaleDetailsService);
});

test('it calls updateTotalPricePaid method of SaleItemQueries class', function (): void {
    $saleItem = SaleItem::factory()->make([
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => null,
        'total_price_paid' => 50.00,
    ]);
    $this->mock(SaleItemQueries::class, function ($mock): void {
        $mock->shouldReceive('updateTotalPricePaid')
            ->once();
    });

    $this->saveSaleDetailsService->updateLayawayPaymentsToTheSaleItems(collect([$saleItem]), 100.00, false);
});

test('It calls addNew method of SaleItemDiscountQueries', function (): void {
    $this->mock(SaleItemDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(4);
    });

    $cartItem = $this->saleDetails['items'][0];
    $cartItem['dream_price_id'] = 1;
    $cartItem['dream_price_amount'] = 10.10;
    $cartItem['complimentary_item_reason_id'] = 1;
    $cartItem['complimentary_item_discount'] = 55;
    $cartItem['amount'] = 123.00;
    $cartItem['happy_hours_offline_id'] = '12345';
    $cartItem['happy_hours_discount_amount'] = 10.10;

    $happyHourDiscount = HappyHourDiscount::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'location_id' => 1,
        'company_id' => 1,
        'authorizer_id' => 1,
    ]);

    $happyHourDiscount->happyHourDiscountTransaction = HappyHourDiscountTransaction::factory()->make([
        'happy_hour_discount_id' => 1,
        'counter_update_id' => 1,
        'authorizer_id' => 1,
        'offline_id' => '12345',
    ]);

    $saleDiscountService = new SaleDiscountService();
    $saleDiscountService->happyHourDiscounts = collect([$happyHourDiscount]);
    $this->checkSaleDetailsService->saleDiscountService = $saleDiscountService;

    $this->saveSaleDetailsService->saveItemDiscounts(
        $this->checkSaleDetailsService,
        $cartItem,
        10,
        [
            'total_discount' => 20.20,
            'happy_hour_discount' => 10.10,
            'dream_price_discount' => 10.10,
            'item_wise_discount' => 10.10,
            'complimentary_item_discount' => 10.10,
        ]
    );
});

test('It can call addNew method of SaleDiscountQueries', function (): void {
    $this->mock(SaleDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
                ->once();
    });
    $this->saleDetails['cart_promotion_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->saveSaleDetailsService->saveCartWideDiscount($this->checkSaleDetailsService, 10, 1);
});

test('saveVoucherDiscount method can call addNew method of SaleDiscountQueries', function (): void {
    $this->saleDetails['voucher_number'] = 'ABC123';
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->companyId = $this->companyId;

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
        'number' => $this->checkSaleDetailsService->saleData->voucher_number,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $saleDiscountService = new SaleDiscountService();
    $saleDiscountService->voucher = $voucher;
    $this->checkSaleDetailsService->saleDiscountService = $saleDiscountService;
    $this->checkSaleDetailsService->location = $location;

    $this->mock(SaleDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsUsed')
            ->once();
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->saveSaleDetailsService->saveVoucherDiscount($this->checkSaleDetailsService, 10, 1);
});

test('it calls the updateSpentTillNow method of the MemberQueries class', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('updateSpentTillNow')
            ->once()
            ->with($sale->total_amount_paid, $sale->member_id);
    });

    $this->saveSaleDetailsService->updateSpentTillNow($sale);
});

test('it calls the updateMemberMembership method of the same class', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $membership = Membership::factory()->make([
        'company_id' => 1,
    ]);

    $mock = $this->createPartialMock(SaveSaleDetailsService::class, ['updateMemberMembership']);

    $mock->expects($this->once())
        ->method('updateMemberMembership');

    $this->mock(MembershipQueries::class, function ($mock) use ($membership): void {
        $mock->shouldReceive('getByCompanyIdSortByMinimumSpendAmount')
            ->once()
            ->with(1)
            ->andReturn(collect($membership));
    });

    $mock->updateMembership($sale, 1);
});

test('updateMemberMembership sets the membership_id column of the member', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'happened_at' => '2022-01-04 04:20:50',
    ]);

    $membership = Membership::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'lifetime_value' => 5,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'spent_till_now' => 10,
    ]);

    $member->membership = $membership;

    $this->mock(MemberQueries::class, function ($mock) use ($sale, $member, $membership): void {
        $mock->shouldReceive('getByIdWithMembership')
            ->once()
            ->with($sale->member_id)
            ->andReturn($member);
        $mock->shouldReceive('setMembershipId')
            ->once()
            ->with($membership->id, $sale->member_id);
    });

    $this->mock(MembershipAssignmentQueries::class, function ($mock) use ($sale, $membership): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($membership->id, $sale->member_id, $sale->happened_at);
    });

    $this->saveSaleDetailsService->updateMemberMembership($sale, collect([$membership]));
});

test(
    'updateMembership will not call the getByCompanyIdSortByMinimumSpendAmount method when the sale user is null',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => null,
            'counter_update_id' => 1,
        ]);

        $this->mock(MembershipQueries::class, function ($mock): void {
            $mock->shouldNotReceive('getByCompanyIdSortByMinimumSpendAmount');
        });

        $this->saveSaleDetailsService->updateMembership($sale, 1);
    }
);

test(
    'it does not set the membership_id if the member membership lifetime_value is greater than membership lifetime_value',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $membership1 = Membership::factory()->make([
            'company_id' => 1,
            'lifetime_value' => 100,
        ]);

        $member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
            'spent_till_now' => 10,
            'membership_id' => $membership1->id,
        ]);

        $member->membership = $membership1;

        $membership2 = Membership::factory()->make([
            'company_id' => 1,
            'lifetime_value' => 50,
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($sale, $member): void {
            $mock->shouldReceive('getByIdWithMembership')
                ->once()
                ->with($sale->member_id)
                ->andReturn($member);
            $mock->shouldNotReceive('setMembershipId');
        });

        $this->saveSaleDetailsService->updateMemberMembership($sale, collect([$membership2]));
    }
);

test('useLoyaltyPoints method returns null if loyalty_points are not available in request', function (): void {
    $member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'spent_till_now' => 10,
        'membership_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale->member = $member;

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->times(0);
    });

    $this->checkSaleDetailsService->saleData = $this->saleData;
    $payment['loyalty_points'] = 0;
    $response = $this->saveSaleDetailsService->useLoyaltyPoints($this->checkSaleDetailsService, new Sale(), $payment);
    $this->assertNull($response);
});

test('it calls decreaseLoyaltyPoints method of LoyaltyPointService class', function (): void {
    $member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'spent_till_now' => 10,
        'membership_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale->member = $member;

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $this->checkSaleDetailsService->saleData = $this->saleData;
    $payment['loyalty_points'] = 100;
    $this->saveSaleDetailsService->useLoyaltyPoints($this->checkSaleDetailsService, $sale, $payment);
});

test('saveItemPriceOverride method calls addNew method of SaleItemPriceOverrideQueries class', function (): void {
    $saleItemPriceOverride = SaleItemPriceOverride::factory()->make([
        'id' => 1,
        'sale_item_id' => 1,
        'negotiator_id' => 1,
        'override_price' => 10.10,
    ]);

    $this->mock(SaleItemPriceOverrideQueries::class, function ($mock) use ($saleItemPriceOverride): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($saleItemPriceOverride);
    });

    $this->mock(SaleItemDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $cartItem = $this->saleDetails['items'][0];
    $cartItem['store_manager_id'] = 1;
    $cartItem['store_manager_passcode'] = 123456;
    $cartItem['price_override_amount'] = 10.10;
    $cartItem['store_manager_authorization_code'] = '12345';

    $this->saveSaleDetailsService->saveItemPriceOverride(
        $this->checkSaleDetailsService,
        10,
        $cartItem,
        [
            'total_discount' => 20.20,
            'dream_price_discount' => 10.10,
            'item_wise_discount' => 10.10,
            'price_override_discount' => 10.10,
        ]
    );
});

test(
    'it calls useCreditNote methods of SaveSaleDetailsService class.',
    function (): void {
        $saleData = $this->saleData;
        $saleData->payments = null;
        $this->checkSaleDetailsService->saleData = $saleData;

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $this->mock(SaveSaleReturnDetailsService::class, function ($mock): void {
            $mock->shouldReceive('useCreditNote')
                ->once();
        });

        $this->saveSaleDetailsService->savePayments($this->checkSaleDetailsService, new Sale(), $saleReturn, 1);
    }
);

test('saveSaleItemComplimentary method calls addNew method of SaleItemComplimentaryQueries class', function (): void {
    $this->mock(SaleItemComplimentaryQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $cartItem = $this->saleDetails['items'][0];
    $cartItem['store_manager_id'] = 1;
    $cartItem['store_manager_passcode'] = 123456;
    $cartItem['complimentary_item_reason_id'] = 1;

    $this->saveSaleDetailsService->saveSaleItemComplimentary($this->checkSaleDetailsService, 1, $cartItem);
});

test(
    'it calls respective methods of GiftCardTransactionQueries class and mark gift card as used.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $salePayment = SalePayment::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'counter_update_id' => null,
            'payment_type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
        ]);

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'status' => GiftCardStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->giftCards = collect([
            '0' => $giftCard,
        ]);

        $this->saleData->payments = [
            [
                'type_id' => 1,
                'amount' => '100',
                'gift_card_id' => 1,
            ],
        ];

        $this->mock(SalePaymentQueries::class, function ($mock) use ($salePayment): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($salePayment->id);
        });

        $this->mock(GiftCardQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(GiftCardTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->saveSaleDetailsService->savePayments($this->checkSaleDetailsService, new Sale(), null, 1);
    }
);

test('vouchers, or cashback is not generated when sale is layaway.', function (): void {
    $this->checkSaleDetailsService->cartItems = $this->cartItems;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->location = $this->location;
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->saleData->is_layaway = true;

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
        'is_non_inventory' => false,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => $product->id,
        'derivative_id' => null,
    ]);

    $sale->saleItems = collect([$saleItem]);

    Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => null,
    ]);

    $this->checkSaleDetailsService->saleDiscountService = $this->mock(
        SaleDiscountService::class,
        function ($mock): void {
            $mock->shouldReceive('getCartDiscountAmountFor')
                ->once()
                ->andReturn([
                    'total_discount' => 10.00,
                    'cart_wide_discount' => 10.00,
                    'voucher_discount' => 10.00,
                    'price_override_discount' => 10.00,
                    'cart_wide_loyalty_point_discount' => 10.00,
                ]);
            $mock->shouldReceive('getItemCartDiscountAmount')
                ->once();
            $mock->shouldReceive('getItemDiscountAmountFor')
                ->once()
                ->andReturn([
                    'total_discount' => 0.00,
                ]);
            $mock->shouldReceive('getTotalItemDiscountAmount')
                ->once();
        }
    );

    $this->checkSaleDetailsService->saleTaxService = $this->mock(SaleTaxService::class, function ($mock): void {
        $mock->shouldReceive('getItemTaxAmountFor')
            ->once();
        $mock->shouldReceive('getTotalTaxAmountFor')
            ->once();
    });

    $this->checkSaleDetailsService->generateLoyaltyPointsService = $this->mock(
        GenerateLoyaltyPointsService::class,
        function ($mock): void {
            $mock->shouldReceive('saveGenerateLoyaltyPoints')
                ->once();
        }
    );

    $this->checkSaleDetailsService->bookingPayments = collect([]);

    $this->checkSaleDetailsService->creditNotes = collect([]);

    $this->checkSaleDetailsService->products = collect([$product]);

    $mock = $this->createPartialMock(
        SaveSaleDetailsService::class,
        [
            'updateInventory',
            'savePayments',
            'saveSaleMismatches',
            'saveItemDiscounts',
            'saveCartWideDiscount',
            'updateLayawayDetails',
            'updateSpentTillNow',
            'updateMembership',
            'saveItemPriceOverride',
            'saveCartPriceOverride',
        ]
    );

    $mock->expects($this->never())
        ->method('updateInventory');

    $mock->expects($this->once())
        ->method('savePayments');

    $mock->expects($this->once())
        ->method('saveSaleMismatches');

    $mock->expects($this->once())
        ->method('saveItemDiscounts');

    $mock->expects($this->once())
        ->method('saveCartWideDiscount');

    $mock->expects($this->once())
        ->method('updateLayawayDetails');

    $mock->expects($this->once())
        ->method('saveItemPriceOverride');

    $mock->expects($this->once())
        ->method('saveCartPriceOverride');

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($sale);
        $mock->shouldReceive('updateTotals')
            ->once();
        $mock->shouldReceive('loadRelations')
            ->times(4)
            ->andReturn($sale);
    });

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($saleItem);
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('updateLastPurchaseDate')
            ->once();
        $mock->shouldReceive('updateSalesQuantity')
            ->once();
    });

    $this->mock(SaleReservedStockService::class, function ($mock): void {
        $mock->shouldReceive('updateReservedStock')
            ->once();
    });
    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
        'company_id' => 1,
    ]);
    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'company_id' => 1,
    ]);
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'country_id' => 1,
    ]);
    $sequence = Sequence::factory()->make([
        'number' => 0o000001,
        'location_id' => 1,
    ]);
    $counterUpdate->counter = $counter;
    $counterUpdate->counter->location = $location;

    $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($sequence);
    });
    $mock->saveDetails($this->cashier, $this->checkSaleDetailsService, 1, new SaleReturn());
});

test(
    'Saving Basic Sale Return with Product Exchange',
    function (): void {
        $saleDetails = [
            'offline_sale_id' => '1',
            'employee_id' => null,
            'vouchers' => null,
            'cashback_id' => 1,
            'cashback_amount' => 12,
            'cashback_round_off_amount' => 0,
            'items' => [
                [
                    'id' => 1,
                    'price' => '10.00',
                    'quantity' => '10',
                    'promotion_id' => 1,
                    'is_exchange' => 1,
                ],
            ],
            'return_items' => [
                [
                    'sale_item_id' => 1,
                    'price_paid_per_unit' => '10.00',
                    'quantity' => '10',
                    'sale_return_details' => [
                        'quantity' => 1,
                        'sale_return_reason_id' => 1,
                    ],
                ],
            ],
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => '100',
                ],
            ],
            'sale_notes' => 'Notes goes here',
            'happened_at' => '2022-01-04 04:20:50',
            'member_id' => 1,
            'is_layaway' => false,
            'cart_promotion_id' => null,
            'sale_round_off_amount' => 0.01,
        ];

        $saleData = new SaleData(...$saleDetails);

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
            'is_non_inventory' => true,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'original_sale_id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $saleReturn->saleReturnItems = collect([
            SaleReturnItem::factory()->make([
                'id' => 1,
                'sale_return_id' => 1,
                'original_sale_item_id' => 1,
                'product_id' => $product->id,
                'derivative_id' => null,
                'sale_return_reason_id' => 1,
            ]),
        ]);

        $this->checkSaleDetailsService->cartItems = collect($saleData->items);
        $this->checkSaleDetailsService->saleData = $saleData;
        $this->checkSaleDetailsService->products = collect([$product]);
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->companyId = 1;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => $product->id,
            'derivative_id' => null,
        ]);

        $sale->saleItems = collect([$saleItem]);

        Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => null,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('getCartDiscountAmountFor')
                    ->once()
                    ->andReturn([
                        'total_discount' => 10.00,
                        'cart_wide_discount' => 10.00,
                        'voucher_discount' => 10.00,
                        'price_override_discount' => 10.00,
                        'cart_wide_loyalty_point_discount' => 10.00,
                    ]);
                $mock->shouldReceive('getItemCartDiscountAmount')
                    ->once();
                $mock->shouldReceive('getItemDiscountAmountFor')
                    ->once()
                    ->andReturn([
                        'total_discount' => 0.00,
                    ]);
                $mock->shouldReceive('getTotalItemDiscountAmount')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->generateVoucherService = $this->mock(
            GenerateVoucherService::class,
            function ($mock): void {
                $mock->shouldReceive('saveVouchers')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->saleCashbackService = $this->mock(
            SaleCashbackService::class,
            function ($mock): void {
                $mock->shouldReceive('saveCashback')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->saleTaxService = $this->mock(
            SaleTaxService::class,
            function ($mock): void {
                $mock->shouldReceive('getItemTaxAmountFor')
                    ->once();
                $mock->shouldReceive('getTotalTaxAmountFor')
                    ->once();
            }
        );

        $this->checkSaleDetailsService->bookingPayments = collect([]);

        $this->checkSaleDetailsService->creditNotes = collect([]);

        $mock = $this->createPartialMock(
            SaveSaleDetailsService::class,
            [
                'savePayments',
                'saveSaleMismatches',
                'saveItemDiscounts',
                'saveCartWideDiscount',
                'updateLayawayDetails',
                'updateSpentTillNow',
                'updateMembership',
                'saveItemPriceOverride',
                'saveCartPriceOverride',
            ]
        );

        $mock->expects($this->once())
            ->method('savePayments');

        $mock->expects($this->once())
            ->method('saveSaleMismatches');

        $mock->expects($this->once())
            ->method('saveItemDiscounts');

        $mock->expects($this->once())
            ->method('saveCartWideDiscount');

        $mock->expects($this->once())
            ->method('saveCartPriceOverride');

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('updateTotals')
                ->once();
            $mock->shouldReceive('loadRelations')
                ->times(4)
                ->andReturn($sale);
        });

        $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($saleItem);
        });

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('updateLastPurchaseDate')
                ->once();
            $mock->shouldReceive('updateSalesQuantity')
                ->once();
        });

        $this->checkSaleDetailsService->generateLoyaltyPointsService = $this->mock(
            GenerateLoyaltyPointsService::class,
            function ($mock): void {
                $mock->shouldReceive('saveGenerateLoyaltyPoints')
                    ->once();
            }
        );

        $this->mock(SaleItemExchangeService::class, function ($mock): void {
            $mock->shouldReceive('saveSaleItemAndReturnItemDetails')
                ->once();
        });
        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
            'company_id' => 1,
        ]);
        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
            'company_id' => 1,
        ]);
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $sequence = Sequence::factory()->make([
            'number' => 0o000001,
            'location_id' => 1,
        ]);
        $counterUpdate->counter = $counter;
        $counterUpdate->counter->location = $location;

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });
        $mock->saveDetails($this->cashier, $this->checkSaleDetailsService, 1, $saleReturn);
    }
);

test('saveCartPriceOverride method calls addNew method of SalePriceOverrideQueries class', function (): void {
    $salePriceOverride = SalePriceOverride::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'negotiator_id' => 1,
        'override_price' => 10.10,
    ]);

    $this->mock(SalePriceOverrideQueries::class, function ($mock) use ($salePriceOverride): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($salePriceOverride);
    });

    $this->mock(SaleDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->saleDetails['store_manager_id'] = 1;
    $this->saleDetails['store_manager_passcode'] = '1111';
    $this->saleDetails['cart_price_override_amount'] = 10;

    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);

    $this->saveSaleDetailsService->saveCartPriceOverride($this->checkSaleDetailsService, 10, 1);
});

test('useItemLoyaltyPoints method returns null if user is null', function (): void {
    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->times(0);
    });

    $this->checkSaleDetailsService->saleData = $this->saleData;
    $cartItem['loyalty_points'] = 0;
    $response = $this->saveSaleDetailsService->useItemLoyaltyPoints(
        $this->checkSaleDetailsService,
        new SaleItem(),
        null,
        $cartItem
    );
    $this->assertNull($response);
});

test('useItemLoyaltyPoints calls decreaseLoyaltyPoints method of LoyaltyPointService class', function (): void {
    $member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'spent_till_now' => 10,
        'membership_id' => 1,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $this->checkSaleDetailsService->saleData = $this->saleData;
    $cartItem['loyalty_points'] = 100;
    $this->saveSaleDetailsService->useItemLoyaltyPoints($this->checkSaleDetailsService, $saleItem, $member, $cartItem);
});

test('it calls the updateCreditDetails method of SaleQueries class', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->saleData->is_credit_sale = true;

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('updateCreditPendingAmountAndStatus')
            ->once();
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale->saleItems = collect([]);

    $this->saveSaleDetailsService->updateCreditDetails($sale, $this->checkSaleDetailsService, null);
});

test(
    'updateCreditPaymentsToTheSaleItems calls updateTotalPricePaid method of SaleItemQueries class',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => null,
            'total_price_paid' => 50.00,
        ]);
        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateTotalPricePaid')
            ->once();
        });

        $this->saveSaleDetailsService->updateCreditPaymentsToTheSaleItems(collect([$saleItem]), 100.00, true);
    }
);

test('It calls addNew method of SaleLoyaltyPointQueries', function (): void {
    $this->mock(SaleItemDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(4);
    });

    $saleLoyaltyPoint = SaleLoyaltyPoint::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'sale_id' => 1,
    ]);

    $this->mock(SaleLoyaltyPointQueries::class, function ($mock) use ($saleLoyaltyPoint): void {
        $mock->shouldReceive('addNew')
            ->times(1)
            ->andReturn($saleLoyaltyPoint);
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->times(1);
    });

    $cartItem = $this->saleDetails['items'][0];
    $cartItem['dream_price_id'] = 1;
    $cartItem['dream_price_amount'] = 10.10;
    $cartItem['complimentary_item_reason_id'] = 1;
    $cartItem['complimentary_item_discount'] = 55;
    $cartItem['loyalty_points'] = 10;
    $cartItem['loyalty_point_item_discount'] = 10;
    $cartItem['store_manager_authorization_code'] = '1354';
    $cartItem['amount'] = 123.00;

    $this->saveSaleDetailsService->saveItemDiscounts(
        $this->checkSaleDetailsService,
        $cartItem,
        10,
        [
            'total_discount' => 20.20,
            'dream_price_discount' => 10.10,
            'item_wise_discount' => 10.10,
            'complimentary_item_discount' => 10.10,
            'loyalty_point_item_discount' => 10.10,
        ]
    );
});

test('saveCartWideLoyaltyPointsDiscount method can call addNew method of SaleDiscountQueries', function (): void {
    $this->saleDetails['cart_loyalty_point_amount'] = 10.10;
    $this->saleDetails['cart_loyalty_points'] = 10;
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->companyId = $this->companyId;

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale->member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'spent_till_now' => 10,
        'membership_id' => 1,
    ]);

    $this->checkSaleDetailsService->saleDiscountService = new SaleDiscountService();

    $this->checkSaleDetailsService->location = $location;

    $saleLoyaltyPoint = SaleLoyaltyPoint::factory()->make([
        'id' => 1,
        'product_id' => 1,
        'sale_id' => 1,
    ]);

    $this->mock(SaleDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(SaleLoyaltyPointQueries::class, function ($mock) use ($saleLoyaltyPoint): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($saleLoyaltyPoint);
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($sale);
    });

    $this->saveSaleDetailsService->saveCartWideLoyaltyPointsDiscount($this->checkSaleDetailsService, 10.0, $sale);
});

test(
    'saveCartWideLoyaltyPointsDiscount method return null when cart loyalty point discount not apply',
    function (): void {
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);

        $response = $this->saveSaleDetailsService->saveCartWideLoyaltyPointsDiscount(
            $this->checkSaleDetailsService,
            10.0,
            new Sale()
        );
        $this->assertNull($response);
    }
);

test(
    'updateBoxProduct method return null when product not bundle',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => null,
            'total_price_paid' => 50.00,
        ]);

        $cartItem['box_product_id'] = null;
        $response = $this->saveSaleDetailsService->updateBoxProduct(
            $this->checkSaleDetailsService,
            $saleItem,
            $cartItem
        );
        $this->assertNull($response);
    }
);

test(
    'updateBoxProduct method call updateBoxProductDetails method of SaleItemQueries class',
    function (): void {
        $saleItem = SaleItem::factory()->make([
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => null,
            'total_price_paid' => 50.00,
        ]);

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('updateBoxProductDetails')
                ->once();
        });

        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'package_type_id' => 1,
        ]);

        $this->product->boxes = collect([$boxProduct]);
        $this->checkSaleDetailsService->products = collect([$this->product]);

        $cartItem['box_product_id'] = 1;
        $cartItem['id'] = 1;
        $response = $this->saveSaleDetailsService->updateBoxProduct(
            $this->checkSaleDetailsService,
            $saleItem,
            $cartItem
        );
        $this->assertNull($response);
    }
);

test(
    'saveSaleItemAssemblyChildProduct method return null when product not Assembly Product',
    function (): void {
        $this->checkSaleDetailsService->products = collect([$this->product]);

        $cartItem['id'] = 1;
        $response = $this->saveSaleDetailsService->saveSaleItemAssemblyChildProduct(
            $this->checkSaleDetailsService,
            1,
            $cartItem
        );
        $this->assertNull($response);
    }
);

test(
    'saveSaleItemAssemblyChildProduct method call addNew method of SaleItemAssemblyChildProductQueries class',
    function (): void {
        $this->mock(SaleItemAssemblyChildProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $assemblyChildProduct = AssemblyChildProduct::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'child_product_id' => 1,
            'units' => 10.10,
        ]);

        $assemblyChildProduct->product = $this->product;

        $this->product->assemblyChildProducts = collect([$assemblyChildProduct]);

        $this->product->type_id = ProductTypes::ASSEMBLY_PRODUCT->value;

        $this->checkSaleDetailsService->products = collect([$this->product]);

        $cartItem['id'] = 1;
        $response = $this->saveSaleDetailsService->saveSaleItemAssemblyChildProduct(
            $this->checkSaleDetailsService,
            1,
            $cartItem
        );
        $this->assertNull($response);
    }
);

test(
    'updateInventory method calls the same class methods as expected when product is assembly product',
    function (): void {
        $this->product->type_id = ProductTypes::ASSEMBLY_PRODUCT->value;
        $this->checkSaleDetailsService->products = collect([$this->product]);
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->location = $this->location;

        $mock = $this->createPartialMock(SaveSaleDetailsService::class, ['updateAssemblyProductInventory']);

        $mock->expects($this->once())
            ->method('updateAssemblyProductInventory');

        $mock->updateInventory(
            new SaleItem(),
            $this->saleDetails['items'][0],
            $this->cashier,
            $this->checkSaleDetailsService
        );
    }
);

test(
    'updateAssemblyProductInventory method calls the fetchOrCreate methods of InventoryQueries class',
    function (): void {
        $this->product->type_id = ProductTypes::ASSEMBLY_PRODUCT->value;

        $assemblyChildProduct = AssemblyChildProduct::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'child_product_id' => 1,
            'units' => 10.10,
        ]);

        $assemblyChildProduct->product = $this->product;

        $this->product->assemblyChildProducts = collect([$assemblyChildProduct]);

        $this->checkSaleDetailsService->products = collect([$this->product]);
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->location = $this->location;

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('fetchOrCreate')
                ->once()
                ->andReturn(new Inventory([
                    'stock' => 50,
                ]));
            $mock->shouldReceive('decreaseStock')
                ->once();
        });

        $this->mock(SaleInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnits')
                ->once();
        });

        $this->saveSaleDetailsService->updateAssemblyProductInventory(
            new SaleItem(),
            $this->saleDetails['items'][0],
            $this->cashier,
            $this->checkSaleDetailsService,
            $this->product
        );
    }
);

test(
    'isAssemblyProduct method call and check product is assembly and non inventory and return always false',
    function (): void {
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
            'upc' => 'abd123',
            'has_batch' => false,
            'type_id' => ProductTypes::ASSEMBLY_PRODUCT->value,
            'is_non_inventory' => true,
        ]);

        $this->checkSaleDetailsService->products = collect([$product]);
        $response = $this->saveSaleDetailsService->isAssemblyProduct(
            $this->checkSaleDetailsService,
            $this->cartItems->toArray()[0]
        );

        expect($response)->toBe(false);
    }
);

test(
    'isAssemblyProduct method call and check product is not assembly and non inventory and return always true',
    function (): void {
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
            'upc' => 'abd123',
            'has_batch' => false,
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            'is_non_inventory' => true,
        ]);

        $this->checkSaleDetailsService->products = collect([$product]);
        $response = $this->saveSaleDetailsService->isAssemblyProduct(
            $this->checkSaleDetailsService,
            $this->cartItems->toArray()[0]
        );

        expect($response)->toBeTrue();
    }
);

test(
    'getSequenceNumber method call and return the sequence number',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $sequence = Sequence::factory()->make([
            'number' => '000001',
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });

        $response = $this->saveSaleDetailsService->getSequenceNumber($location);
        expect($response)->toBeString();
    }
);
