<?php

declare(strict_types=1);

use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\OrderInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\DataObjects\OrderData;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Domains\Order\Services\SaveOrderDetailsService;
use App\Domains\OrderItemAssemblyChildProduct\OrderItemAssemblyChildProductQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\AssemblyChildProduct;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sequence;
use App\Models\StoreManager;

beforeEach(function (): void {
    $this->orderDetails = [
        'order_type' => OrderTypes::PENDING_LAYAWAY_ORDER->value,
        'channel_type' => OrderChannels::B2B_ORDERS->value,
        'member_id' => null,
        'notes' => 'Notes goes here',
        'bill_reference_number' => null,
        'return_items' => null,
        'order_items' => [
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
        'order_round_off_amount' => 0.0,
        'order_return_round_off_amount' => 0.0,
        'total_tax_amount' => 0.0,
        'cart_discount_amount' => 0.0,
        'member_details' => [],
        'location_id' => 1,
        'cart_price_override_amount' => 0.01,
        'cart_price_override_percentage' => 0.01,
        'is_layaway' => true,
        'layaway_pending_amount' => 2,
    ];

    $this->orderData = new OrderData(...$this->orderDetails);

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

    $this->orderItems = collect($this->orderData->order_items);

    $this->companyId = 1;

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $this->saleMismatches = collect([]);
    $this->checkOrderDetailsService = new CheckOrderDetailsService();
    $this->saveOrderDetailsService = new SaveOrderDetailsService();
});

test(
    'saveOrderItemAssemblyChildProduct method return null when product not Assembly Product',
    function (): void {
        $this->checkOrderDetailsService->products = collect([$this->product]);

        $orderItem['id'] = 1;
        $response = $this->saveOrderDetailsService->saveOrderItemAssemblyChildProduct(
            $this->checkOrderDetailsService,
            1,
            $orderItem
        );
        $this->assertNull($response);
    }
);

test(
    'saveOrderItemAssemblyChildProduct method call addNew method of OrderItemAssemblyChildProductQueries class',
    function (): void {
        $this->mock(OrderItemAssemblyChildProductQueries::class, function ($mock): void {
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

        $this->checkOrderDetailsService->products = collect([$this->product]);

        $cartItem['id'] = 1;
        $response = $this->saveOrderDetailsService->saveOrderItemAssemblyChildProduct(
            $this->checkOrderDetailsService,
            1,
            $cartItem
        );
        $this->assertNull($response);
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

        $this->checkOrderDetailsService->products = collect([$this->product]);
        $this->checkOrderDetailsService->saleData = $this->orderData;
        $this->checkOrderDetailsService->location = $this->location;

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('fetchOrCreate')
                ->once()
                ->andReturn(new Inventory([
                    'stock' => 50,
                ]));
            $mock->shouldReceive('decreaseStock')
                ->once();
        });

        $this->mock(OrderInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnits')
                ->once();
        });

        $this->saveOrderDetailsService->updateAssemblyProductInventory(
            new OrderItem(),
            $this->orderDetails['order_items'][0],
            $this->storeManager,
            $this->checkOrderDetailsService,
            $this->product
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

        $response = $this->saveOrderDetailsService->getSequenceNumber($location);
        expect($response)->toBeString();
    }
);
