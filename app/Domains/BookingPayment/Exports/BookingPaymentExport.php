<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\Exports;

use App\CommonFunctions;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\Common\Services\ExportService;
use App\Models\BookingPayment;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BookingPaymentExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $bookingPayments,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->bookingPayments->map(function (BookingPayment $bookingPayment): array {
            /** @var ?Member $member */
            $member = $bookingPayment->member;

            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $bookingPayment->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            /** @var Location $location */
            $location = $counter->location;

            /** @var Cashier $cashier */
            $cashier = $counterUpdate->cashier;

            /** @var Employee $employee */
            $employee = $cashier->employee;

            /** @var ?StoreManager $storeManager */
            $storeManager = $bookingPayment->authorizer;

            /** @var ?Employee $storeManagerEmployee */
            $storeManagerEmployee = $storeManager instanceof StoreManager ? $storeManager->employee : null;

            /** @var Carbon $createdAt */
            $createdAt = $bookingPayment->created_at;
            $happenedAt = null;
            if ($bookingPayment->happened_at) {
                /** @var Carbon $happenedAtFormat */
                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $bookingPayment->happened_at);
                $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');
            }

            $memberName = null !== $member ? $member->getFullName() : '';

            $bookingPaymentData = [
                'digital_invoice_number' => $bookingPayment->digital_invoice_number ?: 'N/A',
                'offline_id' => $bookingPayment->offline_id,
                'location' => $location->getName(),
                'counter' => $counter->getName(),
                'authorizer' => $storeManagerEmployee instanceof Employee ? $storeManagerEmployee->getFullName() : 'N/A',
                'cashier' => $employee->getFullName(),
                'happened_at' => $happenedAt ?: $createdAt->format('d-m-Y h:i:s A'),
                'member' => $memberName,
                'total_amount' => CommonFunctions::currencyFormat((float) $bookingPayment->total_amount),
                'available_amount' => CommonFunctions::currencyFormat((float) $bookingPayment->available_amount),
                'remarks' => $bookingPayment->remarks,
                'bill_reference_number' => $bookingPayment->bill_reference_number,
                'status' => BookingPaymentStatuses::getCaseNameByValue($bookingPayment->getStatus()),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($bookingPaymentData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
