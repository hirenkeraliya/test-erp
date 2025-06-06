<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Batch\BatchQueries;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Types;
use App\Domains\Order\DataObjects\CancelOrderData;
use App\Domains\Order\DataObjects\CompleteCreditOrderData;
use App\Domains\Order\DataObjects\CompleteLayawayOrderData;
use App\Domains\Order\DataObjects\OrderData;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Exports\OrderExport;
use App\Domains\Order\OrderQueries;
use App\Domains\Order\Resources\MarketPlaceOrderListResource;
use App\Domains\Order\Resources\OrderListResource;
use App\Domains\Order\Resources\OrderReceiptResource;
use App\Domains\Order\Services\CancelOrderService;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Domains\Order\Services\OrderService;
use App\Domains\Order\Services\PrintCreditOrderReportService;
use App\Domains\Order\Services\PrintLayawayOrderReportService;
use App\Domains\Order\Services\PrintNinjaVanWayBillService;
use App\Domains\Order\Services\PrintOrderTaxInvoiceService;
use App\Domains\Order\Services\PrintPurchaseOrderService;
use App\Domains\Order\Services\SaveOrderDetailsService;
use App\Domains\OrderAddress\Enums\OrderAddressesType;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderAddress\Resources\OrderAddressResource;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderItem\Resources\OrderItemsReportResource;
use App\Domains\OrderItem\Resources\OrderReturnDetailsResource;
use App\Domains\OrderPayment\OrderPaymentQueries;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\OrderPaymentTypeListResource;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\ReservedStock\Services\OrderReservedStockService;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Order;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        protected OrderQueries $orderQueries
    ) {
    }

    public function create(Request $request): Response
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        if (! $storeManager->can_manage_wholesale) {
            throw new RedirectBackWithErrorException(
                'Sorry!, your not allowed to manage the order. please contact admin.'
            );
        }

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $complimentaryItemReasonQueries = resolve(ComplimentaryItemReasonQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationSalesTaxPercentage($locationId);
        /** @var Company $company */
        $company = $location->company;

        $promoters = $promoterQueries->getPromoterListForPosAndOrders(
            $locationId,
            $company->id,
            SaleReturnOrVoidSaleReasonTypes::ORDERS->value
        );

        $promoters->transform(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentTypesList = $paymentTypeQueries->getActiveOnlyWithSubPaymentTypes($companyId);

        $paymentTypesList = $paymentTypesList->whereNotIn('id', [
            StaticPaymentTypes::BOOKING_PAYMENT->value,
            StaticPaymentTypes::CREDIT_NOTE->value,
            StaticPaymentTypes::LOYALTY_POINT->value,
            StaticPaymentTypes::GIFT_CARD->value,
        ])->sort()->values();

        $roundOffConfiguration = resolve(RoundOffConfiguration::class);

        return Inertia::render('orders/Create', [
            'promoters' => $promoters,
            'details' => [
                'sale_tax_percentage' => $location->sales_tax_percentage ?? 0,
                'price_override_limit_percentage_for_item' => $storeManager->price_override_limit_percentage_for_item ?? 0,
                'price_override_limit_percentage_for_cart' => $storeManager->price_override_limit_percentage_for_cart ?? 0,
                'is_bill_reference_number_mandatory' => $company->is_bill_reference_number_mandatory,
                'allow_price_override_cart_level' => $company->allow_price_override_cart_level,
                'min_promoters_per_item' => $company->min_promoters_per_item,
                'round_off_configurations' => $roundOffConfiguration->getList(),
                'discount_applicable_type' => $company->discount_applicable_type,
                'additional_discount_on_already_discounted_prices' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
                'location_id' => session('store_manager_selected_location_id'),
                'item_wise_price_override' => $storeManager->price_override_type,
            ],
            'priceOverrideTypes' => PriceOverrideTypes::getFormattedArrayForStaticUse(),
            'channelType' => OrderChannels::B2B_ORDERS->value,
            'orderType' => OrderTypes::REGULAR_ORDER->value,
            'memberTypeCorporate' => Types::CORPORATE->value,
            'complimentaryItemReasons' => $complimentaryItemReasonQueries->getList(
                session('store_manager_selected_location_company_id')
            ),
            'staticUsePaymentTypes' => StaticPaymentTypes::getFormattedArrayForStaticUse(),
            'staticUseProductTypes' => ProductTypes::getFormattedArrayForStaticUse(),
            'paymentTypes' => OrderPaymentTypeListResource::collection($paymentTypesList)->toArray($request),
        ]);
    }

    public function b2bOrders(Request $request): Response
    {
        [$locations, $locationId, $paymentTypesList] = $this->getCommonData($request);

        $orderReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $cancelOrderReasonQueries = resolve(VoidSaleReasonQueries::class);

        return Inertia::render('orders/B2bOrder', [
            'orderTypes' => OrderTypes::getList(),
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
            'exportPermission' => PermissionList::getExportPermissionName('order'),
        ]);
    }

    public function saveDetails(Request $request, OrderData $orderData): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $items = collect($orderData->order_items);

        $productQueries = resolve(ProductQueries::class);
        $checkOrderDetailService = resolve(CheckOrderDetailsService::class);

        $products = $productQueries->getByIdsWithBrandAndCategories($items->pluck('id')->toArray(), $companyId);

        $batchQueries = resolve(BatchQueries::class);
        $batches = $batchQueries->getByProductIds($items->pluck('id')->toArray(), $companyId);
        $location = $checkOrderDetailService->getCurrentLocation($locationId, $companyId);

        $checkOrderDetailService->setDetails(
            $storeManager,
            $orderData,
            $products,
            $batches,
            $items,
            $location,
            $companyId
        );

        $checkOrderDetailService->checkRequestDetails();

        $member = $checkOrderDetailService->member;

        DB::beginTransaction();

        try {
            $saveOrderDetailsService = resolve(SaveOrderDetailsService::class);
            $order = $saveOrderDetailsService->saveDetails(
                $storeManager,
                $checkOrderDetailService,
                $locationId,
                $member?->id,
                null,
            );

            DB::commit();

            return [
                'order' => $order instanceof Order ? new OrderReceiptResource($order) : null,
            ];
        } catch (Throwable $throwable) {
            Log::error('Placing Order Error', [
                'Orders' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function fetchB2bOrders(Request $request): array
    {
        [$ordersData, $consolidatedSales] = $this->prepareData($request);

        return [
            'data' => OrderListResource::collection($ordersData->getCollection()),
            'total_records' => $ordersData->total(),
            'total_units_sold' => $consolidatedSales['total_units_sold'] ?? 0,
            'total_orders' => $consolidatedSales['total_orders'] ?? 0,
            'total_orders_amount' => $consolidatedSales['total_orders_amount'] ?? 0,
        ];
    }

    public function marketplacesOrders(Request $request): Response
    {
        $orderService = resolve(OrderService::class);
        $paymentTypesList = $orderService->getPaymentTypeList(session('store_manager_selected_location_company_id'));

        return Inertia::render('orders/MarketplacesOrder', [
            'orderTypes' => OrderTypes::getList(),
            'orderChannels' => OrderChannels::getList(),
            'orderTypesStaticUse' => OrderTypes::getFormattedArrayForStaticUse(),
            'orderStatusStaticUse' => OrderStatus::getFormattedArrayForStaticUse(),
            'paymentTypes' => OrderPaymentTypeListResource::collection($paymentTypesList)->toArray($request),
            'receiptNumber' => $request->receipt_number ?? null,
            'dateRange' => $request->date_range ?? null,
            'orderAddressStaticTypes' => OrderAddressesType::getFormattedArrayForStaticUse(),
        ]);
    }

    public function fetchMarketplacesOrders(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'type_id' => $request->get('type_id'),
            'channel_id' => $request->get('channel_id'),
            'location_id' => session('store_manager_selected_location_id'),
            'company_id' => session('store_manager_selected_location_company_id'),
            'store_manager_id' => null,
            'e_invoice_submitted' => null,
        ];

        $orderService = resolve(OrderService::class);
        [$ordersData, $consolidatedSales] = $orderService->prepareDataForWithoutB2BCommerce($filterData);

        return [
            'data' => MarketPlaceOrderListResource::collection($ordersData->getCollection()),
            'total_records' => $ordersData->total(),
            'total_units_sold' => $consolidatedSales['total_units_sold'] ?? 0,
            'total_orders' => $consolidatedSales['total_orders'] ?? 0,
            'total_orders_amount' => $consolidatedSales['total_orders_amount'] ?? 0,
        ];
    }

    public function fetchOrderItemsByOrderId(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $orderDetails = $this->orderQueries->getOrderItemsBy(
            $request->order_id,
            $storeManager->getKey(),
            $locationId,
            $companyId
        );

        return [
            'order_details' => new OrderItemsReportResource($orderDetails),
        ];
    }

    public function fetchOrderItemsEcommerceByOrderId(Request $request): array
    {
        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $orderDetails = $this->orderQueries->getOrderItemsForEcommerce($request->order_id, $locationId, $companyId);

        return [
            'order_details' => new OrderItemsReportResource($orderDetails),
        ];
    }

    public function fetchOrderReturnDetails(Request $request, string $orderId): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');

        $companyId = session('store_manager_selected_location_company_id');

        $orderDetails = $this->orderQueries->getOrderItemsBy(
            $orderId,
            $storeManager->getKey(),
            $locationId,
            $companyId
        );

        return [
            'order_return_details' => new OrderReturnDetailsResource($orderDetails),
        ];
    }

    public function cancelOrder(CancelOrderData $cancelOrderData, Request $request): RedirectResponse
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');

        $orderReturnQueries = resolve(OrderReturnQueries::class);
        $orderIdExistsInOrderReturn = $orderReturnQueries->doesOrderIdExist($cancelOrderData->orderId);

        if ($orderIdExistsInOrderReturn) {
            throw new RedirectBackWithErrorException('The Selected Order Is Returned, So Cannot Be Cancelled.');
        }

        $order = $this->orderQueries->cancelOrder($cancelOrderData);

        $order = $this->orderQueries->loadOrderItemsAndOrderItemsUnits($order);

        $cancelOrderService = resolve(CancelOrderService::class);
        $cancelOrderService->updateInventory($order, $storeManager, $locationId);

        return to_route('store_manager.orders.b2bOrders')->with(
            'success',
            'Selected Order Has Been Cancelled Successfully.'
        );
    }

    public function completeLayawayOrder(CompleteLayawayOrderData $completeLayawayOrderData, Request $request): void
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $order = $this->orderQueries->getOrderWithStoreAndItemsForCompleteLayaway(
            $completeLayawayOrderData->orderId,
            $storeManager->getKey(),
            $locationId,
            $companyId,
        );

        $paymentTypes = collect($completeLayawayOrderData->paymentTypes);
        $this->checkLayawayCompleteRequestDetails($order, $paymentTypes);

        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);

        DB::beginTransaction();

        try {
            foreach ($completeLayawayOrderData->paymentTypes as $paymentType) {
                $orderPaymentQueries->addNew($order, $paymentType, $storeManager->getKey(), $locationId);
            }

            $isCompleteLayawayOrder = ($order->getLayawayPendingAmount() - $paymentTypes->sum('amount')) <= 0;

            $orderItemQueries->updateLayawayAmountOf($order, $paymentTypes->sum('amount'), $isCompleteLayawayOrder);
            $order = $orderQueries->updateLayawayAmountOf($order, $paymentTypes);

            if ($order->getTypeId() === OrderTypes::COMPLETE_LAYAWAY_ORDER) {
                $orderReservedStockService = resolve(OrderReservedStockService::class);

                foreach ($order->getOrderItems() as $orderItem) {
                    $orderReservedStockService->removeReservationStock(
                        $orderItem,
                        $storeManager,
                        now()->format('y-m-d H:i:s')
                    );
                }
            }

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Complete Layaway Sale', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    private function checkLayawayCompleteRequestDetails(Order $order, Collection $paymentTypes): void
    {
        if (! $order->getLayawayPendingAmount() || $order->getTypeId()->value !== OrderTypes::PENDING_LAYAWAY_ORDER->value) {
            abort(412, 'The specified order is not a layaway order.');
        }

        if ($paymentTypes->sum('amount') > $order->getLayawayPendingAmount()) {
            abort(412, 'Payments exceeding the pending layaway amount are not permitted.');
        }
    }

    public function completeCreditOrder(CompleteCreditOrderData $completeCreditOrderData, Request $request): void
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $order = $this->orderQueries->getOrderWithStoreAndItemsForCompleteCredit(
            $completeCreditOrderData->orderId,
            $storeManager->getKey(),
            $locationId,
            $companyId,
        );

        $paymentTypes = collect($completeCreditOrderData->paymentTypes);
        $this->checkCreditCompleteRequestDetails($order, $paymentTypes);

        $orderPaymentQueries = resolve(OrderPaymentQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);

        DB::beginTransaction();

        try {
            foreach ($completeCreditOrderData->paymentTypes as $paymentType) {
                $orderPaymentQueries->addNew($order, $paymentType, $storeManager->getKey(), $locationId);
            }

            $isCompleteCreditOrder = ($order->getCreditPendingAmount() - $paymentTypes->sum('amount')) <= 0;

            $orderItemQueries->updateCreditAmountOf($order, $paymentTypes->sum('amount'), $isCompleteCreditOrder);
            $order = $orderQueries->updateCreditAmountOf($order, $paymentTypes);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Complete Layaway Sale', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    private function checkCreditCompleteRequestDetails(Order $order, Collection $paymentTypes): void
    {
        if (! $order->getCreditPendingAmount() || $order->getTypeId()->value !== OrderTypes::PENDING_CREDIT_ORDER->value) {
            throw new RedirectBackWithErrorException('The specified order is not a credit order.');
        }

        if ($paymentTypes->sum('amount') > $order->getCreditPendingAmount()) {
            throw new RedirectBackWithErrorException('Payments exceeding the pending credit amount are not permitted.');
        }
    }

    public function printLayawayOrderReport(int $orderId): string
    {
        $printLayawayOrderReportService = resolve(PrintLayawayOrderReportService::class);

        return $printLayawayOrderReportService->print($orderId);
    }

    public function printCreditOrderReport(int $orderId): string
    {
        $printCreditOrderReportService = resolve(PrintCreditOrderReportService::class);

        return $printCreditOrderReportService->print($orderId);
    }

    public function printOrderTaxInvoice(int $orderId): string
    {
        $printOrderTaxInvoiceService = resolve(PrintOrderTaxInvoiceService::class);

        return $printOrderTaxInvoiceService->print($orderId);
    }

    public function printPurchaseOrder(int $orderId): string
    {
        $printPurchaseOrderService = resolve(PrintPurchaseOrderService::class);

        return $printPurchaseOrderService->print($orderId);
    }

    public function accepted(int $orderId): void
    {
        $this->orderQueries->accepted($orderId);
    }

    public function cancelled(int $orderId): void
    {
        $this->orderQueries->cancelled($orderId);
    }

    public function readyForPickup(int $orderId): void
    {
        $this->orderQueries->readyForPickup($orderId);
    }

    public function printNinjaVanWayBill(int $orderId): string
    {
        $ninjaVanWayBillService = resolve(PrintNinjaVanWayBillService::class);

        return $ninjaVanWayBillService->print([$orderId]);
    }

    public function fetchOrderAddress(Request $request): array
    {
        $typeId = (int) $request->get('type');

        $orderAddressQueries = resolve(OrderAddressQueries::class);
        $orderDetails = $orderAddressQueries->getOrderAddress($request->order_id, $typeId);

        return [
            'order_address' => new OrderAddressResource($orderDetails),
        ];
    }

    public function printOrderReceipt(int $orderId): array
    {
        $order = $this->orderQueries->getOrderDetailsForReceipt($orderId);

        return [
            'order_details' => new OrderReceiptResource($order),
        ];
    }

    public function exportMarketplaceOrders(string $filename, Request $request): BinaryFileResponse
    {
        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'type_id' => $request->get('type_id'),
            'channel_id' => $request->get('channel_id'),
            'location_id' => $locationId,
            'company_id' => $companyId,
            'store_manager_id' => null,
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];
        $filteredColumnsFiltered = collect([]);
        if (! empty($exportColumns)) {
            $filteredColumns = collect($exportColumns)->pluck('key');
            $filteredColumnsFiltered = $filteredColumns->reject(
                fn (string $value, int $key): bool => in_array($value, ['action', 'select'])
            );
        }

        $filteredColumnsFiltered = $filteredColumnsFiltered->values();

        if (null !== $request->get('offline_sale_id')) {
            $filterData['date_range'] = null;
        }

        $orderService = resolve(OrderService::class);
        [$ordersData, $consolidatedSales] = $orderService->prepareDataForWithoutB2BCommerce($filterData);

        return Excel::download(new OrderExport($ordersData->getCollection(), $filteredColumnsFiltered), $filename);
    }

    public function printMarketplaceOrders(Request $request): string
    {
        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'type_id' => $request->get('type_id'),
            'channel_id' => $request->get('channel_id'),
            'module_type' => $request->get('module_type'),
            'location_id' => $locationId,
            'company_id' => $companyId,
            'store_manager_id' => null,
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');
        $filteredColumnsFiltered = $filteredColumns->reject(
            fn (string $value, int $key): bool => in_array($value, ['action', 'select'])
        );

        $filteredColumnsFiltered = $filteredColumnsFiltered->values();

        $orderService = resolve(OrderService::class);
        $ordersData = $orderService->prepareDataForPrintMarketplaceOrder($filterData);

        $activityData = $orderService->orderDataPrint(
            $ordersData,
            (int) $filterData['module_type'],
            $filteredColumnsFiltered
        );
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        unset($filterData['module_type']);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        return view('prints.marketplace_orders', [
            'orders' => $activityData,
            'company' => $company,
            'columns' => $filteredColumnsFiltered,
            'filter_header_data' => $filterHeaderData,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    private function getCommonData(Request $request): array
    {
        $companyId = session('store_manager_selected_location_company_id');

        $orderService = resolve(OrderService::class);
        $paymentTypesList = $orderService->getPaymentTypeList($companyId);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locations = $storeManagerQueries->getStoreManagerStores($storeManager);
        $locationId = session('store_manager_selected_location_id');

        return [$locations, $locationId, $paymentTypesList];
    }

    private function prepareData(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();
        $locationId = (int) $request->location_id;
        $companyId = session('store_manager_selected_location_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'type_id' => $request->get('type_id'),
            'e_invoice_submitted' => null,
            'channel_id' => $request->get('channel_id'),
        ];

        $orderService = resolve(OrderService::class);

        return $orderService->getPaginateData(
            $filterData,
            $storeManager->getKey(),
            $locationId,
            $companyId,
            true,
        );
    }
}
