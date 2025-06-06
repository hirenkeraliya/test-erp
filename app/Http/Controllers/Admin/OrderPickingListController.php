<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Order\Enums\OrderPickingStatus;
use App\Domains\Order\OrderQueries;
use App\Domains\Order\Services\PrintNinjaVanWayBillService;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderPickingList\OrderPickingListQueries;
use App\Domains\OrderPickingList\Resources\OrderPickingListResource;
use App\Domains\OrderPickingList\Services\OrderPickingListService;
use App\Domains\OrderPickingList\Services\PrintOrderPackingListService;
use App\Domains\OrderPickingList\Services\PrintOrderPickingListService;
use App\Domains\OrderPickingListItem\OrderPickingListItemQueries;
use App\Domains\OrderPickingListItem\Resources\OrderPickingListItemResource;
use App\Domains\OrderPickingListItem\Resources\OrderPickingListOrderDetailResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderPickingListController extends Controller
{
    public function __construct(
        protected OrderPickingListQueries $orderPickingListQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('order_picking_lists/Index', [
            'orderPickingStatusStaticUse' => OrderPickingStatus::getFormattedArrayForStaticUse(),
        ]);
    }

    public function fetchOrderPickingLists(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->orderPickingListQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => OrderPickingListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function store(Request $request): void
    {
        $orderPickingListService = resolve(OrderPickingListService::class);
        $orderPickingListService->checkOrderPickingList((array) $request->get('order_ids'));

        $orderPickingListService->addOrderPickingList((array) $request->get('order_ids'), session('admin_company_id'));
    }

    public function fetchOrderItemsByOrderPickingId(int $orderPickingId): array
    {
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderPickingItemDetails = $orderItemQueries->getOrderPickingListItemsBy($orderPickingId);

        $orderPickingListItemQueries = resolve(OrderPickingListItemQueries::class);
        $orderDetails = $orderPickingListItemQueries->getOrderPickingListForOrder(
            $orderPickingId,
            session('admin_company_id')
        );

        return [
            'order_details' => OrderPickingListOrderDetailResource::collection($orderDetails),
            'item_details' => OrderPickingListItemResource::collection($orderPickingItemDetails),
        ];
    }

    public function printOrderPackaging(int $orderPickingListId): string
    {
        $printOrderPickingListService = resolve(PrintOrderPickingListService::class);

        return $printOrderPickingListService->print($orderPickingListId, session('admin_company_id'));
    }

    public function printOrderPackingList(int $orderPickingListId): string
    {
        $printOrderPickingListService = resolve(PrintOrderPackingListService::class);

        return $printOrderPickingListService->print($orderPickingListId, session('admin_company_id'));
    }

    public function inprogress(int $orderPickingListId): void
    {
        $this->orderPickingListQueries->inprogress($orderPickingListId);
    }

    public function cancel(int $orderPickingListId): void
    {
        $this->orderPickingListQueries->cancel($orderPickingListId);
        $orderQueries = resolve(OrderQueries::class);
        $orderQueries->markAsAccepted($orderPickingListId);
    }

    public function completed(int $orderPickingListId): void
    {
        $this->orderPickingListQueries->completed($orderPickingListId);
        $orderQueries = resolve(OrderQueries::class);
        $orderQueries->markAsReadyForPickup($orderPickingListId);
    }

    public function printNinjaVanWayBills(int $orderPickingListId): string
    {
        $orderPickingListItemQueries = resolve(OrderPickingListItemQueries::class);
        $ninjaVanWayBillService = resolve(PrintNinjaVanWayBillService::class);

        $orderPickingListItems = $orderPickingListItemQueries->getOrderPickingListForOrderIds(
            $orderPickingListId,
            session('admin_company_id')
        );

        return $ninjaVanWayBillService->print($orderPickingListItems->pluck('order_id')->toArray());
    }
}
