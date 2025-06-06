<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollection\Resources\PosProductCollectionListResource;
use App\Http\Controllers\Api\Pos\ProductCollectionController;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'It calls the List query method of the product collection queries class and returns proper response',
    function (): void {
        $filterData = [
            'per_page' => null,
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'after_updated_at' => null,
        ];
        // $companyId = 1;
        $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);
        // setCompanyIdInSession($companyId);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock) use (
            $filterData
        ): void {
            $mock->shouldReceive('getPaginatedProductCollectionsForPos')
                ->once()
                ->with($filterData, 1)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $productCollectionController = new ProductCollectionController($productCollectionQueries);

        $response = $productCollectionController->getPaginatedList($request);

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(
            PosProductCollectionListResource::collection(collect([])),
            $response['product_collections']
        );
    }
);
