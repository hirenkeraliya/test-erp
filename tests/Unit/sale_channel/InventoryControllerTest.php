<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Http\Controllers\Api\SaleChannel\Inventory\InventoryController;

it('returns a list of stores by product ids.', function (): void {
    $cartDetails = [
        'cart_details' => [
            [
                'product_id' => 1,
                'quantity' => 1,
            ],
        ],
    ];

    $inventoryQueries = $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoresHavingInventoriesByProductIds')
            ->once()
            ->andReturn(collect([]));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($cartDetails);

    $inventoryController = new InventoryController($inventoryQueries);
    $response = $inventoryController->getStoresByProducts($request);

    $this->assertEquals(collect([]), $response['stores']->resource);
    expect($response)
        ->toHaveKeys(['stores']);
});
