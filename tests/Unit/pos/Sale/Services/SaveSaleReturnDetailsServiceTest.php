<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\SaleReturnInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleReturnService;
use App\Domains\Sale\Services\SaveSaleReturnDetailsService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\CreditNote;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemAssemblyChildProduct;
use App\Models\SaleItemUnit;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use App\Models\Sequence;

beforeEach(function (): void {
    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'cashback_round_off_amount' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promotion_id' => 1,
            ],
        ],
        'return_items' => [
            [
                'sale_item_id' => 1,
                'price_paid_per_unit' => '10.00',
                'quantity' => '5',
                'sale_return_details' => [
                    [
                        'quantity' => '2.00',
                        'sale_return_reason_id' => '1',
                    ],
                    [
                        'quantity' => '3.00',
                        'sale_return_reason_id' => '2',
                    ],
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
        'is_layaway' => true,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
        'sale_return_round_off_amount' => 0.01,
    ];

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->companyId = 1;

    $this->cashier = makeCashierForPosWithCounterUpdateId();

    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->saveSaleReturnDetailsService = new SaveSaleReturnDetailsService();
    $this->saleReturnService = new SaleReturnService();
    $this->saleReturnService->returnItems = collect($this->saleData->return_items);
});

test('saveSaleReturnDetails method returns null when cart items is not available', function (): void {
    $this->saleReturnService->returnItems = collect([]);
    $this->checkSaleDetailsService->saleReturnService = $this->saleReturnService;

    $response = $this->saveSaleReturnDetailsService->saveSaleReturnDetails(
        $this->cashier,
        $this->checkSaleDetailsService,
        1
    );

    $this->assertNull($response);
});

test(
    'saveSaleReturnDetails method calls the same class methods as expected',
    function (): void {
        $saleReturnReason = [];
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'credit_note_expiration_days,' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->checkSaleDetailsService->companyId = 1;

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
            'id' => 1,
            'original_sale_id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $saleReturnItem = SaleReturnItem::factory()->make([
            'id' => 1,
            'sale_return_id' => 1,
            'original_sale_item_id' => 1,
            'product_id' => $product->id,
            'sale_return_reason_id' => 1,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => $product->id,
            'derivative_id' => 1,
        ]);

        $saleItem->product = $product;

        $sale = Sale::factory()->make([
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $saleItem->sale = $sale;

        $saleItemUnit = SaleItemUnit::factory()->make([
            'sale_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 100,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $saleReturn->saleItems = collect([$saleReturnItem]);

        $this->checkSaleDetailsService->saleReturnService = $this->mock(
            SaleReturnService::class,
            function ($mock) use ($saleItem, $saleReturnReason): void {
                $mock->shouldReceive('hasReturnItems')
                    ->once()
                    ->andReturn(true);

                $mock->shouldReceive('checkRoundOffValue')
                    ->once();

                $mock->shouldReceive('getReturnedSaleItems')
                    ->once();

                $mock->saleReturnMismatches = collect([]);
                $mock->returnedSaleItems = collect([$saleItem]);
                $mock->returnItems = collect($this->saleData->return_items);
                $mock->saleReturnReasons = collect($saleReturnReason);

                $mock->shouldReceive('isProductBeingExchanged')
                    ->times(2)
                    ->andReturn(false);
            }
        );

        $mock = $this->createPartialMock(
            SaveSaleReturnDetailsService::class,
            ['saveSaleReturnMismatches', 'getSaleItemUnits', 'decreaseLoyaltyPoints', 'revertUsedLoyaltyPoints']
        );

        $mock->expects($this->once())
            ->method('saveSaleReturnMismatches');

        $mock->expects($this->once())
            ->method('decreaseLoyaltyPoints');

        $mock->expects($this->any())
            ->method('revertUsedLoyaltyPoints');

        $mock->expects($this->any())
            ->method('getSaleItemUnits')
            ->will($this->returnValue(collect([$saleItemUnit])));

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('incrementReturnedQuantity')
                ->once();
        });

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($saleReturn);
            $mock->shouldReceive('updateTotals')
                ->once();
            $mock->shouldReceive('loadRelations')
                ->once();
        });

        $this->mock(SaleReturnItemQueries::class, function ($mock) use ($saleReturnItem): void {
            $mock->shouldReceive('addNew')
                ->twice()
                ->andReturn($saleReturnItem);
        });

        $this->mock(CreditNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $sequence = Sequence::factory()->make([
            'number' => '000001',
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->times(2)
                ->andReturn($sequence);
        });

        $mock->saveSaleReturnDetails($this->cashier, $this->checkSaleDetailsService, 1);
    }
);

