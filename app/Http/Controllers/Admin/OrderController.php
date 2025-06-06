<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Order\DataObjects\OrderECommerceAddressData;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Exports\OrderExport;
use App\Domains\Order\Jobs\OrderECommerceChangeAddressJob;
use App\Domains\Order\OrderQueries;
use App\Domains\Order\Resources\MarketPlaceOrderListResource;
use App\Domains\Order\Resources\OrderListResource;
use App\Domains\Order\Resources\OrderReceiptResource;
use App\Domains\Order\Services\OrderService;
use App\Domains\Order\Services\PrintCreditOrderReportService;
use App\Domains\Order\Services\PrintLayawayOrderReportService;
use App\Domains\Order\Services\PrintNinjaVanWayBillService;
use App\Domains\Order\Services\PrintOrderTaxInvoiceService;
use App\Domains\Order\Services\PrintPurchaseOrderService;
use App\Domains\OrderAddress\Enums\OrderAddressesType;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderAddress\Resources\OrderAddressResource;
use App\Domains\OrderItem\Resources\OrderItemsReportResource;
use App\Domains\PaymentType\OrderPaymentTypeListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function __construct(
        protected OrderQueries $orderQueries,
    ) {
    }

    public function b2bOrders(Request $request): Response
    {
        [$storeManagers, $paymentTypesList, $allowEInvoice] = $this->getCommonData();

        return Inertia::render('orders/B2bOrder', [
            'orderTypes' => OrderTypes::getList(),
            'orderTypesStaticUse' => OrderTypes::getFormattedArrayForStaticUse(),
            'paymentTypes' => OrderPaymentTypeListResource::collection($paymentTypesList)->toArray($request),
            'storeManagers' => $storeManagers,
            'eInvoiceGeneratePermission' => 'digital_invoice_' . PermissionList::E_INVOICE_GENERATE->value,
            'moduleType' => ModelMapping::ORDER->name,
            'allowEInvoice' => $allowEInvoice,
        ]);
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
        $paymentTypesList = $orderService->getPaymentTypeList(session('admin_company_id'));

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById(session('admin_company_id'));

        return Inertia::render('orders/MarketplacesOrder', [
            'orderTypes' => OrderTypes::getList(),
            'orderChannels' => OrderChannels::getList(),
            'orderTypesStaticUse' => OrderTypes::getFormattedArrayForStaticUse(),
            'orderStatusStaticUse' => OrderStatus::getFormattedArrayForStaticUse(),
            'paymentTypes' => OrderPaymentTypeListResource::collection($paymentTypesList)->toArray($request),
            'eInvoiceGeneratePermission' => 'digital_invoice_' . PermissionList::E_INVOICE_GENERATE->value,
            'moduleType' => ModelMapping::ORDER->name,
            'allowEInvoice' => $allowEInvoice,
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
            'location_id' => null,
            'company_id' => session('admin_company_id'),
            'store_manager_id' => null,
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
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
        $companyId = session('admin_company_id');
        $orderDetails = $this->orderQueries->getOrderItemsBy(
            $request->order_id,
            (int) $request->store_manager_id,
            (int) $request->location_id,
            $companyId,
        );

        return [
            'order_details' => new OrderItemsReportResource($orderDetails),
        ];
    }

    public function fetchOrderItemsEcommerceByOrderId(Request $request): array
    {
        $companyId = session('admin_company_id');
        $orderDetails = $this->orderQueries->getOrderItemsForEcommerce(
            $request->order_id,
            (int) $request->location_id,
            $companyId,
        );

        return [
            'order_details' => new OrderItemsReportResource($orderDetails),
        ];
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

    public function accepted(int $orderId): void
    {
        $this->orderQueries->accepted($orderId);
    }

    public function cancelled(int $orderId): void
    {
        $this->orderQueries->cancelled($orderId);
    }

    public function printDigitalInvoice(int $orderId): string
    {
        $printDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $printDigitalInvoiceService->print($orderId, ModelMapping::ORDER->name);
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

    private function getCommonData(): array
    {
        $companyId = session('admin_company_id');

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagers = $storeManagerQueries->getAllStoreManagerByCompany($companyId);
        $storeManagers->transform(function ($storeManager): array {
            /** @var Employee $employee */
            $employee = $storeManager->employee;

            return [
                'id' => $storeManager->id,
                'name' => $employee->getFullName(),
            ];
        });
        $orderService = resolve(OrderService::class);
        $paymentTypesList = $orderService->getPaymentTypeList($companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return [$storeManagers, $paymentTypesList, $allowEInvoice];
    }

    private function prepareData(Request $request): array
    {
        $locationId = (int) $request->location_id;
        $storeManagerId = (int) $request->store_manager_id;
        $companyId = session('admin_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'type_id' => $request->get('type_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'channel_id' => $request->get('channel_id'),
        ];

        $orderService = resolve(OrderService::class);

        return $orderService->getPaginateData($filterData, $storeManagerId, $locationId, $companyId, true);
    }

    public function exportMarketplaceOrders(string $filename, Request $request): BinaryFileResponse
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
            'location_id' => null,
            'company_id' => session('admin_company_id'),
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
        $ordersData = $orderService->prepareDataForPrintMarketplaceOrder($filterData);

        return Excel::download(new OrderExport($ordersData, $filteredColumnsFiltered), $filename);
    }

    public function printMarketplaceOrders(Request $request): string
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
            'module_type' => $request->get('module_type'),
            'location_id' => null,
            'company_id' => session('admin_company_id'),
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

        $companyId = session('admin_company_id');
        $orderService = resolve(OrderService::class);
        $ordersData = $orderService->prepareDataForPrintMarketplaceOrder($filterData);

        $companyId = session('admin_company_id');
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

    public function updateAddress(Request $request, int $orderAddressId): void
    {
        $requestData = $request->all();
        $requestData['id'] = $orderAddressId;

        $orderECommerceAddressData = new OrderECommerceAddressData(
            id: $requestData['id'],
            first_name: $requestData['first_name'],
            last_name: $requestData['last_name'],
            phone: $requestData['phone'],
            area_code: $requestData['area_code'],
            city_name: $requestData['city_name'],
            address_line_1: $requestData['address_line_1'],
            address_line_2: $requestData['address_line_2'],
        );

        $orderAddressQueries = resolve(OrderAddressQueries::class);
        $orderAddressQueries->updateOrderAddressECommerce($orderECommerceAddressData, $orderAddressId);

        OrderECommerceChangeAddressJob::dispatch($orderAddressId)->onQueue('medium');
    }
}
