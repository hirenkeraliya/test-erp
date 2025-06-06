<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\Product;
use App\Models\StoreManager;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->checkOrderDetailsService = new CheckOrderDetailsService();
    $this->companyId = 1;
    $this->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'cashback_round_off_amount' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
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

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'retail_price' => 10.00,
        'has_batch' => false,
        'status' => false,
    ]);

    $this->batchA = Batch::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'product_id' => $this->product->id,
        'number' => '123',
    ]);

    $this->batchB = Batch::factory()->make([
        'id' => 2,
        'company_id' => $this->companyId,
        'product_id' => $this->product->id,
        'number' => '2345',
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'stock' => 40.00,
    ]);

    $this->inventoryUnitA = InventoryUnit::factory()->make([
        'id' => 1,
        'inventory_id' => $this->inventory->id,
        'purchase_amount_id' => 1,
        'batch_id' => $this->batchA->id,
        'quantity' => 30.00,
    ]);

    $this->inventoryUnitB = InventoryUnit::factory()->make([
        'id' => 2,
        'inventory_id' => $this->inventory->id,
        'purchase_amount_id' => 2,
        'batch_id' => $this->batchB->id,
        'quantity' => 10.00,
    ]);

    $this->inventory->inventoryUnits = collect([$this->inventoryUnitA]);

    $this->cartItems = collect([
        [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'promoter_ids' => [1],
        ],
    ]);

    $this->batches = collect([$this->batchA, $this->batchB]);

    $this->storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);
});

test(
    'checkBatchNumber method throws an exception when one of the products has batch enabled and batch number is not specified',
    function (): void {
        $this->checkOrderDetailsService->batches = $this->batches;

        $product = $this->product;
        $product->has_batch = true;

        $cartItem = $this->cartItems[0];

        $this->checkOrderDetailsService->checkBatchNumber($product, $cartItem);
    }
)->throws(HttpException::class, 'Batch Number is required for the product with name ABC.');

test(
    'checkBatchNumber method throws an exception when one of the products has batch enabled and Batch Expiry Date is not specified',
    function (): void {
        $this->checkOrderDetailsService->batches = $this->batches;

        $product = $this->product;
        $product->has_batch = true;

        $cartItem = $this->cartItems[0];
        $cartItem['batch_details'][0]['batch_number'] = 'xyz';

        $this->checkOrderDetailsService->checkBatchNumber($product, $cartItem);
    }
)->throws(HttpException::class, 'Batch Expiry Date is required for the product with name ABC.');

test(
    'checkBatchNumber method cell addNew method of BatchQueries class',
    function (): void {
        $this->checkOrderDetailsService->batches = collect([]);
        $this->checkOrderDetailsService->companyId = 1;

        $product = $this->product;
        $product->has_batch = true;

        $cartItem = $this->cartItems[0];
        $cartItem['batch_details'][0]['batch_number'] = 'xyz';
        $cartItem['batch_details'][0]['quantity'] = 10;
        $cartItem['batch_details'][0]['batch_expiry_date'] = now()->format('Y-m-d');
        $batch = Batch::factory()->make([
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'abc123',
            'expiry_date' => '2022-01-01',
        ]);

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($batch);
        });

        $this->checkOrderDetailsService->checkBatchNumber($product, $cartItem);

        expect($this->checkOrderDetailsService->batches->first()->toArray())
            ->toHaveKey('company_id', 1)
            ->toHaveKey('product_id', 1)
            ->toHaveKey('number', 'abc123')
            ->toHaveKey('expiry_date', '2022-01-01');
    }
);

test('checkBatchNumber method returns the response as expected', function (): void {
    $this->checkOrderDetailsService->batches = $this->batches;

    $product = $this->product;
    $product->has_batch = true;

    $cartItem = $this->cartItems[0];
    $cartItem['batch_details'][0]['batch_number'] = '123';

    $response = $this->checkOrderDetailsService->checkBatchNumber($product, $cartItem);
    $this->assertNull($response);
});
