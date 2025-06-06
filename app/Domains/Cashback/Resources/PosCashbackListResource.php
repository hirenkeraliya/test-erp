<?php

declare(strict_types=1);

namespace App\Domains\Cashback\Resources;

use App\Domains\Cashback\Enums\ConditionTypes;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Common\Enums\DiscountTypes;
use App\Models\Cashback;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCashbackListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Cashback $cashback */
        $cashback = $this;

        $cashbackPrices = $cashback->cashbackPrices->map(fn ($cashbackPrice): array => [
            'condition_operator_type_id' => $cashbackPrice->condition_operator_type_id,
            'condition_operator_type' => ConditionTypes::getFormattedCaseName(
                $cashbackPrice->condition_operator_type_id
            ),
            'amount' => $cashbackPrice->amount,
        ]);

        $endDate = Carbon::createFromFormat('Y-m-d', $cashback->end_date);
        $startDate = Carbon::createFromFormat('Y-m-d', $cashback->start_date);

        return [
            'id' => $cashback->id,
            'name' => $cashback->name,
            'exclude_by_type' => ExcludeByTypes::getCaseNameByValue($cashback->exclude_by_type),
            'discount_type' => DiscountTypes::getCaseNameByValue($cashback->discount_type_id),
            'discount_value' => $cashback->discount_value,
            'flat_amount' => (float) $cashback->discount_value,
            'minimum_spend_amount' => (float) $cashback->minimum_spend_amount,
            'products' => $cashback->products->pluck('id')->toArray(),
            'categories' => $cashback->categories->pluck('id')->toArray(),
            'start_date' => $startDate ? $startDate->format('d-m-Y') : '',
            'end_date' => $endDate ? $endDate->format('d-m-Y') : '',
            'cashback_prices' => $cashbackPrices,
        ];
    }
}
