<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\Member\Resources\MemberOrderDetailsApiResource;
use App\Domains\Member\Resources\MemberOrderListApiResource;
use App\Domains\Order\OrderQueries;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedOrderList(Request $request): array
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,receipt_number'],
            'sort_direction' => ['sometimes', 'string'],
        ]);

        $filteredData = [
            'per_page' => $request->per_page,
            'sort_by' => $request->sort_by,
            'sort_direction' => $request->sort_direction,
        ];

        /** @var Member $member */
        $member = $request->user();

        $orderQueries = resolve(OrderQueries::class);
        $orders = $orderQueries->getPaginatedOrderListForMemberApi($filteredData, $member->id);

        return [
            'orders' => MemberOrderListApiResource::collection($orders),
            'total_records' => $orders->total(),
            'last_page' => $orders->lastPage(),
            'current_page' => $orders->currentPage(),
            'per_page' => $orders->perPage(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrderDetails(Request $request, int $orderId): array
    {
        /** @var Member $member */
        $member = $request->user();

        $orderQueries = resolve(OrderQueries::class);
        $order = $orderQueries->getOrderDetailsById($orderId, $member->id);

        return [
            'order' => new MemberOrderDetailsApiResource($order),
        ];
    }
}
