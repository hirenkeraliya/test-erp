<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\OrderReturn\DataObjects\OrderReturnData;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\OrderReturn\Resources\OrderReturnItemsReportResource;
use App\Domains\OrderReturn\Resources\OrderReturnListResource;
use App\Domains\OrderReturn\Resources\OrderReturnReceiptResource;
use App\Domains\OrderReturn\Services\CheckOrderReturnDetailsService;
use App\Domains\OrderReturn\Services\SaveOrderReturnDetailsService;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\OrderPaymentTypeListResource;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrderReturnController extends Controller
{
    public function __construct(
        protected OrderReturnQueries $orderReturnQueries
    ) {
    }

    public function store(Request $request, OrderReturnData $orderReturnData): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $items = collect($orderReturnData->order_return_items);

        $checkOrderReturnDetailsService = resolve(CheckOrderReturnDetailsService::class);

        $checkOrderReturnDetailsService->setDetails($storeManager, $orderReturnData, $items, $companyId, $locationId);
        $checkOrderReturnDetailsService->checkRequestDetails();

        $member = $checkOrderReturnDetailsService->member;

        DB::beginTransaction();

        try {
            $saveOrderReturnDetailsService = resolve(SaveOrderReturnDetailsService::class);
            $orderReturn = $saveOrderReturnDetailsService->saveOrderReturnDetails(
                $storeManager,
                $checkOrderReturnDetailsService,
                $member?->id,
            );

            DB::commit();

            return [
                'order_return' => new OrderReturnReceiptResource($orderReturn),
            ];
        } catch (Throwable $throwable) {
            Log::error('Placing Order Return Error', [
                'Order Returns' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function index(Request $request): Response
    {
        $companyId = session('store_manager_selected_location_company_id');

        $orderReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $cancelOrderReasonQueries = resolve(VoidSaleReasonQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentTypesList = $paymentTypeQueries->getActiveOnlyWithSubPaymentTypes($companyId);

        $paymentTypesList = $paymentTypesList->whereNotIn('id', [
            StaticPaymentTypes::BOOKING_PAYMENT->value,
            StaticPaymentTypes::CREDIT_NOTE->value,
            StaticPaymentTypes::LOYALTY_POINT->value,
            StaticPaymentTypes::GIFT_CARD->value,
        ])->sort()->values();

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locations = $storeManagerQueries->getStoreManagerStores($storeManager);
        $locationId = session('store_manager_selected_location_id');

        return Inertia::render('orders/OrderReturn', [
            'orderTypesStaticUse' => OrderTypes::getFormattedArrayForStaticUse(),
            'orderReturnReasons' => $orderReturnReasonQueries->getListForPOSOrOrders(
                session('store_manager_selected_location_company_id'),
                SaleReturnOrVoidSaleReasonTypes::ORDERS->value
            ),
            'cancelOrderReasons' => $cancelOrderReasonQueries->getListForPOSOrOrders(
                session('store_manager_selected_location_company_id'),
                SaleReturnOrVoidSaleReasonTypes::ORDERS->value
            ),
            'paymentTypes' => OrderPaymentTypeListResource::collection($paymentTypesList)->toArray($request),
            'locations' => $locations,
            'locationId' => $locationId,
            'exportPermission' => PermissionList::getExportPermissionName('order_return'),
        ]);
    }

    public function fetchOrderReturns(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = (int) $request->location_id;

        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
        ];

        $orderReturnsData = $this->orderReturnQueries->getPaginatedCompleteOrderWithRelations(
            $filterData,
            $storeManager->getKey(),
            $locationId
        );

        $consolidatedSales = $this->orderReturnQueries->getFilteredTotalsForReport(
            $filterData,
            $storeManager->getKey(),
            $locationId
        )->first()?->toArray();

        $consolidatedSales = null === $consolidatedSales ? null : head($consolidatedSales['order_return_items']);

        return [
            'data' => OrderReturnListResource::collection($orderReturnsData->getCollection()),
            'total_records' => $orderReturnsData->total(),
            'total_units_sold' => $consolidatedSales['total_units_sold'] ?? 0,
            'total_order_returns' => $consolidatedSales['total_order_returns'] ?? 0,
            'total_order_returns_amount' => $consolidatedSales['total_order_returns_amount'] ?? 0,
        ];
    }

    public function fetchOrderReturnItems(int $orderReturnId): array
    {
        $orderReturnDetails = $this->orderReturnQueries->getOrderReturnItemsForStoreManager(
            $orderReturnId,
            session('store_manager_selected_location_id'),
        );

        return [
            'order_return_details' => new OrderReturnItemsReportResource($orderReturnDetails),
        ];
    }

    public function fetchOrderReturnsForReceipt(int $orderReturnId): array
    {
        $orderReturnDetails = $this->orderReturnQueries->getOrderReturnReceiptForStoreManager(
            $orderReturnId,
            session('store_manager_selected_location_id'),
        );

        return [
            'order_return_details' => new OrderReturnReceiptResource($orderReturnDetails),
        ];
    }
}
