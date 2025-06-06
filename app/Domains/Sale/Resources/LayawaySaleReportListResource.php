<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\CommonFunctions;
use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayawaySaleReportListResource extends JsonResource
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

        /** @var ?StoreManager $storeManager */
        $storeManager = $sale->layawayAuthorizer;

        /** @var ?Employee $storeManagerEmployee */
        $storeManagerEmployee = $storeManager instanceof StoreManager ? $storeManager->employee : null;

        return [
            'id' => $sale->getKey(),
            'offline_sale_id' => $sale->getOfflineSaleId(),
            'bill_reference_number' => $sale->bill_reference_number,
            'location' => $location->getName(),
            'counter' => $counter->getName(),
            'cashier' => $employee->getFullName(),
            'status' => $sale->status === SaleStatus::PENDING_LAYAWAY_SALE->value ? CreditAndLayawaySaleStatuses::getCaseName(
                CreditAndLayawaySaleStatuses::PENDING->value
            ) : CreditAndLayawaySaleStatuses::getCaseName(CreditAndLayawaySaleStatuses::COMPLETE->value),
            'authorizer' => $storeManagerEmployee instanceof Employee ? $storeManagerEmployee->getFullName() : 'N/A',
            'happened_at' => $happenedAt,
            'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
            'gross_sales' => CommonFunctions::numberFormat($sale->getGrossTotalForLayawaySale()),
            'net_sales' => $sale->getLayawayTotalAmount(),
            'total_amount_paid' => $sale->getTotalAmountPaid(),
            'layaway_pending_amount' => $sale->getLayawayPendingAmount(),
            'sale_mismatches' => $sale->mismatches->count(),
            'notes' => $sale->notes ?? 'N/A',
            'digital_invoice_submitted' => $sale->digital_invoice_submitted,
            'digital_invoice_number' => $sale->digital_invoice_number ?: 'N/A',
        ];
    }
}
