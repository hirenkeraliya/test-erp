<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteService;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\Inventory\Services\GoodsReceivedNoteInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\PurchaseAmount;
use App\Models\SerialNumber;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Support\Facades\Config;

test(
    'It calls generateGrnReference method and returns proper response',
    function (): void {
        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getGrnFormat')
                ->once()
                ->with(1)
                ->andReturn('GRN/');
        });

        $goodsReceivedNoteQueries = $this->mock(GoodsReceivedNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('generateGrnReference')
                ->once()
                ->with('GRN/', 1)
                ->andReturn('GRN/1');
        });

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $redirectResponse = $goodsReceivedNoteService->generateGrnReference($goodsReceivedNoteQueries, 1);
        $this->assertEquals('GRN/1', $redirectResponse);
    }
);

test(
    'It calls addProductAndInventory method and returns proper response when product variant is false',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        Config::set('app.product_variant', false);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, true);

        $goodsReceivedNoteProduct = testCommonSeedRecords();

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GoodsReceivedNoteInventoryService::class, function ($mock): void {
            $mock->shouldReceive('addInventory')
                ->once();
        });

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $goodsReceivedNoteService->addProductAndInventory(
            $goodsReceiveNote,
            $goodsReceivedNoteProduct,
            $product,
            $admin,
            1,
            null,
        );
        $this->assertTrue(true);
    }
);

test(
    'It calls addProductAndInventory method and returns proper response when product variant is true',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        Config::set('app.product_variant', true);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, true);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => true,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $goodsReceivedNoteProduct = testCommonSeedRecords();

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GoodsReceivedNoteInventoryService::class, function ($mock): void {
            $mock->shouldReceive('addInventory')
                ->once();
        });

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $goodsReceivedNoteService->addProductAndInventory(
            $goodsReceiveNote,
            $goodsReceivedNoteProduct,
            $product,
            $admin,
            1,
            null,
        );
        $this->assertTrue(true);
    }
);

test(
    'It calls addProductAndInventory method with warehouse and returns proper response when product variant is false',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_warehouse',
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        Config::set('app.product_variant', false);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, false);

        $goodsReceivedNoteProduct = testCommonSeedRecords();

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNewAndGetId');
        });

        $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GoodsReceivedNoteInventoryService::class, function ($mock): void {
            $mock->shouldReceive('addInventory')
                ->once();
        });

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $goodsReceivedNoteService->addProductAndInventory(
            $goodsReceiveNote,
            $goodsReceivedNoteProduct,
            $product,
            $admin,
            1,
            null
        );
        $this->assertTrue(true);
    }
);

test(
    'It calls addProductAndInventory method with warehouse and returns proper response when product variant is true',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_warehouse',
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        Config::set('app.product_variant', true);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, true);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $goodsReceivedNoteProduct = testCommonSeedRecords();

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNewAndGetId');
        });

        $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GoodsReceivedNoteInventoryService::class, function ($mock): void {
            $mock->shouldReceive('addInventory')
                ->once();
        });

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $goodsReceivedNoteService->addProductAndInventory(
            $goodsReceiveNote,
            $goodsReceivedNoteProduct,
            $product,
            $admin,
            1,
            null
        );
        $this->assertTrue(true);
    }
);

test(
    'It calls addProductAndInventory method with derivate product and returns proper response when product variant is false',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'name' => 'Centimeter',
            'ratio' => 100,
        ]);

        Config::set('app.product_variant', false);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, false);

        $goodsReceivedNoteProduct = testCommonSeedRecords();

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNewAndGetId');
        });

        $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GoodsReceivedNoteInventoryService::class, function ($mock): void {
            $mock->shouldReceive('addInventory')
                ->once();
        });

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $goodsReceivedNoteService->addProductAndInventory(
            $goodsReceiveNote,
            $goodsReceivedNoteProduct,
            $product,
            $admin,
            1,
            $derivative
        );
        $this->assertTrue(true);
    }
);

test(
    'It calls addProductAndInventory method with derivate product and returns proper response when product variant is true',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $derivative = UnitOfMeasureDerivative::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => 1,
            'name' => 'Centimeter',
            'ratio' => 100,
        ]);

        Config::set('app.product_variant', true);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, true);

        $masterProduct = MasterProduct::factory()->make([
            'id' => 1,
            'variant_template_id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'has_batch' => false,
            'is_non_inventory' => false,
            'department_id' => 1,
            'brand_id' => 1,
        ]);

        $product->masterProduct = $masterProduct;

        $goodsReceivedNoteProduct = testCommonSeedRecords();

        $this->mock(PurchaseAmountQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAndGetId')
                ->once();
        });

        $this->mock(BatchQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNewAndGetId');
        });

        $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(GoodsReceivedNoteInventoryService::class, function ($mock): void {
            $mock->shouldReceive('addInventory')
                ->once();
        });

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $goodsReceivedNoteService->addProductAndInventory(
            $goodsReceiveNote,
            $goodsReceivedNoteProduct,
            $product,
            $admin,
            1,
            $derivative
        );
        $this->assertTrue(true);
    }
);

