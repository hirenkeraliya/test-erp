<?php

declare(strict_types=1);

namespace App\Domains\SaleReturn\Resources;

use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DifferentStoreReturnsReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var SaleReturn $saleReturn */
        $saleReturn = $this;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $saleReturn->counterUpdate;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var Member|null $member */
        $member = $saleReturn->member;

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleReturn->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        /** @var Sale $sale */
        $sale = $saleReturn->originalSale;

        /** @var Carbon $saleHappenedAtFormat */
        $saleHappenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->getHappenedAt());
        $saleHappenedAt = $saleHappenedAtFormat->format('d-m-Y h:i:s A');

        /** @var CounterUpdate $saleCounterUpdate */
        $saleCounterUpdate = $sale->counterUpdate;

        /** @var Cashier $saleCashier */
        $saleCashier = $saleCounterUpdate->cashier;

        /** @var Employee $saleEmployee */
        $saleEmployee = $saleCashier->employee;

        /** @var Counter $saleCounter */
        $saleCounter = $saleCounterUpdate->counter;

        /** @var Location $saleLocation */
        $saleLocation = $saleCounter->location;

        return [
            'id' => $saleReturn->getKey(),
            'offline_sale_return_id' => $saleReturn->getOfflineSaleReturnId(),
            'original_receipt_id' => $sale->offline_sale_id,
            'sale_happened_at' => $saleHappenedAt,
            'sale_return_happened_at' => $happenedAt,
            'original_location' => [
                'id' => $saleLocation->getKey(),
                'name' => $saleLocation->getName(),
            ],
            'return_location' => [
                'id' => $location->getKey(),
                'name' => $location->getName(),
            ],
            'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
            'member_id' => null !== $member ? $member->getKey() : null,
            'sale_counter' => $saleCounter->getName(),
            'sale_return_counter' => $counter->getName(),
            'sale_cashier' => $saleEmployee->getFullName(),
            'sale_return_cashier' => $employee->getFullName(),
            'sale_return_total_tax_amount' => $saleReturn->getTotalTaxAmount(),
            'sale_return_total_discount_amount' => $saleReturn->getTotalDiscountAmount(),
            'sale_return_amount' => $saleReturn->getTotalPricePaid(),
            'sale_mismatches' => $this->whenLoaded('mismatches', fn () => $saleReturn->mismatches->count()),
            'digital_invoice_submitted' => $saleReturn->digital_invoice_submitted,
            'digital_invoice_number' => $saleReturn->digital_invoice_number ?: 'N/A',
        ];
    }
}
