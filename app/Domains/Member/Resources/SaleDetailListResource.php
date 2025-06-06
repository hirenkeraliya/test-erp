<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\CommonFunctions;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDetailListResource extends JsonResource
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

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

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
            'gross_sales' => CommonFunctions::numberFormat($sale->getGrossTotal()),
            'total_tax_amount' => $sale->getTotalTaxAmount(),
            'total_discount_amount' => $sale->getTotalDiscountAmount(),
            'total_amount_paid' => $sale->getTotalAmountPaid(),
            'net_total' => $sale->getTotalAmountBeforeRoundOff(),
            /* @phpstan-ignore-next-line */
            'units_sold' => $sale->sale_items_sum_quantity,
            /* @phpstan-ignore-next-line */
            'units_returned' => $sale->sale_items_sum_returned_quantity,
            'notes' => $sale->notes ?? 'N/A',
        ];
    }
}
