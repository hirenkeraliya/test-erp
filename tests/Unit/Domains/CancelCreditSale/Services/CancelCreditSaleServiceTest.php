<?php

declare(strict_types=1);

use App\Domains\CancelCreditSale\CancelCreditSaleQueries;
use App\Domains\CancelCreditSale\Services\CancelCreditSaleService;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\Inventory\Services\SaleInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Sale\DataObjects\CancelCreditSaleData;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\CancelCreditSale;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\Location;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnit;
use App\Models\SalePayment;
use App\Models\Sequence;
use App\Models\StoreManager;
use App\Models\Voucher;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('It calls the addNew method of the CancelCreditSaleQueries class and returns proper response', function (): void {
    $cancelCreditSale = CancelCreditSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'store_manager_id' => 1,
    ]);

    $this->mock(CancelCreditSaleQueries::class, function ($mock) use ($cancelCreditSale): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($cancelCreditSale);
    });

    $mock = $this->createPartialMock(
        CancelCreditSaleService::class,
        [
            'loyaltyPointsRevert',
            'vouchersRevert',
            'creditNoteCreateAndRefund',
            'revertInventory',
            'revertUsedItemLoyaltyPoints',
            'revertUsedLoyaltyPoints',
        ]
    );

    $mock->expects($this->once())
            ->method('loyaltyPointsRevert');

    $mock->expects($this->once())
            ->method('vouchersRevert');

    $mock->expects($this->once())
        ->method('creditNoteCreateAndRefund');

    $mock->expects($this->once())
            ->method('revertInventory');

    $mock->expects($this->once())
            ->method('revertUsedItemLoyaltyPoints');

    $mock->expects($this->once())
            ->method('revertUsedLoyaltyPoints');

    $cancelCreditSaleData = new CancelCreditSaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $mock->saveDetails($cancelCreditSaleData, $sale, 1, $location, $cashier);
});

it('loyaltyPointsRevert method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $cancelCreditSale = CancelCreditSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'store_manager_id' => 1,
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

    $cancelCreditSaleData = new CancelCreditSaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $cancelCreditSaleService = new CancelCreditSaleService();
    $cancelCreditSaleService->loyaltyPointsRevert($sale, $cancelCreditSale, $cancelCreditSaleData);
});

it('vouchersRevert method calls respective queries class methods as expected', function (): void {
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

    $cancelCreditSaleService = new CancelCreditSaleService();
    $cancelCreditSaleService->vouchersRevert($sale->id, 1);
});

it('creditNoteCreateAndRefund method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'total_price_paid' => 100.00,
    ]);

    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_credit_sale_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
    ]);

    $payment = SalePayment::factory()->make([
        'sale_id' => 1,
        'payment_type_id' => 1,
        'counter_update_id' => 1,
        'amount' => 100,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'code' => 'AAA',
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $sale->payments = collect([$payment]);

    $sequence = Sequence::factory()->make([
        'number' => '000001',
        'location_id' => 1,
    ]);

    $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
        $mock->shouldReceive('addNew')
            ->times(1)
            ->andReturn($sequence);
    });

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('addNewForCancelCreditSale')
            ->once()
            ->andReturn($creditNote);
    });

    $cancelCreditSaleData = new CancelCreditSaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $cancelCreditSaleService = new CancelCreditSaleService();
    $cancelCreditSaleService->creditNoteCreateAndRefund($sale, $location, $cancelCreditSaleData, 1, 1, 1);
});

it('checkRequestDetails method throws an exception when Generated Voucher Is Used', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'total_price_paid' => 100.00,
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

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('checkGeneratedVoucherIsUsed')
            ->once()
            ->andReturn(true);
    });

    $cancelCreditSaleData = new CancelCreditSaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $cancelCreditSaleService = new CancelCreditSaleService();
    $cancelCreditSaleService->checkRequestDetails($cancelCreditSaleData, $sale, $location, 1, collect([]));
})->throws(
    HttpException::class,
    'I apologize, but it seems that this voucher has already been used for another transaction and is no longer eligible for refunding.'
);

