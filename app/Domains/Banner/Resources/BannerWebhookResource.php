<?php

declare(strict_types=1);

namespace App\Domains\Banner\Resources;

use App\Domains\Banner\Enums\ActionTypes;
use App\Domains\Common\Enums\Statuses;
use App\Models\Banner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Banner $banner */
        $banner = $this;
        /** @var Carbon $updatedAt */
        $updatedAt = $banner->updated_at;
        /** @var Carbon $createdAt */
        $createdAt = $banner->created_at;

        return [
            'id' => $banner->id,
            'name' => $banner->name,
            'description' => $banner->description,
            'custom_url' => $banner->custom_url,
            'status' => Statuses::getFormattedCaseName((int) $banner->status),
            'image' => $banner->getDiskBasedFirstMediaUrl('banner'),
            'action_type' => ActionTypes::getCaseNameByValue((int) $banner->action_type_id),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
