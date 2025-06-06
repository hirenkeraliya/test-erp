<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPaymentVoidUse\BookingPaymentVoidUseQueries;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNoteVoidUse\CreditNoteVoidUseQueries;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\VoidSaleInventoryService;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleVoidCashback\SaleVoidCashbackQueries;
use App\Domains\VoidSale\DataObjects\PosVoidSaleData;
use App\Domains\VoidSale\Services\VoidSaleService;
use App\Domains\VoidSale\VoidSaleQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\BookingPayment;
use App\Models\BookingPaymentUse;
use App\Models\Cashier;
use App\Models\CreditNote;
use App\Models\CreditNoteUse;
use App\Models\GiftCardTransaction;
use App\Models\Inventory;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCashback;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\SaleItemUnit;
use App\Models\SalePayment;
use App\Models\VoidSale;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;

test('It calls the addNew method of the VoidSaleQueries class and returns proper response', function (): void {
    $this->mock(VoidSaleQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn(new VoidSale());
    });

    $voidSaleService = new VoidSaleService();
    $response = $voidSaleService->saveVoidDetails(new PosVoidSaleData(1, 1, '123'), 1, 1);
    expect($response)->toBeObject();
});

test('updateInventory method calls respective queries methods as expected', function (): void {
    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->mock(VoidSaleInventoryService::class, function ($mock): void {
        $mock->shouldReceive('addInventory')
            ->times(2);
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
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
        'is_non_inventory' => true,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
    ]);

    $saleItem->product = $product;

    $sale->saleItems = new Collection([$saleItem]);

    $sale->saleItems[0]->saleItemUnits = new Collection([
        new SaleItemUnit([
            'quantity' => 5,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]),
        new SaleItemUnit([
            'quantity' => 5,
            'inventory_id' => 1,
            'purchase_amount_id' => 2,
            'batch_id' => 2,
        ]),
    ]);

    $voidSaleService = new VoidSaleService();
    $voidSaleService->updateInventory($sale, new VoidSale(), $cashier, 1);
});

test(
    'updateInventory method calls respective queries methods as expected when sale in pending layaway sale',
    function (): void {
        $cashier = Cashier::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $this->mock(SaleReservedStockService::class, function ($mock): void {
            $mock->shouldReceive('revertReservedStock')
            ->times(1);
        });

        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $sale->saleItems = new Collection([
            new SaleItem([
                'product_id' => 1,
                'quantity' => 10,
            ]),
        ]);

        $sale->saleItems[0]->saleItemUnits = new Collection([
            new SaleItemUnit([
                'quantity' => 5,
                'inventory_id' => 1,
                'purchase_amount_id' => 1,
                'batch_id' => 1,
            ]),
            new SaleItemUnit([
                'quantity' => 5,
                'inventory_id' => 1,
                'purchase_amount_id' => 2,
                'batch_id' => 2,
            ]),
        ]);

        $voidSaleService = new VoidSaleService();
        $voidSaleService->updateInventory($sale, new VoidSale(), $cashier, 1);
    }
);

test(
    'RevertReservedStock method calls revertReservedStock method of SaleReservedStockService queries class',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100.00,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
        ]);

        $sale->saleItems = collect([$saleItem]);
        $this->mock(SaleReservedStockService::class, function ($mock): void {
            $mock->shouldReceive('revertReservedStock')
            ->once();
        });

        $voidSaleService = new VoidSaleService();
        $voidSaleService->revertReservedStock($sale);
    }
);

it('checkAndRevertLoyaltyPoints method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $sale->member = $member;

    $loyaltyPoint = LoyaltyPoint::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'sale_id' => $sale->id,
        'loyalty_campaign_id' => 0,
        'points' => 100,
    ]);

    $loyaltyPoint->member = $member;

    $voidSale = VoidSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'voided_by_store_manager_id' => 1,
        'void_sale_reason_id' => 1,
    ]);

    $this->mock(LoyaltyPointQueries::class, function ($mock) use ($sale, $loyaltyPoint): void {
        $mock->shouldReceive('getLoyaltyPointForGivenSale')
            ->once()
            ->with($sale->id)
            ->andReturn(collect([$loyaltyPoint]));
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('loadVoidSaleRelations')
            ->once()
            ->andReturn($sale);
    });

    $voidSaleService = new VoidSaleService();
    $voidSaleService->checkAndRevertLoyaltyPoints($sale, $voidSale);
});

