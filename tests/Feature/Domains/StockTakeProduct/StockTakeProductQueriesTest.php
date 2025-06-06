<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTakeProduct\StockTakeProductQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\StockTake;
use App\Models\StockTakeProduct;
use App\Models\StoreManager;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->stockTake = StockTake::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $this->product = Product::factory([
        'company_id' => $this->companyId,
    ])->create();

    $this->stockTakeProduct = StockTakeProduct::factory()->create([
        'stock_take_id' => $this->stockTake->id,
        'submitted_stock' => null,
        'product_id' => $this->product->id,
    ]);

    $this->stockTakeProduct->product = $this->product;

    $this->stockTakeProductQueries = new StockTakeProductQueries();
});

test('a new stock take product with actual stock zero can be added', function (): void {
    $productId = Product::factory()->create()->id;
    $inventory = Inventory::factory()->create([
        'product_id' => $productId,
    ]);
    $stockTake = StockTake::factory()->create();

    $this->stockTakeProductQueries->addNewWithoutActualStock($productId, $stockTake);

    $this->assertDatabaseHas('stock_take_products', [
        'product_id' => $inventory->product_id,
        'actual_stock' => 0,
    ]);
});

test('the getLists method calls and returns the stock take products lists', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
    ];

    $response = $this->stockTakeProductQueries->getLists(
        $filterData,
        $this->stockTakeProduct->stock_take_id,
        $this->stockTake->location_id,
        $this->stockTake->company_id
    );

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'product_id', 'submitted_stock', 'product', 'product.color', 'product.size']);
});

test('the updateSubmittedStock method calls and update the submitted stock as expected', function (): void {
    $validatedData = [
        'stock_take_product_id' => $this->stockTakeProduct->id,
        'product_id' => $this->stockTakeProduct->product_id,
        'submitted_stock' => 10,
    ];

    $this->stockTakeProductQueries->updateSubmittedStock(
        $validatedData,
        $this->stockTakeProduct->stock_take_id,
        $this->stockTake->location_id,
        $this->stockTake->company_id
    );

    $this->assertDatabaseHas('stock_take_products', [
        'id' => $this->stockTakeProduct->id,
        'product_id' => $this->stockTakeProduct->product_id,
        'stock_take_id' => $this->stockTake->id,
        'submitted_stock' => $validatedData['submitted_stock'],
    ]);
});

test(
    'the getProductsOfSubmittedStockTake method calls and returns the submitted stock take products lists',
    function (): void {
        $stockTake = StockTake::factory()->submitted()->create([
            'company_id' => $this->companyId,
        ]);
        $stockTakeProduct = StockTakeProduct::factory()->create([
            'stock_take_id' => $stockTake->id,
            'submitted_stock' => null,
            'product_id' => $this->product->id,
        ]);
        $stockTakeProduct->product = $this->product;

        $response = $this->stockTakeProductQueries->getProductsOfSubmittedStockTake(
            $stockTakeProduct->stock_take_id,
            $stockTake->location_id,
            $this->companyId
        );

        expect($response->first()->toArray())
        ->toHaveKeys(['id', 'product_id', 'submitted_stock', 'product', 'product.color', 'product.size']);
    }
);

test(
    'the downloadStockTakeProducts method calls and returns the stock take products lists',
    function (): void {
        $stockTake = StockTake::factory([
            'company_id' => $this->companyId,
        ])->submitted()->create();
        StockTakeProduct::factory()->create([
            'stock_take_id' => $stockTake->id,
            'submitted_stock' => null,
        ]);

        $response = $this->stockTakeProductQueries->downloadStockTakeProducts(
            $stockTake->id,
            $stockTake->location_id,
            [
                'brand_ids' => null,
                'department_ids' => null,
                'color_ids' => null,
                'size_ids' => null,
            ],
            $stockTake->company_id,
        );

        expect($response->first()->toArray())
        ->toHaveKeys(['id', 'product_id', 'submitted_stock', 'product', 'product.color', 'product.size']);
    }
);

test('the bulkUpdateSubmitStock method calls and update the submitted_stock value', function (): void {
    $stockTake = StockTake::factory([
        'company_id' => $this->companyId,
    ])->submitted()->create();
    $product = Product::factory()->create();
    $stockTakeProduct = StockTakeProduct::factory()->create([
        'product_id' => $product->id,
        'stock_take_id' => $stockTake->id,
        'submitted_stock' => null,
    ]);

    $records = [
        'product_id' => $product->id,
        'submitted_stock' => 100,
        'stock_take_id' => $stockTake->id,
    ];

    $this->stockTakeProductQueries->bulkUpdateSubmitStock($records);

    $this->assertDatabaseHas('stock_take_products', [
        'id' => $stockTakeProduct->id,
        'submitted_stock' => 100,
    ]);
});