test('updateInventory method calls addInventoryAsPerSaleReturn method of InventoryService class', function (): void {
    $this->checkSaleDetailsService->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $saleReturnItem = SaleReturnItem::factory()->make([
        'id' => 1,
        'sale_return_id' => 1,
        'original_sale_item_id' => 1,
        'product_id' => 1,
        'sale_return_reason_id' => 1,
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

    $saleItemUnit = SaleItemUnit::factory()->make([
        'sale_item_id' => 1,
        'inventory_id' => 1,
        'purchase_amount_id' => 1,
        'batch_id' => 1,
        'quantity' => 100,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->checkSaleDetailsService->saleReturnService = new SaleReturnService();

    $mock = $this->createPartialMock(SaveSaleReturnDetailsService::class, ['getSaleItemUnits']);

    $mock->expects($this->any())
        ->method('getSaleItemUnits')
        ->will($this->returnValue(collect([$saleItemUnit])));

    $this->mock(SaleItemUnitQueries::class, function ($mock): void {
        $mock->shouldReceive('incrementReturnedQuantity')
            ->once();
    });

    $this->mock(SaleReturnInventoryService::class, function ($mock): void {
        $mock->shouldReceive('addInventory')
            ->once();
    });

    $returnItemDetails['quantity'] = 10;

    $mock->updateInventory(
        $this->checkSaleDetailsService,
        $saleItem,
        $saleReturnItem,
        $this->cashier,
        $saleReturnReason,
        $returnItemDetails
    );
});

test('it calls addNew method of PosMismatchQueries class', function (): void {
    $this->saleReturnService->saleReturnMismatches = collect(['Test', 'test 1']);
    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->twice();
    });

    $this->saveSaleReturnDetailsService->saveSaleReturnMismatches(new SaleReturn(), $this->saleReturnService);
});

test('getReturnItemAmountFor method returns as expected', function (): void {
    $response = $this->saveSaleReturnDetailsService->getReturnItemAmountFor(100.00, 10.00, 5.00);
    $this->assertTrue(50.00 === $response);
});

test('getSaleItemUnits method returns the proper units when the sale item product is batch.', function (): void {
    $returnItemDetails = [];
    $returnItemDetails['batch_number'] = 123456;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $saleItemUnit = SaleItemUnit::factory()->make([
        'sale_item_id' => 1,
        'inventory_id' => 1,
        'purchase_amount_id' => 1,
        'batch_id' => 1,
        'quantity' => 100,
    ]);

    $batch = Batch::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'product_id' => 1,
        'number' => '123456',
    ]);

    $saleItem->product = commonGetProductDetails(true);
    $saleItem->saleItemUnits = collect([$saleItemUnit]);

    $saleReturnService = new SaleReturnService();
    $saleReturnService->batches = collect([$batch]);

    $response = $this->saveSaleReturnDetailsService->getSaleItemUnits(
        $saleItem,
        $saleReturnService,
        $returnItemDetails
    );
    expect($response->first()->toArray())
        ->toHaveKey('sale_item_id', 1)
        ->toHaveKey('inventory_id', 1)
        ->toHaveKey('purchase_amount_id', 1)
        ->toHaveKey('batch_id', 1);
});

test('getSaleItemUnits method returns the proper units when the sale item product is normal', function (): void {
    $returnItemDetails = [];
    $returnItemDetails['batch_number'] = 123456;

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $saleItemUnit = SaleItemUnit::factory()->make([
        'sale_item_id' => 1,
        'inventory_id' => 1,
        'purchase_amount_id' => 1,
        'batch_id' => 1,
        'quantity' => 100,
    ]);

    $saleItem->product = commonGetProductDetails(false);
    $saleItem->saleItemUnits = collect([$saleItemUnit]);

    $response = $this->saveSaleReturnDetailsService->getSaleItemUnits(
        $saleItem,
        new SaleReturnService(),
        $returnItemDetails
    );
    expect($response->first()->toArray())
        ->toHaveKey('sale_item_id', 1)
        ->toHaveKey('inventory_id', 1)
        ->toHaveKey('purchase_amount_id', 1)
        ->toHaveKey('batch_id', 1);
});

