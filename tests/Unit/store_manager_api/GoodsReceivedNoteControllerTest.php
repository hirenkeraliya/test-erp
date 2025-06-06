<?php

declare(strict_types=1);

use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteStoreForStoreManagerAppData;
use App\Domains\GoodsReceivedNote\DataObjects\StoreManagerApiGoodsReceivedNoteData;
use App\Domains\GoodsReceivedNote\DataObjects\StoreManagerApiGoodsReceivedNoteProductData;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteCheckRequestService;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteService;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Api\StoreManager\GoodsReceivedNoteController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\GoodsReceivedNote;
use App\Models\ImportRecord;
use App\Models\Location;
use App\Models\StoreManager;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
});

test(
    'calls the getGoodsReceivedNotes method and returns goods received notes list with pagination',
    function (): void {
        $filterData = [
            'store_id' => $this->location->id,
            'location_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
        ];

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdOfStore')
                ->once()
                ->with((int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(GoodsReceivedNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('listQueryForStoreManagerApi')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiGoodsReceivedNoteData = new StoreManagerApiGoodsReceivedNoteData(...$filterData);

        $goodsReceivedNoteController = new GoodsReceivedNoteController();

        $response = $goodsReceivedNoteController->getGoodsReceivedNotes(
            $request,
            $storeManagerApiGoodsReceivedNoteData
        );

        expect($response['data']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getGoodsReceivedNotes method throws an Exception when the store manager specify a different location',
    function (): void {
        $filterData = [
            'store_id' => $this->location->id,
            'location_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
        ];

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiGoodsReceivedNoteData = new StoreManagerApiGoodsReceivedNoteData(...$filterData);

        $goodsReceivedNoteController = new GoodsReceivedNoteController();

        $goodsReceivedNoteController->getGoodsReceivedNotes($request, $storeManagerApiGoodsReceivedNoteData);
    }
)->throws(HttpException::class);

test(
    'getGoodsReceivedNotes method throws an exception when the store manager selects an end date before the start date',
    function (): void {
        $filterData = [
            'store_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subYear()->format('Y-m-d'),
        ];

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);
        $request->validate(StoreManagerApiGoodsReceivedNoteData::rules());

        $storeManagerApiGoodsReceivedNoteData = new StoreManagerApiGoodsReceivedNoteData(...$filterData);

        $goodsReceivedNoteController = new GoodsReceivedNoteController();

        $goodsReceivedNoteController->getGoodsReceivedNotes($request, $storeManagerApiGoodsReceivedNoteData);
    }
)->throws(ValidationException::class);

test(
    'calls the getGoodsReceivedNoteProducts method and returns goods received note products list with pagination',
    function (): void {
        $goodReceivedNote = GoodsReceivedNote::factory()->make([
            'id' => 1,
            'company_id' => $this->company->id,
            'vendor_id' => 1,
            'grn_reference' => 1,
            'purchase_order_reference' => 1,
            'delivery_order_reference' => 1,
        ]);

        $filterData = [
            'id' => $goodReceivedNote->id,
            'store_id' => $this->location->id,
            'location_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
        ];

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdOfStore')
                ->once()
                ->with((int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(GoodsReceivedNoteProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getByGrnIdForApi')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 1, 15));
        });

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiGoodsReceivedNoteProductData = new StoreManagerApiGoodsReceivedNoteProductData(...$filterData);

        $goodsReceivedNoteController = new GoodsReceivedNoteController();

        $response = $goodsReceivedNoteController->getGoodsReceivedNoteProducts(
            $request,
            $storeManagerApiGoodsReceivedNoteProductData
        );

        expect($response['data']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getGoodsReceivedNoteProducts method throws an Exception when the store manager specify a different location',
    function (): void {
        $goodReceivedNote = GoodsReceivedNote::factory()->make([
            'id' => 1,
            'company_id' => $this->company->id,
            'vendor_id' => 1,
            'grn_reference' => 1,
            'purchase_order_reference' => 1,
            'delivery_order_reference' => 1,
        ]);

        $filterData = [
            'id' => $goodReceivedNote->id,
            'store_id' => $this->location->id,
            'location_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
        ];

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiGoodsReceivedNoteProductData = new StoreManagerApiGoodsReceivedNoteProductData(...$filterData);

        $goodsReceivedNoteController = new GoodsReceivedNoteController();

        $goodsReceivedNoteController->getGoodsReceivedNoteProducts(
            $request,
            $storeManagerApiGoodsReceivedNoteProductData
        );
    }
)->throws(HttpException::class);

function getGoodsReceivedNoteProductDataForStoreManager(): GoodsReceivedNoteStoreForStoreManagerAppData
{
    $goodsReceivedNoteProducts = [
        'location_id' => 1,
        'purchase_order_reference' => 'do',
        'delivery_order_reference' => 'po',
        'notes' => 'test_notes',
        'vendor_id' => 1,
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            false
        ),
    ];

    return new GoodsReceivedNoteStoreForStoreManagerAppData(...$goodsReceivedNoteProducts);
}

test('It calls the store method and store successfully', function (): void {
    Queue::fake();

    $vendor = Vendor::factory()->make([
        'company_id' => $this->company->id,
    ]);

    $goodsReceivedNoteData = getGoodsReceivedNoteProductDataForStoreManager();

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfStore')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(VendorQueries::class, function ($mock) use ($vendor): void {
        $mock->shouldReceive('getByIdAndCompanyId')
            ->once()
            ->andReturn($vendor);
    });

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(GoodsReceivedNoteService::class, function ($mock): void {
        $mock->shouldReceive('generateGrnReference')
            ->once()
            ->andReturn(1);
    });

    $this->mock(GoodsReceivedNoteQueries::class, function ($mock) use ($goodsReceivedNoteData): void {
        $mock->shouldReceive('addNewForInternalApplication')
            ->once()
            ->with($goodsReceivedNoteData, 1, 1, $this->storeManager)
            ->andReturn(new GoodsReceivedNote());
    });

    $this->mock(GoodsReceivedNoteCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('validateGrnReference')
            ->once();
    });

    $this->mock(ImportRecordQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn(new ImportRecord());
    });

    $goodsReceivedNoteController = new GoodsReceivedNoteController();
    $goodsReceivedNoteController->store($request, $goodsReceivedNoteData);

    Queue::assertPushed(ImportRecordsJob::class);
});

test('store method throws an Exception when the vendor not found', function (): void {
    $goodsReceivedNoteData = getGoodsReceivedNoteProductDataForStoreManager();

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfStore')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(VendorQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdAndCompanyId')
            ->once()
            ->andReturn(null);
    });

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $goodsReceivedNoteController = new GoodsReceivedNoteController();
    $goodsReceivedNoteController->store($request, $goodsReceivedNoteData);
})->throws(HttpException::class);

test('store method throws an Exception when the store manager specify a different location', function (): void {
    $goodsReceivedNoteData = getGoodsReceivedNoteProductDataForStoreManager();

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(false);
    });

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $goodsReceivedNoteController = new GoodsReceivedNoteController();
    $goodsReceivedNoteController->store($request, $goodsReceivedNoteData);
})->throws(HttpException::class);
