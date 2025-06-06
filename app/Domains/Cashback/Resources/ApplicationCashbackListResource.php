<?php

declare(strict_types=1);

namespace App\Domains\Cashback\Resources;

use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Common\Enums\DiscountTypes;
use App\Models\Cashback;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationCashbackListResource extends JsonResource
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

        $endDate = Carbon::createFromFormat('Y-m-d', $cashback->end_date);
        $startDate = Carbon::createFromFormat('Y-m-d', $cashback->start_date);

        return [
            'id' => $cashback->id,
            'exclude_by_type' => ExcludeByTypes::getFormattedCaseName($cashback->exclude_by_type),
            'name' => $cashback->name,
            'discount_type_id' => $cashback->discount_type_id,
            'discount_type' => DiscountTypes::getCaseNameByValue($cashback->discount_type_id),
            'discount_value' => $cashback->discount_value,
            'flat_amount' => $cashback->discount_value,
            'minimum_spend_amount' => $cashback->minimum_spend_amount,
            'start_date' => $startDate ? $startDate->format('d-m-Y') : '',
            'end_date' => $endDate ? $endDate->format('d-m-Y') : '',
        ];
    }
}