test(
    'decreaseLoyaltyPoints method calls hasLoyaltyPointsAsPaymentTypeInOriginalSale method of SaleReturnService class',
    function (): void {
        $returnItemDetails = [];
        $returnItemDetails['batch_number'] = 123456;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
        ]);

        $sale = new Sale();
        $sale->payments = collect([]);
        $saleItem->sale = $sale;

        $saleReturnService = $this->mock(SaleReturnService::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('areAllOfTheReturnItemsBeingExchanged')
                ->once()
                ->andReturn(true);
            $mock->returnedSaleItems = collect([$saleItem]);
        });

        $response = $this->saveSaleReturnDetailsService->decreaseLoyaltyPoints(new SaleReturn(), $saleReturnService);
        $this->assertNull($response);
    }
);

test(
    'decreaseLoyaltyPoints method calls decreaseLoyaltyPointsForSaleReturn method of LoyaltyPointService class',
    function (): void {
        $returnItemDetails = [];
        $returnItemDetails['batch_number'] = 123456;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 10,
            'happened_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
            'spent_till_now' => 10,
            'membership_id' => 1,
        ]);

        $sale = new Sale([
            'member_id' => 1,
            'total_amount_paid' => 15,
        ]);

        $sale->member = $member;

        $sale->payments = collect([]);

        $loyaltyPoint = new LoyaltyPoint();

        $loyaltyPoint->minimum_spend_amount = 10;
        $loyaltyPoint->points = 5;
        $loyaltyPoint->loyaltyCampaign = new LoyaltyCampaign();

        $sale->issuedLoyaltyPoints = collect([$loyaltyPoint]);

        $saleItem->sale = $sale;

        $saleReturnService = $this->mock(SaleReturnService::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('areAllOfTheReturnItemsBeingExchanged')
                ->once()
                ->andReturn(false);
            $mock->returnedSaleItems = collect([$saleItem]);
        });

        $this->mock(LoyaltyPointService::class, function ($mock): void {
            $mock->shouldReceive('decreaseLoyaltyPoints')
                ->once();
        });

        $mock = $this->createPartialMock(SaveSaleReturnDetailsService::class, ['getTotalAmountPaidExcludedBrands']);

        $mock->expects($this->any())
            ->method('getTotalAmountPaidExcludedBrands')
            ->will($this->returnValue(9));

        $mock->decreaseLoyaltyPoints($saleReturn, $saleReturnService);
    }
);

test(
    'decreaseLoyaltyPoints method return null if return loyalty points zero',
    function (): void {
        $returnItemDetails = [];
        $returnItemDetails['batch_number'] = 123456;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
        ]);

        $saleReturn = new SaleReturn([
            'total_price_paid' => 10,
        ]);

        $sale = new Sale([
            'total_amount_paid' => 15,
        ]);

        $sale->payments = collect([]);

        $loyaltyPoint = new LoyaltyPoint();

        $loyaltyPoint->minimum_spend_amount = 10;

        $sale->issuedLoyaltyPoints = collect([$loyaltyPoint]);

        $saleItem->sale = $sale;

        $saleReturnService = $this->mock(SaleReturnService::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('areAllOfTheReturnItemsBeingExchanged')
                ->once()
                ->andReturn(false);
            $mock->returnedSaleItems = collect([$saleItem]);
        });

        $mock = $this->createPartialMock(SaveSaleReturnDetailsService::class, ['getTotalAmountPaidExcludedBrands']);

        $mock->expects($this->any())
            ->method('getTotalAmountPaidExcludedBrands')
            ->will($this->returnValue(9));

        $mock->decreaseLoyaltyPoints($saleReturn, $saleReturnService);
    }
);

test('getTotalAmountPaid method returns as expected', function (): void {
    $saleItem = new SaleItem([
        'quantity' => 10,
        'returned_quantity' => 5,
        'price_paid_per_unit' => 10,
    ]);

    $sale = new Sale();

    $sale->saleItems = collect([$saleItem]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('loadSaleItems')
            ->once()
            ->andReturn($sale);
    });

    $response = $this->saveSaleReturnDetailsService->getTotalAmountPaid($sale);
    $this->assertTrue(50.00 === $response);
});

test('getItemAmountPaid method returns as expected', function (): void {
    $saleItem = new SaleItem([
        'quantity' => 10,
        'returned_quantity' => 5,
        'price_paid_per_unit' => 10,
    ]);

    $response = $this->saveSaleReturnDetailsService->getItemAmountPaid($saleItem);
    $this->assertTrue(50.00 === $response);
});

