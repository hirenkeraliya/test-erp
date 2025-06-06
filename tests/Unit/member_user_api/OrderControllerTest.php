<?php

declare(strict_types=1);

use App\Domains\Member\Resources\MemberOrderDetailsApiResource;
use App\Domains\Order\OrderQueries;
use App\Http\Controllers\Api\Member\OrderController;
use App\Models\Member;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'it calls the getPaginatedOrderListForMemberApi method and returns the paginated list of member orders',
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

        $this->mock(OrderQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedOrderListForMemberApi')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $orderController = new OrderController();
        $response = $orderController->getPaginatedOrderList($request);
        expect($response['orders'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);

test(
    'it calls the getOrderDetails method and returns the list of member order items details',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'created_location_id' => 1,
            'company_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Member => $member);

        $this->mock(OrderQueries::class, function ($mock): void {
            $mock->shouldReceive('getOrderDetailsById')
                ->once()
                ->andReturn(new Order());
        });

        $orderController = new OrderController();
        $response = $orderController->getOrderDetails($request, 1);
        expect($response['order'])->toBeInstanceOf(MemberOrderDetailsApiResource::class);
    }
);
