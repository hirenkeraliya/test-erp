<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSale\Resources\PosHoldSaleListResource;
use App\Domains\HoldSale\Services\CheckHoldSaleDetailsService;
use App\Domains\HoldSale\Services\SaveHoldSaleDetailsService;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Api\Pos\HoldSaleController;
use App\Models\Cashier;
use App\Models\HoldSale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('saveDetails method throws an exception if counter not open', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => null,
        'username' => 'Cashier',
        'company_id' => 1,
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
        'offline_id' => '1',
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
        'total_amount_paid' => 1.00,
        'items_discount_amount' => 1.00,
        'total_discount_amount' => 1.00,
        'round_off' => 1.00,
        'return_items' => null,
        'items' => [
            [
                'id' => $products->id,
                'price' => $products->retail_price,
                'quantity' => '1',
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $holdSaleData = new HoldSaleData(...$saleDetails);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $holdSaleController = new HoldSaleController();
    $holdSaleController->saveDetails($holdSaleData, $request);
})->throws(HttpException::class, 'The counter has not been opened yet.');

test('It calls the saveDetails method and returns a proper response', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
        'username' => 'Cashier',
        'company_id' => 1,
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
        'offline_id' => '1',
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
        'total_amount_paid' => 1.00,
        'items_discount_amount' => 1.00,
        'total_discount_amount' => 1.00,
        'round_off' => 1.00,
        'return_items' => null,
        'items' => [
            [
                'id' => $products->id,
                'price' => $products->retail_price,
                'quantity' => '1',
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $holdSaleData = new HoldSaleData(...$saleDetails);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($products): void {
        $mock->shouldReceive('getByIdsWithBrandAndCategories')
            ->once()
            ->andReturn(new Collection([$products]));
    });

    $this->mock(CheckHoldSaleDetailsService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();

        $mock->shouldReceive('checkRequestDetails')
            ->once();

        $mock->shouldReceive('checkOfflineId')
            ->once();
    });

    $holdSale = HoldSale::factory()->make([
        'counter_update_id' => 1,
        'offline_id' => 1,
        'complete_sale_id' => 1,
        'id' => 1,
    ]);

    $this->mock(SaveHoldSaleDetailsService::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('saveDetails')
            ->once()
            ->andReturn($holdSale);
    });

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew');
    });

    $holdSaleController = new HoldSaleController();
    $response = $holdSaleController->saveDetails($holdSaleData, $request);
    expect($response['hold_sale'])->toBeInstanceOf(PosHoldSaleListResource::class);
});

test('It calls the cancelHoldSale method and returns a proper response', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
        'username' => 'Cashier',
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
        'offline_id' => '1',
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
        'total_amount_paid' => 1.00,
        'items_discount_amount' => 1.00,
        'total_discount_amount' => 1.00,
        'round_off' => 1.00,
        'return_items' => null,
        'items' => [
            [
                'id' => $products->id,
                'price' => $products->retail_price,
                'quantity' => '1',
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $holdSaleData = new HoldSaleData(...$saleDetails);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($products): void {
        $mock->shouldReceive('getByIdsWithBrandAndCategories')
            ->once()
            ->andReturn(new Collection([$products]));
    });

    $this->mock(CheckHoldSaleDetailsService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();

        $mock->shouldReceive('checkRequestDetails')
            ->once();
    });

    $holdSale = HoldSale::factory()->make([
        'counter_update_id' => 1,
        'offline_id' => 1,
        'complete_sale_id' => 1,
    ]);

    $this->mock(SaveHoldSaleDetailsService::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('saveDetails')
            ->once()
            ->andReturn($holdSale);
    });

    $holdSaleController = new HoldSaleController();
    $response = $holdSaleController->cancelHoldSale($holdSaleData, $request);
    expect($response['hold_sale'])->toBeInstanceOf(PosHoldSaleListResource::class);
});

test('It calls the completeHoldSale method and returns a proper response', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
        'username' => 'Cashier',
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
        'offline_id' => '1',
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
        'total_amount_paid' => 1.00,
        'items_discount_amount' => 1.00,
        'total_discount_amount' => 1.00,
        'round_off' => 1.00,
        'return_items' => null,
        'items' => [
            [
                'id' => $products->id,
                'price' => $products->retail_price,
                'quantity' => '1',
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $holdSaleData = new HoldSaleData(...$saleDetails);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($products): void {
        $mock->shouldReceive('getByIdsWithBrandAndCategories')
            ->once()
            ->andReturn(new Collection([$products]));
    });

    $this->mock(CheckHoldSaleDetailsService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();

        $mock->shouldReceive('checkRequestDetails')
            ->once();
    });

    $holdSale = HoldSale::factory()->make([
        'counter_update_id' => 1,
        'offline_id' => 1,
        'complete_sale_id' => 1,
    ]);

    $this->mock(SaveHoldSaleDetailsService::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('saveDetails')
            ->once()
            ->andReturn($holdSale);
    });

    $holdSaleController = new HoldSaleController();
    $response = $holdSaleController->completeHoldSale($holdSaleData, $request);
    expect($response['hold_sale'])->toBeInstanceOf(PosHoldSaleListResource::class);
});

test('It calls the releasedHoldSale method and returns a proper response', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
        'username' => 'Cashier',
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
        'offline_id' => '1',
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
        'total_amount_paid' => 1.00,
        'items_discount_amount' => 1.00,
        'total_discount_amount' => 1.00,
        'round_off' => 1.00,
        'return_items' => null,
        'items' => [
            [
                'id' => $products->id,
                'price' => $products->retail_price,
                'quantity' => '1',
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $holdSaleData = new HoldSaleData(...$saleDetails);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($products): void {
        $mock->shouldReceive('getByIdsWithBrandAndCategories')
            ->once()
            ->andReturn(new Collection([$products]));
    });

    $this->mock(CheckHoldSaleDetailsService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();

        $mock->shouldReceive('checkRequestDetails')
            ->once();
    });

    $holdSale = HoldSale::factory()->make([
        'counter_update_id' => 1,
        'offline_id' => 1,
        'complete_sale_id' => 1,
    ]);

    $this->mock(SaveHoldSaleDetailsService::class, function ($mock) use ($holdSale): void {
        $mock->shouldReceive('saveDetails')
            ->once()
            ->andReturn($holdSale);
    });

    $holdSaleController = new HoldSaleController();
    $response = $holdSaleController->releasedHoldSale($holdSaleData, $request);
    expect($response['hold_sale'])->toBeInstanceOf(PosHoldSaleListResource::class);
});

test('It returns the get type', function (): void {
    $holdSaleController = new HoldSaleController();
    $response = $holdSaleController->getTypes();
    expect($response['types'])
        ->toHaveKey('0.id', HoldSaleTypes::REGULAR_SALE->value)
        ->toHaveKey('0.key', HoldSaleTypes::REGULAR_SALE->name)
        ->toHaveKey('1.id', HoldSaleTypes::LAYAWAY_SALE->value)
        ->toHaveKey('1.key', HoldSaleTypes::LAYAWAY_SALE->name)
        ->toHaveKey('2.id', HoldSaleTypes::BOOKING_PAYMENT->value)
        ->toHaveKey('2.key', HoldSaleTypes::BOOKING_PAYMENT->name);
});
