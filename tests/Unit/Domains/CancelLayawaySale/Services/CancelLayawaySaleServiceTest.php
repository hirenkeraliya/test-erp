<?php

declare(strict_types=1);

use App\Domains\CancelLayawaySale\CancelLayawaySaleQueries;
use App\Domains\CancelLayawaySale\Services\CancelLayawaySaleService;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\DataObjects\CancelLayawaySaleData;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\CancelLayawaySale;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\Location;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Sequence;
use App\Models\StoreManager;
use App\Models\Voucher;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('It calls the addNew method of the CancelLayawaySaleQueries class and returns proper response', function (): void {
    $cancelLayawaySale = CancelLayawaySale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'store_manager_id' => 1,
    ]);

    $this->mock(CancelLayawaySaleQueries::class, function ($mock) use ($cancelLayawaySale): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($cancelLayawaySale);
    });

    $mock = $this->createPartialMock(
        CancelLayawaySaleService::class,
        [
            'loyaltyPointsRevert',
            'vouchersRevert',
            'creditNoteCreateAndRefund',
            'revertReservedStock',
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
            ->method('revertReservedStock');

    $mock->expects($this->once())
            ->method('revertUsedItemLoyaltyPoints');

    $mock->expects($this->once())
            ->method('revertUsedLoyaltyPoints');

    $cancelLayawaySaleData = new CancelLayawaySaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

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
    ]);

    $mock->saveDetails($cancelLayawaySaleData, $sale, 1, $location);
});

it('loyaltyPointsRevert method calls respective queries class methods as expected', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $cancelLayawaySale = CancelLayawaySale::factory()->make([
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

    $cancelLayawaySaleData = new CancelLayawaySaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $cancelLayawaySaleService = new CancelLayawaySaleService();
    $cancelLayawaySaleService->loyaltyPointsRevert($sale, $cancelLayawaySale, $cancelLayawaySaleData);
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

    $cancelLayawaySaleService = new CancelLayawaySaleService();
    $cancelLayawaySaleService->vouchersRevert($sale->id, 1);
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
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
    ]);

    $payment = SalePayment::factory()->make([
        'sale_id' => 1,
        'payment_type_id' => 1,
        'counter_update_id' => 1,
        'amount' => 100,
    ]);

    $sale->payments = collect([$payment]);

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('addNewForCancelLayawaySale')
            ->once()
            ->andReturn($creditNote);
    });

    $location = Location::factory()->make([
        'id' => 1,
        'code' => 'AAA',
        'company_id' => 1,
    ]);

    $sequence = Sequence::factory()->make([
        'number' => '000001',
        'location_id' => 1,
    ]);

    $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
        $mock->shouldReceive('addNew')
            ->times(1)
            ->andReturn($sequence);
    });

    $cancelLayawaySaleData = new CancelLayawaySaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $cancelLayawaySaleService = new CancelLayawaySaleService();
    $cancelLayawaySaleService->creditNoteCreateAndRefund($sale, $location, $cancelLayawaySaleData, 1, 1, 1);
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
    ]);

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('checkGeneratedVoucherIsUsed')
            ->once()
            ->andReturn(true);
    });

    $cancelLayawaySaleData = new CancelLayawaySaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $cancelLayawaySaleService = new CancelLayawaySaleService();
    $cancelLayawaySaleService->checkRequestDetails($cancelLayawaySaleData, $sale, $location, 1, collect([]));
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

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $cancelLayawaySaleData = new CancelLayawaySaleData(1, '123', now()->format('Y-m-d H:i:s'), 'Test');

    $cancelLayawaySaleService = new CancelLayawaySaleService();
    $cancelLayawaySaleService->checkRequestDetails($cancelLayawaySaleData, $sale, $location, 1, collect([]));
})->throws(HttpException::class, 'Wrong passcode.');

test('RevertReservedStock method calls respective queries class methods as expected', function (): void {
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

    $cancelLayawaySaleService = new CancelLayawaySaleService();
    $cancelLayawaySaleService->revertReservedStock($sale);
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

    $mock = $this->createPartialMock(CancelLayawaySaleService::class, ['revertUsedLoyaltyPoints']);

    $mock->expects($this->once())
            ->method('revertUsedLoyaltyPoints');

    $cancelLayawaySale = CancelLayawaySale::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'store_manager_id' => 1,
    ]);

    $mock->revertUsedItemLoyaltyPoints($sale, $cancelLayawaySale, now()->format('Y-m-d H:i:s'), 10);
});

test('revertUsedLoyaltyPoints method calls respective queries class methods as expected', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $cancelLayawaySale = CancelLayawaySale::factory()->make([
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

    $cancelLayawaySaleService = new CancelLayawaySaleService();
    $cancelLayawaySaleService->revertUsedLoyaltyPoints(
        1,
        ModelMapping::SALE_ITEM->name,
        $member,
        $cancelLayawaySale,
        now()->format('Y-m-d H:i:s')
    );
});

test('revertUsedLoyaltyPoints method return null when no loyalty point used', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $cancelLayawaySale = CancelLayawaySale::factory()->make([
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

    $cancelLayawaySaleService = new CancelLayawaySaleService();
    $response = $cancelLayawaySaleService->revertUsedLoyaltyPoints(
        1,
        ModelMapping::SALE_ITEM->name,
        $member,
        $cancelLayawaySale,
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

        $sale->counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $sale->counterUpdate->counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
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

        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('checkStoreManagerAuthorizationCode')
                ->once();
        });

        $location = Location::factory()->make([
            'id' => 1,
            'country_id' => 1,
            'company_id' => 1,
        ]);

        $cancelLayawaySaleData = new CancelLayawaySaleData(1, '12345', now()->format('Y-m-d H:i:s'), 'Test', '12315');

        $cancelLayawaySaleService = new CancelLayawaySaleService();
        $cancelLayawaySaleService->checkRequestDetails($cancelLayawaySaleData, $sale, $location, 1, collect([]));
    }
);

test(
    'getSequenceNumber method call and return the sequence number',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
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

        $cancelLayawaySaleService = new CancelLayawaySaleService();
        $response = $cancelLayawaySaleService->getSequenceNumber($location, SequenceTypes::CN);
        expect($response)->toBeString();
    }
);

test(
    'checkStore method call return null when same location cancel sale',
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
            'country_id' => 1,
            'company_id' => 1,
        ]);

        $cancelLayawaySaleService = new CancelLayawaySaleService();
        $response = $cancelLayawaySaleService->checkStore($location, $sale);
        $this->assertNull($response);
    }
);

test(
    'checkStore method throws an exception when deferent location cancel sale',
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
            'country_id' => 1,
            'company_id' => 1,
        ]);

        $cancelLayawaySaleService = new CancelLayawaySaleService();
        $response = $cancelLayawaySaleService->checkStore($location, $sale);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Layaway sale cannot be canceled at a different location.');
