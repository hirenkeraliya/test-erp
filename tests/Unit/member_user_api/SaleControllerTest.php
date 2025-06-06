<?php

declare(strict_types=1);

use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Api\Member\SaleController;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

test(
    'it calls the getPaginatedSaleList method and returns the paginated list of member sales',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'created_location_id' => 1,
            'company_id' => 1,
        ]);

        $filterData = [
            'per_page' => 1,
        ];

        $request = new Request($filterData);

        $request->setUserResolver(fn (): Member => $member);

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedSaleListForMemberApi')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $saleController = new SaleController();
        $response = $saleController->getPaginatedSaleList($request);
        expect($response['sales'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);

test(
    'it calls the getSaleDetails method and returns the list of member sale items details',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'created_location_id' => 1,
            'company_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Member => $member);

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getSaleDetailsById')
            ->once()
            ->andReturn(new Collection());
        });

        $saleController = new SaleController();
        $response = $saleController->getSaleDetails($request, 1);
        expect($response['sales'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);
