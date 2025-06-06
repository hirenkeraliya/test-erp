<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\CommonFunctions;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpenCounterSalesReportListResource extends JsonResource
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

        /** @var Member|null $member */
        $member = $sale->member;

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $sale->id,
            'offline_sale_id' => $sale->offline_sale_id,
            'location' => $location->name,
            'counter' => $counter->name,
            'cashier' => $employee->getFullName(),
            'happened_at' => $happenedAt,
            'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
            'member_id' => null !== $member ? $member->getKey() : null,
            'gross_sales' => CommonFunctions::numberFormat($sale->getGrossTotal()),
            'total_tax_amount' => $sale->total_tax_amount,
            'total_amount_paid' => $sale->total_amount_paid,
            'total_discount_amount' => $sale->total_discount_amount,
            'net_total' => $sale->round_off,
            /* @phpstan-ignore-next-line */
            'units_sold' => $sale->sale_items_sum_quantity,
            /* @phpstan-ignore-next-line */
            'units_returned' => $sale->sale_items_sum_returned_quantity,
            'status' => $sale->status ? SaleStatus::getFormattedCaseName($sale->status) : 'N/A',
            'sale_mismatches' => $sale->mismatches->count(),
        ];
    }
}
