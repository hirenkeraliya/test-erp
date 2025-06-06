<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPayment\Exports\BookingPaymentExport;
use App\Domains\BookingPayment\Resources\AdminBookingPaymentsListResource;
use App\Domains\BookingPayment\Resources\BookingPaymentsDetailsResource;
use App\Domains\BookingPayment\Services\PrintBookingPaymentReportService;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Services\PrintDigitalInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BookingPaymentReportController extends Controller
{
    public function __construct(
        protected BookingPaymentQueries $bookingPaymentQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $receiptId = $request->get('receipt_id');

        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);

        return Inertia::render('reports/booking_payments/Index', [
            'locations' => $locations,
            'bookingPaymentStatuses' => BookingPaymentStatuses::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('booking_payment'),
            'eInvoiceGeneratePermission' => 'digital_invoice_'.PermissionList::E_INVOICE_GENERATE->value,
            'moduleType' => ModelMapping::BOOKING_PAYMENT->name,
            'allowEInvoice' => $allowEInvoice,
            'helpCenterMessages' => 'The booking payment report, showcasing all booking details alongside product information for each payment. Provide advanced filters, search options, and seamless export capabilities to facilitate detailed analysis and insights.',
            'receiptId' => $receiptId ?? null,
        ]);
    }

    /**
     * @return array<string, int|float|AnonymousResourceCollection>
     */
    public function fetchBookingPayments(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'location_ids' => $request->get('location_ids'),
            'status_id' => $request->get('status_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'receipt_id' => $request->get('receipt_id'),
        ];

        $lengthAwarePaginator = $this->bookingPaymentQueries->getPaginatedBookingPaymentList(
            $filterData,
            session('admin_company_id')
        );

        $sumOfAvailableAmount = $this->bookingPaymentQueries->getSumOfAvailableAmountByCompany(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_available_amount' => $sumOfAvailableAmount,
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminBookingPaymentsListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportBookingPayments(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'member_id' => $request->get('member_id'),
            'location_ids' => $request->get('location_ids'),
            'status_id' => $request->get('status_id'),
            'e_invoice_submitted' => $request->get('e_invoice_submitted'),
            'receipt_id' => $request->get('receipt_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $bookingPayments = $this->bookingPaymentQueries->getBookingPaymentForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new BookingPaymentExport($bookingPayments, $filteredColumns), $filename);
    }

    public function fetchBookingPaymentsDetailsById(int $bookingPaymentId): array
    {
        $bookingPaymentDetails = $this->bookingPaymentQueries->getDetailsById(
            $bookingPaymentId,
            session('admin_company_id')
        );

        return [
            'bookingPayment_details' => new BookingPaymentsDetailsResource($bookingPaymentDetails),
        ];
    }

    public function printBookingPayment(int $bookingPaymentId): string
    {
        $printBookingPaymentService = resolve(PrintBookingPaymentReportService::class);

        return $printBookingPaymentService->print($bookingPaymentId, session('admin_company_id'), null);
    }

    public function printDigitalInvoice(int $bookingPaymentId): string
    {
        $printDigitalInvoiceService = resolve(PrintDigitalInvoiceService::class);

        return $printDigitalInvoiceService->print($bookingPaymentId, ModelMapping::BOOKING_PAYMENT->name);
    }
}