it('checkAndRevertCreditNote method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $salePayment = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'payment_type_id' => 2,
        'counter_update_id' => 1,
    ]);

    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'total_amount' => 100,
        'available_amount' => 100,
    ]);

    $salePayment->creditNoteUse = CreditNoteUse::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_payment_id' => $salePayment->id,
        'credit_note_id' => 1,
        'booking_payment_payment_id' => null,
    ]);

    $this->mock(SalePaymentQueries::class, function ($mock) use ($sale, $salePayment): void {
        $mock->shouldReceive('getSalePaymentIdAndAmountOfCreditNote')
            ->once()
            ->with($sale->id)
            ->andReturn(collect([$salePayment]));
    });

    $this->mock(CreditNoteVoidUseQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(CreditNoteQueries::class, function ($mock): void {
        $mock->shouldReceive('incrementAvailableAmountAndActivate')
            ->once();
    });

    $voidSaleService = new VoidSaleService();
    $voidSaleService->checkAndRevertCreditNote($sale->id, 1);
});

it('checkAndRevertBookingPayment method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $salePayment = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'payment_type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
        'counter_update_id' => 1,
    ]);

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'total_amount' => 100,
        'available_amount' => 100,
    ]);

    $salePayment->bookingPaymentUse = BookingPaymentUse::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'booking_payment_id' => $bookingPayment->id,
        'sale_payment_id' => $salePayment->id,
    ]);

    $this->mock(SalePaymentQueries::class, function ($mock) use ($sale, $salePayment): void {
        $mock->shouldReceive('getSalePaymentIdAndAmountOfBookingPayment')
            ->once()
            ->with($sale->id)
            ->andReturn(collect([$salePayment]));
    });

    $this->mock(BookingPaymentVoidUseQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(BookingPaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('incrementAvailableAmountAndActivate')
            ->once();
    });

    $voidSaleService = new VoidSaleService();
    $voidSaleService->checkAndRevertBookingPayment($sale->id, 1);
});

it('checkAndRevertVouchersGenerated method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
    ]);

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('getVouchersBySaleId')
            ->once()
            ->andReturn(collect([$voucher]));
        $mock->shouldReceive('updateCancelledAt')
            ->once();
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $voidSaleService = new VoidSaleService();
    $voidSaleService->checkAndRevertVouchersGenerated($sale->id, 1);
});

it('checkAndRevertUsedVoucher method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
    ]);

    SaleDiscount::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'discountable_type' => ModelMapping::VOUCHER->name,
        'discountable_id' => $voucher->id,
    ]);

    $this->mock(SaleDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('getVoucherIdBySale')
            ->once()
            ->andReturn(1);
    });

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($voucher);
        $mock->shouldReceive('resetUsedAt')
            ->once();
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $voidSaleService = new VoidSaleService();
    $voidSaleService->checkAndRevertUsedVoucher($sale->id, 1);
});

it('checkAndRevertGiftCard method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $salePayment = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'payment_type_id' => 1,
        'counter_update_id' => 1,
    ]);

    $giftCardTransaction = GiftCardTransaction::factory()->make([
        'id' => 1,
        'gift_card_id' => 1,
        'affected_by_id' => 1,
        'affected_by_type' => ModelMapping::SALE_PAYMENT,
    ]);

    $giftCardTransaction->affectedBy = $salePayment;

    $this->mock(GiftCardQueries::class, function ($mock): void {
        $mock->shouldReceive('incrementAvailableAmountAndActivate')
            ->once();
    });

    $this->mock(GiftCardTransactionQueries::class, function ($mock) use ($giftCardTransaction): void {
        $mock->shouldReceive('getBySaleId')
            ->once()
            ->andReturn(collect([$giftCardTransaction]));
        $mock->shouldReceive('addNewForVoidSale')
            ->once();
    });

    $voidSaleService = new VoidSaleService();
    $voidSaleService->checkAndRevertGiftCard($sale->id, 1);
});

