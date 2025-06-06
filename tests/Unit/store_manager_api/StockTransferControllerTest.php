<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\DataObjects\StoreManagerStockTransferData;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\DataObjects\StoreManagerStockTransferItemData;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Api\StoreManager\StockTransferController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
    'calls the getPaginatedStockTransfers and returns stock transfers records',
    function (): void {
        $filterData = [
            'per_page' => 10,
            'page' => 1,
            'id' => $this->location->id,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
        ];

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerStockTransferData = new StoreManagerStockTransferData(...$filterData);

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

        $this->mock(StockTransferQueries::class, function ($mock): void {
            $mock->shouldReceive('storeManagerListQueryForApi')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $stockTransferController = new StockTransferController();

        $response = $stockTransferController->getPaginatedStockTransfers($request, $storeManagerStockTransferData);

        expect($response['data']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getPaginatedStockTransfers throws an Exception when the store manager specify a different location',
    function (): void {
        $filterData = [
            'per_page' => 10,
            'page' => 1,
            'id' => $this->location->id,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
        ];

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerStockTransferData = new StoreManagerStockTransferData(...$filterData);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $stockTransferController = new StockTransferController();

        $response = $stockTransferController->getPaginatedStockTransfers($request, $storeManagerStockTransferData);

        expect($response['data']->collection)->toBeInstanceOf(Collection::class);
    }
)->throws(HttpException::class);

test(
    'calls the getStockTransferItemsByStockTransferId and returns stock transfer items',
    function (): void {
        $filterData = [
            'per_page' => 10,
            'page' => 1,
            'store_id' => $this->location->id,
            'location_id' => $this->location->id,
            'id' => 1,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerStockTransferItemData = new StoreManagerStockTransferItemData(...$filterData);

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

        $this->mock(StockTransferItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getByPaginatedStockTransferId')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $stockTransferController = new StockTransferController();

        $response = $stockTransferController->getStockTransferItemsByStockTransferId(
            $request,
            $storeManagerStockTransferItemData
        );

        expect($response['stock_transfer_items']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getStockTransferItemsByStockTransferId throws an Exception when the store manager specify a different location',
    function (): void {
        $filterData = [
            'per_page' => 10,
            'page' => 1,
            'store_id' => $this->location->id,
            'location_id' => $this->location->id,
            'id' => 1,
        ];

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerStockTransferItemData = new StoreManagerStockTransferItemData(...$filterData);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $stockTransferController = new StockTransferController();

        $stockTransferController->getStockTransferItemsByStockTransferId($request, $storeManagerStockTransferItemData);
    }
)->throws(HttpException::class);

test('calls the getTransferTypes and returns transfer types ', function (): void {
    $stockTransferController = new StockTransferController();
    $response = $stockTransferController->getTransferTypes();
    expect($response['transfer_types'][0])
        ->toHaveKeys(['id', 'name', 'key']);
});

test(
    'calls the getStatusList and returns status list',
    function (): void {
        $stockTransferController = new StockTransferController();
        $response = $stockTransferController->getStatusList();
        expect($response['status_list'][0])
            ->toHaveKeys(['id', 'name', 'key']);
    }
);
