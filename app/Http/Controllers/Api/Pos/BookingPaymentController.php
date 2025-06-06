<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPayment\DataObjects\PaginatedBookingPaymentsDataForPos;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPayment\Resources\BookingPaymentResource;
use App\Domains\BookingPayment\Resources\PosBookingPaymentPaymentResource;
use App\Domains\BookingPayment\Resources\PosBookingPaymentRefundResource;
use App\Domains\BookingPayment\Resources\PosBookingPaymentResource;
use App\Domains\BookingPayment\Services\BookingPaymentService;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\BookingPaymentPayments\Services\BookingPaymentPaymentService;
use App\Domains\BookingPaymentProduct\DataObjects\BookingPaymentProductData;
use App\Domains\BookingPaymentRefund\DataObjects\BookingPaymentRefundData;
use App\Domains\BookingPaymentRefund\Services\CheckBookingPaymentRequestDetailsService;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingPaymentController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedBookingPayments(
        Request $request,
        PaginatedBookingPaymentsDataForPos $paginatedBookingPaymentsDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        CommonFunctions::checkIfCounterIsOpen($cashier);

        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $bookingPaymentService = resolve(BookingPaymentService::class);

        [$location, $companyId] = $bookingPaymentService->getCompanyAndStore($cashier);

        $bookingPayments = $bookingPaymentQueries->getPaginatedBookingPaymentsWithProducts(
            $paginatedBookingPaymentsDataForPos->toArray(),
            $companyId,
            $location->id
        );

        return [
            'booking_payments' => BookingPaymentResource::collection($bookingPayments),
            'total_records' => $bookingPayments->total(),
            'last_page' => $bookingPayments->lastPage(),
            'current_page' => $bookingPayments->currentPage(),
            'per_page' => $bookingPayments->perPage(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getBookingPaymentStatuses(): array
    {
        return [
            'booking_payment_statuses' => BookingPaymentStatuses::getList(),
        ];
    }

    public function resetBookingPaymentProducts(
        Request $request,
        BookingPaymentProductData $bookingPaymentProductData,
        int $bookingPaymentId
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        CommonFunctions::checkIfCounterIsOpen($cashier);

        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $checkBookingPaymentRequestDetailsService = resolve(CheckBookingPaymentRequestDetailsService::class);

        $bookingPaymentService = resolve(BookingPaymentService::class);
        [$location, $companyId] = $bookingPaymentService->getCompanyAndStore($cashier);

        $bookingPayment = $bookingPaymentQueries->getById($bookingPaymentId, $companyId, $location->id);
        $checkBookingPaymentRequestDetailsService->handleBookingPaymentChecks(
            $bookingPayment,
            $bookingPaymentProductData,
            $companyId
        );

        DB::beginTransaction();

        try {
            $bookingPaymentService->resetBookingPaymentProducts(
                $bookingPayment,
                $bookingPaymentProductData,
                $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches
            );

            DB::commit();

            $bookingPayment = $bookingPaymentService->addLogMismatchEntries(
                $bookingPayment,
                'Booking Payment Reset Products Mismatches'
            );

            $bookingPayment = $bookingPaymentQueries->loadProductsMemberAndMismatchesRelations($bookingPayment);

            return [
                'booking_payment_products' => new PosBookingPaymentResource($bookingPayment),
            ];
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Booking Payment Reset Products');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function bookingPaymentTopUp(
        Request $request,
        BookingPaymentTopUpData $bookingPaymentTopUpData,
        int $bookingPaymentId
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        CommonFunctions::checkIfCounterIsOpen($cashier);

        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);

        $bookingPaymentService = resolve(BookingPaymentService::class);
        [$location, $companyId] = $bookingPaymentService->getCompanyAndStore($cashier);

        /** @var Company $company */
        $company = $location->company;

        $bookingPayment = $bookingPaymentQueries->getById($bookingPaymentId, $companyId, $location->id);

        $checkBookingPaymentRequestDetailsService = resolve(CheckBookingPaymentRequestDetailsService::class);
        $checkBookingPaymentRequestDetailsService->prepareAndCheckPaymentStatus(
            $bookingPayment,
            $bookingPaymentTopUpData,
            $company
        );

        $bookingPaymentPaymentService = resolve(BookingPaymentPaymentService::class);
        $bookingPaymentPaymentService->prepareAndCheckPayment(
            $bookingPaymentTopUpData,
            $location,
            $companyId,
            $bookingPayment->member_id
        );

        DB::beginTransaction();

        try {
            $misMaMatches = $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches->merge(
                $bookingPaymentPaymentService->paymentMismatches
            );

            $bookingPaymentService->bookingPaymentTopUp(
                $bookingPayment,
                $bookingPaymentTopUpData,
                $bookingPaymentPaymentService,
                $misMaMatches,
                (int) $cashier->getCounterUpdateId()
            );

            DB::commit();

            $bookingPayment = $bookingPaymentService->addLogMismatchEntries(
                $bookingPayment,
                'Booking Payment TopUp Mismatches'
            );

            $bookingPayment = $bookingPaymentQueries->loadProductsMemberAndMismatchesRelations($bookingPayment);

            return [
                'booking_payment_top_up' => new PosBookingPaymentPaymentResource($bookingPayment),
            ];
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Booking Payment TopUp');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function bookingPaymentRefund(
        Request $request,
        BookingPaymentRefundData $bookingPaymentRefundData,
        int $bookingPaymentId
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        CommonFunctions::checkIfCounterIsOpen($cashier);

        $counterUpdateId = (int) $cashier->getCounterUpdateId();

        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);

        $bookingPaymentService = resolve(BookingPaymentService::class);
        [$location, $companyId] = $bookingPaymentService->getCompanyAndStore($cashier);

        $bookingPayment = $bookingPaymentQueries->getById($bookingPaymentId, $companyId, $location->id);

        /** @var Company $company */
        $company = $location->company;

        $checkBookingPaymentRequestDetailsService = resolve(CheckBookingPaymentRequestDetailsService::class);
        $checkBookingPaymentRequestDetailsService->prepareAndAuthorizeRefund(
            $bookingPayment,
            $company,
            $bookingPaymentRefundData,
        );

        DB::beginTransaction();

        try {
            $bookingPaymentService->bookingPaymentRefund(
                $bookingPayment,
                $bookingPaymentRefundData,
                $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches,
                $counterUpdateId
            );

            DB::commit();

            $bookingPayment = $bookingPaymentService->addLogMismatchEntries(
                $bookingPayment,
                'Booking Payment Refund Mismatches'
            );

            $bookingPayment = $bookingPaymentQueries->loadProductsMemberAndMismatchesRelations($bookingPayment);

            return [
                'booking_payment_refund' => new PosBookingPaymentRefundResource($bookingPayment),
            ];
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Booking Payment Refund');
            DB::rollback();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function store(BookingPaymentData $bookingPaymentData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        CommonFunctions::checkIfCounterIsOpen($cashier);

        $checkBookingPaymentRequestDetailsService = resolve(CheckBookingPaymentRequestDetailsService::class);

        $bookingPaymentService = resolve(BookingPaymentService::class);
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        [$location, $companyId] = $bookingPaymentService->getCompanyAndStore($cashier);

        $digitalInvoiceNumber = $this->getSequenceNumber($location);

        /** @var Company $company */
        $company = $location->company;

        $checkBookingPaymentRequestDetailsService->validateBookingPaymentRequestAndMember(
            $company,
            $location,
            $cashier,
            $bookingPaymentData
        );

        $bookingPaymentPaymentService = resolve(BookingPaymentPaymentService::class);
        $bookingPaymentPaymentService->prepareAndCheckPayment(
            $bookingPaymentData,
            $location,
            $companyId,
            (int) $request->member_id
        );

        DB::beginTransaction();
        try {
            $mismatches = $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches->merge(
                $bookingPaymentPaymentService->paymentMismatches
            );

            $bookingPayment = $bookingPaymentService->storeBookingPayment(
                $bookingPaymentData,
                $bookingPaymentPaymentService,
                $mismatches,
                $digitalInvoiceNumber,
                (int) $cashier->getCounterUpdateId(),
            );

            DB::commit();

            $bookingPayment = $bookingPaymentService->addLogMismatchEntries(
                $bookingPayment,
                'Add a Booking Payment Store Mismatches'
            );

            $bookingPayment = $bookingPaymentQueries->loadProductsMemberAndMismatchesRelations($bookingPayment);

            return [
                'booking_payment_store' => new PosBookingPaymentResource($bookingPayment),
            ];
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Booking Payment Store');
            DB::rollback();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    /**
     * @return array<string, BookingPaymentResource>
     */
    public function getBookingPaymentDetails(Request $request, int|string $bookingPaymentId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        CommonFunctions::checkIfCounterIsOpen($cashier);

        $bookingPaymentService = resolve(BookingPaymentService::class);
        [$location, $companyId] = $bookingPaymentService->getCompanyAndStore($cashier);

        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $bookingPayment = $bookingPaymentQueries->getBookingPaymentWithRelation(
            $location->id,
            $companyId,
            $bookingPaymentId
        );

        return [
            'booking_payment' => new BookingPaymentResource($bookingPayment),
        ];
    }

    public function getSequenceNumber(Location $location): string
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $number = $sequenceQueries->addNew($location->id, SequenceTypes::BP->value)->number;

        return $location->code . '-' . SequenceTypes::BP->name . '-' . $number;
    }
}