it('checkAndRevertCashback method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $sale->happened_at = now()->toDateTimeString();

    $saleCashback = SaleCashback::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'cashback_id' => 1,
        'cash_movement_id' => 1,
    ]);

    $saleCashback->sale = $sale;

    $this->mock(SaleCashbackQueries::class, function ($mock) use ($saleCashback): void {
        $mock->shouldReceive('getBySaleId')
            ->once()
            ->andReturn($saleCashback);
    });

    $this->mock(CashMovementQueries::class, function ($mock): void {
        $mock->shouldReceive('addNewForCashbackReversal')
            ->once();
    });

    $this->mock(SaleVoidCashbackQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $voidSaleService = new VoidSaleService();
    $voidSaleService->checkAndRevertCashback($sale->id, 1);
});

test('revertUsedItemLoyaltyPoints method calls same class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'total_price_paid' => 100.00,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $sale->saleItems = collect([$saleItem]);

    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $mock = $this->createPartialMock(VoidSaleService::class, ['revertUsedLoyaltyPoints']);

    $mock->expects($this->once())
            ->method('revertUsedLoyaltyPoints');

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('loadVoidSaleRelations')
            ->once()
            ->andReturn($sale);
    });

    $voidSale = VoidSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'voided_by_store_manager_id' => 1,
        'void_sale_reason_id' => 1,
    ]);

    $mock->revertUsedItemLoyaltyPoints($sale, $voidSale, 10);
});

test('revertUsedLoyaltyPoints method calls respective queries class methods as expected', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $voidSale = VoidSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'voided_by_store_manager_id' => 1,
        'void_sale_reason_id' => 1,
    ]);

    $loyaltyPointUpdates = LoyaltyPointUpdate::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'affected_by_id' => 1,
    ]);

    $this->mock(RevertLoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('increaseLoyaltyPoints')
            ->once();
    });

    $this->mock(LoyaltyPointUpdateQueries::class, function ($mock) use ($loyaltyPointUpdates): void {
        $mock->shouldReceive('getUsedLoyaltyPoint')
            ->once()
            ->andReturn(collect([$loyaltyPointUpdates]));
    });

    $voidSaleService = new VoidSaleService();
    $voidSaleService->revertUsedLoyaltyPoints(1, ModelMapping::SALE_ITEM->name, $member, $voidSale);
});

test('revertUsedLoyaltyPoints method return null when no loyalty point used', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $voidSale = VoidSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'voided_by_store_manager_id' => 1,
        'void_sale_reason_id' => 1,
    ]);

    $this->mock(RevertLoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('increaseLoyaltyPoints')
            ->never();
    });

    $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getUsedLoyaltyPoint')
            ->once()
            ->andReturn(collect([]));
    });

    $voidSaleService = new VoidSaleService();
    $response = $voidSaleService->revertUsedLoyaltyPoints(1, ModelMapping::SALE_ITEM->name, $member, $voidSale);
    $this->assertNull($response);
});

test('updateInventory method calls getInventoryById methods of inventoryQueries class', function (): void {
    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->mock(VoidSaleInventoryService::class, function ($mock): void {
        $mock->shouldReceive('addInventory')
            ->times(2);
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getInventoryById')
            ->times(2)
            ->andReturn(new Inventory([
                'product_id' => 1,
            ]));
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
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
        'is_non_inventory' => true,
        'type_id' => ProductTypes::ASSEMBLY_PRODUCT->value,
    ]);

    $saleItem->product = $product;

    $sale->saleItems = new Collection([$saleItem]);

    $sale->saleItems[0]->saleItemUnits = new Collection([
        new SaleItemUnit([
            'quantity' => 5,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]),
        new SaleItemUnit([
            'quantity' => 5,
            'inventory_id' => 1,
            'purchase_amount_id' => 2,
            'batch_id' => 2,
        ]),
    ]);

    $voidSaleService = new VoidSaleService();
    $voidSaleService->updateInventory($sale, new VoidSale(), $cashier, 1);
});
