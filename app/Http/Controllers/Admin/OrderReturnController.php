<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\OrderReturn\Resources\OrderReturnItemsReportResource;
use App\Domains\OrderReturn\Resources\OrderReturnListResource;
use App\Domains\OrderReturn\Resources\OrderReturnReceiptResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderReturnController extends Controller
{
    public function __construct(
        protected OrderReturnQueries $orderReturnQueries
    ) {
    }

    public function index(): Response
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

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return Inertia::render('order_returns/OrderReturn', [
            'storeManagers' => $storeManagers,
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'moduleType' => ModelMapping::ORDER_RETURN->name,
            'allowEInvoice' => $allowEInvoice,
        ]);
    }

    public function fetchOrderReturns(Request $request): array
    {
        $locationId = (int) $request->location_id;
        $storeManagerId = (int) $request->store_manager_id;

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
            $storeManagerId,
            $locationId
        );

        $consolidatedSales = $this->orderReturnQueries->getFilteredTotalsForReport(
            $filterData,
            $storeManagerId,
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
        $orderReturnDetails = $this->orderReturnQueries->getOrderReturnItems(
            $orderReturnId,
            session('admin_company_id'),
        );

        return [
            'order_return_details' => new OrderReturnItemsReportResource($orderReturnDetails),
        ];
    }

    public function printOrderReturnReceipt(int $orderReturnId): array
    {
        $orderReturnDetails = $this->orderReturnQueries->getOrderReturnReceipt(
            $orderReturnId,
            session('admin_company_id'),
        );

        return [
            'order_return_details' => new OrderReturnReceiptResource($orderReturnDetails),
        ];
    }

    public function printDigitalInvoice(int $orderReturnId): string
    {
        $printDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $printDigitalInvoiceService->print($orderReturnId, ModelMapping::ORDER_RETURN->name);
    }
}
