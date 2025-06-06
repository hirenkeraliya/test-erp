<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesCharges\Resources;

use App\Domains\Common\Enums\Statuses;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineSalesChargeDeleteWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $onlineSalesCharge = $this->resource;

        return [
            'id' => $onlineSalesCharge->id,
            'name' => $onlineSalesCharge->name,
            'minimum_value' => $onlineSalesCharge->minimum_value,
            'maximum_value' => $onlineSalesCharge->maximum_value,
            'amount' => $onlineSalesCharge->amount,
            'status' => Statuses::getFormattedCaseName(Statuses::INACTIVE->value),
            'created_at' => $onlineSalesCharge->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $onlineSalesCharge->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $onlineSalesCharge->deleted_at->format('Y-m-d H:i:s'),
        ];
    }
}