test(
    'saveSaleReturnDetails method set credit note as payment method when return product with exchange',
    function (): void {
        $saleReturnReason = [];
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->creditNotes = collect([]);

        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'credit_note_expiration_days,' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->checkSaleDetailsService->companyId = 1;

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

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $saleReturnItem = SaleReturnItem::factory()->make([
            'id' => 1,
            'sale_return_id' => 1,
            'original_sale_item_id' => 1,
            'product_id' => $product->id,
            'sale_return_reason_id' => 1,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => $product->id,
            'derivative_id' => 1,
        ]);

        $saleItem->product = $product;

        $sale = Sale::factory()->make([
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $saleItem->sale = $sale;

        $saleItemUnit = SaleItemUnit::factory()->make([
            'sale_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 100,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $saleReturn->saleItems = collect([$saleReturnItem]);

        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'total_amount' => 100,
            'available_amount' => 100,
        ]);

        $this->checkSaleDetailsService->saleReturnService = $this->mock(
            SaleReturnService::class,
            function ($mock) use ($saleItem, $saleReturnReason): void {
                $mock->shouldReceive('hasReturnItems')
                    ->once()
                    ->andReturn(true);

                $mock->shouldReceive('checkRoundOffValue')
                    ->once();

                $mock->shouldReceive('getReturnedSaleItems')
                    ->once();

                $mock->shouldReceive('isProductBeingExchanged')
                    ->times(2)
                    ->andReturn(true);

                $mock->saleReturnMismatches = collect([]);
                $mock->returnedSaleItems = collect([$saleItem]);
                $mock->returnItems = collect($this->saleData->return_items);
                $mock->saleReturnReasons = collect($saleReturnReason);
            }
        );

        $mock = $this->createPartialMock(
            SaveSaleReturnDetailsService::class,
            ['saveSaleReturnMismatches', 'getSaleItemUnits', 'updateInventory', 'decreaseLoyaltyPoints']
        );

        $mock->expects($this->once())
            ->method('saveSaleReturnMismatches');

        $mock->expects($this->once())
            ->method('decreaseLoyaltyPoints');

        $mock->expects($this->any())
            ->method('getSaleItemUnits')
            ->will($this->returnValue(collect([$saleItemUnit])));

        $mock->expects($this->once())
            ->method('decreaseLoyaltyPoints');

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('incrementReturnedQuantity')
                ->once();
        });

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($saleReturn);
            $mock->shouldReceive('updateTotals')
                ->once();
            $mock->shouldReceive('loadRelations')
                ->once();
        });

        $this->mock(SaleReturnItemQueries::class, function ($mock) use ($saleReturnItem): void {
            $mock->shouldReceive('addNew')
                ->twice()
                ->andReturn($saleReturnItem);
        });

        $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($creditNote);
        });

        $sequence = Sequence::factory()->make([
            'number' => '000001',
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->times(2)
                ->andReturn($sequence);
        });

        $mock->saveSaleReturnDetails($this->cashier, $this->checkSaleDetailsService, 1);
    }
);

test(
    'useCreditNote method calls addNew method of SalePaymentQueries class',
    function (): void {
        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $saleReturn->creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'total_amount' => 100,
            'available_amount' => 100,
        ]);

        $sale = new Sale([
            'total_amount_paid' => 100,
        ]);

        $this->mock(SalePaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(CreditNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(CreditNoteUseQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->saveSaleReturnDetailsService->useCreditNote($this->checkSaleDetailsService, $sale, $saleReturn, 1);
    }
);

test(
    'getTotalAmountPaidExcludedBrands method returns the applicable loyalty point when excludedBrands is not empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $brandA = Brand::factory()->make([
            'id' => 1,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $brandB = Brand::factory()->make([
            'id' => 2,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $loyaltyCampaign->excludedBrands = collect([$brandA]);

        $saleItemA = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'price_paid_per_unit' => 1,
            'quantity' => 1,
            'returned_quantity' => 0,
        ]);

        $productA = commonGetProductDetails(false);
        $productA->brand = $brandA;

        $saleItemA->product = $productA;

        $saleItemB = SaleItem::factory()->make([
            'id' => 2,
            'sale_id' => 1,
            'product_id' => 2,
            'derivative_id' => 1,
            'price_paid_per_unit' => 10,
            'quantity' => 20,
            'returned_quantity' => 5,
        ]);

        $productB = commonGetProductDetails(false);
        $productB->brand = $brandB;

        $saleItemB->product = $productB;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 10,
        ]);

        $sale->saleItems = collect([$saleItemA, $saleItemB]);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('loadSaleItemsProductAndBrand')
                ->once()
                ->andReturn($sale);
        });

        $response = $this->saveSaleReturnDetailsService->getTotalAmountPaidExcludedBrands($sale, $loyaltyCampaign);
        $this->assertEquals(150, $response);
    }
);

