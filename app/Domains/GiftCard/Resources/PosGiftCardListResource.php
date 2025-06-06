<?php

declare(strict_types=1);

namespace App\Domains\GiftCard\Resources;

use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Models\GiftCard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosGiftCardListResource extends JsonResource
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

        /** @var Carbon $createdAt */
        $createdAt = $giftCard->created_at;

        return [
            'id' => $giftCard->id,
            'type' => $giftCard->type_id ? GiftCardTypes::getFormattedArrayForPos($giftCard->type_id) : null,
            'number' => $giftCard->number,
            'expiry_date' => $giftCard->expiry_date,
            'total_amount' => (float) $giftCard->total_amount,
            'available_amount' => (float) $giftCard->available_amount,
            'status' => $giftCard->status ? GiftCardStatuses::getFormattedArrayForPos($giftCard->status) : null,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