function seedCommonSeedRecordsForGrnService(int $locationId, $hasBatch = false): array
{
    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $product = commonGetProductDetails($hasBatch);

    $goodsReceiveNote = GoodsReceivedNote::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'vendor_id' => 1,
        'location_id' => $locationId,
    ]);

    return [$admin, $product, $goodsReceiveNote];
}

function testCommonSeedRecords(): array
{
    return [
        'upc' => 'abd123',
        'quantity' => 10,
        'derivate_name' => null,
        'fob' => null,
        'freight_charges' => null,
        'insurance_charges' => null,
        'duty' => null,
        'sst' => null,
        'handling_charges' => null,
        'other_charges' => null,
        'batch_number' => 'a123123',
        'batch_expiry_date' => '1990-10-10',
        'batch_external_id' => null,
        'batch_notes' => null,
    ];
}

test(
    'It calls rollbackInventory method and remove inventory',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, true);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
        ]);

        $purchaseAmount = PurchaseAmount::factory()->make([
            'id' => 1,
        ]);

        $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->make([
            'goods_received_note_id' => $goodsReceiveNote->id,
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'purchase_amount_id' => $purchaseAmount->id,
        ]);

        $goodsReceiveNote->goodsReceivedNoteProducts = collect([$goodsReceivedNoteProduct]);

        $this->mock(GoodsReceivedNoteInventoryService::class, function ($mock): void {
            $mock->shouldReceive('rollbackInventoryForGRNCancellation')
                ->once();
        });

        $this->mock(GoodsReceivedNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsCancel')
                ->once();
        });

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $goodsReceivedNoteService->rollbackInventory($goodsReceiveNote, $admin, 'cancelled');
        $this->assertTrue(true);
    }
);

test(
    'If Serial number is active then return false',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, true);

        $serialNumber = SerialNumber::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'serial_number' => '123456789',
            'status' => SerialNumberStatus::ACTIVE->value,
        ]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
        ]);

        $purchaseAmount = PurchaseAmount::factory()->make([
            'id' => 1,
        ]);

        $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->make([
            'goods_received_note_id' => $goodsReceiveNote->id,
            'product_id' => $product->id,
            'serial_number_id' => $serialNumber->id,
            'batch_id' => $batch->id,
            'purchase_amount_id' => $purchaseAmount->id,
        ]);

        $goodsReceiveNote->goodsReceivedNoteProducts = collect([$goodsReceivedNoteProduct]);
        foreach ($goodsReceiveNote->goodsReceivedNoteProducts as $goodsReceivedNoteProduct) {
            $goodsReceivedNoteProduct->serialNumber = $serialNumber;
        }

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $response = $goodsReceivedNoteService->checkGoodReceivedNoteProduct($goodsReceiveNote);
        expect($response)->toBe(false);
    }
);

test(
    'If Serial number is not active then return true',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'new_store',
            'type_id' => LocationTypes::STORE->value,
        ]);

        [$admin, $product, $goodsReceiveNote] = seedCommonSeedRecordsForGrnService($location->id, true);

        $serialNumber = SerialNumber::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
            'serial_number' => '123456789',
            'status' => SerialNumberStatus::DELETED->value,
        ]);

        $batch = Batch::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'product_id' => $product->id,
        ]);

        $purchaseAmount = PurchaseAmount::factory()->make([
            'id' => 1,
        ]);

        $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->make([
            'goods_received_note_id' => $goodsReceiveNote->id,
            'product_id' => $product->id,
            'serial_number_id' => $serialNumber->id,
            'batch_id' => $batch->id,
            'purchase_amount_id' => $purchaseAmount->id,
        ]);

        $goodsReceiveNote->goodsReceivedNoteProducts = collect([$goodsReceivedNoteProduct]);
        foreach ($goodsReceiveNote->goodsReceivedNoteProducts as $goodsReceivedNoteProduct) {
            $goodsReceivedNoteProduct->serialNumber = $serialNumber;
        }

        $goodsReceivedNoteService = new GoodsReceivedNoteService();
        $response = $goodsReceivedNoteService->checkGoodReceivedNoteProduct($goodsReceiveNote);
        expect($response)->toBe(true);
    }
);
