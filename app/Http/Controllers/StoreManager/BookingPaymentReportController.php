<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPayment\Exports\BookingPaymentExport;
use App\Domains\BookingPayment\Resources\AdminBookingPaymentsListResource;
use App\Domains\BookingPayment\Resources\BookingPaymentsDetailsResource;
use App\Domains\BookingPayment\Services\PrintBookingPaymentReportService;
use App\Domains\Permission\Enums\PermissionList;
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
        $receiptId = $request->get('receipt_id');

        return Inertia::render('reports/booking_payments/Index', [
            'bookingPaymentStatuses' => BookingPaymentStatuses::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('booking_payment'),
            'helpCenterMessages' => 'The booking payment report, showcasing all booking details alongside product information for each payment. Provide advanced filters, search options, and seamless export capabilities to facilitate detailed analysis and insights.',
            'receiptId' => $receiptId ?? null,
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
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
            'status_id' => $request->get('status_id'),
            'receipt_id' => $request->get('receipt_id'),
        ];

        $lengthAwarePaginator = $this->bookingPaymentQueries->getPaginatedBookingPaymentListForStoreManager(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        return [
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
            'status_id' => $request->get('status_id'),
            'receipt_id' => $request->get('receipt_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $bookingPayments = $this->bookingPaymentQueries->getBookingPaymentForExportForStoreManager(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        return Excel::download(new BookingPaymentExport($bookingPayments, $filteredColumns), $filename);
    }

    public function fetchBookingPaymentsDetailsById(int $bookingPaymentId): array
    {
        $bookingPaymentDetails = $this->bookingPaymentQueries->getDetailsById(
            $bookingPaymentId,
            session('store_manager_selected_location_company_id')
        );

        return [
            'bookingPayment_details' => new BookingPaymentsDetailsResource($bookingPaymentDetails),
        ];
    }

    public function printBookingPayment(int $bookingPaymentId): string
    {
        $printBookingPaymentService = resolve(PrintBookingPaymentReportService::class);

        return $printBookingPaymentService->print(
            $bookingPaymentId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );
    }
}
