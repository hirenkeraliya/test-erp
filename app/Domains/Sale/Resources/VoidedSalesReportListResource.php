<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use App\Models\StoreManager;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoidedSalesReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Sale $sale */
        $sale = $this;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var Company $company */
        $company = $location->company;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

        /** @var Member|null $member */
        $member = $sale->member;

        /** @var VoidSale $voidSale */
        $voidSale = $sale->voidSale;

        /** @var VoidSaleReason $voidSaleReason */
        $voidSaleReason = $voidSale->voidSaleReason;

        /** @var StoreManager $storeManager */
        $storeManager = $voidSale->voidedByStoreManager;

        /** @var Employee $storeManagerEmployee */
        $storeManagerEmployee = $storeManager->employee;

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $sale->getKey(),
            'offline_sale_id' => $sale->getOfflineSaleId(),
            'bill_reference_number' => $sale->bill_reference_number,
            'location' => $location->getName(),
            'counter' => $counter->getName(),
            'cashier' => $employee->getFullName(),
            'happened_at' => $happenedAt,
            'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
            'total_amount_paid' => $sale->getTotalAmountPaid(),
            'void_sale_number' => $voidSale->getVoidSaleNumber($company->getVoidSaleNumberPrefix()),
            'void_reason' => $voidSaleReason->getReason(),
            'voided_by' => $storeManagerEmployee->getFullName(),
            'sale_mismatches' => $sale->mismatches->count(),
            'digital_invoice_submitted' => $sale->digital_invoice_submitted,
            'digital_invoice_number' => $sale->digital_invoice_number ?: 'N/A',
        ];
    }
}
