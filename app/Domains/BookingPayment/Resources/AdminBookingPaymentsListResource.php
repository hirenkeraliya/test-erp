<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\Resources;

use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Models\BookingPayment;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBookingPaymentsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var BookingPayment $bookingPayment */
        $bookingPayment = $this;

        /** @var Member|null $member */
        $member = $bookingPayment->member;

        /** @var Carbon $createdAt */
        $createdAt = $bookingPayment->created_at;

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

        $happenedAt = null;

        if ($bookingPayment->happened_at) {
            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $bookingPayment->happened_at);
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');
        }

        return [
            'id' => $bookingPayment->id,
            'offline_id' => $bookingPayment->offline_id,
            'member' => $member instanceof Member ? $member->getFullName() : null,
            'total_amount' => (float) $bookingPayment->total_amount,
            'available_amount' => (float) $bookingPayment->available_amount,
            'remarks' => $bookingPayment->remarks,
            'bill_reference_number' => $bookingPayment->bill_reference_number,
            'status' => BookingPaymentStatuses::getCaseNameByValue($bookingPayment->getStatus()),
            'happened_at' => $happenedAt ?: $createdAt->format('d-m-Y h:i:s A'),
            'location' => $location->getName(),
            'counter' => $counter->getName(),
            'cashier' => $employee->getFullName(),
            'authorizer' => $storeManagerEmployee instanceof Employee ? $storeManagerEmployee->getFullName() : 'N/A',
            'mismatches' => $bookingPayment->mismatches->count(),
            'digital_invoice_submitted' => $bookingPayment->digital_invoice_submitted,
            'digital_invoice_number' => $bookingPayment->digital_invoice_number ?: 'N/A',
        ];
    }
}