test('revertUsedLoyaltyPoints method calls respective queries class methods as expected', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $saleReturnItem = SaleReturnItem::factory()->make([
        'id' => 1,
        'sale_return_id' => 1,
        'original_sale_item_id' => 1,
        'product_id' => 1,
        'sale_return_reason_id' => 1,
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

    $this->saveSaleReturnDetailsService->revertUsedLoyaltyPoints(
        1,
        ModelMapping::SALE_ITEM->name,
        $member,
        $saleReturnItem,
        now()->format('Y-m-d H:i:s'),
        10
    );
});

test('revertUsedLoyaltyPoints method return null when no loyalty point used', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $saleReturnItem = SaleReturnItem::factory()->make([
        'id' => 1,
        'sale_return_id' => 1,
        'original_sale_item_id' => 1,
        'product_id' => 1,
        'sale_return_reason_id' => 1,
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

    $response = $this->saveSaleReturnDetailsService->revertUsedLoyaltyPoints(
        1,
        ModelMapping::SALE_ITEM->name,
        $member,
        $saleReturnItem,
        now()->format('Y-m-d H:i:s'),
        10
    );
    $this->assertNull($response);
});

test('updateInventory method calls same class method when product is Assembly Product', function (): void {
    $this->checkSaleDetailsService->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->checkSaleDetailsService->companyId = 1;

    $saleReturnItem = SaleReturnItem::factory()->make([
        'id' => 1,
        'sale_return_id' => 1,
        'original_sale_item_id' => 1,
        'product_id' => 1,
        'sale_return_reason_id' => 1,
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

    $saleItemUnit = SaleItemUnit::factory()->make([
        'sale_item_id' => 1,
        'inventory_id' => 1,
        'purchase_amount_id' => 1,
        'batch_id' => 1,
        'quantity' => 100,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->checkSaleDetailsService->saleReturnService = new SaleReturnService();

    $mock = $this->createPartialMock(SaveSaleReturnDetailsService::class, ['updateAssemblyProductInventory']);

    $mock->expects($this->once())
        ->method('updateAssemblyProductInventory');

    $returnItemDetails['quantity'] = 10;

    $mock->updateInventory(
        $this->checkSaleDetailsService,
        $saleItem,
        $saleReturnItem,
        $this->cashier,
        $saleReturnReason,
        $returnItemDetails
    );
});

test(
    'updateAssemblyProductInventory method calls addInventoryAsPerSaleReturn method of InventoryService class',
    function (): void {
        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->checkSaleDetailsService->companyId = 1;

        $saleReturnItem = SaleReturnItem::factory()->make([
            'id' => 1,
            'sale_return_id' => 1,
            'original_sale_item_id' => 1,
            'product_id' => 1,
            'sale_return_reason_id' => 1,
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

        $this->checkSaleDetailsService->saleData = $this->saleData;

        $saleItemAssemblyChildProduct = SaleItemAssemblyChildProduct::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'child_product_id' => 1,
            'units' => 10.10,
        ]);

        $saleItem->saleItemAssemblyChildProducts = collect([$saleItemAssemblyChildProduct]);

        $saleItemUnit = SaleItemUnit::factory()->make([
            'sale_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 100,
        ]);

        $saleReturnReason = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->checkSaleDetailsService->saleReturnService = new SaleReturnService();

        $mock = $this->createPartialMock(SaveSaleReturnDetailsService::class, ['getSaleItemUnits']);

        $mock->expects($this->any())
            ->method('getSaleItemUnits')
            ->will($this->returnValue(collect([$saleItemUnit])));

        $this->mock(SaleItemUnitQueries::class, function ($mock): void {
            $mock->shouldReceive('incrementReturnedQuantity')
                ->once();
        });

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'location_id' => 1,
        ]);

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('fetchOrCreate')
                ->once()
                ->andReturn($inventory);
        });

        $this->mock(SaleReturnInventoryService::class, function ($mock): void {
            $mock->shouldReceive('addInventory')
                ->once();
        });

        $returnItemDetails['quantity'] = 10;

        $mock->updateAssemblyProductInventory(
            $this->checkSaleDetailsService,
            $saleItem,
            $saleReturnItem,
            $this->cashier,
            $saleReturnReason,
            $returnItemDetails
        );
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

        $response = $this->saveSaleReturnDetailsService->getSequenceNumber($location, SequenceTypes::SR);
        expect($response)->toBeString();
    }
);