it('checkRequestDetails method throws an exception when Wrong passcode', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'total_price_paid' => 100.00,
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

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '12345',
    ]);

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('checkGeneratedVoucherIsUsed')
            ->once()
            ->andReturn(false);
    });

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($storeManager);
    });

    $cancelCreditSaleData = new CancelCreditSaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $cancelCreditSaleService = new CancelCreditSaleService();
    $cancelCreditSaleService->checkRequestDetails($cancelCreditSaleData, $sale, $location, 1, collect([]));
})->throws(HttpException::class, 'Wrong passcode.');

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

    $mock = $this->createPartialMock(CancelCreditSaleService::class, ['revertUsedLoyaltyPoints']);

    $mock->expects($this->once())
            ->method('revertUsedLoyaltyPoints');

    $cancelCreditSale = CancelCreditSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'store_manager_id' => 1,
    ]);

    $mock->revertUsedItemLoyaltyPoints($sale, $cancelCreditSale, now()->format('Y-m-d H:i:s'), 10);
});

test('revertUsedLoyaltyPoints method calls respective queries class methods as expected', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $cancelCreditSale = CancelCreditSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'store_manager_id' => 1,
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

    $cancelCreditSaleService = new CancelCreditSaleService();
    $cancelCreditSaleService->revertUsedLoyaltyPoints(
        1,
        ModelMapping::SALE_ITEM->name,
        $member,
        $cancelCreditSale,
        now()->format('Y-m-d H:i:s')
    );
});

test('revertUsedLoyaltyPoints method return null when no loyalty point used', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $cancelCreditSale = CancelCreditSale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'store_manager_id' => 1,
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

    $cancelCreditSaleService = new CancelCreditSaleService();
    $response = $cancelCreditSaleService->revertUsedLoyaltyPoints(
        1,
        ModelMapping::SALE_ITEM->name,
        $member,
        $cancelCreditSale,
        now()->format('Y-m-d H:i:s')
    );
    $this->assertNull($response);
});

it(
    'checkRequestDetails method call checkStoreManagerAuthorizationCode of StoreManagerAuthorizationCodeUsageService class',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100.00,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '12345',
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

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('checkGeneratedVoucherIsUsed')
                ->once()
                ->andReturn(false);
        });

        $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($storeManager);
        });

        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('checkStoreManagerAuthorizationCode')
                ->once();
        });

        $cancelCreditSaleData = new CancelCreditSaleData(1, '12345', now()->format('Y-m-d H:i:s'), 'Test', '12315');

        $cancelCreditSaleService = new CancelCreditSaleService();
        $cancelCreditSaleService->checkRequestDetails($cancelCreditSaleData, $sale, $location, 1, collect([]));
    }
);

test('revertInventory method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'total_price_paid' => 100.00,
        'happened_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $saleItemUnit = SaleItemUnit::factory()->make([
        'id' => 1,
        'sale_item_id' => $saleItem->id,
        'inventory_id' => 1,
        'purchase_amount_id' => 1,
        'batch_id' => 1,
    ]);

    $saleItem->saleItemUnits = collect([$saleItemUnit]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $sale->saleItems = collect([$saleItem]);
    $this->mock(SaleInventoryService::class, function ($mock): void {
        $mock->shouldReceive('addInventory')
            ->once();
    });

    $cancelCreditSaleService = new CancelCreditSaleService();
    $cancelCreditSaleService->revertInventory($sale, $cashier, $location->id);
});

test(
    'getSequenceNumber method call and return the sequence number',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'code' => 'AAA',
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
            'country_id' => 1,
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

        $cancelCreditSaleService = new CancelCreditSaleService();
        $response = $cancelCreditSaleService->getSequenceNumber($location, SequenceTypes::CN);
        expect($response)->toBeString();
    }
);

test(
    'checkStore method call return null when same store cancel sale',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100.00,
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

        $cancelCreditSaleService = new CancelCreditSaleService();
        $response = $cancelCreditSaleService->checkStore($location, $sale);
        $this->assertNull($response);
    }
);

test(
    'checkStore method throws an exception when deferent store cancel sale',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100.00,
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

        $cancelCreditSaleService = new CancelCreditSaleService();
        $response = $cancelCreditSaleService->checkStore($location, $sale);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Credit sale cannot be canceled at a different location.');
