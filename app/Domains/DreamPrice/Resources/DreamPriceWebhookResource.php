<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Resources;

use App\Domains\Common\Enums\Statuses;
use App\Models\DreamPrice;
use App\Models\MemberGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DreamPriceWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var DreamPrice $dreamPrice */
        $dreamPrice = $this;

        /** @var Collection $memberGroups */
        $memberGroups = $dreamPrice->memberGroups;

        /** @var Carbon $updatedAt */
        $updatedAt = $dreamPrice->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $dreamPrice->created_at;

        return [
            'id' => $dreamPrice->id,
            'name' => $dreamPrice->name,
            'start_date' => $dreamPrice->start_date,
            'end_date' => $dreamPrice->end_date,
            'status' => Statuses::getFormattedCaseName((int) $dreamPrice->status),
            'member_groups' => $memberGroups->map(function ($memberGroup): array {
                /** @var MemberGroup $dreamPriceMemberGroup */
                $dreamPriceMemberGroup = $memberGroup;

                return [
                    'id' => $dreamPriceMemberGroup->id,
                    'name' => $dreamPriceMemberGroup->name,
                ];
            }),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
