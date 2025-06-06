<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ProductCollection\DataObjects\ProductCollectionData;
use App\Domains\ProductCollection\DataObjects\ProductCollectionImagesData;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollection\Jobs\CreateUpdateProductCollectionJob;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollection\Resources\ProductCollectionResource;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Http\Controllers\Admin\ProductCollectionController;
use App\Models\Admin;
use App\Models\ImportRecord;
use App\Models\ProductCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

test(
    'It calls the List query method of the product collection queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $filterData = [
            'search_text' => null,
            'per_page' => 10,
        ];

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock) use (
            $filterData
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($filterData, 1)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $productCollectionController = new ProductCollectionController($productCollectionQueries);

        $response = $productCollectionController->fetchProductCollections(new Request($filterData));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(ProductCollectionResource::collection(collect([])), $response['data']);
    }
);

test(
    'It calls the addNew method of the product collection queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        Queue::fake();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);
        $this->actingAs($admin);

        $productCollection = [
            'name' => 'ABC',
            'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
            'collection_filter_types' => [],
        ];

        $productCollectionData = new ProductCollectionData(...$productCollection);

        $productCollection = ProductCollection::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock) use (
            $productCollection
        ): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($productCollection);
        });

        $importRecord = ImportRecord::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_by_id' => 1,
        ]);

        $this->mock(ImportRecordQueries::class, function ($mock) use ($importRecord): void {
            $mock->shouldReceive('addNewForProductCollection')
                ->once()
                ->andReturn($importRecord);
        });

        $productCollectionController = new ProductCollectionController($productCollectionQueries);
        $redirectResponse = $productCollectionController->store($productCollectionData);
        Queue::assertPushed(CreateUpdateProductCollectionJob::class);
        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'Product collection added successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/product-collection', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the update method of the product collection queries class and returns proper response',
    function (): void {
        Bus::fake();
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);
        $this->actingAs($admin);

        $productCollectionId = 1;

        $productCollectionRecord = ProductCollection::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $importRecord = ImportRecord::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => ImportTypes::PRODUCT_COLLECTION->value,
            'status' => Status::COMPLETED->value,
            'created_by_id' => 1,
            'created_by_type' => ModelMapping::ADMIN->name,
            'module_id' => 1,
            'module_type' => ModelMapping::PRODUCT_COLLECTION->name,
        ]);

        $productCollectionRecord->importRecord = $importRecord;

        $productCollection = [
            'name' => 'ABC',
            'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
            'collection_filter_types' => [],
        ];

        $productCollectionData = new ProductCollectionData(...$productCollection);

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock) use (
            $productCollectionRecord
        ): void {
            $mock->shouldReceive('getByIdWithRelation')
                ->once()
                ->andReturn($productCollectionRecord);

            $mock->shouldReceive('update')
                ->once();
        });

        $this->mock(ProductCollectionProductQueries::class, function ($mock): void {
            $mock->shouldReceive('removeByProductCollectionId')
                ->once();
        });

        $this->mock(ImportRecordQueries::class, function ($mock) use ($importRecord): void {
            $mock->shouldReceive('addNewForProductCollection')
                ->once()
                ->andReturn($importRecord);
        });

        $productCollectionController = new ProductCollectionController($productCollectionQueries);
        $redirectResponse = $productCollectionController->update($productCollectionData, $productCollectionId);
        Bus::assertDispatched(CreateUpdateProductCollectionJob::class);
        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'Product collection updated successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/product-collection', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the change status method of the product collection queries class and returns proper response',
    function (): void {
        $productCollectionId = [
            'productCollectionId' => 1,
        ];

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock): void {
            $mock->shouldReceive('changeStatus')
                ->once();
        });

        $productCollectionController = new ProductCollectionController($productCollectionQueries);
        $redirectResponse = $productCollectionController->changeStatus(new Request($productCollectionId));

        expect($redirectResponse)->toBe(null);
    }
);

test(
    'It calls the delete method of the product collection queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $productCollectionId = 1;

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock): void {
            $mock->shouldReceive('delete')
                ->once();
        });

        $productCollectionController = new ProductCollectionController($productCollectionQueries);
        $redirectResponse = $productCollectionController->delete($productCollectionId);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'Product Collection deleted successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/product-collection', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the uploadImages method of the product collection queries class and returns proper response',
    function (): void {
        $productCollectionImageData = [
            'square_image' => null,
            'portrait_images' => [],
            'landscape_images' => [],
        ];

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->once();
            $mock->shouldReceive('uploadImages')
                ->once();
        });

        $productCollectionController = new ProductCollectionController($productCollectionQueries);
        $redirectResponse = $productCollectionController->uploadImages(
            new ProductCollectionImagesData(...$productCollectionImageData),
            1
        );

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Images upload successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/product-collection', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the getFilteredProductCollections method of the product collection queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getFilteredProductCollectionsByCompanyId')
                ->once();
        });

        $request = new Request([
            'search_text' => 'ABC',
        ]);

        $productCollectionController = new ProductCollectionController($productCollectionQueries);
        $response = $productCollectionController->getFilteredProductCollections($request);

        expect($response)->toHaveKey('productCollections');
    }
);