test(
    'the getSubmittedStockTakeProductsByStockTakeId method calls and returns the submitted stock take products lists',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyId,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);

            $this->product->master_product_id = $masterProduct->id;
            $this->product->save();
            $this->product->refresh();
            $this->product->masterProduct = $masterProduct;
            $this->product->masterProduct->product = $this->product;
        }

        $stockTake = StockTake::factory([
            'company_id' => $this->companyId,
        ])->submitted()->create();
        $stockTakeProduct = StockTakeProduct::factory()->create([
            'stock_take_id' => $stockTake->id,
            'submitted_stock' => null,
            'product_id' => $this->product->id,
        ]);
        $stockTakeProduct->product = $this->product;

        $response = $this->stockTakeProductQueries->getSubmittedStockTakeProductsByStockTakeId(
            $stockTake->id,
            $this->companyId
        );

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKeys(
                    [
                        'id',
                        'product_id',
                        'submitted_stock',
                        'product',
                        'product.master_product',
                        'product.product_variant_values',
                    ]
                );
        } else {
            expect($response->first()->toArray())
                ->toHaveKeys(['id', 'product_id', 'submitted_stock', 'product', 'product.color', 'product.size']);
        }
    }
)->with([[true], [false]]);

test(
    'the getPendingStockProductsSubmissionCount method calls and returns the pending submitted stock take products count',
    function (): void {
        $locationId = Location::factory([
            'company_id' => $this->companyId,
        ])->create()->id;
        $stockTake = StockTake::factory()->submitted()->create([
            'company_id' => $this->companyId,
            'location_id' => $locationId,
        ]);

        StockTakeProduct::factory()->create([
            'stock_take_id' => $stockTake->id,
            'submitted_stock' => 0,
        ]);

        $response = $this->stockTakeProductQueries->getPendingStockProductsSubmissionCount(
            $stockTake->id,
            $locationId,
            $stockTake->company_id,
        );
        $this->assertEquals(1, $response);
    }
);

test(
    'the updateSubmittedStockByStockId method calls and update the submitted stock by stock-take id',
    function (): void {
        $employee = Employee::factory()->create([
            'company_id' => $this->location->company_id,
        ]);

        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);
        $stockTake = StockTake::factory()->create([
            'company_id' => $this->location->company_id,
            'requested_by_id' => $storeManager->id,
            'requested_by_type' => ModelMapping::STORE_MANAGER->name,
            'location_id' => $this->location->id,
        ]);

        $productId = Product::factory()->create()->id;

        $stockTakeProduct = StockTakeProduct::factory()->create([
            'stock_take_id' => $stockTake->id,
            'product_id' => $productId,
            'submitted_stock' => null,
        ]);

        $validatedData = [
            'product_id' => $stockTakeProduct->product_id,
            'submitted_stock' => 10,
        ];

        $this->stockTakeProductQueries->updateSubmittedStockByStockId(
            $validatedData,
            $stockTake->id,
            $stockTake->location_id,
            $stockTake->company_id,
        );

        $this->stockTakeProduct->refresh();

        $this->assertDatabaseHas('stock_take_products', [
            'id' => $stockTakeProduct->id,
            'stock_take_id' => $stockTake->id,
            'submitted_stock' => $validatedData['submitted_stock'],
        ]);
    }
);

test('if product is merged then the product id is updated', function (): void {
    $productAId = Product::factory()->create([
        'company_id' => $this->companyId,
    ])->id;
    $productBId = Product::factory()->create([
        'company_id' => $this->companyId,
    ])->id;

    $stockTake = StockTake::factory()->create([
        'company_id' => $this->companyId,
        'location_id' => $this->location->id,
    ]);

    StockTakeProduct::factory()->create([
        'stock_take_id' => $stockTake->id,
        'product_id' => $productBId,
        'submitted_stock' => null,
    ]);

    $this->stockTakeProductQueries->updateProductId($this->companyId, $productBId, $productAId);

    $this->assertDatabaseHas('stock_take_products', [
        'stock_take_id' => $stockTake->id,
        'product_id' => $productAId,
    ]);
});
