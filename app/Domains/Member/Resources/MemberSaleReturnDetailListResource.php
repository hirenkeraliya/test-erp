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
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberSaleReturnDetailListResource extends JsonResource
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

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleReturn->getHappenedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        /** @var Sale $sale */
        $sale = $saleReturn->originalSale;

        return [
            'id' => $saleReturn->getKey(),
            'offline_sale_return_id' => $saleReturn->getOfflineSaleReturnId(),
            'bill_reference_number' => $sale->bill_reference_number,
            'happened_at' => $happenedAt,
            'location' => [
                'id' => $location->getKey(),
                'name' => $location->getName(),
            ],
            'counter' => $counter->getName(),
            'cashier' => $employee->getFullName(),
            'gross_returns' => CommonFunctions::numberFormat($saleReturn->getGrossTotal()),
            'total_tax_amount' => $saleReturn->getTotalTaxAmount(),
            'total_discount_amount' => $saleReturn->getTotalDiscountAmount(),
            'return_amount' => $saleReturn->getTotalPricePaid(),
            'sale_mismatches' => $saleReturn->mismatches->count(),
            /* @phpstan-ignore-next-line */
            'units_returned' => $saleReturn->sale_return_items_sum_quantity,
        ];
    }
}
