<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPointUpdate\Resources;

use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Models\CancelCreditSale;
use App\Models\CancelLayawaySale;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\LoyaltyPointUpdate;
use App\Models\Order;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\VoidSale;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberAppLoyaltyPointsUpdateListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var LoyaltyPointUpdate $loyaltyPointUpdate */
        $loyaltyPointUpdate = $this;

        /** @var Sale|SaleReturn|VoidSale|SaleItem|SaleReturnItem|Voucher|Order|CancelCreditSale|CancelLayawaySale $affectedBy */
        $affectedBy = $loyaltyPointUpdate->affectedBy;

        $locationName = null;

        if ($affectedBy instanceof Sale || $affectedBy instanceof SaleReturn) {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $affectedBy->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            $location = $counter->location;

            $locationName = $location?->name;
        }

        if (
            $affectedBy instanceof VoidSale ||
            $affectedBy instanceof SaleItem ||
            $affectedBy instanceof CancelCreditSale ||
            $affectedBy instanceof CancelLayawaySale
        ) {
            /** @var Sale $sale */
            $sale = $affectedBy->sale;

            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $sale->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            $location = $counter->location;

            $locationName = $location?->name;
        }

        if ($affectedBy instanceof Voucher) {
            $location = $affectedBy->createdByLocation;
            $locationName = $location?->name;
        }

        if ($affectedBy instanceof Order) {
            $location = $affectedBy->location;
            $locationName = $location?->name;
        }

        if ($affectedBy instanceof SaleReturnItem) {
            /** @var SaleReturn $saleReturn */
            $saleReturn = $affectedBy->saleReturn;

            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $saleReturn->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            $location = $counter->location;

            $locationName = $location?->name;
        }

        return [
            'id' => $loyaltyPointUpdate->id,
            'points' => $loyaltyPointUpdate->points,
            'closing_loyalty_points_balance' => $loyaltyPointUpdate->closing_loyalty_points_balance,
            'type_id' => LoyaltyPointUpdateTypes::getCaseNameByValue($loyaltyPointUpdate->type_id),
            'affected_by_type' => $loyaltyPointUpdate->affected_by_type,
            'affected_by_id' => $loyaltyPointUpdate->affected_by_id,
            'happened_at' => $loyaltyPointUpdate->happened_at,
            'type' => $loyaltyPointUpdate->points < 0 ? 'REDEEMED' : 'REWARDED',
            'location_name' => $locationName,
        ];
    }
}
