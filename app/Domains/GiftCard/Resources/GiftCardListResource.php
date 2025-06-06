<?php

declare(strict_types=1);

namespace App\Domains\GiftCard\Resources;

use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Models\GiftCard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftCardListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var GiftCard $giftCard */
        $giftCard = $this;

        $expiryDate = null;

        if ($giftCard->expiry_date) {
            /** @var Carbon $expiryDate */
            $expiryDate = Carbon::createFromFormat('Y-m-d', $giftCard->expiry_date);
            $expiryDate = $expiryDate->format('d-m-Y');
        }

        /** @var Carbon $createdAt */
        $createdAt = $giftCard->created_at;

        return [
            'type_id' => GiftCardTypes::getFormattedCaseName($giftCard->type_id),
            'number' => $giftCard->number,
            'expiry_date' => $expiryDate ?? 'N/A',
            'total_amount' => $giftCard->total_amount,
            'available_amount' => $giftCard->available_amount,
            'status' => GiftCardStatuses::getFormattedCaseName($giftCard->status),
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
        ];
    }
}
