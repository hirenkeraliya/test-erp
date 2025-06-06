<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\Resources;

use App\Models\MysteryGift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class AdminMysteryGiftListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MysteryGift $mysteryGift */
        $mysteryGift = $this;

        $startDate = '';

        if ($mysteryGift->start_date) {
            /** @var Carbon $startDateFormat */
            $startDateFormat = Carbon::createFromFormat('Y-m-d', $mysteryGift->start_date);
            $startDate = $startDateFormat->format('d-m-Y');
        }

        $endDate = '';

        if ($mysteryGift->end_date) {
            /** @var Carbon $endDateFormat */
            $endDateFormat = Carbon::createFromFormat('Y-m-d', $mysteryGift->end_date);
            $endDate = $endDateFormat->format('d-m-Y');
        }

        return [
            'id' => $mysteryGift->id,
            'name' => $mysteryGift->name,
            'min_flat_amount' => $mysteryGift->min_flat_amount,
            'max_flat_amount' => $mysteryGift->max_flat_amount,
            'min_percentage' => $mysteryGift->min_percentage,
            'max_percentage' => $mysteryGift->max_percentage,
            'is_flat_amount' => $mysteryGift->is_flat_amount,
            'is_percentage' => $mysteryGift->is_percentage,
            'is_free_product' => $mysteryGift->is_free_product,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'minimum_spend' => $mysteryGift->minimum_spend,
            'minimum_spend_amount_for_free_product' => $mysteryGift->minimum_spend_amount_for_free_product,
            'minimum_spend_amount_for_percentage' => $mysteryGift->minimum_spend_amount_for_percentage,
            'minimum_spend_amount_for_flat_amount' => $mysteryGift->minimum_spend_amount_for_flat_amount,
            'status' => $mysteryGift->status,
        ];
    }
}
