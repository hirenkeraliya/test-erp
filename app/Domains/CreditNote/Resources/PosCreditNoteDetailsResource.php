<?php

declare(strict_types=1);

namespace App\Domains\CreditNote\Resources;

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCreditNoteDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $creditNote = $this->resource;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $creditNote->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var ?SaleReturn $saleReturn */
        $saleReturn = $creditNote->saleReturn;

        /** @var ?Sale $sale */
        $sale = $saleReturn?->originalSale;

        $userDataPrepare = resolve(UserDataPreparer::class);

        return [
            'id' => $creditNote->id,
            'counter_update_id' => $creditNote->counter_update_id,
            'cashier' => [
                'id' => $cashier->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ],
            'counter' => [
                'id' => $counter->id,
                'name' => $counter->getName(),
            ],
            'sale_return_id' => $creditNote->sale_return_id,
            'sale_return_receipt_number' => $saleReturn?->offline_sale_return_id,
            'original_sale_receipt_number' => $sale?->offline_sale_id,
            'user_type' => $userDataPrepare->getUserType($creditNote),
            'user_id' => $creditNote->member_id,
            'user_details' => $userDataPrepare->getBasicUserDetails($creditNote->member),
            'member_id' => $creditNote->member_id,
            'member' => $userDataPrepare->getBasicUserDetails($creditNote->member),
            'expiry_date' => $creditNote->expiry_date,
            'total_amount' => (float) $creditNote->total_amount,
            'available_amount' => (float) $creditNote->available_amount,
            'currency_id' => $creditNote->creditNoteRefund?->currency_id,
            'current_currency_rate' => $creditNote->creditNoteRefund?->currency_rate,
            'currency_amount' => $creditNote->creditNoteRefund?->currency_amount,
            'currency_symbol' => $creditNote->creditNoteRefund?->currency ? $creditNote->creditNoteRefund->currency->symbol : null,
            'status' => CreditNoteStatuses::getCaseNameByValue($creditNote->status),
        ];
    }
}
